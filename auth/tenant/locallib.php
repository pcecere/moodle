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
 * Local stuff for tenant auth plugin.
 *
 * @package    auth
 * @subpackage tenant
 * @copyright  2011 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/auth/tenant/auth.php");


/**
 * Event handler for tenant sso auth plugin.
 *
 * We try to keep everything in sync via listening to events,
 * it may fail sometimes, so we always do a full sync in cron too.
 */
class auth_tenant_handler {
    public function member_added($ca) {
        global $DB;

        if (!is_enabled_auth('tenant')) {
            return true;
        }

        if (!$DB->record_exists('auth_tenant_cohorts', array('cohortid'=>$ca->cohortid))) {
            return;
        }

        auth_tenant_sync($ca->userid);

        return true;
    }

    public function member_removed($ca) {
        global $DB;

        if (!is_enabled_auth('tenant')) {
            return true;
        }

        if (!$DB->record_exists('auth_tenant_cohorts', array('cohortid'=>$ca->cohortid))) {
            return;
        }

        auth_tenant_sync($ca->userid);

        return true;
    }

    public function deleted($cohort) {
        global $DB;

        if (!$DB->record_exists('auth_tenant_cohorts', array('cohortid'=>$cohort->id))) {
            return;
        }

        $DB->delete_records('auth_tenant_cohorts', array('cohortid'=>$cohort->id));

        if (!is_enabled_auth('tenant')) {
            return true;
        }

        auth_tenant_sync();

        return true;
    }
}


/**
 * Synchronise tenant SSO users.
 *
 * @param int $userid 0 means all users
 * @param bool $verbose
 * @return int CLI result 0 means success, 1 error
 */
function auth_tenant_sync($userid = 0, $verbose = false) {
    global $DB;

    if ($verbose) {
        mtrace('Starting user account synchronisation...');
    }

    if ($userid) {
        $usercontext = context_user::instance($userid, IGNORE_MISSING);
        if (!$usercontext and $usercontext->tenantid) {
            // only valid main site accounts are accepted
            return 1;
        }
    }

    // first create new users
    $params = array('userid'=>$userid);
    $oneuser = $userid ? "AND u.id = :userid" : "";
    $sql = "SELECT u.*, atc.tenantid AS newtenantid, t.hostid
              FROM {user} u
              JOIN {cohort_members} cm ON (cm.userid = u.id)
              JOIN {cohort} c ON (c.id = cm.cohortid AND c.tenantid = 0)
              JOIN {auth_tenant_cohorts} atc ON (atc.cohortid = cm.cohortid)
              JOIN {tenant} t ON (t.id = atc.tenantid)
         LEFT JOIN {auth_tenant_users} atu ON (atu.userid = u.id AND atu.tenantid = atc.tenantid)
             WHERE u.deleted = 0 AND u.tenantid = 0 AND atu.id IS NULL
                   $oneuser";
    $rs = $DB->get_records_sql($sql, $params);
    foreach ($rs as $user) {
        if ($DB->record_exists('user', array('mnethostid'=>$user->hostid, 'username'=>$user->username))) {
            // bad luck, some other auth already created the user record...
            if ($verbose) {
                mtrace("  can not create tenant user account for $user->id in tenant $user->newtenantid, username collision");
            }
            continue;
        }
        $tenantuser = clone($user);
        unset($tenantuser->id);
        unset($tenantuser->hostid);
        unset($tenantuser->newtenantid);
        $tenantuser->auth         = 'tenant';
        $tenantuser->mnethostid   = $user->hostid;
        $tenantuser->tenantid     = $user->newtenantid;
        $tenantuser->password     = 'not cached';
        $tenantuser->suspended    = 0;
        $tenantuser->timecreated  = time();
        $tenantuser->timemodified = $tenantuser->timecreated;
        $tenantuser->firstaccess  = 0;
        $tenantuser->lastaccess   = 0;
        $tenantuser->id = $DB->insert_record('user', $tenantuser);

        $record = new stdClass();
        $record->tenantid     = $tenantuser->tenantid;
        $record->userid       = $user->id;
        $record->tenantuserid = $tenantuser->id;
        $record->timecreated  = time();
        $DB->insert_record('auth_tenant_users', $record);

        context_user::instance($tenantuser->id);

        events_trigger('user_created', $tenantuser);

        if ($verbose) {
            mtrace("  created account: $tenantuser->id ($user->id) in tenant $tenantuser->tenantid");
        }
    }


    // suspend users that should not be able to access tenant site
    $params = array('userid'=>$userid);
    $oneuser = $userid ? "AND u.id = :userid" : "";
    $sql = "SELECT tu.id, tu.tenantid, u.id AS mainid
              FROM {user} tu
              JOIN {auth_tenant_users} atu ON (atu.tenantuserid = tu.id)
              JOIN {user} u ON (u.id = atu.userid AND u.tenantid = 0)
             WHERE tu.suspended = 0
                   $oneuser
                   AND NOT EXISTS (SELECT cm.id
                                     FROM {cohort_members} cm
                                     JOIN {auth_tenant_cohorts} atc ON (atc.cohortid = cm.cohortid AND atc.tenantid = tu.tenantid)
                                    WHERE cm.userid = u.id)";
    $rs = $DB->get_records_sql($sql, $params);
    foreach ($rs as $user) {
        // do NOT try to delete users from tenants, instead just suspend accounts!
        $DB->set_field('user', 'suspended', 1, array('id'=>$user->id));
        if ($verbose) {
            mtrace("  suspended account: $user->id ($user->mainid) in tenant $user->tenantid");
        }
    }


    // unsuspend users
    $params = array('userid'=>$userid);
    $oneuser = $userid ? "AND u.id = :userid" : "";
    $sql = "SELECT tu.id, tu.tenantid, u.id AS mainid
              FROM {user} tu
              JOIN {auth_tenant_users} atu ON (atu.tenantuserid = tu.id)
              JOIN {user} u ON (u.id = atu.userid AND u.tenantid = 0)
              JOIN {cohort_members} cm ON (cm.userid = u.id)
              JOIN {auth_tenant_cohorts} atc ON (atc.cohortid = cm.cohortid AND atc.tenantid = tu.tenantid)
             WHERE tu.suspended = 1
                   $oneuser";
    $rs = $DB->get_records_sql($sql, $params);
    foreach ($rs as $user) {
        // do NOT try to delete users from tenants, instead just suspend accounts!
        $DB->set_field('user', 'suspended', 0, array('id'=>$user->id));

        if ($verbose) {
            mtrace("  unsuspended account: $user->id ($user->mainid) in tenant $user->tenantid");
        }
    }

    $allroles = null;

    // assign tenant roles
    $params = array('userid'=>$userid, 'tenantlevel'=>CONTEXT_TENANT);
    $oneuser = $userid ? "AND u.id = :userid" : "";
    $sql = "SELECT tu.id, ctx.id AS contextid, atc.roleid, tu.tenantid, u.id AS mainid
              FROM {user} tu
              JOIN {auth_tenant_users} atu ON (atu.tenantuserid = tu.id)
              JOIN {user} u ON (u.id = atu.userid AND u.tenantid = 0)
              JOIN {cohort_members} cm ON (cm.userid = u.id)
              JOIN {auth_tenant_cohorts} atc ON (atc.cohortid = cm.cohortid AND atc.tenantid = tu.tenantid)
              JOIN {role} r ON (r.id = atc.roleid)
              JOIN {context} ctx ON (ctx.contextlevel = :tenantlevel AND ctx.instanceid = atc.tenantid)
         LEFT JOIN {role_assignments} ra ON (ra.contextid = ctx.id AND ra.userid = tu.id AND ra.roleid = atc.roleid AND ra.component='auth_tenant')
             WHERE ra.id IS NULL
                   $oneuser";
    $rs = $DB->get_records_sql($sql, $params);
    foreach ($rs as $user) {
        if (!$allroles) {
            $allroles = get_all_roles();
        }
        role_assign($user->roleid, $user->id, $user->contextid, 'auth_tenant');
        if ($verbose) {
            $role = $allroles[$user->roleid]->shortname;
            mtrace("  assigned role '$role': $user->id ($user->mainid) in tenant $user->tenantid");
        }
    }

    // unassign tenant roles
    $params = array('userid'=>$userid, 'tenantlevel'=>CONTEXT_TENANT);
    $oneuser = $userid ? "AND u.id = :userid" : "";
    $sql = "SELECT tu.id, ra.contextid, ra.roleid, tu.tenantid, u.id AS mainid
              FROM {user} tu
              JOIN {auth_tenant_users} atu ON (atu.tenantuserid = tu.id)
              JOIN {user} u ON (u.id = atu.userid AND u.tenantid = 0)
              JOIN {context} ctx ON (ctx.contextlevel = :tenantlevel AND ctx.instanceid = atu.tenantid)
              JOIN {role_assignments} ra ON (ra.contextid = ctx.id AND ra.userid = tu.id AND ra.component='auth_tenant')
             WHERE tu.tenantid > 0
                   $oneuser
                   AND NOT EXISTS (SELECT cm.id
                                     FROM {cohort_members} cm
                                     JOIN {auth_tenant_cohorts} atc ON (atc.cohortid = cm.cohortid AND atc.tenantid = tu.tenantid AND atc.roleid = ra.roleid)
                                    WHERE cm.userid = u.id
                                   )";
    $rs = $DB->get_records_sql($sql, $params);
    foreach ($rs as $user) {
        if (!$allroles) {
            $allroles = get_all_roles();
        }
        role_unassign($user->roleid, $user->id, $user->contextid, 'auth_tenant');
        if ($verbose) {
            $role = $allroles[$user->roleid]->shortname;
            mtrace("  unassigned role '$role': $user->id ($user->mainid) in tenant $user->tenantid");
        }
    }

    if ($verbose) {
        mtrace('...user account synchronisation finished.');
    }

    return 0;
}