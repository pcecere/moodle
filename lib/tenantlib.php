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
 * Utility classes and functions for text editor integration.
 *
 * @package    core
 * @subpackage tenant
 * @copyright  2011 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Install new tenant site
 * @param stdClass $data name + wwwroot + status
 */
function tenant_install($data) {
    global $DB, $CFG;
    require_once("$CFG->libdir/blocklib.php");

    $data->timecreated  = time();
    $data->timemodified = $data->timecreated;
    $data->hostid       = NULL;
    $tenantid = $DB->insert_record('tenant', $data);
    $tenant = $DB->get_record('tenant', array('id'=>$tenantid), '*', MUST_EXIST);
    context_tenant::instance($tenant->id);

    $frontpage = new stdClass();
    $frontpage->tenantid     = $tenant->id;
    $frontpage->fullname     = $tenant->fullname;
    $frontpage->shortname    = $tenant->shortname;
    $frontpage->summary      = '';
    $frontpage->newsitems    = 3;
    $frontpage->numsections  = 0;
    $frontpage->category     = 0;
    $frontpage->format       = 'site';  // Only for this course
    $frontpage->timecreated  = time();
    $frontpage->timemodified = $frontpage->timecreated;
    $frontpage->id = $DB->insert_record('course', $frontpage);
    context_course::instance($frontpage->id);

    $mnethost = new stdClass();
    $mnethost->wwwroot    = $tenant->wwwroot;
    $mnethost->name       = 'TENANT:'.$tenant->shortname;
    $mnethost->public_key = '';
    $mnethost->ip         = 'UNKNOWN';
    $mnethost->id = $DB->insert_record('mnet_host', $mnethost);
    $DB->set_field('tenant', 'hostid', $mnethost->id, array('id'=>$tenant->id));

    $cat = new stdClass();
    $cat->tenantid     = $tenant->id;
    $cat->name         = get_string('miscellaneous');
    $cat->depth        = 1;
    $cat->sortorder    = MAX_COURSES_IN_CATEGORY;
    $cat->timemodified = time();
    $cat->id = $DB->insert_record('course_categories', $cat);
    $DB->set_field('course_categories', 'path', '/'.$cat->id, array('id'=>$cat->id));
    tenant_set_config($tenant->id, null, 'defaultrequestcategory', $cat->id);
    context_coursecat::instance($cat->id);

    $guest = new stdClass();
    $guest->tenantid     = $tenant->id;
    $guest->auth         = 'manual';
    $guest->username     = 'guest';
    $guest->password     = hash_internal_user_password('guest');
    $guest->firstname    = get_string('guestuser');
    $guest->lastname     = ' ';
    $guest->email        = 'root@localhost';
    $guest->description  = get_string('guestuserinfo');
    $guest->mnethostid   = $mnethost->id;
    $guest->confirmed    = 1;
    $guest->lang         = $CFG->lang;
    $guest->timemodified = time();
    $guest->id = $DB->insert_record('user', $guest);
    tenant_set_config($tenant->id, null, 'siteguest', $guest->id);
    context_user::instance($guest->id);

    // override some reasonable setting defaults
    tenant_set_config($tenant->id, null, 'auth', 'manual,tenant');
    tenant_set_config($tenant->id, null, 'enrol_plugins_enabled', 'cohort,guest,manual,meta,self');
    tenant_set_config($tenant->id, null, 'registerauth', '');


    //Note: we continue tweaking that requires the global $TENANT in tenant_install_hacks() bellow
}

/**
 * Install new tenant site
 * @param stdClass $data name + wwwroot + status
 */
function tenant_update($data) {
    global $DB;

    $data->timemodified = time();
    $DB->update_record('tenant', $data);

    $tenant = $DB->get_record('tenant', array('id'=>$data->id), '*', MUST_EXIST);
    if ($mnethost = $DB->get_record('mnet_host', array('id'=>$tenant->hostid))) {
        $mnethost->wwwroot = $tenant->wwwroot;
        $mnethost->name    = 'TENANT:'.$tenant->shortname;
        $DB->update_record('mnet_host', $mnethost);
    }
}

/**
 * Set tenant configuration override value
 * @param int $tenantid
 * @param string $plugin null means core config table, anything else references plugin field in the config_plugins table
 * @param string $name
 * @param string $value
 */
function tenant_set_config($tenantid, $plugin, $name, $value) {
    global $DB;

    if ($config = $DB->get_record('config_tenants', array('tenantid'=>$tenantid, 'plugin'=>$plugin, 'name'=>$name), 'id')) {
        $DB->set_field('config_tenants', 'value', $value, array('id'=>$config->id));
    } else {
        $config = new stdClass();
        $config->tenantid = $tenantid;
        $config->plugin   = $plugin;
        $config->name     = $name;
        $config->value    = $value;
        $DB->insert_record('config_tenants', $config);
    }
}

/**
 * Set tenant configuration override value
 *
 * This are usually read via standard get_config() when access from the tenant site
 *
 * @param int $tenantid
 * @param string $plugin null means core config table, anything else references plugin field in the config_plugins table
 * @param string $name
 * @param mixed $default
 * @return mixed
 */
function tenant_get_config($tenantid, $plugin, $name, $default=null) {
    global $DB;

    if ($config = $DB->get_record('config_tenants', array('tenantid'=>$tenantid, 'plugin'=>$plugin, 'name'=>$name), 'id, value')) {
        return $config->value;
    } else {
        return $default;
    }
}

/**
 * Random stuff we could not do from elsewhere
 */
function tenant_install_hacks() {
    global $CFG, $TENANT, $SITE;

    if (empty($TENANT->id) or isset($CFG->tenantinstalled)) {
        return;
    }

    //NOTE: I hate blocks!!!! --skodak
    require_once("$CFG->libdir/blocklib.php");

    $page = new moodle_page();
    $page->set_context(context_tenant::instance($TENANT->id));
    $page->blocks->add_blocks(array(BLOCK_POS_LEFT => array('navigation', 'settings')), '*', null, true);
    $page->blocks->add_blocks(array(BLOCK_POS_LEFT => array('admin_bookmarks')), 'admin-*', null, null, 2);

    $page->blocks->add_blocks(array(BLOCK_POS_RIGHT => array('private_files', 'online_users'), 'content' => array('course_overview')), 'my-index', null, false);

    $blocknames = blocks_get_default_site_course_blocks();
    $page = new moodle_page();
    $page->set_course($SITE);
    $page->blocks->add_blocks($blocknames, 'site-index');

    tenant_set_config($TENANT->id, null, 'tenantinstalled', 1);
    $CFG->tenantinstalled = 1;
}
