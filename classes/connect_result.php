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
 * The Odea backend's answer to an install report.
 *
 * @package    local_odea
 * @copyright  2026 Mouseion (https://mouseion.com)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class connect_result {
    /**
     * Constructor.
     *
     * @param string $status "active" when the tenant is live, "pending" when it awaits review.
     * @param string $message Human-readable detail from the backend.
     */
    public function __construct(
        /** @var string "active" when the tenant is live, "pending" when it awaits review. */
        public readonly string $status,
        /** @var string Human-readable detail from the backend. */
        public readonly string $message
    ) {
    }
}
