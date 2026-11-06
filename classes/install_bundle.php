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
 * The typed payload POSTed to the Odea backend after provisioning.
 *
 * @package    local_odea
 * @copyright  2026 Mouseion (https://mouseion.com)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class install_bundle {
    /**
     * Constructor.
     *
     * @param string $platformurl This Moodle site's root URL ($CFG->wwwroot) — the LTI issuer.
     * @param string $clientid Client id Moodle assigned to the Odea tool.
     * @param string $deploymentid The lti_types id, sent as deployment_id in launches.
     * @param string $wstoken The permanent web service token minted for Odea.
     * @param string $contactname Administrator name for the registration contact.
     * @param string $contactemail Administrator work email for the registration contact.
     */
    public function __construct(
        /** @var string This Moodle site's root URL — the LTI issuer. */
        private readonly string $platformurl,
        /** @var string Client id Moodle assigned to the Odea tool. */
        private readonly string $clientid,
        /** @var string The lti_types id, sent as deployment_id in launches. */
        private readonly string $deploymentid,
        /** @var string The permanent web service token minted for Odea. */
        private readonly string $wstoken,
        /** @var string Administrator name for the registration contact. */
        private readonly string $contactname,
        /** @var string Administrator work email for the registration contact. */
        private readonly string $contactemail
    ) {
    }

    /**
     * The wire shape the backend's zod schema expects.
     *
     * @return array<string, string>
     */
    public function to_payload(): array {
        return [
            'platform_url' => $this->platformurl,
            'client_id' => $this->clientid,
            'deployment_id' => $this->deploymentid,
            'wstoken' => $this->wstoken,
            'contact_name' => $this->contactname,
            'contact_email' => $this->contactemail,
            'plugin_version' => (string) get_config('local_odea', 'version'),
        ];
    }
}
