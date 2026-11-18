# Changelog

All notable changes to `local_odea` are documented here.

## 0.2.7 (2026-11-18)

- The plugin now adds one entry under Site administration → Plugins → Local plugins,
  named **Odea**, which opens the connect page. Before, there were two: a settings page
  and "Odea connection".
- The Odea server address is no longer a setting. An administrator cannot change where
  the site connects to.
- The plugin now connects to `https://lti.odea.education`, Odea's address for LMS
  integrations. Earlier releases used `https://production-backend.odea.education`.

## 0.2.6 (2026-11-17)

- No change to what the plugin does.
- Added `docs/SRS.md`, the software requirements specification (ISO/IEC/IEEE 29148:2018
  structure). It maps each requirement to the test that verifies it.
- Each PHPUnit test is now named after the requirement it verifies, for example
  `test_odea_moodle_9_enables_webservices_and_rest`.

## 0.2.5 (2026-11-17)

- Connecting now logs what it changes on the site. Switching on web services and REST
  appears in the config changes report. Enabling the service, creating the service
  account and minting the token appear as standard Moodle events. Uninstalling logs the
  removal of the service.
- A Reconnect that changes nothing adds no log rows.
- When Odea gives no answer, the error now names the cause curl reported and says to try
  again first. It told the administrator to check the server URL before, whatever the cause.
- When Odea answers with a server error, the page says Odea is temporarily unavailable.
- A step that fails on the site before anything is sent to Odea is logged as a failed
  connection.
- Fixed a PHP warning when the site's own HTTP security settings block the Odea URL.

## 0.2.4 (2026-11-16)

- Simplified the plugin's code. The connect page and the data sent to Odea are unchanged.
- Connecting now stops with an error when Moodle does not define a capability the
  service account needs. It skipped such a capability before, which left the account
  without a permission and no message.

## 0.2.3 (2026-11-16)

- Fixed: upgrading the plugin deleted the Odea service and its token, which stopped
  course sync until an Odea administrator stepped in. The service is now declared in
  `db/services.php`, so Moodle keeps it. An upgrade from 0.2.2 keeps the existing token.
- The connect page shows what Odea last answered. It said "This site is connected to
  Odea" on every visit after the first connect, also while the site still waited for
  approval, or when Odea never received the connection.
- While a site waits for approval, the button reads **Check status**.
- The pending status says teachers can add Odea straight away and that it opens after
  approval.

## 0.2.2 (2026-11-16)

- Added Moodle 5.3 support: the service account is created with
  `\core\user::create_user()` where it exists.
- Fixed the CI matrix, which collapsed to one Moodle version, and added 5.3.
- The README documents the plugin for Moodle's certification review: supported
  versions, the settings it changes, roles and capabilities, and the data sent to Odea.
- The pending status no longer promises an approval email.

## 0.2.1 (2026-11-06)

- Prepared for Moodle Marketplace: the plugin is free, so the README and the
  privacy metadata no longer describe it as requiring a paid contract.
- Documented that a site must be publicly reachable over HTTPS to finish connecting.
- The service account's name now comes from language strings.
- Removed internal backend names from comments and from a language string id.
- `make release` also leaves out `phpcs.xml` and earlier release zips.

## 0.2.0 (2026-09-24)

- Rewrote the privacy provider from a `null_provider` to a full metadata
  provider declaring an `add_external_location_link()` for the contact
  name, contact email and platform URL sent to Odea during connect.
- Added `local_odea\event\site_connected` and
  `local_odea\event\site_connection_failed`, triggered from
  `connector::connect()`. Neither event carries the web service token.
- Added a timeout (10s, 5s connect) to the outbound install-report request.
- Added PHPUnit coverage for the privacy provider, for `connect()`'s happy
  and failure paths (outbound call mocked), for the events each path fires,
  and for the uninstall hook.
- Added a Behat feature checking the connect page renders.
- Added CI (`.github/workflows/ci.yml`) running moodle-plugin-ci across
  Moodle 4.5 LTS through 5.2, PHP 8.1-8.4, Postgres and MariaDB.
- Added `make release` (packages `local_odea-<release>.zip`).
- Sorted `lang/en/local_odea.php` alphabetically.
- Renamed the component from `local_alexandria` to `local_odea` (product
  rename: Odea, built by Mouseion). The service shortname, service account
  username (`odea-service`), role shortname (`odeaws`), and all
  user-facing strings were updated to match.

## 0.1.0 (2026-07-30)

- Initial version: one-click connect flow that enables web services, creates
  the dedicated external service, service account, role and permanent
  token, registers Odea as an Active LTI 1.3 tool, and reports the bundle to
  the Odea backend.
