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

use assignsubmission_s3\admin\admin_setting_config_size;
use assignsubmission_s3\event\assessable_uploaded;
use assignsubmission_s3\event\submission_created;
use assignsubmission_s3\event\submission_updated;
use assignsubmission_s3\persistent\assignsubmission_s3;
use assignsubmission_s3\s3;
use core_form\filetypes_util;

/**
 * Library class for S3 submission plugin extending submission plugin base class.
 *
 * @package   assignsubmission_s3
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2025
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_s3 extends assign_submission_plugin {
    /**
     * The file component to store file records in.
     */
    public const FILECOMPONENT = 'assignsubmission_s3';

    /**
     * The filearea to store file records in.
     */
    public const FILEAREA = 'submission_s3';

    /**
     * The size of the chunks we are uploading.
     */
    public const FILECHUNKS = 10 * 1024 * 1024;

    /**
     * The name of the submission plugin.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('s3', 'assignsubmission_s3');
    }

    /**
     * Get the form elements for the user to submit their file.
     *
     * @param mixed $submissionorgrade
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @param $userid
     * @return void
     */
    public function get_form_elements_for_user($submissionorgrade, MoodleQuickForm $mform, stdClass $data, $userid): void {
        global $OUTPUT, $PAGE;

        $PAGE->requires->js_call_amd(
            'assignsubmission_s3/upload',
            'init',
            [
                $this->assignment->get_course_module()->id,
                self::FILECHUNKS,
            ]
        );
        $mform->addElement(
            'html',
            $OUTPUT->render_from_template(
                'assignsubmission_s3/form/file',
                [
                    'accept' => implode(',', self::get_filetypes()),
                    'size' => self::get_maxfilesize(),
                    'humansize' => self::get_human_readable_size(self::get_maxfilesize()),
                ]
            ),
        );
    }

    /**
     * Settings form elements for the assignment settings page.
     *
     * @param MoodleQuickForm $mform
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform): void {
        global $OUTPUT;

        $config = get_config('assignsubmission_s3');

        if (!empty($config->forceglobal)) {
            if (empty($config->region) || empty($config->bucket) || empty($config->secret) || empty($config->key)) {
                $mform->addElement(
                    'static',
                    'misconfigured',
                    '',
                    $OUTPUT->notification(get_string('error:misconfigured', 'assignsubmission_s3'), 'error')
                );
                return;
            }
            // Static element doesn't allow hideif so we use a group to do this (MDL-66251).
            $group = [];
            $group[] = $mform->createElement(
                'static',
                'assignsubmission_s3_label',
                '',
                $OUTPUT->notification(get_string('activitysetting:globalwarning', 'assignsubmission_s3'), 'info')
            );
            $mform->addGroup($group, 's3labelgroup', '', ' ', false);
            $mform->hideIf('s3labelgroup', 'assignsubmission_s3_enabled');
        }

        $mform->hideIf('allowsubmissionsfromdate', 'assignsubmission_s3_enabled', 'checked');
        $notification = $mform->createElement(
            'static',
            's3fromdatenotification',
            '',
            $OUTPUT->notification(
                get_string('activitysetting:noallowsubmissionsfromdate', 'assignsubmission_s3'),
                'info',
            )
        );
        $mform->insertElementBefore($notification, 'allowsubmissionsfromdate');
        $mform->hideIf('s3fromdatenotification', 'assignsubmission_s3_enabled');

        // Only output if force global not enabled.
        if (empty($config->forceglobal)) {
            // Add the base URL field.
            $mform->addElement(
                'text',
                'baseurl',
                get_string('activitysetting:baseurl', 'assignsubmission_s3'),
                ['maxlength' => 255, 'size' => 50],
            );
            $mform->setType('baseurl', PARAM_URL);
            $mform->setDefault('baseurl', !empty($this->get_config('baseurl')) ? $this->get_config('baseurl') : '');
            $mform->addHelpButton('baseurl', 'activitysetting:baseurl', 'assignsubmission_s3');
            $mform->hideIf('baseurl', 'assignsubmission_s3_enabled');

            if (!$this->can_configure()) {
                $mform->freeze(['baseurl']);
            }

            if ($this->can_configure()) {
                // Add the bucket field.
                $mform->addElement(
                    'text',
                    'bucket',
                    get_string('activitysetting:bucket', 'assignsubmission_s3'),
                    ['maxlength' => 255, 'size' => 50],
                );
                $mform->setType('bucket', PARAM_TEXT);
                $mform->setDefault('bucket', !empty($this->get_config('bucket')) ? $this->get_config('bucket') : '');
                $mform->addHelpButton('bucket', 'activitysetting:bucket', 'assignsubmission_s3');
                $mform->hideIf('bucket', 'assignsubmission_s3_enabled');

                // Add the region field.
                $mform->addElement(
                    'text',
                    'region',
                    get_string('activitysetting:region', 'assignsubmission_s3'),
                    ['maxlength' => 255, 'size' => 50],
                );
                $mform->setType('region', PARAM_ALPHANUM);
                $mform->setDefault('region', !empty($this->get_config('region')) ? $this->get_config('region') : '');
                $mform->addHelpButton('region', 'activitysetting:region', 'assignsubmission_s3');
                $mform->hideIf('region', 'assignsubmission_s3_enabled');

                // Add the key field.
                $mform->addElement(
                    'text',
                    'key',
                    get_string('activitysetting:key', 'assignsubmission_s3'),
                    ['maxlength' => 255, 'size' => 50],
                );
                $mform->setType('key', PARAM_ALPHANUM);
                $mform->setDefault('key', !empty($this->get_config('key')) ? $this->get_config('key') : '');
                $mform->addHelpButton('key', 'activitysetting:key', 'assignsubmission_s3');
                $mform->hideIf('key', 'assignsubmission_s3_enabled');

                // Add the secret field.
                $mform->addElement(
                    'passwordunmask',
                    'secret',
                    get_string('activitysetting:secret', 'assignsubmission_s3'),
                    ['maxlength' => 255, 'size' => 50],
                );
                $mform->setType('secret', PARAM_ALPHANUM);
                $mform->setDefault('secret', !empty($this->get_config('secret')) ? $this->get_config('secret') : '');
                $mform->addHelpButton('secret', 'activitysetting:secret', 'assignsubmission_s3');
                $mform->hideIf('secret', 'assignsubmission_s3_enabled');
            }
        }
    }

    /**
     * Check if the current user can configure the plugin in the provided context.
     *
     * @return bool
     */
    public function can_configure(): bool {
        global $PAGE;

        return has_capability('assignsubmission/s3:configure', $PAGE->context);
    }

    /**
     * Validate and save the form settings.
     *
     * @param stdClass $formdata
     * @return bool
     */
    public function save_settings(stdClass $formdata): bool {
        global $DB;

        $config = get_config('assignsubmission_s3');

        $instance = $this->assignment->get_instance();
        // Update the instance with the 14 day window for submissions from date using due date.
        if ($instance->duedate > 0) {
            $instance->allowsubmissionsfromdate = $instance->duedate - (2 * WEEKSECS);
            $DB->update_record('assign', $instance);
        }

        if (empty($config->forceglobal) && $this->can_configure()) {
            $this->set_config('baseurl', $formdata->baseurl);
            $this->set_config('bucket', $formdata->bucket);
            $this->set_config('region', $formdata->region);
            $this->set_config('key', $formdata->key);
            $this->set_config('secret', $formdata->secret);
        }
        return true;
    }

    /**
     * Save any custom data for this form submission.
     * As we would have already processed the file, this is just event triggers.
     *
     * @param stdClass $submissionorgrade
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $submissionorgrade, stdClass $data): bool {
        global $USER, $DB;

        // Trigger all events.
        $params = [
            'context' => context_module::instance($this->assignment->get_course_module()->id),
            'courseid' => $this->assignment->get_course()->id,
            'objectid' => $submissionorgrade->id,
            'other' => [
                'content' => '',
                'pathnamehashes' => [],
            ],
        ];
        if (!empty($submissionorgrade->userid) && ($submissionorgrade->userid != $USER->id)) {
            $params['relateduserid'] = $submissionorgrade->userid;
        }
        if ($this->assignment->is_blind_marking()) {
            $params['anonymous'] = 1;
        }
        $event = assessable_uploaded::create($params);
        $event->trigger();

        $groupname = null;
        $groupid = 0;
        // Get the group name as other fields are not transcribed in the logs and this information is important.
        if (empty($submissionorgrade->userid) && !empty($submissionorgrade->groupid)) {
            $groupname = $DB->get_field('groups', 'name', ['id' => $submissionorgrade->groupid], MUST_EXIST);
            $groupid = $submissionorgrade->groupid;
        } else {
            $params['relateduserid'] = $submissionorgrade->userid;
        }

        // Unset the objectid and other field from params for use in submission events.
        unset($params['objectid']);
        unset($params['other']);
        $params['other'] = [
            'submissionid' => $submissionorgrade->id,
            'submissionattempt' => $submissionorgrade->attemptnumber,
            'submissionstatus' => $submissionorgrade->status,
            'groupid' => $groupid,
            'groupname' => $groupname,
        ];

        $filesubmission = $this->get_file_submission($submissionorgrade->id);
        if ($filesubmission->get('status') === assignsubmission_s3::STATUS_REMOTE_STANDARD) {
            // If the status is remote then we're updating an existing submission.
            $filesubmission->save();

            $params['objectid'] = $filesubmission->get('id');

            $event = submission_updated::create($params);
            $event->set_assign($this->assignment);
            $event->trigger();
            return true;
        } else {
            // Otherwise it's a new file we've processed, so set the status to now be remote.
            $filesubmission->set('status', assignsubmission_s3::STATUS_REMOTE_STANDARD);
            $filesubmission->save();
            $params['objectid'] = $filesubmission->get('id');

            $event = submission_created::create($params);
            $event->set_assign($this->assignment);
            $event->trigger();
            return $params['objectid'] > 0;
        }
    }

    /**
     * Remove the submission.
     *
     * @param stdClass $submission
     * @return void
     * @throws coding_exception
     */
    public function remove(stdClass $submission): void {
        // Delete the custom table reference which triggers the deletion of the file in the bucket and stub files.
        if ($s3submission = $this->get_file_submission($submission->id)) {
            $s3submission->delete();
        }
    }

    /**
     * Return the file(s) for the users submission.
     *
     * @param stdClass $submissionorgrade
     * @param stdClass $user
     * @return array
     */
    public function get_files(stdClass $submissionorgrade, stdClass $user): array {
        return assignsubmission_s3::get_records([
            'usermodified' => $user->id,
            'assignment' => $this->assignment->get_course_module()->id,
            'submission' => $submissionorgrade->id,
        ]);
    }

    /**
     * The assignment has been deleted - remove the plugin specific data
     *
     * @return bool
     */
    public function delete_instance(): bool {
        $records = assignsubmission_s3::get_records(['assignment' => $this->assignment->get_course_module()->id]);
        foreach ($records as $record) {
            $record->delete();
        }
        return true;
    }

    /**
     * Return if the plugin is in use or not.
     *
     * @param stdClass $submissionorgrade
     * @return bool
     */
    public function is_empty(stdClass $submissionorgrade): bool {
        $records = assignsubmission_s3::get_records(['assignment' => $this->assignment->get_course_module()->id]);
        return empty($records);
    }

    /**
     * Return true if there was no S3 submission.
     *
     * @param stdClass $data
     * @return bool
     */
    public function submission_is_empty(stdClass $data): bool {
        global $USER;

        $records = assignsubmission_s3::get_records([
            'usermodified' => $USER->id,
            'assignment' => $this->assignment->get_course_module()->id,
        ]);
        return empty($records);
    }

    /**
     * Get file submission information from the database.
     *
     * @param int $submissionid
     * @return assignsubmission_s3|false
     */
    private function get_file_submission(int $submissionid): assignsubmission_s3|false {
        return assignsubmission_s3::get_record(['submission' => $submissionid]);
    }

    /**
     * Return the view summary for the
     *
     * @param stdClass $submissionorgrade
     * @param $showviewlink
     * @return string
     */
    public function view_summary(stdClass $submissionorgrade, &$showviewlink): string {
        return $this->assignment->render_area_files('assignsubmission_s3', self::FILEAREA, $submissionorgrade->id);
    }

    /**
     * Validate the submissions size and type
     *
     * @param string $filename
     * @param string $filesize
     * @return string
     */
    public static function validate_submission(string $filename, string $filesize): string {
        if (!self::is_allowed_filesize((int) $filesize)) {
            return get_string(
                'error:filesize',
                'assignsubmission_s3',
                self::get_human_readable_size(self::get_maxfilesize()),
            );
        }

        if (!self::is_allowed_filetype($filename)) {
            return get_string('error:filetype', 'assignsubmission_s3', $filename);
        }
        return '';
    }

    /**
     * Generate the stub file and assignsubmission_s3 record.
     *
     * @param $assignmentid
     * @param $filename
     * @param $mimetype
     * @return assignsubmission_s3
     */
    public static function create_submission($assignmentid, $filename, $mimetype): assignsubmission_s3 {
        global $USER;

        $context = context_module::instance($assignmentid);
        $assignment = new assign($context, null, null);

        // Get the users submission (creating one if one doesn't exist).
        $instance = $assignment->get_instance();

        if ($instance->teamsubmission) {
            $submission = $assignment->get_group_submission($USER->id, 0, true);
        } else {
            $submission = $assignment->get_user_submission($USER->id, true);
        }
        $fs = get_file_storage();
        if (!$fs->file_exists($context->id, self::FILECOMPONENT, self::FILEAREA, $submission->id, '/', $filename)) {
            $fs->delete_area_files($context->id, self::FILECOMPONENT, self::FILEAREA, $submission->id);

            // Create a file with an empty string so that we can store the mime/type, name, and size for download.
            $filerecord = new stdClass();
            $filerecord->contextid = $context->id;
            $filerecord->component = self::FILECOMPONENT;
            $filerecord->filearea = self::FILEAREA;
            $filerecord->itemid = $submission->id;
            $filerecord->filepath = '/';
            $filerecord->filename = $filename;
            $filerecord->mimetype = $mimetype;

            $fs->create_file_from_string($filerecord, '');
        }
        $s3submission = assignsubmission_s3::get_record([
            'usermodified' => $USER->id,
            'assignment' => $assignmentid,
            'submission' => $submission->id,
        ]);
        if (!$s3submission) {
            // Saving the new record will create a UUID for the user on this assignment.
            $s3submission = new assignsubmission_s3();
            $s3submission->set_many([
                'assignment' => $assignmentid,
                'submission' => $submission->id,
            ]);
            $s3submission->save();
        }
        return $s3submission;
    }

    /**
     * Generate the pre-signed url.
     *
     * @param int $assignmentid
     * @param assignsubmission_s3 $s3submission
     * @param int $filesize
     * @param null $handler
     * @return array An array of an error string and/or the pre-signed url and upload ID.
     * @throws coding_exception
     */
    public static function generate_pre_signed(
        int $assignmentid,
        assignsubmission_s3 $s3submission,
        int $filesize,
        $handler = null
    ): array {
        $context = context_module::instance($assignmentid);
        $assignment = new assign($context, null, null);

        $s3 = new s3($assignment, $handler);
        // If the connection failed (due to permissions or config) return an error.
        if ($s3->has_error()) {
            return [
                $s3->get_error(),
                '',
            ];
        }
        $s3urls = [];
        $parts = ceil($filesize / self::FILECHUNKS);
        $key = $s3submission->get('uuid');
        for ($partNumber = 1; $partNumber <= $parts; $partNumber++) {
            $request = $s3->create_presigned_part_upload_requests(
                $key,
                $partNumber,
            );
            $s3urls[] = (string)$request->getUri();
        }

        return [
            '',
            $s3urls,
            $s3->get_mulitpart_upload_id($key),
        ];
    }

    /**
     * Get the max allowed filesize for the upload.
     *
     * @return int
     */
    public static function get_maxfilesize(): int {
        return get_config('assignsubmission_s3', 'maxsize');
    }

    /**
     * Get the configured filetypes normalized.
     *
     * @return array
     */
    public static function get_filetypes(): array {
        $filetypes = get_config('assignsubmission_s3', 'filetypes');
        $util = new filetypes_util();
        return $util->normalize_file_types($filetypes);
    }

    /**
     * Get the human readable size of the bytes provided.
     *
     * @param int $size
     * @return string
     */
    public static function get_human_readable_size(int $size): string {
        $i = floor(log($size) / log(1024));
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        return sprintf('%.02F', $size / pow(1024, $i)) * 1 . ' ' . $sizes[$i];
    }

    /**
     * Check if the file provided is of the right type to be uploaded.
     *
     * @param string $filename
     * @return bool
     */
    public static function is_allowed_filetype(string $filename): bool {
        return (new filetypes_util())->is_allowed_file_type($filename, self::get_filetypes());
    }

    /**
     * Check if the file provided is of the right size to be uploaded.
     *
     * @param int $filesize
     * @return bool
     */
    public static function is_allowed_filesize(int $filesize): bool {
        return $filesize <= self::get_maxfilesize();
    }
}
