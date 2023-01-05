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
    public function definition() {
        global $CFG;
        $mform = $this->_form;

        // Form instructions as heading.
        $mform->addElement('html', '<h6>' . get_string('exportformsubtitle', 'local_emp') . '</h6>');

        // Add hidden form fields for post parameters.
        $mform->addElement('hidden', 'sessionId');
        $mform->setType('sessionId', PARAM_RAW_TRIMMED);
        $mform->addElement('hidden', 'returnUrl');
        $mform->setType('returnUrl', PARAM_URL);

        \MoodleQuickForm::registerElementType('achievement_checkbox',
                                         "$CFG->dirroot/local/emp/classes/output/form/achievement_checkbox_form_element.php",
                                         'achievement_checkbox_form_element');

        // Render selectable achievements table.
        $this->table = new achievements_table (
            'achievement_table' . '_' . time(),
            $this->_customdata['user'],
            $this->_customdata['baseurl'],
            $mform,
        );

        // Get Table html and include it in the form.
        ob_start();
        $this->table->out(10, false);
        $out = ob_get_contents();
        ob_end_clean();

        $mform->addElement('html', $out);

        $this->add_action_buttons(true, get_string('exportformsubmit', 'local_emp'));
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

        foreach (array_keys((array)$data) as $key) {
            preg_match('/select-achievement(\d+)/', $key, $matches);
            if (empty($matches)) {
                continue;
            }

            if ($data->{$key} == 0) {
                continue;
            }
            unset($data->{$key});
            $courseid = $matches[1];

            foreach ($this->table->rawdata as $emprecord) {
                if ($emprecord->courseid == $courseid) {
                    $data->achievements[] = $emprecord;
                }
            }
        }

        return $data;
    }
}
