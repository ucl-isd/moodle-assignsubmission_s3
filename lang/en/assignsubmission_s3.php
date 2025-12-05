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
 * Language file.
 *
 * @package   assignsubmission_s3
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2025
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['activitysetting:baseurl'] = 'Base URL';
$string['activitysetting:baseurl_help'] = 'Alternate URL for S3 compatible endpoints. Leave blank for normal S3 use';
$string['activitysetting:bucket'] = 'Bucket';
$string['activitysetting:bucket_help'] = 'Amazon S3 bucket to store files in';
$string['activitysetting:gclabel'] = 'Global credentials';
$string['activitysetting:gcwarning'] = 'S3 credentials have been set globally';
$string['activitysetting:key'] = 'Key';
$string['activitysetting:key_help'] = 'Amazon S3 key credential';
$string['activitysetting:noallowsubmissionsfromdate'] = 'As S3 submissions is enabled "Allow submissions from date" will be set automatically to 14 days prior to due date';
$string['activitysetting:region'] = 'Region';
$string['activitysetting:region_help'] = 'Amazon S3 API gateway region';
$string['activitysetting:secret'] = 'Secret';
$string['activitysetting:secret_help'] = 'Amazon S3 secret credential';
$string['enabled'] = 'S3 submissions';
$string['enabled_help'] = 'If enabled, students are able to upload one or more files as their submission to S3.';
$string['event:assessable_uploaded:description'] = 'The user with id "{$a->userid}" has uploaded a file to the submission with id "{$a->objectid}" in the assignment activity with course module id "{$a->contextinstanceid}"';
$string['event:object_requested:description'] = 'The user with id "{$a->userid}" has requested the file for the submission with id "{$a->objectid}" in the assignment activity with course module id "{$a->contextinstanceid}"';
$string['event:submission_created:description'] = 'The user with id "{$a->userid}" created an S3 file submission and uploaded a file in the assignment with course module id "{$a->contextinstanceid}"';
$string['event:submission_created:description_group'] = 'The user with id "{$a->userid}" created an S3 file submission and uploaded a file in the assignment with course module id "{$a->contextinstanceid}" for the group with id "{$a->groupid}"';
$string['event:submission_updated:description'] = 'The user with id "{$a->userid}" updated an S3 file submission and uploaded a file in the assignment with course module id "{$a->contextinstanceid}"';
$string['event:submission_updated:description_group'] = 'The user with id "{$a->userid}" updated an S3 file submission and uploaded a file in the assignment with course module id "{$a->contextinstanceid}" for the group with id "{$a->groupid}"';
$string['pluginname'] = 'S3 submissions';
$string['s3:configure'] = 'Configure S3 submission';
$string['s3'] = 'S3 submission';
$string['setting:acceptedfiletypes'] = 'Default accepted file types';
$string['setting:acceptedfiletypes_help'] = 'Accepted file types can be restricted by entering a list of file extensions. If the field is left empty, then all file types are allowed.';
$string['setting:baseurl'] = 'Base URL';
$string['setting:baseurl_help'] = 'Alternate URL for S3 compatible endpoints. Leave blank for normal S3 use';
$string['setting:bucket'] = 'Bucket';
$string['setting:bucket_help'] = 'Amazon S3 bucket to store files in';
$string['setting:default'] = 'Enabled by default';
$string['setting:default_help'] = 'If set, this submission method will be enabled by default for all new assignments.';
$string['setting:forceglobal'] = 'Force global credentials';
$string['setting:forceglobal_help'] = 'Always use these credentials to connect to S3';
$string['setting:glacierduration'] = 'Glacier duration';
$string['setting:glacierduration_help'] = 'Length of time once a submission has been marked to move the file ';
$string['setting:glacierrestoreduration'] = 'Glacier restore duration';
$string['setting:glacierrestoreduration_help'] = 'When an object is restored from Glacier storage it will only be available for teh period defined here. This will also be included in the notification to the user once the file is available.';
$string['setting:glaciertag'] = 'Glacier tag';
$string['setting:glaciertag_help'] = 'Tag to add to objects to move to glacier storage with AWS lifecycle policy';
$string['setting:key'] = 'Key';
$string['setting:key_help'] = 'Amazon S3 key credential';
$string['setting:maxfiles'] = 'Maximum files per submission';
$string['setting:maxfiles_help'] = 'If S3 submissions are enabled, each assignment can be set to accept up to this number of files for their submission.';
$string['setting:maxbytesunit'] = 'Maximum submission size unit';
$string['setting:maxbytesunit_help'] = '';
$string['setting:maxbytesvalue'] = 'Maximum submission size value';
$string['setting:maxbytesvalue_help'] = 'Files uploaded by students may be up to this size (used in conjunction with below unit setting).';
$string['setting:region'] = 'Region';
$string['setting:region_help'] = 'Amazon S3 API gateway region';
$string['setting:secret'] = 'Secret';
$string['setting:secret_help'] = 'Amazon S3 secret credential';

