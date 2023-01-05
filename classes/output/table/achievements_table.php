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

namespace local_emp\output\table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir. "/tablelib.php");


/**
 * Table that lists achieved credits and allows to select
 * the achievements are supposed to be exported to an EMREX client.
 *
 * @package   local_emp
 * @copyright 2022, Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class achievements_table extends \table_sql {

    /**
     * Table defintion.
     *
     * @param string $uniqueid
     * @param boolean $showactions Wether action buttons should be added to the table, that allow managing the certificates.
     * @param int|null $courseid If this is null, the course column won't be included.
     * @param int $userid If this is null, the user name column won't be included.
     * @param string $lang The target language for the lang_strings used by the table.
     */
    public function __construct($uniqueid, $user, $baseurl, $mform) {
        parent::__construct($uniqueid);
        $this->lang = $user->lang;
        $this->mform = $mform;

        // Define headers and columns.
        // Checkbox and courseid.
        $headers[] = \html_writer::checkbox('check-all', null, false, null, array('id' => 'm-element-select-all'));
        $columns[] = 'courseid';

        // Coursename.
        $headers[] = (new \lang_string('course'))->out($this->lang);
        $columns[] = 'coursename';

        // Language of instruction.
        $headers[] = (new \lang_string('languageofinstruction', 'local_emp'))->out($this->lang);
        $columns[] = 'languageofinstruction';

        // Educational level.
        $headers[] = (new \lang_string('levelvalue', 'local_emp'))->out($this->lang);
        $columns[] = 'levelvalue';

        // Amount of engagementhours.
        $headers[] = (new \lang_string('engagementhours', 'local_emp'))->out($this->lang);
        $columns[] = 'engagementhours';

        // Amount of achieved credits.
        $headers[] = (new \lang_string('creditvalue', 'local_emp'))->out($this->lang);
        $columns[] = 'creditvalue';

        // Define the list of columns to show.
        $this->define_columns($columns);

        $this->column_class('courseid', 'col-select');
        $this->column_class('coursename', 'col-coursename');
        $this->column_class('creditvalue', 'col-creditvalue');

        // Define the titles of columns to show in header.
        $this->define_headers($headers);

        // Set preferences.
        $this->is_downloadable(false);
        $this->initialbars(false);
        $this->set_attribute('class', 'm-element-achievements-table');
        $this->sortable(true, 'coursename', SORT_DESC);
        $this->no_sorting('courseid');
        $this->collapsible(false);

        // Get achievements.
        // Achievements are completed courses that award credits.
        $select = 'emp.*, c.fullname as coursename, c.summary';
        $from = '{course_completions} cc, {local_emp_course} emp, {course} c';
        $where = 'cc.course = emp.courseid AND cc.course = c.id AND cc.userid = :userid';
        $params = array('userid' => $user->id);
        $this->set_sql($select, $from, $where, $params);

        $this->define_baseurl($baseurl);
    }

    /**
     * This function is called for each data row to allow processing of the
     * courseid value.
     *
     * @param \stdClass $values Contains object with all the values of record.
     * @return $string Return a checkbox for an achievement/passed course with achieved credits.
     */
    protected function col_courseid($values) {
        $attributes = array('class' => 'm-element-select-achievement');
        $this->mform->addElement('hidden',  'select-achievement' . $values->courseid);
        $this->mform->setType('select-achievement' . $values->courseid, PARAM_BOOL);
        return \html_writer::checkbox('select-achievement' . $values->courseid, $values->courseid, false, null, $attributes);
    }

    /**
     * This function is called for each data row to allow processing of the
     * coursename value.
     *
     * @param \stdClass $values Contains object with all the values of record.
     * @return $string Return coursename as a link to the course.
     */
    protected function col_coursename($values) {
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
     * levelvalue value.
     *
     * @param \stdClass $values Contains object with all the values of record.
     * @return $string Return educational level.
     */
    protected function col_levelvalue($values) {
        return $values->levelvalue . ' (' . $values->leveltype . ')';
    }

    /**
     * This function is called for each data row to allow processing of the
     * languageofinstruction value.
     *
     * @param \stdClass $values Contains object with all the values of record.
     * @return $string Return language of instruction.
     */
    protected function col_languageofinstruction($values) {
        return $values->languageofinstruction;
    }

    /**
     * This function is called for each data row to allow processing of the
     * engagementhours value.
     *
     * @param \stdClass $values Contains object with all the values of record.
     * @return $string Return engagement hours.
     */
    protected function col_engagementhours($values) {
        return $values->engagementhours;
    }

    /**
     * This function is called for each data row to allow processing of the
     * creditvalue value.
     *
     * @param \stdClass $values Contains object with all the values of record.
     * @return $string Return amount of achieved credits in a course.
     */
    protected function col_creditvalue($values) {
        return $values->creditvalue;
    }
}
