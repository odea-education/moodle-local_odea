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

use advanced_testcase;

/**
 * Tests for the connector's local provisioning steps.
 *
 * These tests exercise provision_site() — the public seam that runs every
 * local step without the HTTP report to Odea — so they cover everything the
 * plugin changes on the Moodle site itself. The outbound POST, mocked, and
 * the events connect() fires are covered separately in connect_test.php; the
 * server side of the install-report contract is tested in the Odea backend.
 *
 * @package    local_odea
 * @copyright  2026 Mouseion (https://mouseion.com)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_odea\connector
 */
final class connector_test extends advanced_testcase {
    /**
     * Runs the full local provisioning once.
     *
     * @return provisioned_site
     */
    private function provision(): provisioned_site {
        global $CFG;
        $CFG->forced_plugin_settings['local_odea'] = ['baseurl' => 'https://backend.odea.example'];
        return (new connector())->provision_site();
    }

    /**
     * The newest value the config changes report holds for a setting. Moodle's
     * own install already logged one row per setting.
     *
     * @param string $name The setting.
     * @return string
     */
    private function last_config_change(string $name): string {
        global $DB;
        $rows = $DB->get_records('config_log', ['name' => $name], 'id DESC', 'id, value', 0, 1);
        return reset($rows)->value;
    }

    /**
     * Web services and the REST protocol are enabled site-wide.
     *
     * Verifies ODEA-MOODLE-9.
     */
    public function test_odea_moodle_9_enables_webservices_and_rest(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->provision();

        $this->assertNotEmpty($CFG->enablewebservices);
        $this->assertContains('rest', explode(',', $CFG->webserviceprotocols));
    }

    /**
     * The dedicated service exists, enabled, with exactly the read functions
     * the Odea backend calls.
     *
     * Verifies ODEA-MOODLE-10.
     */
    public function test_odea_moodle_10_creates_service_with_read_functions(): void {
        global $DB;
        $this->resetAfterTest();
        $this->provision();

        $service = $DB->get_record('external_services', ['shortname' => 'local_odea']);
        $this->assertNotFalse($service);
        $this->assertEquals(1, (int) $service->enabled);
        $this->assertEquals(0, (int) $service->uploadfiles);

        $functions = $DB->get_fieldset_select(
            'external_services_functions',
            'functionname',
            'externalserviceid = ?',
            [$service->id]
        );
        sort($functions);
        $this->assertSame([
            'core_course_get_contents',
            'core_course_get_courses_by_field',
            'core_enrol_get_enrolled_users',
            'core_enrol_get_users_courses',
            'core_user_get_users_by_field',
            'core_webservice_get_site_info',
        ], $functions);
    }

    /**
     * A permanent token exists for the dedicated service account, and the
     * bundle carries it.
     *
     * Verifies ODEA-MOODLE-12, ODEA-MOODLE-14.
     */
    public function test_odea_moodle_14_mints_permanent_token_for_service_account(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $site = $this->provision();

        $serviceuser = $DB->get_record('user', [
            'username' => 'odea-service',
            'mnethostid' => $CFG->mnet_localhost_id,
        ]);
        $this->assertNotFalse($serviceuser);

        $token = $DB->get_record('external_tokens', ['userid' => $serviceuser->id]);
        $this->assertNotFalse($token);
        $this->assertEquals(0, (int) $token->validuntil);
        $this->assertSame($token->token, $site->wstoken);
    }

    /**
     * The LTI tool is created Active (no pending review), LTI 1.3, pointing
     * at the configured backend — domain matching the launch URL host, port
     * included, per Moodle's domain/target_link_uri check.
     *
     * Verifies ODEA-MOODLE-15, ODEA-MOODLE-16.
     */
    public function test_odea_moodle_15_creates_active_lti13_tool(): void {
        global $DB;
        $this->resetAfterTest();
        $site = $this->provision();

        $tool = $DB->get_record('lti_types', ['id' => (int) $site->toolid]);
        $this->assertNotFalse($tool);
        $this->assertEquals(LTI_TOOL_STATE_CONFIGURED, (int) $tool->state);
        $this->assertSame(LTI_VERSION_1P3, $tool->ltiversion);
        $this->assertSame('backend.odea.example', $tool->tooldomain);
        $this->assertSame('https://backend.odea.example/lti/launch', $tool->baseurl);
        $this->assertSame($tool->clientid, $site->clientid);
        $this->assertEquals(LTI_COURSEVISIBLE_ACTIVITYCHOOSER, (int) $tool->coursevisible);
    }

    /**
     * Provisioning twice changes nothing: same token, same tool, no
     * duplicate service/account rows. This is what makes Reconnect safe.
     *
     * Verifies ODEA-MOODLE-18.
     */
    public function test_odea_moodle_18_provisioning_is_idempotent(): void {
        global $DB;
        $this->resetAfterTest();
        $first = $this->provision();
        $second = $this->provision();

        $this->assertSame($first->wstoken, $second->wstoken);
        $this->assertSame($first->clientid, $second->clientid);
        $this->assertSame($first->toolid, $second->toolid);
        $this->assertEquals(1, $DB->count_records('external_services', ['shortname' => 'local_odea']));
        $this->assertEquals(1, $DB->count_records('user', ['username' => 'odea-service']));
        $this->assertEquals(1, $DB->count_records('lti_types'));
    }

    /**
     * An explicit port in the backend URL survives into the tool domain —
     * Moodle's LTI_URL_DOMAIN_REGEX compares everything up to the first
     * slash, so dropping it would break local installs.
     *
     * Verifies ODEA-MOODLE-16.
     */
    public function test_odea_moodle_16_tool_domain_keeps_an_explicit_port(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $CFG->forced_plugin_settings['local_odea'] = ['baseurl' => 'http://localhost:4000'];
        $site = (new connector())->provision_site();

        $tool = $DB->get_record('lti_types', ['id' => (int) $site->toolid]);
        $this->assertSame('localhost:4000', $tool->tooldomain);
    }

    /**
     * A plugin upgrade keeps the service and its token. Moodle deletes every
     * service of a component that db/services.php does not declare, tokens
     * included, each time the plugin is upgraded — and Odea refuses the fresh
     * token a Reconnect would mint, because it changes the registration.
     *
     * Verifies ODEA-MOODLE-19.
     */
    public function test_odea_moodle_19_plugin_upgrade_keeps_service_and_token(): void {
        global $CFG;
        require_once($CFG->libdir . '/upgradelib.php');
        $this->resetAfterTest();
        $site = $this->provision();

        external_update_descriptions('local_odea');

        $this->assertTrue((new connector())->has_token());
        $this->assertSame($site->wstoken, $this->provision()->wstoken);
    }

    /**
     * Each step that changes the site leaves the row an administrator would
     * get from doing it by hand: the two settings in the config changes
     * report, and Moodle's own events for the service, the account, the role
     * and the token.
     *
     * Verifies ODEA-MOODLE-36.
     */
    public function test_odea_moodle_36_provisioning_logs_what_it_changes(): void {
        $this->resetAfterTest();
        set_config('enablewebservices', 0);
        set_config('webserviceprotocols', '');

        $sink = $this->redirectEvents();
        $this->provision();
        $fired = [];
        foreach ($sink->get_events() as $event) {
            $fired[] = $event->eventname;
        }
        $sink->close();

        $this->assertContains('\\core\\event\\webservice_service_updated', $fired);
        $this->assertContains('\\core\\event\\user_created', $fired);
        $this->assertContains('\\core\\event\\role_created', $fired);
        $this->assertContains('\\core\\event\\role_assigned', $fired);
        $this->assertContains('\\core\\event\\webservice_token_created', $fired);
        $this->assertSame('1', $this->last_config_change('enablewebservices'));
        $this->assertSame('rest', $this->last_config_change('webserviceprotocols'));
    }

    /**
     * A second run changes nothing, so it logs nothing: a Reconnect must not
     * fill the logs with accounts, tokens and settings that were already there.
     *
     * Verifies ODEA-MOODLE-37.
     */
    public function test_odea_moodle_37_second_run_logs_nothing_new(): void {
        global $DB;
        $this->resetAfterTest();
        $this->provision();
        $configrows = $DB->count_records('config_log');

        $sink = $this->redirectEvents();
        $this->provision();
        $fired = [];
        foreach ($sink->get_events() as $event) {
            $fired[] = $event->eventname;
        }
        $sink->close();

        $this->assertNotContains('\\core\\event\\webservice_service_updated', $fired);
        $this->assertNotContains('\\core\\event\\user_created', $fired);
        $this->assertNotContains('\\core\\event\\role_created', $fired);
        $this->assertNotContains('\\core\\event\\webservice_token_created', $fired);
        $this->assertSame($configrows, $DB->count_records('config_log'));
    }
}
