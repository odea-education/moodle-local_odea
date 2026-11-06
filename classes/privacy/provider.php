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

declare(strict_types=1);

namespace local_odea\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\plugin\provider as plugin_provider;
use core_privacy\local\request\userlist;
use context;

/**
 * Privacy provider.
 *
 * The plugin stores no personal data of its own — the connect page keeps
 * nothing in the database beyond the plugin's own settings (the backend
 * URL) and the site-wide service account, which is a synthetic account
 * created by the plugin, not a natural person. It does, however:
 *
 * - send the administrator's own name and work email, entered on the
 *   connect page, to the Odea backend as the registration contact, along
 *   with technical identifiers for this Moodle site;
 * - mint a permanent web service token that lets Odea read enrolled users'
 *   full name, email address and course enrolments through the dedicated
 *   service ({@see \local_odea\connector::SERVICE_FUNCTIONS}), for as long
 *   as the site stays connected; and
 * - register an LTI 1.3 tool with `sendname`/`sendemailaddr` on, so a
 *   user's full name and email are also sent to Odea on every LTI launch.
 *
 * All three are declared below as external location links. Since nothing
 * is stored in any context tied to a user *on this site*, every
 * request-handling method below is a no-op — the same shape Moodle core
 * uses for external-only data (e.g. block_tag_flickr).
 *
 * @package    local_odea
 * @copyright  2026 Mouseion (https://mouseion.com)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    core_userlist_provider,
    plugin_provider {
    /**
     * Describes what personal data this plugin sends to the Odea
     * backend, and why.
     *
     * @param collection $collection The collection to add metadata to.
     * @return collection The completed collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_external_location_link('odea', [
            'contactname' => 'privacy:metadata:odea:contactname',
            'contactemail' => 'privacy:metadata:odea:contactemail',
            'platformurl' => 'privacy:metadata:odea:platformurl',
            'userfullname' => 'privacy:metadata:odea:userfullname',
            'useremail' => 'privacy:metadata:odea:useremail',
            'userenrolments' => 'privacy:metadata:odea:userenrolments',
        ], 'privacy:metadata:odea');

        return $collection;
    }

    /**
     * Nothing is stored in any context tied to a user, so no context ever
     * holds data for them.
     *
     * @param int $userid The user to search.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        return new contextlist();
    }

    /**
     * Nothing is stored in any context, so no context ever holds users.
     *
     * @param userlist $userlist The userlist to add users to.
     */
    public static function get_users_in_context(userlist $userlist): void {
    }

    /**
     * Nothing is stored, so there is nothing to export.
     *
     * @param approved_contextlist $contextlist The approved contexts to export for.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
    }

    /**
     * Nothing is stored, so there is nothing to delete.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
    }

    /**
     * Nothing is stored, so there is nothing to delete.
     *
     * @param approved_contextlist $contextlist The approved contexts and user to delete for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
    }

    /**
     * Nothing is stored, so there is nothing to delete.
     *
     * @param approved_userlist $userlist The approved context and users to delete for.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
    }
}
