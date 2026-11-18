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
 * The Odea connect page: one button that provisions everything.
 *
 * @package    local_odea
 * @copyright  2026 Mouseion (https://mouseion.com)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_odea');

$connector = new \local_odea\connector();

$message = '';
$error = null;
if (data_submitted() && confirm_sesskey()) {
    $contactname = trim(required_param('contactname', PARAM_TEXT));
    $contactemail = trim(required_param('contactemail', PARAM_EMAIL));
    if ($contactname === '' || $contactemail === '') {
        $error = get_string('errornocontact', 'local_odea');
    } else {
        try {
            $message = $connector->connect($contactname, $contactemail)->message;
        } catch (moodle_exception $exception) {
            $error = $exception->getMessage();
        }
    }
}

$status = $connector->last_status();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('connectheading', 'local_odea'));

if ($error !== null) {
    echo $OUTPUT->notification(s($error), \core\output\notification::NOTIFY_ERROR, false);
}

if ($status === 'active') {
    echo $OUTPUT->notification(
        get_string('statusactive', 'local_odea'),
        \core\output\notification::NOTIFY_SUCCESS,
        false
    );
    $buttonlabel = get_string('reconnectbutton', 'local_odea');
} else if ($status !== '') {
    echo $OUTPUT->notification(
        get_string('statuspending', 'local_odea'),
        \core\output\notification::NOTIFY_INFO,
        false
    );
    $buttonlabel = get_string('checkstatusbutton', 'local_odea');
} else if ($connector->has_token()) {
    // Set up here, but Odea never accepted the report. A failed attempt already says why.
    if ($error === null) {
        echo $OUTPUT->notification(
            get_string('statusunknown', 'local_odea'),
            \core\output\notification::NOTIFY_WARNING,
            false
        );
    }
    $buttonlabel = get_string('reconnectbutton', 'local_odea');
} else {
    $buttonlabel = get_string('connectbutton', 'local_odea');
}

if ($message !== '') {
    echo html_writer::tag('p', s($message));
}

echo html_writer::tag('p', get_string('connectintro', 'local_odea'));

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $PAGE->url->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

echo html_writer::start_div('form-group mb-3');
echo html_writer::label(get_string('contactname', 'local_odea'), 'id_contactname');
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'id' => 'id_contactname',
    'name' => 'contactname',
    'class' => 'form-control',
    'maxlength' => '255',
    'required' => 'required',
    'value' => fullname($USER),
]);
echo html_writer::end_div();

echo html_writer::start_div('form-group mb-3');
echo html_writer::label(get_string('contactemail', 'local_odea'), 'id_contactemail');
echo html_writer::empty_tag('input', [
    'type' => 'email',
    'id' => 'id_contactemail',
    'name' => 'contactemail',
    'class' => 'form-control',
    'maxlength' => '320',
    'required' => 'required',
    'value' => $USER->email,
]);
echo html_writer::div(get_string('contactemail_help', 'local_odea'), 'form-text text-muted');
echo html_writer::end_div();

echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'class' => 'btn btn-primary',
    'value' => $buttonlabel,
]);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
