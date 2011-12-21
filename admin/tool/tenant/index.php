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
 * Tenant management.
 *
 * @package    tool
 * @subpackage tenantsso
 * @copyright  2011 Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('tooltenant');

// Print the header.
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('tenants'));

$stredit = get_string('edit');
$strurl = get_string('url');
$strusers = get_string('users');
$strtenant = get_string('tenant');
$strnewtenant = get_string('newtenant', 'tool_tenant');


$data = array();
$tenants = $DB->get_records('tenant', array(), 'shortname, wwwroot');
foreach($tenants as $tenant) {
    $line = array();
    $line[0] = format_string($tenant->fullname)." [$tenant->shortname]";
    $line[1] = $tenant->wwwroot;
    $line[2] = $DB->count_records('user', array('tenantid'=>$tenant->id, 'deleted'=>0));
    $line[3] = "<a title=\"$stredit\" href=\"edit.php?id=$tenant->id\"><img".
        " src=\"" . $OUTPUT->pix_url('t/edit') . "\" class=\"iconsmall\" alt=\"$stredit\" /></a> ";

    $data[] = $line;
}

$table = new html_table();
$table->head  = array($strtenant, $strurl, $strusers, $stredit);
$table->size  = array('50%', '30%', '10%', '10%');
$table->align = array('left', 'left', 'left', 'center');
$table->width = '90%';
$table->data  = $data;
echo html_writer::table($table);

echo $OUTPUT->container_start('buttons');
echo $OUTPUT->single_button(new moodle_url('edit.php'), $strnewtenant);
echo $OUTPUT->container_end();

echo $OUTPUT->footer();
