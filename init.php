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
 * This page provides a contact point for EMREX Clients.
 * This page will provide an overview of achieved credits for authentivcated users
 * and a way to send back selected achievements to the EMREx client as ELMO-XML.
 *
 * @package     local_emp
 * @copyright   2022 Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_emp\output\form\export_form;
use local_emp\manager;
use local_emp\elmo_builder;

$sessionid = required_param('sessionId', PARAM_RAW_TRIMMED);
$returnurl = optional_param('returnUrl', null, PARAM_URL);
$context = context_system::instance();


$url = new moodle_url('/local/emp/init.php', array('sessionId' => $sessionid, 'returnUrl' => $returnurl));

$PAGE->set_pagelayout('report');
$PAGE->set_context($context);
$PAGE->set_url($url);

// User has to be logged in.
require_login();

// Check capabilities.
if (!has_capability('local/emp:allowaccess', $context)) {
    redirect(new moodle_url('/'));
}

$PAGE->set_title(get_string('exportformtitle', 'local_emp'));
$PAGE->set_heading(get_string('exportformtitle', 'local_emp'));
$PAGE->requires->js(new moodle_url('/local/emp/js/export_form.js'));
$PAGE->requires->css(new moodle_url('/local/emp/css/styles.css'));

$manager = new manager($sessionid, $returnurl);

$issuer = $DB->get_records("local_emp_issuer");
if (!isset($issuer) || empty($issuer)) {
    $errormessage = 'EMREX Contact Point not configured correctly. No issuer set.';
    $manager->ncp_error($errormessage);
}
$issuer = reset($issuer);

$customdata = array(
    'user' => $DB->get_record("user", array('id' => $USER->id), 'id, lang', MUST_EXIST),
    'baseurl' => qualified_me(),
);

$mform = new export_form($url, $customdata);

if (empty($mform->achievements)) {
    $manager->ncp_no_results();
}

if ($mform->is_cancelled()) {
    $manager->ncp_cancel();
} else if ($fromform = $mform->get_data()) {
    // If achievements were selected, send them to the EMREX client.
    if (empty($fromform->achievements)) {
        $manager->ncp_error('No achievements selected.');
    } else {
        $elmo = new elmo_builder($USER, $issuer, $fromform->achievements);
        $signedelmo = $elmo->sign();

        $reponse = $manager->ncp_ok($signedelmo);
    }
} else {
    // Render page and export form.
    echo $OUTPUT->header();

    // Check if required fields are set in user profile.
    $profileurl = new moodle_url('/user/editadvanced.php', ['id' => $USER->id, 'course' => 1]);
    $requiredfieldsset = true;
    if (empty($USER->profile['local_emp_placeOfBirth'])) {
        \core\notification::error(get_string('placeofbirthnotset', 'local_emp', $profileurl->out(true)));
        $requiredfieldsset = false;
    }
    if (empty($USER->profile['local_emp_birthName'])) {
        \core\notification::error(get_string('birthnamenotset', 'local_emp', $profileurl->out(true)));
        $requiredfieldsset = false;
    }
    if (empty($USER->profile['local_emp_bday'])) {
        \core\notification::error(get_string('birthdaynotset', 'local_emp', $profileurl->out(true)));
        $requiredfieldsset = false;
    }

    // Display form if required fields are set.
    if ($requiredfieldsset) {
        $toform = array(
            'sessionId' => $sessionid,
            'returnUrl' => $returnurl,
        );
        $mform->set_data($toform);
        $mform->display();
    }

    echo $OUTPUT->footer();
}
