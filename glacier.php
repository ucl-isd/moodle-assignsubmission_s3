<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Glacier retrieval or progress update.
 *
 * @package   assignsubmission_s3
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use assignsubmission_s3\event\object_requested;
use assignsubmission_s3\persistent\assignsubmission_s3;
use assignsubmission_s3\persistent\assignsubmission_s3_requests;
use core\output\notification;

require_once(dirname(__FILE__, 5) . '/config.php');

$id = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', false, PARAM_BOOL);

// Load the data and check that the assign data is valid and user has permission.
$s3submission = new assignsubmission_s3($id);
$submission = $DB->get_record('assign_submission', ['id' => $s3submission->get('submission')]);
$assign = $s3submission->get_assign();
$assignurl = new moodle_url('/mod/assign/view.php', ['id' => $assign->get_course_module()->id]);
require_course_login($assign->get_course(), false, $assign->get_course_module());

if (!has_capability('assignsubmission/s3:glacier_request', $assign->get_context())) {
    // Redirect.
    redirect($assignurl, get_string('glacier:permission_denied', 'assignsubmission_s3'));
}

if ($assign->get_instance()->id != $submission->assignment) {
    send_file_not_found();
}

if ($assign->get_instance()->teamsubmission && !$assign->can_view_group_submission($submission->groupid)) {
    send_file_not_found();
}

if (!$assign->get_instance()->teamsubmission && !$assign->can_view_submission($submission->userid)) {
    send_file_not_found();
}

$PAGE->set_context($assign->get_context());
$PAGE->set_url('/mod/assign/submission/s3/glacier.php', ['id' => $id]);
$PAGE->set_cm($assign->get_course_module());

$message = '';
$params = [
    'requester' => $USER->id,
    'ass3id' => $s3submission->get('id'),
];
$requestedcount = assignsubmission_s3_requests::count_records($params);
$assignurl = new moodle_url('/mod/assign/view.php', ['id' => $assign->get_course_module()->id]);

if ($confirm) {
    if (!$s3submission->is_requested()) {
        // Set the status of the submission to requested.
        $s3submission->set('status', assignsubmission_s3::STATUS_REMOTE_REQUESTED);
        $s3submission->save();
    }
    // Add the user to the requests table to be notified.
    $request = new assignsubmission_s3_requests();
    $request->set_many($params);
    $request->save();
    // Trigger the object_requested event.
    object_requested::create([
        'context' => $assign->get_context(),
        'courseid' => $assign->get_course()->id,
        'objectid' => $submission->id,
        'other' => [],
        'relateduserid' => $USER->id,
    ])->trigger();
    // Redirect beck to the assignment page.
    redirect($assignurl, get_string('glacier:requested', 'assignsubmission_s3'), null, notification::NOTIFY_SUCCESS);
}
if ($s3submission->is_requested()) {
    // Already requested so check if the user needs or will be notified.
    if ($requestedcount > 0) {
        // Redirect.
        redirect($assignurl, get_string('glacier:already_requested', 'assignsubmission_s3'));
    } else {
        $message = get_string('glacier:confirm:notified', 'assignsubmission_s3');
    }
} else if ($s3submission->is_restored()) {
    // File has already been restored so redirect back with a message.
    // Shouldn't happen, but better to catch this just in case.
    redirect($assignurl, get_string('glacier:already_restored', 'assignsubmission_s3'), null, notification::NOTIFY_SUCCESS);
} else {
    $message = get_string('glacier:confirm', 'assignsubmission_s3');
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('glacier:heading', 'assignsubmission_s3'));
echo $OUTPUT->confirm(
    $message,
    new moodle_url('/mod/assign/submission/s3/glacier.php', ['sesskey' => sesskey(), 'confirm' => 1, 'id' => $id]),
    $assignurl,
);
echo $OUTPUT->footer();
