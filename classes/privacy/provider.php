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
 * TODO Add description
 *
 * @package   assignsubmission_s3
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2025
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_s3\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use mod_assign\privacy\assign_plugin_request_data;
use mod_assign\privacy\assignsubmission_provider;
use mod_assign\privacy\assignsubmission_user_provider;
use mod_assign\privacy\useridlist;

class provider implements assignsubmission_provider, assignsubmission_user_provider, \core_privacy\local\metadata\provider {
    public static function get_context_for_userid_within_submission(int $userid, contextlist $contextlist) {
        // TODO: Implement get_context_for_userid_within_submission() method.
    }

    public static function get_student_user_ids(useridlist $useridlist) {
        // TODO: Implement get_student_user_ids() method.
    }

    public static function export_submission_user_data(assign_plugin_request_data $exportdata) {
        // TODO: Implement export_submission_user_data() method.
    }

    public static function delete_submission_for_context(assign_plugin_request_data $requestdata) {
        // TODO: Implement delete_submission_for_context() method.
    }

    public static function delete_submission_for_userid(assign_plugin_request_data $exportdata) {
        // TODO: Implement delete_submission_for_userid() method.
    }

    public static function get_userids_from_context(userlist $userlist) {
        // TODO: Implement get_userids_from_context() method.
    }

    public static function delete_submissions(assign_plugin_request_data $deletedata) {
        // TODO: Implement delete_submissions() method.
    }

    public static function get_metadata(collection $collection): collection {
        // TODO: Implement get_metadata() method.
    }
}
