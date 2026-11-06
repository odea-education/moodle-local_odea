# Changelog

All notable changes to `local_odea` are documented here.

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
