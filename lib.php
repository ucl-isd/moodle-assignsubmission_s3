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
 * Library file.
 *
 * @package   assignsubmission_s3
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2025
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use assignsubmission_s3\persistent\assignsubmission_s3;
use core\session\manager;

/**
 * Serves S3 assignment submissions.
 *
 * @param $course
 * @param $cm
 * @param context $context
 * @param $filearea
 * @param $args
 * @param $forcedownload
 * @param array $options
 * @return void
 */
function assignsubmission_s3_pluginfile(
    $course,
    $cm,
    context $context,
    $filearea,
    $args,
) {
    global $CFG, $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, false, $cm);
    $itemid = (int)array_shift($args);
    $submission = $DB->get_record('assign_submission',
        ['id' => $itemid],
        'userid, assignment, groupid',
        MUST_EXIST
    );
    $userid = $submission->userid;
    $groupid = $submission->groupid;

    require_once($CFG->dirroot . '/mod/assign/locallib.php');

    $assign = new assign($context, $cm, $course);

    if ($assign->get_instance()->id != $submission->assignment) {
        send_file_not_found();
    }

    if ($assign->get_instance()->teamsubmission &&
        !$assign->can_view_group_submission($groupid)) {
        send_file_not_found();
    }

    if (!$assign->get_instance()->teamsubmission &&
        !$assign->can_view_submission($userid)) {
        send_file_not_found();
    }

    $relativepath = implode('/', $args);

    $fullpath = "/{$context->id}/assignsubmission_s3/$filearea/$itemid/$relativepath";

    $fs = get_file_storage();
    if (!($file = $fs->get_file_by_hash(sha1($fullpath))) || $file->is_directory()) {
        send_file_not_found();
    }

    $s3submission = assignsubmission_s3::get_record([
        'usermodified' => $userid,
        'assignment' => $cm->id,
        'submission' => $itemid,
    ]);
    if (!$s3submission) {
        send_file_not_found();
    }

    $s3 = new \assignsubmission_s3\s3();
    $object = new stdClass();
    $object->name = $file->get_filename();
    $object->mimetype = $file->get_mimetype();
    $object->uuid = $s3submission->get('uuid');
    $presignedurl = $s3->retrieve_object($object)->getUri();

    // Unlock session during file serving.
    manager::write_close();

    header("Location: $presignedurl");
    exit();
}
