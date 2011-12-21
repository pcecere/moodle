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
 * Tenant SSO extra admin pages
 *
 * @package    auth
 * @subpackage tenant
 * @copyright  2011 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('authsettings', new admin_category('authtenantcategory', get_string('pluginname', 'auth_tenant')));

$ADMIN->add('authtenantcategory', new admin_settingpage('authsettingtenant', get_string('adminpagesettings', 'auth_tenant'), 'moodle/site:config', !$enabled));
//TODO: add some settings

$ADMIN->add('authtenantcategory', new admin_externalpage('authtenantcohorts', get_string('adminpagecohorts', 'auth_tenant'), "$CFG->wwwroot/auth/tenant/cohorts.php", 'moodle/site:config', !$enabled));

$settings = null;
