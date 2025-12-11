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
require_once($CFG->dirroot . '/mod/assign/submission/s3/locallib.php');

use assign_submission_s3;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

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
            'filesize' => new external_value(PARAM_TEXT, 'The file size'),
        ]);
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'error' => new external_value(PARAM_BOOL, 'Was an error generated?', VALUE_REQUIRED),
            'error_title' => new external_value(PARAM_TEXT, 'Error title if error was generated', VALUE_OPTIONAL),
            'error_msg' => new external_value(PARAM_TEXT, 'Error message if error was generated', VALUE_OPTIONAL),
            's3_url' => new external_value(PARAM_URL, 'Pre-signed S3 URL.', VALUE_OPTIONAL),
        ]);
    }

    public static function execute(int $assignmentid, string $filename, string $mimetype, string $filesize): array {

        $error = assign_submission_s3::validate_submission($filename, $filesize);
        if (!empty($error)) {
            return [
                'error' => true,
                'error_title' => get_string('error'),
                'error_msg' => $error,
                's3url' => '',
            ];
        }

        [$error, $s3url] = assign_submission_s3::generate_pre_signed(
            $assignmentid,
            assign_submission_s3::create_submission($assignmentid, $filename, $mimetype)
        );

        return [
            'error' => !empty($error),
            'error_title' => get_string('error'),
            'error_msg' => $error,
            's3_url' => $s3url,
        ];
    }
}
