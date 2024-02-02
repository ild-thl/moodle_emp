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

namespace local_emp;

/**
 * Library of utility functions for local_emp.
 *
 * @package     local_emp
 * @copyright   2022 Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /**
     * ID that is used by the EMREX Client to identify the session.
     *
     * @var string
     */
    private $sessionid;

    /**
     * The address for the elmo response and where the user is supposed to be returned to.
     *
     * @var string
     */
    private $returnurl;

    /**
     * Constructor.
     *
     * @param string $sessionid
     * @param string $returnurl
     */
    public function __construct($sessionid, $returnurl = null) {
        $this->sessionid = $sessionid;
        $this->returnurl = $returnurl;
    }

    /**
     * Escapes all characters that have special meaning in xml.
     *
     * @param string $string XML String
     * @return string
     */
    public static function xml_escape($string) {
        return str_replace(array('&', '<', '>', '\'', '"'), array('&amp;', '&lt;', '&gt;', '&apos;', '&quot;'), $string);
    }

    /**
     * Sends a response to the requesting EMREX Client.
     *
     * @param string $returncode
     * @param string $returnmessage
     * @param string|null $elmo
     * @return void
     */
    private function send_response(
        string $returncode,
        string $returnmessage = "",
        string $elmo = null) {

        global $OUTPUT, $PAGE;

        // If no returnUrl is given send the elmo xml as a file instead for download by the client browser.
        if (!isset($this->returnurl) || empty($this->returnurl)) {
            if (isset($elmo) && !empty($elmo)) {
                send_file($elmo, 'transcript' . time() . '.xml', 0, 0, true, false);
            }

            return;
        }

        if (isset($elmo) && !empty($elmo)) {
            // Compress elmo certificate with gzip.
            $elmo = gzencode($elmo);
            // Convert compressed elmo to base64.
            $elmo = base64_encode($elmo);
        }

        $templatedata = array(
            'returnurl' => $this->returnurl,
            'sessionid' => $this->sessionid,
            'returncode' => $returncode,
            'returnmessage' => $returnmessage,
            'elmo' => $elmo,
        );

        // Show hidden form that redirects automatically back to the EMREX Client
        // and posts reponse data.
        $PAGE->requires->js(new \moodle_url('/local/emp/js/respond.js'));

        echo $OUTPUT->header();

        echo $OUTPUT->render_from_template('local_emp/respond', $templatedata);

        echo $OUTPUT->footer();
    }

    /**
     * Sends a response containing the code NCP_OK and an elmo certificate to the requesting EMREX Client.
     *
     * @param string $elmo
     * @return void
     */
    public function ncp_ok(string $elmo) {
        self::send_response('NCP_OK', '', $elmo);

        \core\notification::info(get_string('ncpok', 'local_emp'));
    }

    /**
     * Sends a response containing the code NCP_CANCEL to the requesting EMREX Client.
     *
     * @return void
     */
    public function ncp_cancel() {
        self::send_response('NCP_CANCEL');

        \core\notification::warning(get_string('ncpcancel', 'local_emp'));
    }

    /**
     * Sends a response containing the code NCP_NO_RESULTS to the requesting EMREX Client.
     *
     * @return void
     */
    public function ncp_no_results() {
        self::send_response('NCP_NO_RESULTS');

        \core\notification::warning(get_string('ncpnoresults', 'local_emp'));
    }

    /**
     * Sends a response containing the code NCP_ERROR and a message containing the error description
     * to the requesting EMREX Client.
     *
     * @param string $error
     * @return void
     */
    public function ncp_error(string $error) {
        self::send_response('NCP_ERROR', $error);

        \core\notification::error(get_string('ncperror', 'local_emp', $error));
    }

    /**
     * Returns the course identifier by concatenating the Moodle course ID and the issuer's SCHAC.
     *
     * @param int $moodlecourseid The Moodle course ID.
     * @param string $issuerschac The issuer's SCHAC.
     * @return string The course identifier.
     */
    public static function generate_learningopportunity_indentifier(int $moodlecourseid, string $issuerschac): string {
        return $issuerschac . ":" . $moodlecourseid;
    }
}
