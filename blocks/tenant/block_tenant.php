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
 * Tenant SSO block.
 *
 * This block can be displayed only on the main site, it allows users
 * that have linked account on the tenant site to enter the tenant site.
 *
 * @package    block
 * @subpackage tenant
 * @copyright  2011 Petr Skoda {@ling http://skodak.org/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_tenant extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_tenant');
    }

    function applicable_formats() {
        return array('site' => true);
    }

    function get_content () {
        global $USER, $CFG, $DB, $TENANT;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content->footer  = '';
        $this->content->text    = '';

        if (!is_enabled_auth('tenant')) {
            // we need the tenant auth plugin here
            return $this->content;
        }

        if ($TENANT->id or !isloggedin() or isguestuser()) {
            // block not applicable
            return $this->content;
        }

        $params = array('me'=>$USER->id);
        $sql = "SELECT t.id, t.shortname, tu.id AS userid
                  FROM {tenant} t
                  JOIN {auth_tenant_users} atu ON (atu.tenantid = t.id AND atu.userid = :me)
                  JOIN {user} tu ON (tu.id = atu.tenantuserid AND tu.suspended = 0 AND tu.deleted = 0 AND tu.auth = 'tenant')
                 WHERE t.status = 0";
        if (!$tenants = $DB->get_records_sql($sql, $params)) {
            return $this->content;
        }

        // Finally we have a list of tenant sites that the current user is allowed to access via linked account

        $this->content->text .= "<ul class='list'>\n";
        foreach ($tenants as $tenant) {
            $this->content->text .= '<li class="listentry">';
            $url = new moodle_url('/auth/tenant/jump.php', array('sesskey'=>sesskey(), 'id'=>$tenant->userid));
            $this->content->text .= html_writer::tag('a',format_string($tenant->shortname), array('href'=>$url, 'target'=>'_blank')); // blank target is ok in html5
            $this->content->text .= "</li>\n";
        }
        $this->content->text .= '</ul><div class="clearer"><!-- --></div>';

        return $this->content;
    }
}

