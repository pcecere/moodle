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
 * Logs in tenant user via SSO
 *
 * @package    auth
 * @subpackage tenant
 * @copyright  2011 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__) . '/../../config.php');

if (!$TENANT->id) {
    throw new tenant_access_exception('landing in main site does not make any sense');
}

$token = required_param('token', PARAM_RAW);

$PAGE->set_url('/auth/tenant/land.php', array('token'=>$token));

if (!$sso = $DB->get_record('auth_tenant_sso', array('token'=>$token))) {
    throw new tenant_access_exception('can not find token');
}

if (!$tu = $DB->get_record('auth_tenant_users', array('id'=>$sso->tuid))) {
    //very weird
    $DB->delete_records('auth_tenant_sso', array('id'=>$sso->id));
    throw new tenant_access_exception('can not find tenant user info');
}

if ($tu->tenantid != $TENANT->id) {
    throw new tenant_access_exception('trying to land in the wrong tenant');
}

if (isset($sso->tenantsid)) {
    // this is not likely
    if ($USER->id == $tu->tenantuserid) {
        // strange we are already here
        $DB->set_field('auth_tenant_sso', 'tenantsid', session_id(), array('id'=>$sso->id));
        redirect("$CFG->wwwroot");
    }
    throw new tenant_access_exception('token was already used');
}

if ($USER->id == $tu->tenantuserid) {
    //repeated jump, we do not need the new session

    if ($oldsso = $DB->get_record('auth_tenant_sso', array('tenantsid'=>session_id(), 'tuid'=>$tu->id))) {
        // no need for new sso entry, let's reuse the existing one
        $DB->set_field('auth_tenant_sso', 'mainsid', $sso->mainsid, array('id'=>$oldsso->id));
        $DB->delete_records('auth_tenant_sso', array('id'=>$sso->id));
    } else {
        // this should not happen
        $DB->set_field('auth_tenant_sso', 'tenantsid', session_id(), array('id'=>$sso->id));

    }
    redirect("$CFG->wwwroot");
}

if (isloggedin() and !isguestuser()) {
    //TODO: give them a chance to logout somehow
    $DB->delete_records('auth_tenant_sso', array('id'=>$sso->id));
    error('You can not jumpt to tenant site where you are already logged in as different user, sorry');
}


// finally login the user!
if (!$user = $DB->get_record('user', array('id'=>$tu->tenantuserid, 'deleted'=>0, 'suspended'=>0, 'auth'=>'tenant'))) {
    // invalid tenant user
    throw new tenant_access_exception('used not enabled');
}

complete_user_login($user);

redirect("$CFG->wwwroot");
