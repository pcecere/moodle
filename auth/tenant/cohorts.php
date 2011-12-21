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
 * Cohort sync management.
 *
 * @package    auth
 * @subpackage tenant
 * @copyright  2011 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__) . '/../../config.php');
require_once("$CFG->libdir/adminlib.php");
require_once("$CFG->dirroot/auth/tenant/cohorts_form.php");
require_once("$CFG->dirroot/auth/tenant/locallib.php");

$add = optional_param('add', 0, PARAM_BOOL);
$delete = optional_param('delete', 0, PARAM_INT);
$cohortid = optional_param('cohortid', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

admin_externalpage_setup('authtenantcohorts');

if ($add) {
    $editform = new tenant_cohort_form();
    if ($data = $editform->get_data()) {
        $data->roleid = empty($data->roleid) ? null : $data->roleid;
        if (!$DB->record_exists('auth_tenant_cohorts', array('cohortid'=>$data->cohortid, 'tenantid'=>$data->tenantid, 'roleid'=>$data->roleid))) {
            $data->timecreated = time();
            $DB->insert_record('auth_tenant_cohorts', $data);
        }
        auth_tenant_sync();

        redirect($PAGE->url);
    }

    echo $OUTPUT->header();
    echo $editform->display();
    echo $OUTPUT->footer();
    die;

} else if ($delete and $cohortid) {
    $cohort = $DB->get_record('cohort', array('id'=>$cohortid, 'tenantid'=>0), '*', MUST_EXIST);
    $tenant = $DB->get_record('tenant', array('id'=>$delete), '*', MUST_EXIST);

    if ($confirm and sesskey()) {
        $DB->delete_records('auth_tenant_cohorts', array('cohortid'=>$cohort->id, 'tenantid'=>$tenant->id));
        auth_tenant_sync();
        redirect($PAGE->url);
    }

    $strheading = get_string('delcohort', 'auth_tenant');
    $PAGE->navbar->add($strheading);
    $PAGE->set_title($strheading);
    echo $OUTPUT->header();
    echo $OUTPUT->heading($strheading);
    $yesurl = new moodle_url($PAGE->url, array('delete'=>$tenant->id, 'cohortid'=>$cohort->id, 'confirm'=>1,'sesskey'=>sesskey()));
    $a = (object)array('cohort'=>$cohort->name, 'tenant'=>format_string($tenant->fullname));
    $message = get_string('delcohortconfirm', 'auth_tenant', $a);
    echo $OUTPUT->confirm($message, $yesurl, $PAGE->url);
    echo $OUTPUT->footer();
    die;
}

$strdelete = get_string('delete');
$allroles = get_all_roles();

$sql = "SELECT t.*
          FROM {tenant} t
         WHERE EXISTS (SELECT atc.id
                         FROM {auth_tenant_cohorts} atc
                        WHERE atc.tenantid = t.id)
      ORDER BY t.fullname, t.wwwroot";
$tenants = $DB->get_records_sql($sql);

$data = array();
foreach($tenants as $tenant) {
    $line = array();
    $line[0] = format_string($tenant->fullname)." [$tenant->shortname]<br /><a href='$tenant->wwwroot'>$tenant->wwwroot</a>";

    $sql = "SELECT c.*, atc.roleid
              FROM {cohort} c
              JOIN {auth_tenant_cohorts} atc ON (atc.cohortid = c.id AND atc.tenantid = ?)
          ORDER BY c.name";
    $str = '';
    $cohorts = $DB->get_records_sql($sql, array($tenant->id));
    foreach ($cohorts as $cohort) {
        $str .= format_string($cohort->name);
        if ($cohort->roleid) {
            $str .= ' ('.$allroles[$cohort->roleid]->shortname.')';
        }
        $str .= "<a title=\"$strdelete\" href=\"cohorts.php?delete=$tenant->id&amp;cohortid=$cohort->id\"><img src=\"" . $OUTPUT->pix_url('t/delete') . "\" class=\"iconsmall\" alt=\"$strdelete\" /></a> ";
        $str .= '<br />';
    }
    $line[1] = $str;

    $data[] = $line;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('adminpagesettings', 'auth_tenant'));

$table = new html_table();
$table->head  = array(get_string('tenant'), get_string('cohorts', 'cohort'));
$table->size  = array('50%', '50%');
$table->align = array('left', 'left');
$table->width = '90%';
$table->data  = $data;
echo html_writer::table($table);

if ($DB->record_exists('tenant', array()) and $DB->record_exists('cohort', array('contextid'=>context_system::instance()->id))) {
    echo $OUTPUT->container_start('buttons');
    echo $OUTPUT->single_button(new moodle_url($PAGE->url, array('add'=>1)), get_string('addcohort', 'auth_tenant'));
    echo $OUTPUT->container_end();
} else {
    echo $OUTPUT->notification(get_string('createtenantsandcohorts', 'auth_tenant'));
}

echo $OUTPUT->footer();
