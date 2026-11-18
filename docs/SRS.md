# Software Requirements Specification: Odea for Moodle (`local_odea`)

|                    |                                                              |
| ------------------ | ------------------------------------------------------------ |
| Product            | Odea for Moodle, Moodle local plugin `local_odea`            |
| Applies to release | 0.2.7 (`version.php`: 2026111800)                            |
| Publisher          | Mouseion (https://mouseion.com)                              |
| Licence            | GNU GPL v3 or later                                          |
| Structure          | ISO/IEC/IEEE 29148:2018, software requirements specification |

## 1. Introduction

### 1.1 Purpose

This document states what `local_odea` is required to do, and how each requirement is
verified. It is written for reviewers of the plugin: Moodle's integration reviewers, a
site's security officer, and the plugin's maintainers.

Section 3 holds the requirements. Section 4 maps every requirement to the automated test
that verifies it, or names the other method used when no automated test can.

### 1.2 Scope

`local_odea` connects one Moodle site to Odea, a hosted study assistant that answers
students' questions from their own course material.

The plugin does one job: when a site administrator asks for it, it prepares the Moodle
site so Odea can read courses and be launched from them, and tells Odea the site exists.
It has no part in what happens after that. Reading courses is done by Odea through
Moodle's own web service. Opening Odea is done by Moodle's own LTI 1.3 activity.

Out of scope: the Odea service itself, the content of its answers, and its user interface.

### 1.3 Definitions

| Term            | Meaning                                                                                                |
| --------------- | ------------------------------------------------------------------------------------------------------ |
| Odea            | The hosted service the plugin connects the site to.                                                    |
| Server URL      | The origin of the Odea backend, fixed in the plugin: `https://lti.odea.education`.                     |
| Connect         | The action an administrator starts from the connect page: provisioning followed by the install report. |
| Provisioning    | The changes Connect makes on the Moodle site (section 3.3).                                            |
| Install report  | The one HTTPS request Connect sends to Odea (section 3.4).                                             |
| Service         | The Moodle external service with shortname `local_odea`.                                               |
| Service account | The Moodle user `odea-service`, created by the plugin. Not a natural person.                           |
| Role            | The Moodle role with shortname `odeaws`, created by the plugin.                                        |
| Token           | The web service token minted for the service account, limited to the service.                          |
| Tool            | The LTI 1.3 external tool the plugin registers for Odea.                                               |
| Status          | What Odea last answered to an install report: `pending` or `active`.                                   |

### 1.4 References

1. ISO/IEC/IEEE 29148:2018, Systems and software engineering, Life cycle processes, Requirements engineering.
2. 1EdTech, Learning Tools Interoperability Core Specification 1.3.
3. Moodle developer documentation: Web services API, Privacy API, Events API, Access API.
4. `README.md` in this repository: installation, the settings the plugin changes, roles and capabilities, data sent to Odea.

### 1.5 Conventions

Each requirement has an identifier, one sentence with "shall", and a verification method:

| Method | Meaning                                                                                                                      |
| ------ | ---------------------------------------------------------------------------------------------------------------------------- |
| T      | Test. An automated PHPUnit test or Behat scenario in `tests/`.                                                               |
| A      | Analysis. An automated static check or the compatibility matrix in continuous integration.                                   |
| I      | Inspection. Reading the named file. Used where Moodle's test tooling cannot exercise the behaviour, and the reason is given. |

A requirement id is `ODEA-MOODLE-n`: the platform, the module and the requirement number.
The name of a PHPUnit test starts with the id of the requirement it verifies, written the
way PHP allows: `test_odea_moodle_9_…` verifies ODEA-MOODLE-9. A test that verifies more
than one requirement lists all of them in a "Verifies" line in its docblock, so a search
for an id finds every test of that requirement. A Behat scenario carries its ids in the
comment line above it. In section 4.2 a test is written as `class::method` and a scenario
as its feature file and title.

## 2. Overall description

### 2.1 Product perspective

The plugin sits between three parties and adds no component of its own to two of the
three paths:

| Path           | Who talks to whom                   | Carried by                                      |
| -------------- | ----------------------------------- | ----------------------------------------------- |
| Connect        | Moodle site to Odea, once per click | The plugin (section 3.4)                        |
| Course reading | Odea to Moodle site                 | Moodle core web service (REST), using the token |
| Launch         | Browser from Moodle to Odea         | Moodle core `mod_lti`, LTI 1.3                  |

The plugin has no database tables, no scheduled task, no event observer and no
JavaScript.

### 2.2 Product functions

1. Show a connect page to site administrators.
2. On request, provision the site: web services, the service, the service account and its role, the token, the tool.
3. Report the site to Odea and show what Odea answered.
4. Record every change and every attempt in Moodle's logs.
5. Remove everything it provisioned when it is uninstalled.
6. Declare the personal data sent to Odea under the Moodle Privacy API.

### 2.3 User characteristics

| User               | Uses                                                                 | Needs                               |
| ------------------ | -------------------------------------------------------------------- | ----------------------------------- |
| Site administrator | The connect page                                                     | The capability `moodle/site:config` |
| Teacher            | Adds the Odea activity to a course through Moodle's activity chooser | No interaction with the plugin      |
| Student            | Opens the Odea activity                                              | No interaction with the plugin      |

### 2.4 Constraints

1. Moodle 4.5 (LTS) through 5.3. PHP 8.1 through 8.4. PostgreSQL or MariaDB.
2. The site must be reachable from the internet over HTTPS, at the root of its host, because Odea calls the site's web service and LTI endpoints.
3. The site must be able to make outgoing HTTPS requests to the Server URL.
4. The core activity module `mod_lti` (External tool) must be enabled.

### 2.5 Assumptions and dependencies

1. Odea approves a new site by hand before it becomes active. Until then the status is `pending`.
2. The plugin depends on Moodle core only. It ships no third-party library.

## 3. Specific requirements

### 3.1 External interfaces

#### 3.1.1 User interface

| Id            | Requirement                                                                                                          | Method                                                                                               |
| ------------- | -------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------- |
| ODEA-MOODLE-1 | The plugin shall add exactly one entry, "Odea", under Site administration, Plugins, Local plugins: the connect page. | T                                                                                                    |
| ODEA-MOODLE-2 | The connect page shall be available only to users holding `moodle/site:config`.                                      | I: `settings.php`, `index.php` (`admin_externalpage_setup`). No test logs in without the capability. |
| ODEA-MOODLE-3 | The connect page shall show a form with the fields "Your name" and "Your work email" and one submit button.          | T                                                                                                    |
| ODEA-MOODLE-4 | The form shall be prefilled with the full name and email of the logged-in administrator.                             | I: `index.php`                                                                                       |
| ODEA-MOODLE-5 | The plugin shall provide no setting. No page in Moodle shall let an administrator change the Server URL.             | I: `settings.php`, `classes/connector.php` (`odea_base_url`)                                         |
| ODEA-MOODLE-6 | All text the plugin shows shall come from its language pack.                                                         | I: `lang/en/local_odea.php`                                                                          |

#### 3.1.2 Software interfaces

The plugin uses these Moodle interfaces and no other system's:

| Interface                                             | Used for                                                         |
| ----------------------------------------------------- | ---------------------------------------------------------------- |
| Admin settings tree                                   | The connect page                                                 |
| Web services (`external_services`, `external_tokens`) | The service and the token                                        |
| Access API (roles, capabilities)                      | The role and its assignment                                      |
| User API                                              | The service account                                              |
| `mod_lti` type API                                    | The tool                                                         |
| `curl` (`lib/filelib.php`)                            | The install report, subject to the site's HTTP security settings |
| Events API and config log                             | Logging                                                          |
| Privacy API                                           | The data declaration                                             |

#### 3.1.3 Communication interface: the install report

One request, from the Moodle site to Odea.

|                |                                           |
| -------------- | ----------------------------------------- |
| Method and URL | `POST <Server URL>/lti/plugin/install`    |
| Content type   | `application/json`                        |
| Time limits    | 5 seconds to connect, 10 seconds in total |

Request body:

| Field            | Content                                   |
| ---------------- | ----------------------------------------- |
| `platform_url`   | The site's address (`$CFG->wwwroot`)      |
| `client_id`      | The client id Moodle assigned to the tool |
| `deployment_id`  | The tool's id                             |
| `wstoken`        | The token                                 |
| `contact_name`   | The name typed into the form              |
| `contact_email`  | The email typed into the form             |
| `plugin_version` | The installed plugin version              |

Answer: a JSON object with `status` and `message`.

| HTTP status | Meaning                                     | Plugin behaviour               |
| ----------- | ------------------------------------------- | ------------------------------ |
| 200         | Accepted; `status` is `pending` or `active` | ODEA-MOODLE-23                 |
| 4xx         | Refused; `message` says why                 | ODEA-MOODLE-32                 |
| 5xx         | Odea is unavailable                         | ODEA-MOODLE-31                 |
| none        | No answer                                   | ODEA-MOODLE-29, ODEA-MOODLE-30 |

#### 3.1.4 Interfaces the plugin enables for Odea

| Interface                                               | Direction               | What it carries                                                     |
| ------------------------------------------------------- | ----------------------- | ------------------------------------------------------------------- |
| Moodle REST web service, six functions (ODEA-MOODLE-10) | Odea to Moodle          | Course structure and files, enrolled users' names, emails and roles |
| LTI 1.3 launch (ODEA-MOODLE-15)                         | Browser, Moodle to Odea | The user's id, name, email, roles and the course id                 |

### 3.2 Access and input

| Id            | Requirement                                                                                                                                               | Method                             |
| ------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------- |
| ODEA-MOODLE-7 | The plugin shall act on a form submission only when it carries the user's valid session key.                                                              | I: `index.php` (`confirm_sesskey`) |
| ODEA-MOODLE-8 | The plugin shall refuse a submission with an empty name or without a valid email address, show an error, and neither provision the site nor contact Odea. | I: `index.php`                     |

### 3.3 Provisioning

On Connect, the plugin shall make the following changes to the site.

| Id             | Requirement                                                                                                                                                                                                                                                                                                                                                | Method                                                                                               |
| -------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------- |
| ODEA-MOODLE-9  | It shall enable web services and add REST to the enabled protocols.                                                                                                                                                                                                                                                                                        | T                                                                                                    |
| ODEA-MOODLE-10 | It shall enable the service, which holds exactly these six functions: `core_webservice_get_site_info`, `core_user_get_users_by_field`, `core_enrol_get_users_courses`, `core_course_get_courses_by_field`, `core_course_get_contents`, `core_enrol_get_enrolled_users`.                                                                                    | T                                                                                                    |
| ODEA-MOODLE-11 | The service shall contain no function that writes to the site, and shall not allow file upload.                                                                                                                                                                                                                                                            | I: `db/services.php`                                                                                 |
| ODEA-MOODLE-12 | It shall create the service account if it does not exist.                                                                                                                                                                                                                                                                                                  | T                                                                                                    |
| ODEA-MOODLE-13 | It shall create the role, assignable at system level only, assign it to the service account at system level, and grant it these seven capabilities: `webservice/rest:use`, `moodle/course:view`, `moodle/course:viewhiddencourses`, `moodle/course:viewparticipants`, `moodle/course:useremail`, `moodle/user:viewdetails`, `moodle/site:accessallgroups`. | T (the seven are sufficient), I (no eighth is granted): `classes/connector.php`, `ROLE_CAPABILITIES` |
| ODEA-MOODLE-14 | It shall mint one token for the service account, limited to the service, without an expiry date.                                                                                                                                                                                                                                                           | T                                                                                                    |
| ODEA-MOODLE-15 | It shall register the tool as LTI 1.3, active without a review step, shown in the activity chooser, with the launch URL `<Server URL>/lti/launch`.                                                                                                                                                                                                         | T                                                                                                    |
| ODEA-MOODLE-16 | The tool's domain shall be the host of the Server URL, with its port when the URL names one.                                                                                                                                                                                                                                                               | T                                                                                                    |
| ODEA-MOODLE-17 | The tool shall send the user's name and email on launch, shall not accept grades, and shall open in a new window.                                                                                                                                                                                                                                          | I: `classes/connector.php`, `ensure_lti_tool`                                                        |
| ODEA-MOODLE-18 | Repeating Connect shall create nothing new and shall keep the same token and the same tool.                                                                                                                                                                                                                                                                | T                                                                                                    |
| ODEA-MOODLE-19 | Upgrading the plugin shall keep the service and the token.                                                                                                                                                                                                                                                                                                 | T                                                                                                    |
| ODEA-MOODLE-20 | With the seven capabilities, the service account shall be able to call all six functions and receive: its own identity; a user by id; a user's courses; a course that is hidden; a course's contents including a file; and an enrolled student's name, email and roles when the student hides their email and the course uses separate groups.             | T                                                                                                    |

### 3.4 Reporting to Odea

| Id             | Requirement                                                                                                      | Method                                                                                                      |
| -------------- | ---------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------- |
| ODEA-MOODLE-21 | After provisioning, the plugin shall send the install report of section 3.1.3.                                   | I: `classes/connector.php`, `report_to_odea`. Moodle's HTTP mock does not expose the request that was sent. |
| ODEA-MOODLE-22 | The plugin shall contact Odea only when an administrator submits the connect form.                               | I: the plugin has no `db/tasks.php`, no `db/events.php` and one call site of `curl`.                        |
| ODEA-MOODLE-23 | When Odea accepts the report, the plugin shall store the status Odea answered and return it with Odea's message. | T                                                                                                           |
| ODEA-MOODLE-24 | When Odea does not accept the report, the stored status shall stay what it was.                                  | T                                                                                                           |

### 3.5 Status display

| Id             | Requirement                                                                                                 | Method         |
| -------------- | ----------------------------------------------------------------------------------------------------------- | -------------- |
| ODEA-MOODLE-25 | With status `active`, the page shall say the site is connected and offer "Reconnect".                       | T              |
| ODEA-MOODLE-26 | With status `pending`, the page shall say the site waits for approval and offer "Check status".             | T              |
| ODEA-MOODLE-27 | When the site is provisioned but Odea never accepted a report, the page shall say so and offer "Reconnect". | I: `index.php` |
| ODEA-MOODLE-28 | When the site was never connected, the page shall offer "Connect to Odea".                                  | I: `index.php` |

### 3.6 Failure handling

| Id             | Requirement                                                                                                                        | Method                                                             |
| -------------- | ---------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------ |
| ODEA-MOODLE-29 | When Odea gives no answer, the plugin shall fail with a message naming the address it called and the cause the transport reported. | T                                                                  |
| ODEA-MOODLE-30 | When the site's own HTTP security settings refuse the request, the plugin shall fail the same way.                                 | T                                                                  |
| ODEA-MOODLE-31 | When Odea answers with a 5xx status, the plugin shall fail with a message saying Odea is temporarily unavailable.                  | I: `classes/connector.php`. Moodle's HTTP mock always answers 200. |
| ODEA-MOODLE-32 | When Odea answers with a 4xx status, the plugin shall fail with Odea's `message`.                                                  | I: `classes/connector.php`. Same reason.                           |
| ODEA-MOODLE-33 | When the answer is not the expected JSON, the plugin shall fail with a generic message.                                            | T                                                                  |
| ODEA-MOODLE-34 | Text from Odea's answer shall never reach a page or a log entry as markup.                                                         | T                                                                  |
| ODEA-MOODLE-35 | A failure during provisioning, before anything is sent, shall be reported as a failed connection.                                  | T                                                                  |

### 3.7 Logging

| Id             | Requirement                                                                                                                                                                                                                              | Method |
| -------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------ |
| ODEA-MOODLE-36 | Each change of section 3.3 shall leave the record an administrator would leave by doing it by hand: the two settings in the config changes report, and Moodle's own events for the service, the service account, the role and the token. | T      |
| ODEA-MOODLE-37 | A Connect that changes nothing shall add no such record.                                                                                                                                                                                 | T      |
| ODEA-MOODLE-38 | Each accepted report shall trigger the event `\local_odea\event\site_connected` carrying the status.                                                                                                                                     | T      |
| ODEA-MOODLE-39 | Each failed Connect shall trigger the event `\local_odea\event\site_connection_failed` carrying the reason.                                                                                                                              | T      |
| ODEA-MOODLE-40 | The token shall not appear in any event or error message.                                                                                                                                                                                | T      |

### 3.8 Uninstall

| Id             | Requirement                                                                                                                                                                                    | Method                |
| -------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------- |
| ODEA-MOODLE-41 | Uninstalling the plugin shall remove the token, the service, the service account, the role and the tool, and shall trigger Moodle's deletion events for the service, the account and the role. | T                     |
| ODEA-MOODLE-42 | Uninstalling shall remove only tools whose domain is the Server URL's host, and none when the Server URL has no host.                                                                          | I: `db/uninstall.php` |

### 3.9 Privacy

| Id             | Requirement                                                                                                                                                                                                           | Method                                 |
| -------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------- |
| ODEA-MOODLE-43 | The plugin shall declare, through the Moodle Privacy API, one external location with these six items: the contact's name, the contact's email, the site address, users' full names, users' emails, users' enrolments. | T                                      |
| ODEA-MOODLE-44 | The plugin shall store no personal data in tables of its own.                                                                                                                                                         | I: the plugin has no `db/install.xml`. |

### 3.10 Quality requirements

| Id             | Requirement                                                                                                                                                       | Method                                                                              |
| -------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| ODEA-MOODLE-45 | The plugin shall install and pass its tests on Moodle 4.5, 5.0, 5.1, 5.2 and 5.3, on the lowest and highest PHP version each supports, on PostgreSQL and MariaDB. | A: the twelve jobs of `.github/workflows/ci.yml`                                    |
| ODEA-MOODLE-46 | The plugin shall pass Moodle's code checker and PHPDoc checker without warnings, and Moodle's plugin validation.                                                  | A: `moodle-plugin-ci phpcs`, `phpdoc`, `validate` in the same workflow              |
| ODEA-MOODLE-47 | The plugin shall pass static analysis at PHPStan level 6 against the Moodle 5.3 source.                                                                           | A: `make check`                                                                     |
| ODEA-MOODLE-48 | The plugin shall depend on Moodle core only.                                                                                                                      | I: `version.php` declares no dependency; the release package contains no `vendor/`. |

### 3.11 Known limitation

Content in a hidden section of a visible course is not readable by the service account.
Reading it would need `moodle/course:viewhiddensections` and
`moodle/course:viewhiddenactivities`, which the role does not grant. This is recorded,
not required: `service_account_capabilities_test::test_hidden_section_content_is_not_synced`
fails if the behaviour changes.

## 4. Verification

### 4.1 Running the tests

In a Moodle checkout with the plugin at `local/odea`:

```bash
php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit --testsuite local_odea_testsuite
php admin/tool/behat/cli/run.php --tags=@local_odea
```

Every push to the public repository runs both suites on the twelve combinations of
ODEA-MOODLE-45.

### 4.2 Traceability: requirement to verification

PHPUnit classes are in `tests/`. `provider_test` is in `tests/privacy/`. The Behat feature
is `tests/behat/connect_page.feature`.

| Requirement     | Verified by                                                                                                                                                       |
| --------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| ODEA-MOODLE-1   | `connect_page.feature`: The connect page renders with the connect form                                                                                            |
| ODEA-MOODLE-3   | `connect_page.feature`: The connect page renders with the connect form                                                                                            |
| ODEA-MOODLE-9   | `connector_test::test_odea_moodle_9_enables_webservices_and_rest`                                                                                                 |
| ODEA-MOODLE-10  | `connector_test::test_odea_moodle_10_creates_service_with_read_functions`                                                                                         |
| ODEA-MOODLE-12  | `connector_test::test_odea_moodle_14_mints_permanent_token_for_service_account`                                                                                   |
| ODEA-MOODLE-13  | `service_account_capabilities_test::test_odea_moodle_20_service_account_can_read_everything_odea_needs`                                                           |
| ODEA-MOODLE-14  | `connector_test::test_odea_moodle_14_mints_permanent_token_for_service_account`                                                                                   |
| ODEA-MOODLE-15  | `connector_test::test_odea_moodle_15_creates_active_lti13_tool`                                                                                                   |
| ODEA-MOODLE-16  | `connector_test::test_odea_moodle_15_creates_active_lti13_tool`, `connector_test::test_odea_moodle_16_tool_domain_keeps_an_explicit_port`                         |
| ODEA-MOODLE-18  | `connector_test::test_odea_moodle_18_provisioning_is_idempotent`                                                                                                  |
| ODEA-MOODLE-19  | `connector_test::test_odea_moodle_19_plugin_upgrade_keeps_service_and_token`                                                                                      |
| ODEA-MOODLE-20  | `service_account_capabilities_test::test_odea_moodle_20_service_account_can_read_everything_odea_needs`                                                           |
| ODEA-MOODLE-23  | `connect_test::test_odea_moodle_23_connect_success_triggers_site_connected_event`, `connect_test::test_odea_moodle_23_connect_remembers_what_odea_answered`       |
| ODEA-MOODLE-24  | `connect_test::test_odea_moodle_24_failed_report_leaves_the_last_status_untouched`                                                                                |
| ODEA-MOODLE-25  | `connect_page.feature`: An approved site is reported as connected                                                                                                 |
| ODEA-MOODLE-26  | `connect_page.feature`: A site waiting for approval is not reported as connected                                                                                  |
| ODEA-MOODLE-29  | `connect_test::test_odea_moodle_29_connect_without_an_answer_says_why`                                                                                            |
| ODEA-MOODLE-30  | `connect_test::test_odea_moodle_30_connect_to_a_blocked_url_fails_as_unreachable`                                                                                 |
| ODEA-MOODLE-33  | `connect_test::test_odea_moodle_33_connect_failure_triggers_site_connection_failed_event`                                                                         |
| ODEA-MOODLE-34  | `connect_test::test_odea_moodle_34_connect_never_carries_markup_from_the_backend_body`                                                                            |
| ODEA-MOODLE-35  | `connect_test::test_odea_moodle_35_failed_local_step_triggers_site_connection_failed_event`                                                                       |
| ODEA-MOODLE-36  | `connector_test::test_odea_moodle_36_provisioning_logs_what_it_changes`                                                                                           |
| ODEA-MOODLE-37  | `connector_test::test_odea_moodle_37_second_run_logs_nothing_new`                                                                                                 |
| ODEA-MOODLE-38  | `connect_test::test_odea_moodle_23_connect_success_triggers_site_connected_event`                                                                                 |
| ODEA-MOODLE-39  | `connect_test::test_odea_moodle_33_connect_failure_triggers_site_connection_failed_event`, `connect_test::test_odea_moodle_29_connect_without_an_answer_says_why` |
| ODEA-MOODLE-40  | `connect_test::test_odea_moodle_33_connect_failure_triggers_site_connection_failed_event`                                                                         |
| ODEA-MOODLE-41  | `uninstall_test::test_odea_moodle_41_uninstall_removes_everything_provisioned`                                                                                    |
| ODEA-MOODLE-43  | `provider_test::test_odea_moodle_43_get_metadata_declares_external_location_link`                                                                                 |
| Limitation 3.11 | `service_account_capabilities_test::test_hidden_section_content_is_not_synced`                                                                                    |

Requirements verified by inspection or analysis name their evidence in section 3:
ODEA-MOODLE-2, ODEA-MOODLE-4, ODEA-MOODLE-5, ODEA-MOODLE-6, ODEA-MOODLE-7, ODEA-MOODLE-8, ODEA-MOODLE-11, ODEA-MOODLE-17, ODEA-MOODLE-21, ODEA-MOODLE-22, ODEA-MOODLE-27,
ODEA-MOODLE-28, ODEA-MOODLE-31, ODEA-MOODLE-32, ODEA-MOODLE-42, ODEA-MOODLE-44, ODEA-MOODLE-45, ODEA-MOODLE-46, ODEA-MOODLE-47, ODEA-MOODLE-48.

### 4.3 Coverage

|                                                | Count |
| ---------------------------------------------- | ----- |
| Requirements                                   | 48    |
| Verified by an automated test                  | 28    |
| Verified by analysis in continuous integration | 3     |
| Verified by inspection only                    | 17    |
| PHPUnit tests                                  | 21    |
| Behat scenarios                                | 3     |

Every PHPUnit test and every Behat scenario of the plugin is named in section 4.2 or
3.11. `make check` runs `tools/check-srs-trace.sh`, which fails when this document names a
test that does not exist, or when a test or scenario is named nowhere in it.
