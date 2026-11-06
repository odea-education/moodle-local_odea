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
use core_privacy\tests\provider_testcase;

/**
 * Tests for the privacy provider.
 *
 * @package    local_odea
 * @copyright  2026 Mouseion (https://mouseion.com)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_odea\privacy\provider
 */
final class provider_test extends provider_testcase {
    /**
     * The metadata collection declares exactly one external location link,
     * naming every field the connect flow, the web service token and the LTI
     * launch send to Odea: the admin's own contact details, this site's
     * identity, and the enrolled user data the token and LTI launches expose.
     */
    public function test_get_metadata_declares_external_location_link(): void {
        $collection = new collection('local_odea');
        $collection = provider::get_metadata($collection);

        $items = $collection->get_collection();
        $this->assertCount(1, $items);

        $item = reset($items);
        $this->assertSame('odea', $item->get_name());

        $fields = $item->get_privacy_fields();
        $this->assertArrayHasKey('contactname', $fields);
        $this->assertArrayHasKey('contactemail', $fields);
        $this->assertArrayHasKey('platformurl', $fields);
        $this->assertArrayHasKey('userfullname', $fields);
        $this->assertArrayHasKey('useremail', $fields);
        $this->assertArrayHasKey('userenrolments', $fields);
    }
}
