# Odea for Moodle (`local_odea`)

Connects a Moodle site to [Odea](https://odea.education), a hosted learning platform
built by Mouseion that answers students' questions from their own course materials.
The plugin is free. It needs the external Odea service to do anything useful: it sets
up the connection, and Odea does the rest.

- **Plugin type:** local plugin (`local_odea`), installed to `<moodle>/local/odea`.
- **Supported Moodle versions:** 4.5 (LTS), 5.0, 5.1, 5.2 and 5.3. Every release is
  tested on each of them with PHP 8.1 to 8.4 on PostgreSQL and MariaDB
  (`.github/workflows/ci.yml`).
- **Dependencies:** none beyond Moodle core. No API keys to enter, nothing to install
  on the server.

## What it does

The connect page (**Site administration → Plugins → Local plugins → Odea connection**)
performs in one click the setup that would otherwise take an administrator four manual
steps:

1. Enables web services and the REST protocol.
2. Enables its dedicated, read-only external service (`local_odea`), which holds exactly
   the six functions Odea calls, and creates a service account, a role scoped to those
   functions, and a permanent web service token.
3. Registers Odea as a site-wide, active LTI 1.3 tool. Teachers find it in the activity
   chooser, with no manual key exchange.
4. Sends the connection details to Odea. Odea verifies the site by fetching its LTI
   configuration over TLS and checking the token against the site before anything is
   stored.

Odea reviews every new site by hand before it is activated, usually within a working
day. Until then the connect page reports **Almost there**, and launches from the site
are refused. The page keeps showing what Odea last answered. Click **Check status** on
it to ask again: once the site is approved it reports **Connected**.

## Requirements

- Moodle 4.5 (LTS) through 5.3.
- A site reachable from the internet over HTTPS. Odea fetches the site's LTI
  configuration and calls its web service itself, so a site on `localhost` or a private
  network cannot complete the connection. Everything on the Moodle side is still set up.
- Moodle installed at the root of its host (`https://moodle.example.edu`). A site in a
  subdirectory (`https://example.edu/moodle`) cannot connect yet.
- Users with an email address on the institution's own domain. Odea admits a launch only
  from the email domains approved for the institution.

## Install and connect

1. Download `local_odea-<version>.zip` from the
   [latest release](https://github.com/odea-education/moodle-local_odea/releases/latest).
2. Go to **Site administration → Plugins → Install plugins**, upload the ZIP and follow
   the upgrade prompts. Alternatively unzip it to `<moodle>/local/odea` and run
   `php admin/cli/upgrade.php`.
3. Go to **Site administration → Plugins → Local plugins → Odea connection**.
4. Enter your name and your work email, and click **Connect to Odea**. Use an address on
   the institution's own domain: Odea uses its domain to decide which users belong to
   the institution, and it is how Odea reaches you.
5. Once Odea has approved the site, teachers add **Odea** to a course from the activity
   chooser. It opens in a new window, and teachers and students are signed in through
   LTI without a separate Odea account.

To limit Odea to some courses, edit the tool under **Site administration → Plugins →
Activity modules → External tool → Manage tools** and use **Restrict to category**.

## Moodle settings it changes

| Setting                        | Value                                          |
| ------------------------------ | ---------------------------------------------- |
| `enablewebservices`            | on                                             |
| `webserviceprotocols`          | `rest` added                                   |
| External service `local_odea`  | enabled. Installed with the plugin, off before |
| User `odea-service`            | created, manual auth, not used to log in       |
| Role `odeaws` (system context) | created and assigned to `odea-service` only    |
| Web service token              | one permanent token for `odea-service`         |
| LTI 1.3 tool **Odea**          | created, active, shown in the activity chooser |

The tool's settings: launch in a new window, send the user's name and email, no
grades returned to Moodle, no content-item selection.

Each change is logged where Moodle logs the same change made by hand. The two settings
appear under **Site administration → Reports → Config changes**. The service, the account,
the role and the token appear as standard Moodle events under **Reports → Logs**, next to
the plugin's own **Site connected to Odea** and **Connecting site to Odea failed**. Every
read Odea makes afterwards is logged by Moodle as a web service call by `odea-service`.

Uninstalling the plugin removes all of the above: the token, the service, the service
account, the role and the Odea LTI tool. Web services and REST stay enabled, since other
plugins may rely on them.

## Roles and capabilities

The plugin defines no capabilities of its own.

| Who                | What they can do                                                                                                           |
| ------------------ | -------------------------------------------------------------------------------------------------------------------------- |
| Site administrator | Open the connect page and connect the site (`moodle/site:config`).                                                         |
| Teacher            | Add Odea to a course as an External tool activity, with the standard `mod/lti` capabilities. In Odea they are an educator. |
| Student            | Open the Odea activity in a course they are enrolled in. In Odea they are a student.                                       |
| `odea-service`     | Read the data listed below through the web service. Nothing else.                                                          |

Odea maps the LTI roles Moodle sends at launch:

| LTI role                                                      | Odea role          |
| ------------------------------------------------------------- | ------------------ |
| Instructor, Administrator, ContentDeveloper, Manager, Officer | Educator           |
| TeachingAssistant, Mentor                                     | Educator assistant |
| Learner, Member, and any other role                           | Student            |

The `odeaws` role allows these capabilities, at system context, and nothing more:

| Capability                        | Why                                                                   |
| --------------------------------- | --------------------------------------------------------------------- |
| `webservice/rest:use`             | Call the web service over REST.                                       |
| `moodle/course:view`              | Pass `require_login()` for a course without being enrolled in it.     |
| `moodle/course:viewhiddencourses` | Include courses that are not yet visible to students.                 |
| `moodle/course:viewparticipants`  | Required by `core_enrol_get_enrolled_users`.                          |
| `moodle/course:useremail`         | Return an enrolled user's email when their own setting would hide it. |
| `moodle/user:viewdetails`         | Required by `core_user_get_users_by_field`.                           |
| `moodle/site:accessallgroups`     | List enrolled users in courses that use separate groups.              |

`tests/service_account_capabilities_test.php` calls all six functions as this account
and fails if any capability is missing.

## Data sent to Odea

Odea only reads from Moodle. It never writes to it, and the external service contains
no write functions.

**When an administrator connects the site**, the plugin sends: the site URL, the LTI
client id and deployment id, the web service token, the plugin version, and the name and
work email typed into the connect form. Odea uses them to verify the site, to identify it
on LTI launches, and to contact the person responsible for the installation.

**When a user opens Odea**, Moodle sends a standard LTI 1.3 launch. Odea reads the user's
LTI id, email, roles and the course id. It uses the email to find or create the user's
Odea account and the course id to find the course.

**Through the web service**, using the six functions of the `local_odea` service:

| Function                           | What Odea reads                                       | Why                                                   |
| ---------------------------------- | ----------------------------------------------------- | ----------------------------------------------------- |
| `core_webservice_get_site_info`    | The token owner's id, full name and username          | Check the token works                                 |
| `core_user_get_users_by_field`     | A user's id, full name and email                      | Match a Moodle user to an Odea user                   |
| `core_enrol_get_users_courses`     | A user's courses: id, short and full name, start date | Let a teacher pick which course to connect            |
| `core_course_get_courses_by_field` | A course's id and names                               | Look up a course                                      |
| `core_course_get_contents`         | Sections, activities, resources and their files       | Build the course library students ask questions about |
| `core_enrol_get_enrolled_users`    | Enrolled users' id, full name, email and roles        | Give the right people access to the course in Odea    |

Odea downloads the files of the courses a teacher connects. It reads no grades,
submissions, forum posts, messages or logs.

The plugin itself stores no personal data. `classes/privacy/provider.php` declares the
data sent to Odea under the Moodle Privacy API.

## Settings

`local_odea | baseurl`: the Odea server URL. Leave the default
(`https://production-backend.odea.education`) unless Odea support told you otherwise.

## Development

Lint and type-check (a quick subset of the `moodle-plugin-ci` run Marketplace review uses):

```bash
make check   # phpcs (moodle-cs) + phpstan
```

The first run downloads Composer packages into `vendor/`, including the Moodle 5.3.0
source (about 90 MB to download, 580 MB on disk). PHPStan checks the plugin against that
source, and an IDE that indexes `vendor/` resolves Moodle's classes from it. Nothing in
`vendor/` ships with the plugin.

## Release packaging

```bash
make release
```

Produces `local_odea-<release>.zip` (the version from `version.php`) with a top-level
`odea/` folder, excluding dev-only files (`vendor/`, `.composer/`, `composer.*`,
`phpcs.xml`, `phpstan.neon`, `tools/`, `Makefile`, `.github/`). Verify its contents with
`unzip -l local_odea-<release>.zip` before uploading it anywhere.

## Support and bug tracker

https://github.com/odea-education/moodle-local_odea/issues, or support@mouseion.com.

## Changelog

See `CHANGES.md`.

## License

GPL v3 or later, see `LICENSE`.
