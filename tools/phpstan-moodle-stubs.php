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
 * PHPStan bootstrap stubs for Moodle core symbols the plugin touches.
 *
 * The plugin is analysed stand-alone (no Moodle checkout in this repo), so
 * every core function, class and constant it references is declared here
 * with the real signature shape. Types checked against MOODLE_500_STABLE.
 *
 * phpcs:ignoreFile
 *
 * @package    local_odea
 * @copyright  2026 Mouseion (https://mouseion.com)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace {
// Constants.
const MOODLE_INTERNAL = true;
const MATURITY_BETA = 100;
const PARAM_TEXT = 'text';
const PARAM_EMAIL = 'email';
const PARAM_URL = 'url';
const CONTEXT_SYSTEM = 10;
const CAP_ALLOW = 1;
const MUST_EXIST = 2;
const EXTERNAL_TOKEN_PERMANENT = 0;
const LTI_TOOL_STATE_CONFIGURED = 1;
define('LTI_VERSION_1P3', '1.3.0');
const LTI_JWK_KEYSET = 'JWK_KEYSET';
const LTI_COURSEVISIBLE_ACTIVITYCHOOSER = 2;
const LTI_LAUNCH_CONTAINER_WINDOW = 4;
const DEBUG_DEVELOPER = 32767;
const DEBUG_NORMAL = 15;

// Core functions.
function set_config(string $name, $value, ?string $plugin = null): bool {}
function get_config(string $plugin, ?string $name = null) {}
function get_string(string $identifier, string $component = '', $a = null): string {}
function generate_password(int $maxlen = 10): string {}
function user_create_user($user, bool $updatepassword = true, bool $triggerevent = true): int {}
function user_delete_user(stdClass $user): bool {}
function create_role(string $name, string $shortname, string $description, string $archetype = ''): int {}
function delete_role(int $roleid): bool {}
function set_role_contextlevels(int $roleid, array $contextlevels): void {}
function assign_capability(string $capability, int $permission, int $roleid, $contextid, bool $overwrite = false): bool {}
function role_assign(int $roleid, int $userid, $contextid, string $component = '', int $itemid = 0): int {}
function lti_add_type(stdClass $type, stdClass $config): int {}
function lti_delete_type(int $id): void {}
function required_param(string $parname, string $type) {}
function data_submitted() {}
function confirm_sesskey(?string $sesskey = null): bool {}
function sesskey(): string {}
function fullname($user, bool $override = false): string {}
function require_login($courseorid = null): void {}
function require_capability(string $capability, context $context): void {}
function admin_externalpage_setup(string $section): void {}
function s($var): string {}
function debugging(string $message, int $level = DEBUG_NORMAL, array $backtrace = []): bool {}

// Core classes.
class context {
    public int $id;
    public function mark_dirty(): void {}
}
class context_system extends context {
    public static function instance(): context_system {}
}
class moodle_exception extends Exception {
    public function __construct(string $errorcode, string $module = '', string $link = '', $a = null, $debuginfo = null) {}
}
class moodle_url {
    public function __construct(string $url, ?array $params = null) {}
    public function out(bool $escaped = true): string {}
}
class curl {
    public function __construct(array $settings = []) {}
    public function setHeader($header): void {}
    public function post(string $url, $params = '', array $options = []): string {}
    public function get(string $url, array $params = [], array $options = []): string {}
    /** @return array<string, mixed> */
    public function get_info(): array {}
}
class webservice {
    public function get_external_service_by_shortname(string $shortname, int $strictness = 0) {}
    public function get_external_service_by_id(int $serviceid, int $strictness = 0) {}
    public function add_external_service(stdClass $service): int {}
    public function update_external_service(stdClass $service): void {}
    public function add_external_function_to_service(string $functionname, int $serviceid): void {}
    public function service_function_exists(string $functionname, int $serviceid): bool {}
}
class html_writer {
    public static function tag(string $tagname, string $contents, array $attributes = []): string {}
    public static function start_tag(string $tagname, array $attributes = []): string {}
    public static function end_tag(string $tagname): string {}
    public static function empty_tag(string $tagname, array $attributes = []): string {}
    public static function start_div(string $classes = '', array $attributes = []): string {}
    public static function end_div(): string {}
    public static function div(string $content, string $classes = '', array $attributes = []): string {}
    public static function label(string $text, ?string $for, bool $colonize = true, array $attributes = []): string {}
}
class admin_settingpage {
    public function __construct(string $name, string $visiblename, string $reqcapability = 'moodle/site:config', bool $hidden = false) {}
    public function add($setting): bool {}
}
class admin_setting_configtext {
    public function __construct(string $name, string $visiblename, string $description, $defaultsetting, $paramtype = PARAM_TEXT, ?int $size = null) {}
}
class admin_externalpage {
    public function __construct(string $name, string $visiblename, $url, $reqcapability = 'moodle/site:config', bool $hidden = false) {}
}

}

namespace core_privacy\local\metadata {

/** Privacy API marker for plugins that store no personal data. */
interface null_provider {
    /**
     * The lang string key explaining why no data is stored.
     *
     * @return string
     */
    public static function get_reason(): string;
}

/** Privacy API marker for plugins that declare what personal data they hold. */
interface provider {
    /**
     * Describes the personal data this plugin stores or transmits.
     *
     * @param collection $collection The collection to add metadata to.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection;
}

/** Collects the metadata a privacy provider declares. */
class collection {
    public function __construct(string $component) {}

    /**
     * Declares data sent to an external location.
     *
     * @param string $name The location name.
     * @param string[] $privacyfields Field name to lang string key.
     * @param string $summary Lang string key summarising the location.
     * @return $this
     */
    public function add_external_location_link(string $name, array $privacyfields, string $summary): self {}
}

}

namespace core_privacy\local\request {

/** A list of approved contexts for one user's data request. */
class approved_contextlist {
}

/** A list of contexts holding data for one user. */
class contextlist {
    public function add_from_sql(string $sql, array $params): self {}
}

/** A list of users within a context, being built up by a provider. */
class userlist {
}

/** A list of users within a context, approved for a data request. */
class approved_userlist {
}

/** Marker for plugins whose request provider also handles multi-user delete. */
interface core_userlist_provider {
    /**
     * @param userlist $userlist The userlist to add users to.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void;

    /**
     * @param approved_userlist $userlist The approved context and users to delete for.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void;
}

}

namespace core_privacy\local\request\plugin {

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;

/** Marker for plugins that declare and handle export/delete requests. */
interface provider {
    /**
     * @param int $userid The user to search.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist;

    /**
     * @param approved_contextlist $contextlist The approved contexts to export for.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void;

    /**
     * @param \context $context The specific context to delete data for.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void;

    /**
     * @param approved_contextlist $contextlist The approved contexts and user to delete for.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void;
}

}

namespace core_external {

/** Token utilities (lib/external/classes/util.php). */
class util {
    public static function generate_token(int $tokentype, \stdClass $service, int $userid, \context $context, int $validuntil = 0, string $iprestriction = '', string $name = ''): string {}
}

}

namespace core\output {

class notification {
    public const NOTIFY_SUCCESS = 'success';
    public const NOTIFY_INFO = 'info';
    public const NOTIFY_ERROR = 'error';
}
}

namespace core\event {

/**
 * Base class every Moodle event extends. Only the members the plugin's
 * event classes actually use are stubbed here.
 */
abstract class base {
    public const LEVEL_OTHER = 0;
    public const LEVEL_PARTICIPATING = 1;
    public const LEVEL_TEACHING = 2;

    /** @var array<string, mixed> */
    protected array $data = [];

    /** @var int */
    public int $userid = 0;

    /** @var array<string, mixed> */
    public array $other = [];

    /**
     * @param mixed[] $eventdata Event data, at minimum a context.
     * @return static
     */
    public static function create(array $eventdata): static {}

    protected function init(): void {}

    abstract public static function get_name(): string;

    public function get_description(): string {}

    public function get_url(): \moodle_url {}

    public function trigger(): void {}
}

}
