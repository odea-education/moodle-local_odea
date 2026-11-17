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
 * Tests for the uninstall hook: everything provision_site() creates must be
 * gone afterwards.
 *
 * @package    local_odea
 * @copyright  2026 Mouseion (https://mouseion.com)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::xmldb_local_odea_uninstall
 */
final class uninstall_test extends advanced_testcase {
    /**
     * The token, service, service account, role and LTI tool are all
     * removed by the uninstall hook.
     */
    public function test_uninstall_removes_everything_provisioned(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        set_config('baseurl', 'https://backend.odea.example', 'local_odea');
        (new connector())->provision_site();

        require_once($CFG->dirroot . '/local/odea/db/uninstall.php');
        $sink = $this->redirectEvents();
        $this->assertTrue(xmldb_local_odea_uninstall());
        $fired = [];
        foreach ($sink->get_events() as $event) {
            $fired[] = $event->eventname;
        }
        $sink->close();

        $this->assertContains('\\core\\event\\webservice_service_deleted', $fired);
        $this->assertContains('\\core\\event\\user_deleted', $fired);
        $this->assertContains('\\core\\event\\role_deleted', $fired);

        $this->assertFalse($DB->record_exists('external_services', ['shortname' => 'local_odea']));
        $this->assertFalse($DB->record_exists('user', [
            'username' => 'odea-service',
            'mnethostid' => $CFG->mnet_localhost_id,
            'deleted' => 0,
        ]));
        $this->assertFalse($DB->record_exists('role', ['shortname' => 'odeaws']));
        $this->assertEquals(0, $DB->count_records('lti_types'));
    }
}
