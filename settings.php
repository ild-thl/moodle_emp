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
 * Plugin administration pages are defined here.
 *
 * @package     local_emp
 * @category    admin
 * @copyright   2022 Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $modfolder = new admin_category(
        'localempfolder',
        new lang_string(
            'pluginname',
            'local_emp'
        )
    );
    $ADMIN->add('localplugins', $modfolder);

    if ($ADMIN->fulltree) {
        $settingspage = new admin_settingpage('managelocalemp', new lang_string('managelocalemp', 'local_emp'));

        $settingspage->add(new admin_setting_configtext(
            'localemp/keyfile',
            get_string('keyfile', 'local_emp'),
            '',
            $CFG->dirroot . '/local/emp/my-key.key'
        ));
        $settingspage->add(new admin_setting_configtext(
            'localemp/certfile',
            get_string('certfile', 'local_emp'),
            '',
            $CFG->dirroot . '/local/emp/my-cert.crt'
        ));
        $settingspage->add(new admin_setting_configtext(
            'localemp/pempassphrase',
            get_string('pempassphrase', 'local_emp'),
            '',
            ''
        ));

        $ADMIN->add('localempfolder', $settingspage);
    }

    $ADMIN->add(
        'localempfolder',
        new admin_externalpage(
            'localemp_edit_issuer',
            get_string('editissuer', 'local_emp'),
            $CFG->wwwroot . '/local/emp/edit_issuer.php'
        )
    );
}

// Prevent Moodle from adding settings block in standard location.
$settings = null;
