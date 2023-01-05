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
 * Page that shows a form to manage and set additional metadata dor a course.
 *
 * @package     local_emp
 * @copyright   2022 Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_emp\output\form\edit_metadata_form;

$id = required_param('courseid', PARAM_INT);
$context = context_course::instance($id);

$redirectto = new moodle_url('/course/view.php', array('id' => $id));

// Check capabilities.
if (!has_capability('local/emp:editmetadata', $context)) {
    redirect($redirectto, get_string('nopermissiontoaccesspage'), null, \core\output\notification::NOTIFY_WARNING);
}

// User has to be logged in.
require_login($id, false);

$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$PAGE->set_url(qualified_me());
$PAGE->set_title(get_string('editempcourse', 'local_emp'));
$PAGE->set_heading(get_string('editempcourse', 'local_emp'));

$table = 'local_emp_course';

$toform = $DB->get_record($table, ['courseid' => $id]);

$mform = new edit_metadata_form(qualified_me());

if ($mform->is_cancelled()) {
    redirect($redirectto);
} else if ($fromform = $mform->get_data()) {
    // If course is not in db yet, else update.
    if (!isset($toform) || empty($toform)) {
        $DB->insert_record($table, $fromform);
    } else {
        $DB->update_record($table, $fromform);
    }

    redirect($redirectto, get_string('editempcoursesuccess', 'local_emp'), null, \core\output\notification::NOTIFY_SUCCESS);
}

// Set default values.
if (!isset($toform) || empty($toform)) {
    $toform = new stdClass();
    $toform->courseid = $id;
}


echo $OUTPUT->header();

$mform->set_data($toform);
$mform->display();

echo $OUTPUT->footer();
