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
 * English language strings for local_odea.
 *
 * @package    local_odea
 * @copyright  2026 Mouseion (https://mouseion.com)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['baseurl'] = 'Odea server URL';
$string['baseurl_desc'] = 'The Odea backend this site connects to. Leave the default unless Odea support told you otherwise.';
$string['checkstatusbutton'] = 'Check status';
$string['connectbutton'] = 'Connect to Odea';
$string['connectheading'] = 'Connect this site to Odea';
$string['connectintro'] = 'One click sets everything up: it enables the Moodle web service Odea reads courses through, creates a dedicated service account with a token, registers Odea as an LTI 1.3 tool so teachers can add it to their courses, and sends the connection details to Odea.';
$string['contactemail'] = 'Your work email';
$string['contactemail_help'] = 'Use your institutional address. Its domain tells Odea which email domains belong to your school and is used to contact you about this installation.';
$string['contactname'] = 'Your name';
$string['errorgeneric'] = 'The connection could not be completed: {$a}';
$string['errornocontact'] = 'A contact name and a valid work email are required.';
$string['errorunreachable'] = 'Odea could not be reached at {$a}. Check the Odea server URL setting.';
$string['event_site_connected'] = 'Site connected to Odea';
$string['event_site_connection_failed'] = 'Connecting site to Odea failed';
$string['pluginname'] = 'Odea';
$string['privacy:metadata'] = 'The Odea connector plugin does not store any personal data of its own, but the web service token and LTI tool it creates let Odea read enrolled users\' name, email address and course enrolments as part of the course sync it provides. It also sends the name and email address the administrator enters on the connect page to the Odea service as the registration contact.';
$string['privacy:metadata:odea'] = 'In order to register this site with Odea, identify the administrator responsible for it, and let Odea sync courses, the following data is sent to the Odea backend.';
$string['privacy:metadata:odea:contactemail'] = 'The work email address of the administrator who ran the connect flow, used to contact them about this installation.';
$string['privacy:metadata:odea:contactname'] = 'The name of the administrator who ran the connect flow, entered on the connect page.';
$string['privacy:metadata:odea:platformurl'] = 'This site\'s root URL, used by Odea as the LTI issuer identifying this Moodle site.';
$string['privacy:metadata:odea:useremail'] = 'The email address of an enrolled user, read through the dedicated web service and sent on LTI launches, so Odea can sync courses and identify the launching user.';
$string['privacy:metadata:odea:userenrolments'] = 'Which courses a user is enrolled in, read through the dedicated web service so Odea can keep its copy of course membership in sync.';
$string['privacy:metadata:odea:userfullname'] = 'The full name of an enrolled user, read through the dedicated web service and sent on LTI launches, so Odea can sync courses and identify the launching user.';
$string['reconnectbutton'] = 'Reconnect';
$string['roledescription'] = 'Grants the Odea service account read access to courses, contents and enrolments over the web service API.';
$string['rolename'] = 'Odea web service';
$string['serviceuserlastname'] = 'service account';
$string['setuppage'] = 'Odea connection';
$string['statusactive'] = 'Connected. Odea is active for your institution. Teachers can add Odea to their courses from the activity chooser.';
$string['statuspending'] = 'Almost there. Everything is set up on this site, and Odea verifies your institution by hand, usually within a working day. Teachers can already add Odea to their courses, but it opens only after approval. Click Check status to see whether your institution has been approved.';
$string['statusunknown'] = 'This site is set up for Odea, but Odea has not confirmed the connection. Click Reconnect to finish connecting.';
$string['tooldescription'] = 'Launch Odea courses from Moodle using LTI 1.3.';
$string['toolname'] = 'Odea';
