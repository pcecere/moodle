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
 * @package    auth
 * @subpackage tenant
 * @copyright  2011 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

class tenant_cohort_form extends moodleform {

    /**
     * Define the tenant edit form
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;

        $syscontext = context_system::instance();

        $rs = $DB->get_recordset('cohort', array('contextid'=>$syscontext->id, 'tenantid'=>0));
        $cohorts = array(''=>get_string('choosedots'));
        foreach ($rs as $c) {
            $cohorts[$c->id] = format_string($c->name);
        }
        $rs->close();

        $roles = get_assignable_roles($syscontext);
        $roles[0] = get_string('none');
        $roles = array_reverse($roles, true); // descending default sortorder

        $rs = $DB->get_recordset('tenant', array(), 'fullname, shortname', '*');
        $tenants = array(''=>get_string('choosedots'));
        foreach ($rs as $tenant) {
            $tenants[$tenant->id] = "$tenant->fullname [$tenant->wwwroot]";
        }
        $rs->close();

        $mform->addElement('select', 'tenantid', get_string('tenant'), $tenants);
        $mform->addRule('tenantid', get_string('required'), 'required', null, 'client');

        $mform->addElement('select', 'cohortid', get_string('cohort', 'cohort'), $cohorts);
        $mform->addRule('cohortid', get_string('required'), 'required', null, 'client');

        $mform->addElement('select', 'roleid', get_string('role'), $roles);

        $mform->addElement('hidden', 'add');
        $mform->setType('add', PARAM_INT);

        $this->add_action_buttons(true, get_string('addinstance', 'enrol'));

        $this->set_data(array('add'=>1));
    }
}

