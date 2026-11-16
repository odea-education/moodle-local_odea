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
use curl;
use local_odea\event\site_connected;
use local_odea\event\site_connection_failed;
use moodle_exception;

/**
 * Tests for {@see connector::connect()}: the full flow including the report
 * to Odea, with the outbound HTTP call mocked via {@see curl::mock_response()}
 * so no network access happens. Covers the happy path, the failure path, and
 * the events each one fires, and the answer the connect page later shows.
 *
 * @package    local_odea
 * @copyright  2026 Mouseion (https://mouseion.com)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_odea\connector
 */
final class connect_test extends advanced_testcase {
    /**
     * A successful report triggers site_connected with the backend's status,
     * and connect() returns that status to the caller.
     */
    public function test_connect_success_triggers_site_connected_event(): void {
        $this->resetAfterTest();
        set_config('baseurl', 'https://backend.odea.example', 'local_odea');
        curl::mock_response(json_encode(['status' => 'active', 'message' => 'Welcome to Odea.']));

        $sink = $this->redirectEvents();
        $result = (new connector())->connect('Jane Admin', 'jane@school.example');
        $events = $sink->get_events();
        $sink->close();

        $this->assertTrue($result->is_active());
        $this->assertSame('Welcome to Odea.', $result->message);

        $connected = array_values(array_filter(
            $events,
            fn ($event): bool => $event instanceof site_connected
        ));
        $this->assertCount(1, $connected);
        $this->assertSame('active', $connected[0]->other['status']);
        $this->assertStringContainsString('active', $connected[0]->get_description());
    }

    /**
     * A malformed backend response triggers site_connection_failed and
     * propagates the moodle_exception to the caller — it never carries the
     * web service token.
     */
    public function test_connect_failure_triggers_site_connection_failed_event(): void {
        $this->resetAfterTest();
        set_config('baseurl', 'https://backend.odea.example', 'local_odea');
        curl::mock_response('not valid json');

        $sink = $this->redirectEvents();
        try {
            (new connector())->connect('Jane Admin', 'jane@school.example');
            $this->fail('Expected a moodle_exception.');
        } catch (moodle_exception $exception) {
            $this->assertStringNotContainsString('wstoken', $exception->getMessage());
        }
        $events = $sink->get_events();
        $sink->close();

        $failed = array_values(array_filter(
            $events,
            fn ($event): bool => $event instanceof site_connection_failed
        ));
        $this->assertCount(1, $failed);
        $this->assertStringNotContainsString('wstoken', $failed[0]->other['reason']);

        $connected = array_filter($events, fn ($event): bool => $event instanceof site_connected);
        $this->assertCount(0, $connected);

        // The unparsable body goes to the developer log, never into the exception
        // message or the event the caller reads.
        $this->assertDebuggingCalled();
    }

    /**
     * A backend response carrying HTML/script markup (e.g. an error page from a
     * misconfigured reverse proxy) never reaches the exception message or the
     * event's reason: both must be plain text with no markup, since index.php
     * displays the exception message and Moodle's event viewer displays
     * get_description() to admins.
     */
    public function test_connect_never_carries_markup_from_the_backend_body(): void {
        $this->resetAfterTest();
        set_config('baseurl', 'https://backend.odea.example', 'local_odea');
        curl::mock_response('<html><body><script>alert(1)</script>Server error</body></html>');

        $sink = $this->redirectEvents();
        try {
            (new connector())->connect('Jane Admin', 'jane@school.example');
            $this->fail('Expected a moodle_exception.');
        } catch (moodle_exception $exception) {
            $this->assertStringNotContainsString('<script', $exception->getMessage());
            $this->assertStringNotContainsString('<html', $exception->getMessage());
        }
        $events = $sink->get_events();
        $sink->close();

        $failed = array_values(array_filter(
            $events,
            fn ($event): bool => $event instanceof site_connection_failed
        ));
        $this->assertCount(1, $failed);
        $this->assertStringNotContainsString('<script', $failed[0]->other['reason']);
        $this->assertStringNotContainsString('<script', $failed[0]->get_description());

        $this->assertDebuggingCalled();
    }

    /**
     * The page reads the last answer on every visit: it must be what Odea said,
     * and nothing at all before Odea ever accepted a report.
     */
    public function test_connect_remembers_what_odea_answered(): void {
        $this->resetAfterTest();
        set_config('baseurl', 'https://backend.odea.example', 'local_odea');
        $connector = new connector();
        $this->assertNull($connector->last_answer());

        curl::mock_response(json_encode(['status' => 'pending', 'message' => 'Waiting for review.']));
        $connector->connect('Jane Admin', 'jane@school.example');
        $this->assertFalse($connector->last_answer()->is_active());

        curl::mock_response(json_encode(['status' => 'active', 'message' => 'Welcome to Odea.']));
        $connector->connect('Jane Admin', 'jane@school.example');
        $this->assertTrue($connector->last_answer()->is_active());
    }

    /**
     * A report Odea did not accept says nothing about the site's status, so the
     * last accepted answer stands — and a site Odea never accepted has none.
     */
    public function test_failed_report_leaves_the_last_answer_untouched(): void {
        $this->resetAfterTest();
        set_config('baseurl', 'https://backend.odea.example', 'local_odea');
        $connector = new connector();

        curl::mock_response('not valid json');
        try {
            $connector->connect('Jane Admin', 'jane@school.example');
            $this->fail('Expected a moodle_exception.');
        } catch (moodle_exception $exception) {
            $this->assertTrue($connector->is_connected());
            $this->assertNull($connector->last_answer());
        }
        $this->assertDebuggingCalled();

        curl::mock_response(json_encode(['status' => 'active', 'message' => '']));
        $connector->connect('Jane Admin', 'jane@school.example');

        curl::mock_response('not valid json');
        try {
            $connector->connect('Jane Admin', 'jane@school.example');
            $this->fail('Expected a moodle_exception.');
        } catch (moodle_exception $exception) {
            $this->assertTrue($connector->last_answer()->is_active());
        }
        $this->assertDebuggingCalled();
    }
}
