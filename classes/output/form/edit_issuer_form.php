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

require_once($CFG->libdir . "/formslib.php");

/**
 * Form to create or edit an elmo issuer.
 *
 * @package     local_emp
 * @copyright   2022 Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_issuer_form extends \moodleform {
    /**
     * Form definition.
     * @return void
     */
    public function definition() {

        $mform = $this->_form;

        // Private key input.
        $mform->addElement('hidden', 'id', null);
        $mform->setType('id', PARAM_INT);

        // Name english.
        $mform->addElement('text',  'titleen',  get_string('issuertitleen', 'local_emp'));
        $mform->setType('titleen', PARAM_NOTAGS);
        $mform->addRule('titleen', get_string('required'), 'required', null, 'client');

        // Name german.
        $mform->addElement('text',  'titlede',  get_string('issuertitlede', 'local_emp'));
        $mform->setType('titlede', PARAM_NOTAGS);

        // URL.
        $mform->addElement('text',  'url',  get_string('issuerurl', 'local_emp'));
        $mform->setType('url', PARAM_URL);
        $mform->addRule('url', get_string('required'), 'required', null, 'client');

        // SCHAC ID.
        $mform->addElement('text',  'schac',  get_string('issuerschac', 'local_emp'));
        $mform->setType('schac', PARAM_NOTAGS);
        $mform->addRule('schac', get_string('required'), 'required', null, 'client');

        // ERASMUS ID.
        $mform->addElement('text',  'erasmus',  get_string('issuererasmus', 'local_emp'));
        $mform->setType('erasmus', PARAM_NOTAGS);

        // PIC ID.
        $mform->addElement('text',  'pic',  get_string('issuerpic', 'local_emp'));
        $mform->setType('pic', PARAM_NOTAGS);

        // Country code.
        $mform->addElement('text',  'country',  get_string('country', 'local_emp'));
        $mform->setType('country', PARAM_ALPHA);

        $this->add_action_buttons(true);
    }

    /**
     * Gets input data of submitted form.
     *
     * @return object
     **/
    public function get_data() {
        $data = parent::get_data();

        if (empty($data)) {
            return false;
        }

        return $data;
    }
}
