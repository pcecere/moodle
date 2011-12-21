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
 * @subpackage tenant
 * @copyright  2011 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once("$CFG->libdir/adminlib.php");
require_once("$CFG->libdir/tenantlib.php");
require_once("$CFG->dirroot/$CFG->admin/tool/tenant/edit_form.php");

$id = optional_param('id', 0, PARAM_INT);

admin_externalpage_setup('tooltenant');

if ($id) {
    $tenant = $DB->get_record('tenant', array('id'=>$id), '*', MUST_EXIST);
} else {
    $tenant = new stdClass();
    $tenant->id = 0;
}

$editform = new tenant_edit_form(null, $tenant);

if ($editform->is_cancelled()) {
    redirect(new moodle_url('/admin/tool/tenant/index.php'));

} else if ($data = $editform->get_data()) {
    $data->wwwroot = rtrim($data->wwwroot, '/');
    if ($data->id) {
        tenant_update($data);
    } else {
        tenant_install($data);
    }

    redirect(new moodle_url('/admin/tool/tenant/index.php'));
}

echo $OUTPUT->header();
echo $editform->display();
echo $OUTPUT->footer();

