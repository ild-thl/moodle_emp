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
 * Page for edditing and creating new issuers.
 *
 * @package     local_emp
 * @copyright   2022 Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();

$context = context_system::instance();

// Check capabilities.
if (!has_capability('local/emp:editmetadata', $context)) {
    redirect($CFG->wwwroot);
}

$PAGE->set_context($context);
$PAGE->set_url(qualified_me());
$PAGE->set_title(get_string('editissuer', 'local_emp'));
$PAGE->set_heading(get_string('editissuer', 'local_emp'));

$redirectto = new moodle_url('/admin/category.php', array('category' => 'localempfolder'));

// Inform moodle which menu entry currently is active!
admin_externalpage_setup('localemp_edit_issuer');



$mform = new local_emp\output\form\edit_issuer_form();
$issuer = null;
$records = $DB->get_records('local_emp_issuer');
$issuer = reset($records);

if ($mform->is_cancelled()) {
    redirect($redirectto);
} else if ($data = $mform->get_data()) {
    if (isset($data->id) && !empty($data->id)) {
        $DB->update_record('local_emp_issuer', $data);
    } else {
        $data->id = $DB->insert_record('local_emp_issuer', $data);
    }

    redirect($redirectto);
} else {
    // Set default data (if any).
    if (isset($issuer)) {
        $mform->set_data($issuer);
    }
}

// Display page.
echo $OUTPUT->header();

if (isset($issuer)) {
    echo $OUTPUT->heading(get_string('editissuer', 'local_emp'));
} else {
    echo $OUTPUT->heading(get_string('createissuer', 'local_emp'));
}

$mform->display();

echo $OUTPUT->footer();
