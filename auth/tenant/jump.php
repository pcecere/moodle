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
 * Initiates SSO jump to a tenant site.
 *
 * This page is supposed to be opened in new window!
 *
 * @package    auth
 * @subpackage tenant
 * @copyright  2011 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__) . '/../../config.php');

if ($TENANT->id) {
    throw new tenant_access_exception('jump from tenant site does not make any sense');
}

$id = required_param('id', PARAM_INT); // user id in tenant site

$PAGE->set_url('/auth/tenant/jump.php', array('id'=>$id));

require_login();

if (!$tu = $DB->get_record('auth_tenant_users', array('userid'=>$USER->id, 'tenantuserid'=>$id))) {
    // invalid request
    throw new tenant_access_exception('current user can not sso to tenant user');
}

if (!$tenant = $DB->get_record('tenant', array('id'=>$tu->tenantid, 'status'=>0))) {
    // tenant not active
    throw new tenant_access_exception('tenant site disabled');
}

if (!$DB->record_exists('user', array('id'=>$id, 'deleted'=>0, 'suspended'=>0, 'auth'=>'tenant'))) {
    // invalid tenant user
    throw new tenant_access_exception('target tenant user not available');
}

require_sesskey();


//TODO: verify if target session already exists and reuse the token, but we need to deal with session termination first

do {
    $token = random_string(40);
} while ($DB->record_exists('auth_tenant_sso', array('token'=>$token)));

// let's jump!!
$sso = new stdClass();
$sso->tuid        = $tu->id;
$sso->mainsid     = session_id();
$sso->tenantsid   = null; // anything else than null means jump succeeded
$sso->token       = $token;
$sso->timecreated = time();

$DB->insert_record('auth_tenant_sso', $sso);

$targeturl = new moodle_url("$tenant->wwwroot/auth/tenant/land.php", array('token'=>$sso->token));

redirect($targeturl);
