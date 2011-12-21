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
 * Tenant edit form
 *
 * @package    tool
 * @subpackage tenant
 * @copyright  2011 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

class tenant_edit_form extends moodleform {

    /**
     * Define the tenant edit form
     */
    public function definition() {

        $mform = $this->_form;
        $tenant = $this->_customdata;

        $mform->addElement('text', 'fullname', get_string('fullsitename'), 'maxlength="255" size="100"');
        $mform->addRule('fullname', get_string('required'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_MULTILANG);

        $mform->addElement('text', 'shortname', get_string('shortsitename'), 'maxlength="255" size="30"');
        $mform->addRule('shortname', get_string('required'), 'required', null, 'client');
        $mform->setType('shortname', PARAM_NOTAGS);

        $mform->addElement('text', 'wwwroot', get_string('wwwroot', 'tool_tenant'), 'maxlength="255" size="100"');
        $mform->addRule('wwwroot', get_string('required'), 'required', null, 'client');
        $mform->setType('wwwroot', PARAM_URL);

        $mform->addElement('text', 'contactname', get_string('contactname', 'tool_tenant'), 'maxlength="255" size="50"');
        $mform->addRule('contactname', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'contactemail', get_string('contactemail', 'tool_tenant'), 'maxlength="255" size="50"');
        $mform->addRule('contactemail', get_string('required'), 'required', null, 'client');
        $mform->setType('contactemail', PARAM_EMAIL);

        $options = array(0 => get_string('enabled', 'tool_tenant'),
                         1 => get_string('disabled', 'tool_tenant'));
        $mform->addElement('select', 'status', get_string('status', 'tool_tenant'), $options);
        $mform->setDefault('status', 0);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $label = empty($tenant->id) ? get_string('newtenant', 'tool_tenant') : null;
        $this->add_action_buttons(true, $label);

        $this->set_data($tenant);
    }

    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        $wwwroot = clean_param($data['wwwroot'], PARAM_URL);
        $wwwroot = rtrim($wwwroot, '/');

        if (!preg_match('#^https?://[a-z]+#', $wwwroot)) {
            $errors['wwwroot'] = get_string('error');

        } else if (empty($data['id'])) {
            if ($DB->record_exists('tenant', array('wwwroot'=>$wwwroot))) {
                $errors['wwwroot'] = get_string('error');
            }

        } else {
            if ($tenant = $DB->get_record('tenant', array('wwwroot'=>$wwwroot)) and $tenant->id != $data['id']) {
                $errors['wwwroot'] = get_string('error');
            }
        }

        return $errors;
    }
}

