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

/**
 * What local provisioning produced on this site: the credentials the
 * install report carries to Odea.
 *
 * @package    local_odea
 * @copyright  2026 Mouseion (https://mouseion.com)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provisioned_site {
    /**
     * Constructor.
     *
     * @param string $wstoken The permanent web service token minted for Odea.
     * @param string $clientid Client id Moodle assigned to the Odea LTI tool.
     * @param string $toolid The lti_types id (deployment id in launches).
     */
    public function __construct(
        /** @var string The permanent web service token minted for Odea. */
        public readonly string $wstoken,
        /** @var string Client id Moodle assigned to the Odea LTI tool. */
        public readonly string $clientid,
        /** @var string The lti_types id (deployment id in launches). */
        public readonly string $toolid
    ) {
    }
}
