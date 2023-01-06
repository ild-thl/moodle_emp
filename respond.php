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
$returnurl = required_param('returnUrl', PARAM_URL);
$returncode = required_param('returnCode', PARAM_RAW_TRIMMED);
$returnmessage = optional_param('returnMessage', '', PARAM_RAW);
$elmo = optional_param('elmo', '', PARAM_RAW);
$context = context_system::instance();

// User has to be logged in.
require_login();

// Check capabilities.
if (!has_capability('local/emp:allowaccess', $context)) {
    redirect(new moodle_url('/'));
}

$PAGE->requires->js(new moodle_url('/local/emp/js/respond.js'));

$templatedata = array(
    'returnurl' => $returnurl,
    'sessionid' => $sessionid,
    'returncode' => $returncode,
    'returnmessage' => $returnmessage,
    'elmo' => $elmo,
);

echo $OUTPUT->render_from_template('local_emp/respond', $templatedata);

