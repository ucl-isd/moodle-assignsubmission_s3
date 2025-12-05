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

namespace assignsubmission_s3\external;

global $CFG;
require_once($CFG->dirroot . '/mod/assign/locallib.php');

use assign;
use assign_submission_s3;
use assignsubmission_s3\persistent\assignsubmission_s3;
use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use assignsubmission_s3\s3;
use stdClass;

/**
 * External service to get a generated pre-signed S3 URL.
 *
 * @package   assignsubmission_s3
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2025
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_s3_presigned extends external_api {

    /**
     * Describes the parameters for update_category_order webservice.
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'assignmentid' => new external_value(PARAM_INT, 'The assignment ID'),
            'filename' => new external_value(PARAM_TEXT, 'The file name'),
            'mimetype' => new external_value(PARAM_TEXT, 'The file mime type'),
        ]);
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            's3_url' => new external_value(PARAM_URL, 'Pre-signed S3 URL.'),
        ]);
    }

    public static function execute(int $assignmentid, string $filename, string $mimetype): array {
        global $USER;

        $context = context_module::instance($assignmentid);

        // Get the users submission (creating one if one doesn't exist).
        $assignment = new assign($context, null, null);
        $submission = $assignment->get_user_submission($USER->id, true);

        $fs = get_file_storage();
        $fs->delete_area_files($context->id, assign_submission_s3::FILECOMPONENT, assign_submission_s3::FILEAREA, $submission->id);

        // Create a file with an empty string so that we can store the mime/type, name, and size for download.
        $filerecord = new stdClass();
        $filerecord->contextid = $context->id;
        $filerecord->component = assign_submission_s3::FILECOMPONENT;
        $filerecord->filearea = assign_submission_s3::FILEAREA;
        $filerecord->itemid = $submission->id;
        $filerecord->filepath = '/';
        $filerecord->filename = $filename;
        $filerecord->mimetype = $mimetype;

        $fs->create_file_from_string($filerecord, '');

        $s3submission = assignsubmission_s3::get_record([
            'usermodified' => $USER->id,
            'assignment' => $assignmentid,
            'submission' => $submission->id,
        ]);
        if (!$s3submission) {
            // Saving the new record will create a UUID for the user on this asignment.
            $s3submission = new assignsubmission_s3();
            $s3submission->set_many([
                'assignment' => $assignmentid,
                'submission' => $submission->id,
            ]);
            $s3submission->save();
        }

        $s3 = new s3();
        $request = $s3->create_presigned_request($s3submission->get('uuid'));

        // TODO Trigger event.

        return ['s3_url' => (string) $request->getUri()];
    }
}
