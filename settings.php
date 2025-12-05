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
 * Settings file.
 *
 * @package   assignsubmission_s3
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2025
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/locallib.php');

$settings->add(
    new admin_setting_heading('connectionsettings', 'Connection settings', '')
);

$settings->add(
    new admin_setting_configcheckbox(
        'assignsubmission_s3/forceglobal',
        new lang_string('setting:forceglobal', 'assignsubmission_s3'),
        new lang_string('setting:forceglobal_help', 'assignsubmission_s3'),
        1,
    )
);

$settings->add(
    new admin_setting_configtext(
        'assignsubmission_s3/baseurl',
        new lang_string('setting:baseurl', 'assignsubmission_s3'),
        new lang_string('setting:baseurl_help', 'assignsubmission_s3'),
        '',
        PARAM_TEXT,
    )
);

$settings->add(
    new admin_setting_configtext(
        'assignsubmission_s3/region',
        new lang_string('setting:region', 'assignsubmission_s3'),
        new lang_string('setting:region_help', 'assignsubmission_s3'),
        '',
        PARAM_TEXT,
    )
);

$settings->add(
    new admin_setting_configtext(
        'assignsubmission_s3/bucket',
        new lang_string('setting:bucket', 'assignsubmission_s3'),
        new lang_string('setting:bucket_help', 'assignsubmission_s3'),
        '',
        PARAM_TEXT,
    )
);

$settings->add(
    new admin_setting_configpasswordunmask(
        'assignsubmission_s3/secret',
        new lang_string('setting:secret', 'assignsubmission_s3'),
        new lang_string('setting:secret_help', 'assignsubmission_s3'),
        '',
    )
);

$settings->add(
    new admin_setting_configtext(
        'assignsubmission_s3/key',
        new lang_string('setting:key', 'assignsubmission_s3'),
        new lang_string('setting:key_help', 'assignsubmission_s3'),
        '',
        PARAM_TEXT,
    )
);

$settings->add(
    new admin_setting_heading('glaciersettings', 'Glacier settings', '')
);

$settings->add(
    new admin_setting_configtext(
        'assignsubmission_s3/glaciertag',
        new lang_string('setting:glaciertag', 'assignsubmission_s3'),
        new lang_string('setting:glaciertag_help', 'assignsubmission_s3'),
        '',
        PARAM_TEXT,
    )
);

$settings->add(
    new admin_setting_configduration(
        'assignsubmission_s3/glacierduration',
        new lang_string('setting:glacierduration', 'assignsubmission_s3'),
        new lang_string('setting:glacierduration_help', 'assignsubmission_s3'),
        7 * DAYSECS,
    )
);

$settings->add(
    new admin_setting_configduration(
        'assignsubmission_s3/glacierrestoreduration',
        new lang_string('setting:glacierrestoreduration', 'assignsubmission_s3'),
        new lang_string('setting:glacierrestoreduration_help', 'assignsubmission_s3'),
        7 * DAYSECS,
    )
);

$settings->add(
    new admin_setting_heading('submissionsettings', 'Submission settings', '')
);

$settings->add(
    new admin_setting_configcheckbox(
        'assignsubmission_s3/default',
        new lang_string('setting:default', 'assignsubmission_s3'),
        new lang_string('setting:default_help', 'assignsubmission_s3'),
        1,
    )
);
//
//$settings->add(
//    new admin_setting_configtext(
//        'assignsubmission_s3/maxfiles',
//        new lang_string('setting:maxfiles', 'assignsubmission_s3'),
//        new lang_string('setting:maxfiles_help', 'assignsubmission_s3'),
//        assign_submission_s3::DEFAULT_MAXFILES,
//        PARAM_INT,
//    )
//);

$settings->add(
    new admin_setting_filetypes(
        'assignsubmission_s3/filetypes',
        new lang_string('setting:acceptedfiletypes', 'assignsubmission_s3'),
        new lang_string('setting:acceptedfiletypes_help', 'assignsubmission_s3'),
        '',
    )
);

$settings->add(
    new admin_setting_configtext(
        'assignsubmission_s3/maxbytesvalue',
        new lang_string('setting:maxbytesvalue', 'assignsubmission_s3'),
        new lang_string('setting:maxbytesvalue_help', 'assignsubmission_s3'),
        assign_submission_s3::DEFAULT_MAXBYTESVALUE,
        PARAM_INT,
    )
);

$settings->add(
    new admin_setting_configselect(
        'assignsubmission_s3/maxbytesunit',
        new lang_string('setting:maxbytesunit', 'assignsubmission_s3'),
        new lang_string('setting:maxbytesunit_help', 'assignsubmission_s3'),
        assign_submission_s3::SIZE_UNIT_GIGABYTE,
        [
            assign_submission_s3::SIZE_UNIT_KILOBYTE => new lang_string('sizekb'),
            assign_submission_s3::SIZE_UNIT_MEGABYTE => new lang_string('sizemb'),
            assign_submission_s3::SIZE_UNIT_GIGABYTE => new lang_string('sizegb'),
            assign_submission_s3::SIZE_UNIT_TERABYTE => new lang_string('sizetb'),
        ],
    )
);
