# Odea for Moodle (`local_odea`)

Connects a Moodle site to [Odea](https://odea.education), a hosted learning platform
built by Mouseion that answers students' questions from their own course materials.
The plugin is free. It needs the external Odea service to do anything useful: it sets
up the connection, and Odea does the rest.

The connect page (Site administration → Plugins → Local plugins → Odea connection)
performs the whole setup that would otherwise take an admin four manual steps:

1. Enables web services and the REST protocol.
2. Creates a dedicated, read-only external service (`local_odea`) with exactly the
   functions Odea calls, a service account, a role scoped to those functions, and a
   permanent web service token.
3. Registers Odea as a site-wide **Active** LTI 1.3 tool (teachers find it in the
   activity chooser) — no pending review step, no manual key exchange.
4. Sends the connection bundle (site URL, LTI client id, deployment id, web service
   token, admin contact) to the Odea backend, which verifies the site by fetching
   its LTI configuration over TLS and validating the token against the site before
   anything is stored.

Schools on their own domain are activated immediately. Sites on shared vendor hosts
(e.g. `*.moodlecloud.com`) are reviewed by hand, usually within a working day.

## Requirements

- Moodle 4.5 (LTS) through 5.2 — see `version.php` for the exact range this release
  was tested against.
- A publicly reachable site served over HTTPS. Odea verifies a site by fetching its LTI
  configuration over TLS, so a site on `localhost` or a private network cannot complete
  the connection; everything on the Moodle side is still set up.
- PHP and database versions as required by the Moodle version you run.

## Install

- **From a zip** (admin UI): Site administration → Plugins → Install plugins → upload the
  release zip (`local_odea-<version>.zip`, produced by `make release`).
- **From source**: copy this folder to `<moodle>/local/odea`, then visit Site
  administration → Notifications (or run `php admin/cli/upgrade.php`).

## What it creates on your site

- A web service (`local_odea`), enabled with exactly the read-only functions Odea's
  Moodle client calls.
- A dedicated service account (`odea-service`) and role (`odeaws`), scoped to those
  functions only.
- A permanent web service token for that account.
- A site-wide, Active LTI 1.3 tool registration for Odea.

Uninstalling the plugin removes all of the above: the token, service, service account,
role and the Odea LTI tool it created.

## Privacy

This plugin stores no personal data of its own, but it does hand Odea two channels to
read personal data of enrolled users:

- When an administrator runs the connect flow, their name and work email (typed into
  the connect form) are sent to the Odea backend as the registration contact, together
  with technical identifiers for this site (its URL, LTI client id, deployment id).
- The permanent web service token it mints, and the LTI 1.3 tool it registers
  (`sendname`/`sendemailaddr` on), let Odea read an enrolled user's full name, email
  address and course enrolments — through the dedicated read-only service and on every
  LTI launch — for as long as the site stays connected. This is what lets Odea sync
  courses and identify the launching user; it is not stored by the plugin itself.

See `classes/privacy/provider.php` and the `privacy:metadata:*` language strings for
the full declaration.

## Settings

`local_odea | baseurl` — the Odea backend origin. Leave the default unless
Odea support told you otherwise.

## Development

Lint/type-check (a quick subset of the `moodle-plugin-ci` run Marketplace review uses):

```bash
make check   # phpcs (moodle-cs) + phpstan
```

## Release packaging

```bash
make release
```

Produces `local_odea-<release>.zip` (the version from `version.php`) with a top-level
`odea/` folder, excluding dev-only files (`vendor/`, `.composer/`, `composer.*`,
`phpcs.xml`, `phpstan.neon`, `tools/`, `Makefile`, `.github/`). Verify its contents with
`unzip -l local_odea-<release>.zip` before uploading to Moodle Marketplace.

## Moodle Marketplace

Listed on [Moodle Marketplace](https://marketplace.moodle.com) as `local_odea`. Releases
are zips of this folder named `local_odea-<version>.zip` (see `make release` above).

## Support / bug tracker

https://github.com/odea-education/moodle-local_odea/issues

## Changelog

See `CHANGES.md`.

## License

GPL v3 or later — see `LICENSE`.
