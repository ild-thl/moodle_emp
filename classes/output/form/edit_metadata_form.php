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

require_once("$CFG->libdir/formslib.php");

/**
 * Form to manage additional course metadata.
 *
 * @package     local_emp
 * @copyright   2022 Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_metadata_form extends \moodleform {
    /**
     * Define form.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        // Credits.
        $mform->addElement('float', 'creditvalue', get_string('creditvalue', 'local_emp'));
        $mform->addRule('creditvalue', get_string('required'), 'required', null, 'client');
        $creditschemes = array('ECTS' => 'ECTS (European Credential Transfer and Accumulation System)');
        $mform->addElement('select', 'creditscheme', get_string('creditscheme', 'local_emp'), $creditschemes);
        $mform->setDefault('creditscheme', 'ECTS');

        // Level of education.
        $levelvalues = array(
            '1' => '1',
            '2' => '2',
            '3' => '3',
            '4' => '4',
            '5' => '5',
            '6' => '6',
            '7' => '7',
            '8' => '8'
        );
        $mform->addElement('select', 'levelvalue', get_string('levelvalue', 'local_emp'), $levelvalues);
        $mform->setDefault('levelvalue', '6');
        $leveltypes = array(
            'EQF' => 'EQF (European Qualification Framework)',
            'DQR' => 'DQR (Deutscher Qualifikationsrahmen)'
        );
        $mform->addElement('select', 'leveltype', get_string('leveltype', 'local_emp'), $leveltypes);
        $mform->setDefault('leveltype', 'EQF');

        // Language of instruction.
        $languages = array(
            'de' => get_string('deu', 'iso6392'),
            'en' => get_string('eng', 'iso6392'),
        );
        $mform->addElement('select', 'languageofinstruction', get_string('languageofinstruction', 'local_emp'), $languages);
        $mform->setDefault('languageofinstruction', 'de');

        // Engagement hours.
        $mform->addElement('float', 'engagementhours', get_string('engagementhours', 'local_emp'));

        // Is course programme?
        // HasPart.
        $mform->addElement('checkbox', 'hasparts', get_string('hascourseparts', 'local_emp'));
        $possiblecourseparts = $this->_customdata;
        if (!empty($possiblecourseparts)) {
            $select = $mform->addElement('select', 'parts', get_string('courseparts', 'local_emp'), $possiblecourseparts);
            $select->setMultiple(true);
            $mform->disabledIf('parts', 'hasparts');
        } else {
            $mform->addElement('static', 'nopossiblecourseparts', '', get_string('nopossiblecourseparts', 'local_emp'));
        }

        $this->add_action_buttons();
    }
}
