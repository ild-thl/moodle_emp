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

namespace local_emp\output\form;

defined('MOODLE_INTERNAL') || die();

use local_emp\output\table\achievements_table;
use local_emp\manager;

require_once("$CFG->libdir/formslib.php");

/**
 * Form to select achievements that should be sent to EMREX Client.
 *
 * @package     local_emp
 * @copyright   2022 Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class export_form extends \moodleform {
    /**
     * Users learning achievements to be displayed by the form.
     *
     * @var array
     */
    public $achievements;

    public function definition() {
        $mform = $this->_form;

        // Form instructions as heading.
        $mform->addElement('html', '<h6>' . get_string('exportformsubtitle', 'local_emp') . '</h6>');

        // Add hidden form fields for post parameters.
        $mform->addElement('hidden', 'sessionId');
        $mform->setType('sessionId', PARAM_RAW_TRIMMED);
        $mform->addElement('hidden', 'returnUrl');
        $mform->setType('returnUrl', PARAM_URL);
        
        $this->achievements = $this->get_achievements($this->_customdata['user']->id);
        $achievementstable = $this->render_achievements($this->achievements);

        $mform->addElement('html', $achievementstable);

        $this->add_action_buttons(true, get_string('exportformsubmit', 'local_emp'));
    }

    protected function get_achievements(string $userid):array {
        global $DB;
        $sorted = array();

        $sql = 'SELECT emp.*, c.fullname as coursename, c.summary, GROUP_CONCAT(hp.haspart SEPARATOR ", ") as haspart
                FROM {course_completions} cc
                    JOIN {local_emp_course} emp ON cc.course = emp.courseid 
                    JOIN {course} c ON cc.course = c.id 
                    LEFT JOIN {local_emp_course_haspart} hp ON hp.parent = emp.id
                WHERE cc.userid = :userid
                GROUP BY emp.id;';
        
        $params = array('userid' => $userid);
        $records = $DB->get_records_sql($sql, $params);
        if (empty($records)) {
            return $sorted;
        }

        foreach ($records as $record) {
            if (!empty($record->haspart)) {
                $record->parts = array();
                foreach (explode(', ', $record->haspart) as $partid) {
                    foreach ($records as $r) {
                        if ($r->id == $partid) {
                            $record->parts[] = $r;
                        }
                    }
                }

                $sorted[$record->id] = $record;
            }
        }

        return $sorted;
    }

    protected function render_achievements(array $achievements):string {
        global $OUTPUT;
        ob_start();
        ?>
        <div class="no-overflow">
            <table class="flexible table table-striped table-hover m-element-achievements-table">
                <thead>
                    <tr>
                        <th class="header c0 col-select" scope="col">
                            <input id="m-element-select-all" type="checkbox" name="check-all">
                            <div class="commands"></div>
                        </th>
                        <th class="header c1 col-coursename" scope="col"><a data-sortable="1" data-sortby="coursename" data-sortorder="4" role="button" href="http://moodle.local/local/emp/init.php?sessionId=test_session_id&amp;returnUrl&amp;tsort=coursename&amp;tdir=4">Course<span class="accesshide ">Sort by Course Descending</span></a> <i class="icon fa fa-sort-desc fa-fw " title="Descending" role="img" aria-label="Descending"></i>
                            <div class="commands"></div>
                        </th>
                        <th class="header c2" scope="col"><a data-sortable="1" data-sortby="languageofinstruction" data-sortorder="3" role="button" href="http://moodle.local/local/emp/init.php?sessionId=test_session_id&amp;returnUrl&amp;tsort=languageofinstruction&amp;tdir=3">Language of instruction<span class="accesshide ">Sort by Language of instruction Ascending</span></a>
                            <div class="commands"></div>
                        </th>
                        <th class="header c3" scope="col"><a data-sortable="1" data-sortby="levelvalue" data-sortorder="3" role="button" href="http://moodle.local/local/emp/init.php?sessionId=test_session_id&amp;returnUrl&amp;tsort=levelvalue&amp;tdir=3">Educational level<span class="accesshide ">Sort by Educational level Ascending</span></a>
                            <div class="commands"></div>
                        </th>
                        <th class="header c4" scope="col"><a data-sortable="1" data-sortby="engagementhours" data-sortorder="3" role="button" href="http://moodle.local/local/emp/init.php?sessionId=test_session_id&amp;returnUrl&amp;tsort=engagementhours&amp;tdir=3">Engagement hours<span class="accesshide ">Sort by Engagement hours Ascending</span></a>
                            <div class="commands"></div>
                        </th>
                        <th class="header c5 col-creditvalue" scope="col"><a data-sortable="1" data-sortby="creditvalue" data-sortorder="3" role="button" href="http://moodle.local/local/emp/init.php?sessionId=test_session_id&amp;returnUrl&amp;tsort=creditvalue&amp;tdir=3">Credits<span class="accesshide ">Sort by Credits Ascending</span></a>
                            <div class="commands"></div>
                        </th>
                    </tr>
                </thead>
                <tbody>
        <?php
        foreach ($achievements as $achievement) {
            ?>
                    <tr class="local-emp-parent">
                        <td class="cell c0 col-select">
                            <?php echo($this->render_courseid($achievement)) ?>
                        </td>
                        <td class="cell c1 col-coursename">
                            <?php echo($this->render_coursename($achievement)) ?>
                        </td>
                        <td class="cell c2 col-languageofinstruction">
                            <?php echo($this->render_languageofinstruction($achievement)) ?>
                        </td>
                        <td class="cell c3 col-levelvalue">
                            <?php echo($this->render_levelvalue($achievement)) ?>
                        </td>
                        <td class="cell c4 col-engagementhours">
                            <?php echo($this->render_engagementhours($achievement)) ?>
                        </td>
                        <td class="cell c5 col-creditvalue">
                            <?php echo($this->render_creditvalue($achievement)) ?>
                        </td>
                    </tr>
            <?php
            foreach ($achievement->parts as $index => $part) {
                ?>
                        <tr class="local-emp-part">
                            <td class="cell c0 col-select">
                                <?php echo($this->render_courseid($part)) ?>
                            </td>
                            <td class="cell c1 col-coursename">
                                <span><?php echo ($index >= count($achievement->parts) - 1) ? '└' : '├' ?></span>
                                <?php echo($this->render_coursename($part)) ?>
                            </td>
                            <td class="cell c2 col-languageofinstruction">
                                <?php echo($this->render_languageofinstruction($part)) ?>
                            </td>
                            <td class="cell c3 col-levelvalue">
                                <?php echo($this->render_levelvalue($part)) ?>
                            </td>
                            <td class="cell c4 col-engagementhours">
                                <?php echo($this->render_engagementhours($part)) ?>
                            </td>
                            <td class="cell c5 col-creditvalue">
                                <?php echo($this->render_creditvalue($part)) ?>
                            </td>
                        </tr>
                <?php
            }
        }
        ?>
                </tbody>
            </table>
        </div>
        <?php
        $out = ob_get_contents();
        ob_end_clean();
        return $out;
    }

    /**
     * This function is called for each data row to allow processing of the
     * courseid value.
     *
     * @param \stdClass $values Contains object with all the values of record.
     * @return $string Return a checkbox for an achievement/passed course with achieved credits.
     */
    protected function render_courseid($values) {
        $attributes = array('class' => 'm-element-select-achievement');
        $this->_form->addElement('hidden',  'select-achievement' . $values->courseid);
        $this->_form->setType('select-achievement' . $values->courseid, PARAM_BOOL);
        return \html_writer::checkbox('select-achievement' . $values->courseid, $values->courseid, false, null, $attributes);
    }

    /**
     * This function is called for each data row to allow processing of the
     * coursename value.
     *
     * @param \stdClass $values Contains object with all the values of record.
     * @return $string Return coursename as a link to the course.
     */
    protected function render_coursename($values) {
        return \html_writer::link(
            new \moodle_url(
                '/course/view.php',
                array(
                    'id' => $values->courseid,
                )
            ),
            $values->coursename,
        );
    }

    /**
     * This function is called for each data row to allow processing of the
     * languageofinstruction value.
     *
     * @param \stdClass $values Contains object with all the values of record.
     * @return $string Return language of instruction.
     */
    protected function render_languageofinstruction($values) {
        return $values->languageofinstruction;
    }

    /**
     * This function is called for each data row to allow processing of the
     * levelvalue value.
     *
     * @param \stdClass $values Contains object with all the values of record.
     * @return $string Return educational level.
     */
    protected function render_levelvalue($values) {
        return $values->levelvalue . ' (' . $values->leveltype . ')';
    }

    /**
     * This function is called for each data row to allow processing of the
     * engagementhours value.
     *
     * @param \stdClass $values Contains object with all the values of record.
     * @return $string Return engagement hours.
     */
    protected function render_engagementhours($values) {
        return $values->engagementhours;
    }

    /**
     * This function is called for each data row to allow processing of the
     * creditvalue value.
     *
     * @param \stdClass $values Contains object with all the values of record.
     * @return $string Return amount of achieved credits in a course.
     */
    protected function render_creditvalue($values) {
        return $values->creditvalue;
    }

    /**
     * Reformats submitted data.
     *
     * @return object Reformatted submitted data; NULL if not valid or not submitted or cancelled.
     */
    public function get_data() {
        $data = parent::get_data();

        if (empty($data)) {
            return null;
        }

        $data->achievements = array();

        $toexport = array();

        foreach (array_keys((array)$data) as $key) {
            preg_match('/select-achievement(\d+)/', $key, $matches);
            if (empty($matches)) {
                continue;
            }

            if ($data->{$key} == 0) {
                continue;
            }
            unset($data->{$key});
            $toexport[] = $matches[1];
        }

        foreach ($this->achievements as $achievement) {
            if (!in_array($achievement->courseid, $toexport)) {
                continue;
            }

            foreach ($achievement->parts as $index => $part) {
                if (!in_array($part->courseid, $toexport)) {
                    unset($achievement->parts[$index]);
                }
            }

            $data->achievements[] = $achievement;
        }

        return $data;
    }
}
