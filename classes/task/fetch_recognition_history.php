<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Task schedule configuration for the local_emp plugin.
 *
 * @package   local_emp
 * @copyright 2024, Pascal Hürten <pascal.huerten@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_emp\task;

defined('MOODLE_INTERNAL') || die();

use local_emp\manager;
use moodle_exception;

/**
 * Class fetch_recognition_history
 *
 * This class represents a scheduled task for fetching recognition history.
 * It extends the \core\task\scheduled_task class.
 */
class fetch_recognition_history extends \core\task\scheduled_task {
    /**
     * Returns the name of the task.
     *
     * @return string The name of the task.
     */
    public function get_name() {
        return get_string('fetchrecognitionrequests', 'local_emp');
    }

    /**
     * Executes the fetch recognition history task.
     *
     * This method retrieves all courses that are configured for credit export and their corresponding
     * recognition history from the PIM API. It then saves the history to the database.
     *
     * @throws \Exception If there is not exactly one issuer configured.
     */
    public function execute() {
        // Get all courses that are configuered for credit export.
        global $DB;

        $issuers = $DB->get_records('local_emp_issuer', array('id' => 1), '', 'id, titlede, erasmus, pic, schac');
        if (count($issuers) != 1) {
            throw new \Exception('There must be exactly one issuer configured.');
        }
        $issuer = array_pop($issuers);
        $issuer = json_decode(json_encode($issuer), true);
        // Get all courses that are configured for credit export with their name from courses table.
        $sql = "SELECT emp.courseid, c.fullname as name
                FROM {local_emp_course} emp
                JOIN {course} c ON emp.courseid = c.id
        ";
        $courses = $DB->get_records_sql($sql);

        // Delete all recognition history from database.
        $DB->delete_records('local_emp_recognitions');

        // Get all external achievments from PIM API.
        foreach ($courses as $course) {
            $course->identifier = manager::generate_learningopportunity_indentifier($course->courseid, $issuer["schac"]);
            // Get achievments where learningOpportunity->name is course->name.
            $history = PIM_API::get_history_by_learningopportunity($course->identifier, $course->name, $issuer);
            if (count($history) == 0) {
                mtrace("No history found for course " . $course->name . " (" . $course->identifier . ").");
                continue;
            }
            mtrace("Found " . count($history) . " history sets for course " . $course->name . " (" . $course->identifier . ").");
            // Save history to database.
            foreach ($history as $historyset) {
                $this->save_recognition_history($course->courseid, $historyset);
            }
        }
    }

    private function save_recognition_history(int $courseid, array $historyset) {
        global $DB;

        $data = new \stdClass();
        $data->courseid = $courseid;
        $data->pimhistorysetid = $historyset['id'];
        $data->learningopportunity = $historyset['internalHistoryAchievement']['learningOpportunity']['name'];
        $data->studyprogramme = $historyset['studyProgram']['name'];
        $data->hei = $historyset['responsibleHei']['oe']['name'];
        $data->status = $historyset['internalHistoryAchievement']['achievementStatus'] == 'REJECTED_RECOGNITION' ? 0 : 1;
        $data->credits = $historyset['internalHistoryAchievement']['credits'];
        $data->year = $historyset['externalHistoryAchievement']['yearOfCompletion'];

        mtrace("Saving recognition history for course " . $data->courseid .
            " (" . ($data->status ? 'approved' : 'rejected') . ").");
        $DB->insert_record('local_emp_recognitions', $data);
    }
}

class PIM_API {

    /**
     * Retrieves the history achievements based on the given parameters.
     *
     * @param string|null $id The ID of the achievement.
     * @param string|null $source The source of the achievement.
     * @param int|null $learningopportunityid The ID of the learning opportunity.
     * @return array An array of history achievements.
     */
    public static function get_history_achievements(
        string $id = null,
        string $source = null,
        int $learningopportunityid = null
    ): array {
        $historyachievements = self::get_data_with_caching('/historyachievement', $id);
        // Filter by source if it's set.
        if (!empty($source)) {
            if (!in_array($source, array("INTERNAL", "EXTERNAL"))) {
                throw new moodle_exception("Parameter source must be either \"INTERNAL\" or \"EXTERNAL\", $source given.");
            }

            $historyachievements = array_filter($historyachievements, function ($item) use ($source) {
                return $item['achievementSource'] === $source;
            });
        }

        // Filter by learningOpportunity id if it's set.
        if (!empty($learningopportunityid)) {
            $historyachievements = array_filter($historyachievements, function ($item) use ($learningopportunityid) {
                return $item['learningOpportunity']['id'] === $learningopportunityid;
            });
        }

        return $historyachievements;
    }

    /**
     * Retrieves the learning opportunities for a given ID.
     *
     * @param string|null $id The ID of the learning opportunity (optional).
     * @return array An array of learning opportunities.
     */
    public static function get_learningopportunities(string $id = null): array {
        return self::get_data_with_caching('/learningopportunity', $id);
    }

    /**
     * Retrieves the history sets for a given ID.
     *
     * @param string|null $id The ID to retrieve history sets for.
     * @return array An array of history sets.
     */
    public static function get_historysets(string $id = null): array {
        return self::get_data_with_caching('/historyset', $id);
    }

    /**
     * Retrieves the HEIs (Higher Education Institutions) based on the given ID.
     *
     * @param string|null $id The ID to filter the HEIs. If null, all HEIs will be retrieved.
     * @return array An array of HEIs.
     */
    public static function get_heis(string $id = null): array {
        return self::get_data_with_caching('/hei', $id);
    }

    /**
     * Retrieves the history of achievements based on the learning opportunity ID or name.
     *
     * @param string $id The ID of the learning opportunity.
     * @param string $name The name of the learning opportunity.
     * @param array $issuer The issuer of the learning opportunity.
     * @return array The history of achievements matching the learning opportunity ID or name.
     */
    public static function get_history_by_learningopportunity(string $id, string $name, array $issuer): array {
        $alllearningopportunities = self::get_learningopportunities();
        $history = [];
        foreach ($alllearningopportunities as $lo) {
            $match = false;
            if (array_key_exists('opportunityIdentifier', $lo) && $lo['opportunityIdentifier'] == $id) {
                $match = true;
            } else if ($lo['name'] == $name) {
                $hei = self::get_heis($lo['heiId']);
                if (empty($hei)) {
                    mtrace("No hei found for learning opportunity " . $lo['name'] . " (" . $lo['id'] . ").");
                    continue;
                }

                if ((isset($hei['oe']['name'], $issuer['titlede']) && $hei['oe']['name'] == $issuer['titlede']) ||
                    (isset($hei['erasmuscode'], $issuer['erasmus']) && $hei['erasmuscode'] == $issuer['erasmus']) ||
                    (isset($hei['pic'], $issuer['pic']) && $hei['pic'] == $issuer['pic']) ||
                    (isset($hei['schac'], $issuer['schac']) && $hei['schac'] == $issuer['schac'])
                ) {
                    $match = true;
                }
            }
            if (!$match) {
                continue;
            }
            mtrace("Found learning opportunity " . $lo['name'] . " (" . $lo['id'] . ").");

            // Get external achievements from pim api by learningOpportunity id.
            $externalachievements = self::get_history_achievements(null, 'EXTERNAL', $lo['id']);
            foreach ($externalachievements as $achievement) {
                // Get historysets from pim api by historySet id.
                $historyset = self::get_historysets($achievement['historySet']['id']);
                if (empty($historyset)) {
                    mtrace("No historyset found for achievement " . $achievement['id'] . ".");
                    continue;
                }
                // Set externalHistoryAchievement to $achievement.
                $historyset['externalHistoryAchievement'] = $achievement;

                // Check if historyset has internalHistoryAchievement.
                if (
                    !array_key_exists('internalHistoryAchievement', $historyset)
                    or empty($historyset['internalHistoryAchievement'])
                ) {
                    mtrace("No internalHistoryAchievement found for achievement " . $achievement['id'] . ".");
                    continue;
                }
                // Get data for internalHistoryAchievement from api.
                $internalhistoryachievement = self::get_history_achievements($historyset['internalHistoryAchievement'][0]['id']);
                // Get hei of internalHistoryAchievement.
                $lo = self::get_learningopportunities($internalhistoryachievement['learningOpportunity']['id']);
                if (empty($lo)) {
                    mtrace("No learning opportunity found for achievement " . $achievement['id'] . ".");
                    continue;
                }
                $hei = self::get_heis($lo['heiId']);
                if (empty($hei)) {
                    mtrace("No hei found for achievement " . $achievement['id'] . ".");
                    continue;
                }
                $historyset['responsibleHei'] = $hei;
                // Set internalHistoryAchievement to $internalhistoryachievement.
                $historyset['internalHistoryAchievement'] = $internalhistoryachievement;
                $history[] = $historyset;
            }
        }
        return $history;
    }

    /**
     * Fetches data from the API and caches it.
     *
     * @param string $endpoint The API endpoint to fetch data from.
     * @param string|null $id The ID of the item to fetch. If null, fetches all items.
     * @return array The fetched data.
     */
    public static function get_data_with_caching(string $endpoint, string $id = null): array {
        static $cache = [];

        // Initialize the cache for this endpoint if it doesn't exist.
        if (!array_key_exists($endpoint, $cache)) {
            $cache[$endpoint] = [];
        }

        // If cache for this endpoint is not empty and an ID is provided, search the cache.
        if (!empty($cache[$endpoint]) && !empty($id)) {
            if (array_key_exists($id, $cache[$endpoint])) {
                return $cache[$endpoint][$id];
            }
        }

        // If an ID is provided, fetch the specific item.
        if (!empty($id)) {
            $item = self::get_pim_api_data("$endpoint/$id");
            // If the item is an array with numeric keys, return the first item.
            if (is_array($item) && array_keys($item) === range(0, count($item) - 1)) {
                $item = $item[0];
            }
            // Store the fetched item in the cache.
            $cache[$endpoint][$id] = $item;
            return $item;
        }

        // If cache for this endpoint is empty, fetch all items from API.
        if (empty($cache[$endpoint])) {
            $items = self::get_pim_api_data($endpoint);
            // Store the fetched items in the cache, using their IDs as keys.
            foreach ($items as $item) {
                $cache[$endpoint][$item['id']] = $item;
            }
        }

        return $cache[$endpoint];
    }

    /**
     * Retrieves data from the PIM API.
     *
     * @param string $endpoint The API endpoint to retrieve data from.
     * @param array $params Additional parameters to include in the API request.
     * @return array The retrieved data from the PIM API.
     */
    public static function get_pim_api_data(string $endpoint, array $params = []): array {
        $data = [];
        $mh = curl_multi_init();
        $curlarray = [];
        $running = null;

        // Fetch the first page to get the total number of pages.
        $response = self::get_pim_api_page($endpoint, 1, $params);
        $data = array_merge($data, $response['data']);
        $totalpages = isset($response['totalPages']) ? $response['totalPages'] : 1;

        if ($totalpages == 1) {
            return $data;
        }

        // Calculate the start page.
        $startpage = 2;

        // Initialize cURL handles for each page.
        for ($page = $startpage; $page <= $totalpages; $page++) {
            $curlarray[$page] = self::get_pim_api_page($endpoint, $page, $params, false);
            curl_multi_add_handle($mh, $curlarray[$page]);
        }

        // Execute the handles.
        do {
            curl_multi_exec($mh, $running);
        } while ($running);

        // Collect the data.
        for ($page = $startpage; $page <= $totalpages; $page++) {
            $res = curl_multi_getcontent($curlarray[$page]);
            $httpcode = curl_getinfo($curlarray[$page], CURLINFO_HTTP_CODE);

            if ($httpcode >= 500) {
                mtrace("Server error when fetching page $page of $endpoint. Skipping this page.");
                continue;
            }

            $decodedresponse = json_decode($res, true);
            if (!empty($decodedresponse) && array_key_exists('data', $decodedresponse) && is_array($decodedresponse['data'])) {
                $data = array_merge($data, $decodedresponse['data']);
            } else {
                // Handle the case where $decodedresponse['data'] is not an array.
                // You might want to log an error message here.
                mtrace("Warning: Decoded response data is not an array on page $page of $endpoint.");
            }
            curl_multi_remove_handle($mh, $curlarray[$page]);
        }

        curl_multi_close($mh);
        mtrace("Fetched " . count($data) . " items from $endpoint.");
        return $data;
    }

    /**
     * Retrieves a page from the PIM API.
     *
     * @param string $endpoint The API endpoint to retrieve data from.
     * @param int $page The page number to retrieve.
     * @param array $params Additional parameters to include in the API request.
     * @param bool $exec Whether to execute the API request immediately or not.
     * @return mixed The response from the API, or null if $exec is false.
     */
    public static function get_pim_api_page(string $endpoint, int $page, array $params = [], bool $exec = true): mixed {
        $host = "https://test.pim-plattform.de/api/v1";
        $params['pageNumber'] = $page;
        $query = http_build_query($params);
        $curl = curl_init($host . $endpoint . '?' . $query);

        $token = get_config('localemp', 'pimapitoken');
        if (empty($token)) {
            throw new \moodle_exception('PIM API token is not set.');
        }
        $headers = array(
            'Authorization: Bearer ' . $token,
        );

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if ($exec) {
            $response = curl_exec($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if ($httpcode >= 500) {
                mtrace("Server error when fetching page $page of $endpoint. Skipping this page.");
                curl_close($curl);
                return [];
            }

            if ($response === false) {
                $error = 'Curl error: ' . curl_error($curl);
                curl_close($curl);
                throw new \moodle_exception($error);
            }

            curl_close($curl);
            $decodedresponse = json_decode($response, true);

            if ($decodedresponse === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new \moodle_exception('JSON decode error: ' . json_last_error_msg() . ' JSON: ' . $response);
            }

            // Response must have keys 'data' and 'totalPages'.
            if (!array_key_exists('data', $decodedresponse)) {
                throw new \moodle_exception('Response does not contain required key "data". ' . $endpoint . '?' . $query);
            }

            return $decodedresponse;
        } else {
            return $curl;
        }
    }
}
