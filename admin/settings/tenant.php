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
 * Very basic tenant settings.
 *
 * @package    core
 * @subpackage tenant
 * @copyright  2011 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (empty($TENANT->id)) {
    // oops!
    return;
}

$tenantcontext = context_tenant::instance($TENANT->id);
$frontpagecontext = context_course::instance($SITE->id);

//TODO: add external pages only - saving of standard settings can not be supported, sorry
//TODO: we need to build special tenant setting classes for overriding of main settings in tenants

if (empty($CFG->loginhttps)) {
    $securewwwroot = $CFG->wwwroot;
} else {
    $securewwwroot = str_replace('http:','https:',$CFG->wwwroot);
}

$ADMIN->add('root', new admin_category('users', get_string('users','admin')));
$ADMIN->add('root', new admin_category('courses', get_string('courses','admin')));
$ADMIN->add('root', new admin_category('frontpage', get_string('frontpage','admin')));


$ADMIN->add('users', new admin_category('accounts', get_string('accounts', 'admin')));
$ADMIN->add('users', new admin_category('roles', get_string('permissions', 'role')));

$ADMIN->add('accounts', new admin_externalpage('editusers', get_string('userlist','admin'), "$CFG->wwwroot/$CFG->admin/user.php", array('moodle/user:update', 'moodle/user:delete'), false, $tenantcontext));
//$ADMIN->add('accounts', new admin_externalpage('userbulk', get_string('userbulk','admin'), "$CFG->wwwroot/$CFG->admin/user/user_bulk.php", array('moodle/user:update', 'moodle/user:delete'), false, $tenantcontext));
$ADMIN->add('accounts', new admin_externalpage('addnewuser', get_string('addnewuser'), "$securewwwroot/user/editadvanced.php?id=-1", 'moodle/user:create', false, $tenantcontext));
$ADMIN->add('accounts', new admin_externalpage('cohorts', get_string('cohorts', 'cohort'), $CFG->wwwroot . '/cohort/index.php', array('moodle/cohort:manage', 'moodle/cohort:view'), false, $tenantcontext));

$ADMIN->add('roles', new admin_externalpage('assignroles', get_string('assignglobalroles', 'role'), "$CFG->wwwroot/$CFG->admin/roles/assign.php?contextid=".$tenantcontext->id, 'moodle/role:assign', false, $tenantcontext));
$ADMIN->add('roles', new admin_externalpage('checkpermissions', get_string('checkglobalpermissions', 'role'), "$CFG->wwwroot/$CFG->admin/roles/check.php?contextid=".$tenantcontext->id, array('moodle/role:assign', 'moodle/role:safeoverride', 'moodle/role:override', 'moodle/role:manage'), false, $tenantcontext));
$ADMIN->add('roles', new admin_externalpage('permissions', get_string('permissions', 'role'), "$CFG->wwwroot/$CFG->admin/roles/permissions.php?contextid=".$tenantcontext->id, array('moodle/role:assign', 'moodle/role:safeoverride', 'moodle/role:override', 'moodle/role:manage'), false, $tenantcontext));

$ADMIN->add('courses', new admin_externalpage('coursemgmt', get_string('coursemgmt', 'admin'), $CFG->wwwroot . '/course/index.php?categoryedit=on', array('moodle/category:manage', 'moodle/course:create'), false, $tenantcontext));


$ADMIN->add('frontpage', new admin_externalpage('frontpageroles', get_string('frontpageroles', 'admin'), "$CFG->wwwroot/$CFG->admin/roles/assign.php?contextid=" . $frontpagecontext->id, 'moodle/role:assign', false, $frontpagecontext));
$ADMIN->add('frontpage', new admin_externalpage('frontpagefilters', get_string('frontpagefilters', 'admin'), "$CFG->wwwroot/filter/manage.php?contextid=" . $frontpagecontext->id, 'moodle/filter:manage', false, $frontpagecontext));
