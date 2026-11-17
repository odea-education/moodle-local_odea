<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Uninstall hook: removes everything the connect flow provisioned.
 *
 * @package    local_odea
 * @copyright  2026 Mouseion (https://mouseion.com)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

/**
 * Removes the token, service, service account, role and LTI tool.
 *
 * @return bool
 */
function xmldb_local_odea_uninstall(): bool {
    global $CFG, $DB;

    // The service, with its tokens.
    $serviceid = $DB->get_field('external_services', 'id', ['shortname' => 'local_odea']);
    if ($serviceid) {
        require_once($CFG->dirroot . '/webservice/lib.php');
        (new \webservice())->delete_service($serviceid);
        \core\event\webservice_service_deleted::create(['objectid' => $serviceid])->trigger();
    }

    // The service account.
    $serviceuser = $DB->get_record('user', [
        'username' => 'odea-service',
        'mnethostid' => $CFG->mnet_localhost_id,
        'deleted' => 0,
    ]);
    if ($serviceuser) {
        require_once($CFG->dirroot . '/user/lib.php');
        user_delete_user($serviceuser);
    }

    // The role.
    $roleid = (int) $DB->get_field('role', 'id', ['shortname' => 'odeaws']);
    if ($roleid) {
        delete_role($roleid);
    }

    // The LTI tool. An empty domain would match tools that are not Odea's.
    require_once($CFG->dirroot . '/mod/lti/locallib.php');
    $tooldomain = (new \local_odea\connector())->tool_domain();
    if ($tooldomain !== '') {
        $tools = $DB->get_records('lti_types', ['tooldomain' => $tooldomain], '', 'id');
        foreach ($tools as $tool) {
            lti_delete_type((int) $tool->id);
        }
    }

    return true;
}
