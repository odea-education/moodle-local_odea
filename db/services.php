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
 * The external service Odea reads this site through.
 *
 * Declared here so Moodle keeps the service, and the token minted for it,
 * when the plugin is upgraded: Moodle deletes any service of this component
 * that this file does not list. The service is installed disabled, and the
 * connect flow enables it.
 *
 * @package    local_odea
 * @copyright  2026 Mouseion (https://mouseion.com)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$services = [
    // Moodle matches services by this name. Sites connected with 0.2.2 or earlier
    // carry a service called "Odea", so renaming it would delete theirs.
    'Odea' => [
        // The read functions Odea's Moodle client calls, and nothing else.
        'functions' => [
            'core_webservice_get_site_info',
            'core_user_get_users_by_field',
            'core_enrol_get_users_courses',
            'core_course_get_courses_by_field',
            'core_course_get_contents',
            'core_enrol_get_enrolled_users',
        ],
        'shortname' => 'local_odea',
        'enabled' => 0,
        'restrictedusers' => 0,
        'downloadfiles' => 1,
        'uploadfiles' => 0,
    ],
];
