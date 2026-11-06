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

    // Tokens and the service.
    $serviceid = $DB->get_field('external_services', 'id', ['shortname' => 'local_odea']);
    if ($serviceid) {
        $DB->delete_records('external_tokens', ['externalserviceid' => $serviceid]);
        $DB->delete_records('external_services_functions', ['externalserviceid' => $serviceid]);
        $DB->delete_records('external_services_users', ['externalserviceid' => $serviceid]);
        $DB->delete_records('external_services', ['id' => $serviceid]);
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

    // The LTI tool(s) pointing at Odea.
    require_once($CFG->dirroot . '/mod/lti/locallib.php');
    $baseurl = rtrim((string) get_config('local_odea', 'baseurl'), '/');
    if ($baseurl !== '') {
        // Match on tooldomain, not baseurl: baseurl is a text column and Moodle DML
        // forbids equality comparisons on text.
        $port = parse_url($baseurl, PHP_URL_PORT);
        $tooldomain = (string) parse_url($baseurl, PHP_URL_HOST) . ($port === null ? '' : ':' . $port);
        $tools = $DB->get_records('lti_types', ['tooldomain' => $tooldomain], '', 'id');
        foreach ($tools as $tool) {
            lti_delete_type((int) $tool->id);
        }
    }

    return true;
}
