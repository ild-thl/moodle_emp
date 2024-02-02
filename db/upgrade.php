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
 * Upgrade script for local_emp.
 *
 * @package     local_emp
 * @copyright   2022 Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrades the database according to the current version.
 *
 * @param int $oldversion
 * @return boolean
 */
function xmldb_local_emp_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2024012815) {
        // Define table recognition_history to be created.
        $table = new xmldb_table('recognition_history');

        // Adding fields to table recognition_history.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('pimhistorysetid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('learningopportunity', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('studyprogramme', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('hei', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_BINARY, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('credits', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, null);
        $table->add_field('year', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table recognition_history.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('recognition_history_courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'courses', ['id']);
        $table->add_key('unique_pimhistorysetid', XMLDB_KEY_UNIQUE, ['pimhistorysetid']);

        // Conditionally launch create table for recognition_history.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2024012815, 'local', 'emp');
    }

    return true;
}
