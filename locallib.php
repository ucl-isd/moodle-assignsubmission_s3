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
     * Default max files.
     */
    public const DEFAULT_MAXFILES = 1;

    /**
     * Default max bytes value.
     */
    public const DEFAULT_MAXBYTESVALUE = 4;

    /**
     * Default max bytes unit.
     */
    public const DEFAULT_MAXBYTESUNIT = self::SIZE_UNIT_GIGABYTE;

    /**
     * File size KB.
     */
    public const SIZE_UNIT_KILOBYTE = 1024;

    /**
     * File size MB.
     */
    public const SIZE_UNIT_MEGABYTE = self::SIZE_UNIT_KILOBYTE * 1024;

    /**
     * File size GB.
     */
    public const SIZE_UNIT_GIGABYTE = self::SIZE_UNIT_MEGABYTE * 1024;

    /**
     * File size TB.
     */
    public const SIZE_UNIT_TERABYTE = self::SIZE_UNIT_GIGABYTE * 1024;

    /**
     * The filearea to store file records in.
     */
    public const FILEAREA = 'submission_s3';

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

        $fileoptions = $this->get_file_options();

        file_prepare_standard_filemanager($data,
            'files',
            $fileoptions,
            $this->assignment->get_context(),
            'assignsubmission_s3',
            self::FILEAREA,
            $submissionorgrade ? $submissionorgrade->id : 0
        );
        $mform->addElement('filemanager', 's3_file', $this->get_name(), null, $fileoptions, null);
    }

    /**
     * Get the file options for the filemanager.
     *
     * @return array
     */
    private function get_file_options(): array {

        $config = get_config('assignsubmission_s3');

        $maxbytesvalue = $config->maxbytesvalue ?? self::DEFAULT_MAXBYTESVALUE;
        $maxbytesunit = $config->maxbytesunit ?? self::DEFAULT_MAXBYTESUNIT;
        $maxfiles = $config->maxfiles ?? self::DEFAULT_MAXFILES;
        return [
            'subdirs' => 1,
            'maxbytes' => $maxbytesvalue * $maxbytesunit,
            'maxfiles' => $maxfiles,
            'accepted_types' => $this->get_configured_typesets(),
            'return_types' => (FILE_INTERNAL | FILE_CONTROLLED_LINK)
        ];
    }

    /**
     * Get the supported filetypes.
     *
     * @return array
     */
    private function get_configured_typesets(): array {
        return (new filetypes_util())->normalize_file_types((string)$this->get_config('filetypes'));
    }

    public function get_settings(MoodleQuickForm $mform): void {
        global $CFG, $OUTPUT;

        require_once($CFG->dirroot . '/mod/assign/submission/maharaws/lib.php');

        $config = get_config('assignsubmission_s3');

        if (!empty($config->forceglobal)) {
            if (empty($config->region) || empty($config->bucket) || empty($config->secret) || empty($config->key)) {
                return;
            }
            // Static elemment doesn't allow hideif so we use a group to do this (MDL-66251).
            $group = [];
            $group[] = $mform->createElement('static', 'assignsubmission_s3_label',
                get_string('activitysetting:gclabel', 'assignsubmission_s3'),
                get_string('activitysetting:gcwarning', 'assignsubmission_s3'));
            $mform->addGroup($group, 's3labelgroup', '', ' ', false);
            $mform->hideIf('s3labelgroup', 'assignsubmission_s3_enabled');
        }

        $mform->hideIf('allowsubmissionsfromdate', 'assignsubmission_s3_enabled', 'checked');
        $notification = $mform->createElement(
            'static',
            's3fromdatenotification',
            '',
            $OUTPUT->notification(get_string('activitysetting:noallowsubmissionsfromdate', 'assignsubmission_s3'), 'info', false)
        );
        $mform->insertElementBefore($notification, 'allowsubmissionsfromdate');
        $mform->hideIf('s3fromdatenotification', 'assignsubmission_s3_enabled',);

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

    public function save(stdClass $submissionorgrade, stdClass $data) {
        return parent::save($submissionorgrade, $data); // TODO: Change the autogenerated stub
    }

    public function remove(stdClass $submission) {
        parent::remove($submission); // TODO: Change the autogenerated stub
    }

    public function get_files(stdClass $submissionorgrade, stdClass $user) {
        return parent::get_files($submissionorgrade, $user); // TODO: Change the autogenerated stub
    }

    public function delete_instance() {
        return parent::delete_instance(); // TODO: Change the autogenerated stub
    }

    public function is_empty(stdClass $submissionorgrade) {
        return parent::is_empty($submissionorgrade); // TODO: Change the autogenerated stub
    }

    public function submission_is_empty(stdClass $data) {
        return parent::submission_is_empty($data); // TODO: Change the autogenerated stub
    }

    public function get_file_areas() {
        return parent::get_file_areas(); // TODO: Change the autogenerated stub
    }

}