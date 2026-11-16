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

namespace local_odea;

use context_system;
use core_external\util as external_util;
use curl;
use local_odea\event\site_connected;
use local_odea\event\site_connection_failed;
use moodle_exception;
use stdClass;

/**
 * Provisions everything Odea needs on this Moodle site and reports the
 * connection to the Odea backend.
 *
 * One call to {@see connector::connect()} performs, in order: enable web
 * services + REST, enable the dedicated external service holding exactly the
 * read functions Odea calls, create the service account and its role,
 * mint a permanent token, register Odea as an Active LTI 1.3 tool, and
 * report the result to the Odea backend. Every step is idempotent,
 * so Reconnect is safe.
 *
 * @package    local_odea
 * @copyright  2026 Mouseion (https://mouseion.com)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class connector {
    /** @var string Shortname of the dedicated external service. */
    private const SERVICE_SHORTNAME = 'local_odea';

    /** @var string Username of the dedicated service account. */
    private const SERVICE_USERNAME = 'odea-service';

    /** @var string Shortname of the role granting the service account read access. */
    private const ROLE_SHORTNAME = 'odeaws';

    /** @var string Plugin config key holding the status Odea last answered with. */
    private const STATUS_CONFIG = 'status';

    /** @var int Seconds to wait for the Odea backend to answer the install report. */
    private const REPORT_TIMEOUT_SECONDS = 10;

    /** @var int Seconds to wait for the TCP connection to the Odea backend. */
    private const REPORT_CONNECT_TIMEOUT_SECONDS = 5;

    /**
     * Capabilities the service account needs for the service's six functions
     * (db/services.php), allowed at system context through the plugin's role.
     * All are read-only. tests/service_account_capabilities_test.php calls the
     * six functions as this account and fails if one is missing.
     *
     * @var string[]
     */
    private const ROLE_CAPABILITIES = [
        // REST is the only protocol this plugin enables.
        'webservice/rest:use',
        // Lets a service account that is never enrolled pass require_login()
        // for every course (lib/accesslib.php::is_viewing()) — without it,
        // every one of the six calls below is rejected outright.
        'moodle/course:view',
        // Includes courses Odea has not published yet in course/get_contents
        // and course/get_courses_by_field (both otherwise skip them).
        'moodle/course:viewhiddencourses',
        // Hard-required by enrol_get_enrolled_users; it throws
        // required_capability_exception without it.
        'moodle/course:viewparticipants',
        // Exposes an enrolled user's email in enrol_get_enrolled_users when
        // their own maildisplay setting would otherwise hide it — the Odea
        // backend requires an email for every user.
        'moodle/course:useremail',
        // Hard-required by user_get_user_details (called with no course, from
        // core_user_get_users_by_field): without it every user is skipped.
        'moodle/user:viewdetails',
        // Without it, enrol_get_enrolled_users returns an empty array for any
        // course using separate groups mode, since the service account is a
        // member of none.
        'moodle/site:accessallgroups',
    ];

    /**
     * Runs the whole connection flow.
     *
     * @param string $contactname Name of the administrator running the install.
     * @param string $contactemail Work email of that administrator.
     * @return connect_result What the Odea backend answered.
     */
    public function connect(string $contactname, string $contactemail): connect_result {
        $site = $this->provision_site();

        try {
            $result = $this->report_to_odea($site, $contactname, $contactemail);
        } catch (moodle_exception $exception) {
            site_connection_failed::create([
                'context' => context_system::instance(),
                'other' => ['reason' => $exception->getMessage()],
            ])->trigger();
            throw $exception;
        }

        set_config(self::STATUS_CONFIG, $result->status, 'local_odea');
        site_connected::create([
            'context' => context_system::instance(),
            'other' => ['status' => $result->status],
        ])->trigger();
        return $result;
    }

    /**
     * The status Odea answered with the last time it accepted this site's report.
     *
     * @return string "active", "pending", or "" when Odea never accepted a report.
     */
    public function last_status(): string {
        return (string) get_config('local_odea', self::STATUS_CONFIG);
    }

    /**
     * Every local provisioning step, without the report to Odea:
     * web services + REST, the dedicated service and account, the permanent
     * token, and the Active LTI tool. Idempotent — running it again returns
     * the same credentials and creates nothing new.
     *
     * @return provisioned_site What the install report will carry.
     */
    public function provision_site(): provisioned_site {
        $this->enable_webservices();
        $service = $this->enable_service();
        $serviceuser = $this->ensure_service_user();
        $this->ensure_role($serviceuser);
        $token = $this->ensure_token($service, $serviceuser);
        $toolid = $this->ensure_lti_tool();

        return new provisioned_site($token, $this->lti_client_id($toolid), (string) $toolid);
    }

    /**
     * Whether the connect flow ran on this site before, whether or not Odea
     * accepted its report.
     *
     * @return bool
     */
    public function has_token(): bool {
        global $DB;
        $serviceid = $DB->get_field(
            'external_services',
            'id',
            ['shortname' => self::SERVICE_SHORTNAME],
            MUST_EXIST
        );
        return $DB->record_exists('external_tokens', ['externalserviceid' => $serviceid]);
    }

    /**
     * Host of the Odea backend, with its port when the URL names one. Moodle
     * matches a tool's domain against its launch URL up to the first slash.
     *
     * @return string "" when the configured backend URL has no host.
     */
    public function tool_domain(): string {
        $base = $this->odea_base_url();
        $port = parse_url($base, PHP_URL_PORT);
        return (string) parse_url($base, PHP_URL_HOST) . ($port ? ':' . $port : '');
    }

    /**
     * Enables web services and the REST protocol site-wide.
     */
    private function enable_webservices(): void {
        global $CFG;
        set_config('enablewebservices', 1);
        $protocols = empty($CFG->webserviceprotocols) ? [] : explode(',', $CFG->webserviceprotocols);
        if (!in_array('rest', $protocols, true)) {
            $protocols[] = 'rest';
            set_config('webserviceprotocols', implode(',', $protocols));
        }
    }

    /**
     * Enables the dedicated external service. Moodle installs it, disabled,
     * from db/services.php.
     *
     * @return stdClass The external_services record.
     */
    private function enable_service(): stdClass {
        global $CFG;
        require_once($CFG->dirroot . '/webservice/lib.php');
        $manager = new \webservice();

        $service = $manager->get_external_service_by_shortname(self::SERVICE_SHORTNAME, MUST_EXIST);
        if (!$service->enabled) {
            $service->enabled = 1;
            $manager->update_external_service($service);
        }
        return $service;
    }

    /**
     * Creates the dedicated service account if it does not exist yet.
     *
     * @return stdClass The user record.
     */
    private function ensure_service_user(): stdClass {
        global $CFG, $DB;
        $existing = $DB->get_record('user', [
            'username' => self::SERVICE_USERNAME,
            'mnethostid' => $CFG->mnet_localhost_id,
            'deleted' => 0,
        ]);
        if ($existing) {
            return $existing;
        }

        require_once($CFG->dirroot . '/user/lib.php');
        $user = new stdClass();
        $user->username = self::SERVICE_USERNAME;
        $user->firstname = get_string('pluginname', 'local_odea');
        $user->lastname = get_string('serviceuserlastname', 'local_odea');
        $user->email = 'noreply-odea-service@' . parse_url($CFG->wwwroot, PHP_URL_HOST);
        $user->password = generate_password(24);
        $user->auth = 'manual';
        $user->confirmed = 1;
        $user->mnethostid = $CFG->mnet_localhost_id;
        // Moodle 5.3 deprecated user_create_user(); 4.5 to 5.2 have no replacement.
        if ((int) $CFG->branch >= 503) {
            $userid = \core\user::create_user($user, true, false);
        } else {
            $userid = user_create_user($user, true, false);
        }
        return $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
    }

    /**
     * Creates the role carrying the required capabilities and assigns it to
     * the service account at system context.
     *
     * @param stdClass $serviceuser The service account.
     */
    private function ensure_role(stdClass $serviceuser): void {
        global $DB;
        $context = context_system::instance();

        $roleid = (int) $DB->get_field('role', 'id', ['shortname' => self::ROLE_SHORTNAME]);
        if (!$roleid) {
            $roleid = create_role(
                get_string('rolename', 'local_odea'),
                self::ROLE_SHORTNAME,
                get_string('roledescription', 'local_odea')
            );
            set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);
        }
        foreach (self::ROLE_CAPABILITIES as $capability) {
            assign_capability($capability, CAP_ALLOW, $roleid, $context->id, true);
        }
        $context->mark_dirty();
        role_assign($roleid, (int) $serviceuser->id, $context->id);
    }

    /**
     * Mints (or reuses) the permanent web service token for the service account.
     *
     * @param stdClass $service The external service.
     * @param stdClass $serviceuser The service account.
     * @return string The token.
     */
    private function ensure_token(stdClass $service, stdClass $serviceuser): string {
        global $DB;
        $existing = $DB->get_record('external_tokens', [
            'externalserviceid' => $service->id,
            'userid' => $serviceuser->id,
        ]);
        if ($existing) {
            return $existing->token;
        }
        return external_util::generate_token(
            EXTERNAL_TOKEN_PERMANENT,
            $service,
            (int) $serviceuser->id,
            context_system::instance(),
            0,
            '',
            'Odea'
        );
    }

    /**
     * Registers Odea as a site-wide, Active LTI 1.3 tool. Reuses the
     * existing tool if one already points at this Odea backend.
     *
     * @return int The lti_types id (the deployment id in launches).
     */
    private function ensure_lti_tool(): int {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/lti/locallib.php');

        $base = $this->odea_base_url();
        $launchurl = $base . '/lti/launch';
        $tooldomain = $this->tool_domain();

        // The baseurl column is text and Moodle DML forbids equality on text —
        // tooldomain (varchar) identifies the same tool.
        $existing = $DB->get_record('lti_types', ['tooldomain' => $tooldomain], 'id');
        if ($existing) {
            return (int) $existing->id;
        }

        $type = new stdClass();
        // CONFIGURED is what the admin UI calls Active: no pending review step.
        $type->state = LTI_TOOL_STATE_CONFIGURED;

        $config = new stdClass();
        $config->lti_typename = get_string('toolname', 'local_odea');
        $config->lti_description = get_string('tooldescription', 'local_odea');
        $config->lti_toolurl = $launchurl;
        $config->lti_tooldomain = $tooldomain;
        $config->lti_ltiversion = LTI_VERSION_1P3;
        $config->lti_keytype = LTI_JWK_KEYSET;
        $config->lti_publickeyset = $base . '/lti/jwks';
        $config->lti_initiatelogin = $base . '/lti/login';
        $config->lti_redirectionuris = $launchurl;
        $config->lti_coursevisible = LTI_COURSEVISIBLE_ACTIVITYCHOOSER;
        $config->lti_launchcontainer = LTI_LAUNCH_CONTAINER_WINDOW;
        $config->lti_contentitem = 0;
        $config->lti_sendname = 1;
        $config->lti_sendemailaddr = 1;
        $config->lti_acceptgrades = 0;

        return lti_add_type($type, $config);
    }

    /**
     * The client id Moodle stored for the tool (auto-generated by
     * lti_prepare_type_for_save when the tool was created).
     *
     * @param int $toolid The lti_types id.
     * @return string
     */
    private function lti_client_id(int $toolid): string {
        global $DB;
        return (string) $DB->get_field('lti_types', 'clientid', ['id' => $toolid], MUST_EXIST);
    }

    /**
     * POSTs the install report to the Odea backend and interprets the
     * JSON answer.
     *
     * @param provisioned_site $site The credentials provisioning produced.
     * @param string $contactname Name of the administrator running the install.
     * @param string $contactemail Work email of that administrator.
     * @return connect_result
     */
    private function report_to_odea(
        provisioned_site $site,
        string $contactname,
        string $contactemail
    ): connect_result {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $endpoint = $this->odea_base_url() . '/lti/plugin/install';
        $curl = new curl();
        $curl->setHeader('Content-Type: application/json');
        $response = $curl->post($endpoint, json_encode([
            // The LTI issuer.
            'platform_url' => $CFG->wwwroot,
            'client_id' => $site->clientid,
            'deployment_id' => $site->toolid,
            'wstoken' => $site->wstoken,
            'contact_name' => $contactname,
            'contact_email' => $contactemail,
            'plugin_version' => (string) get_config('local_odea', 'version'),
        ]), [
            'CURLOPT_TIMEOUT' => self::REPORT_TIMEOUT_SECONDS,
            'CURLOPT_CONNECTTIMEOUT' => self::REPORT_CONNECT_TIMEOUT_SECONDS,
        ]);
        $httpcode = (int) $curl->get_info()['http_code'];

        if ($httpcode === 0) {
            throw new moodle_exception('errorunreachable', 'local_odea', '', $endpoint);
        }

        $decoded = json_decode((string) $response, true);
        if (!is_array($decoded) || !isset($decoded['status'])) {
            // The response body is the backend's own output, untrusted markup that must
            // never reach a page: keep it out of the exception message (which the caller
            // displays) and only ever put it in the developer log.
            debugging(
                'Odea install report at ' . $endpoint . ' returned an unparsable response '
                    . '(HTTP ' . $httpcode . '): ' . substr((string) $response, 0, 1000),
                DEBUG_DEVELOPER
            );
            throw new moodle_exception('errorgeneric', 'local_odea', '', 'HTTP ' . $httpcode);
        }
        if ($httpcode >= 400) {
            throw new moodle_exception(
                'errorgeneric',
                'local_odea',
                '',
                (string) ($decoded['message'] ?? ('HTTP ' . $httpcode))
            );
        }
        return new connect_result(
            (string) $decoded['status'],
            (string) ($decoded['message'] ?? '')
        );
    }

    /**
     * The configured Odea backend origin, without a trailing slash.
     *
     * @return string
     */
    private function odea_base_url(): string {
        $configured = (string) get_config('local_odea', 'baseurl');
        return rtrim($configured, '/');
    }
}
