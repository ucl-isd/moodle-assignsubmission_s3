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

namespace assignsubmission_s3\privacy;

use assignsubmission_s3\persistent\assignsubmission_s3;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use mod_assign\privacy\assign_plugin_request_data;
use mod_assign\privacy\assignsubmission_provider;
use mod_assign\privacy\assignsubmission_user_provider;
use mod_assign\privacy\useridlist;

/**
 * Data provider class.
 *
 * @package   assignsubmission_s3
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2025
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements assignsubmission_provider, assignsubmission_user_provider, \core_privacy\local\metadata\provider {
    /**
     * This is covered by mod_assign provider.
     *
     * @param  int $userid The user ID that we are finding contexts for.
     * @param  contextlist $contextlist A context list to add sql and params to for contexts.
     */
    public static function get_context_for_userid_within_submission(int $userid, contextlist $contextlist) {
    }

    /**
     * This is covered by the mod_assign providers.
     *
     * @param useridlist $useridlist An object for obtaining user IDs of students.
     */
    public static function get_student_user_ids(useridlist $useridlist) {
    }

    /**
     * This is covered by the mod_assign providers.
     *
     * @param  userlist $userlist The userlist object
     */
    public static function get_userids_from_context(userlist $userlist) {
    }

    /**
     * This method is used to export any user data this sub-plugin has using the assign_plugin_request_data object to get the
     * context and userid.
     * assign_plugin_request_data contains:
     * - context
     * - submission object
     * - current path (subcontext)
     * - user object
     *
     * @param assign_plugin_request_data $exportdata
     * @return void
     */
    public static function export_submission_user_data(assign_plugin_request_data $exportdata): void {
        if ($exportdata->get_user() != null) {
            return;
        }
        $user = new \stdClass();
        $assign = $exportdata->get_assign();
        $plugin = $assign->get_plugin_by_type('assignsubmission', 's3');
        $files = $plugin->get_files($exportdata->get_pluginobject(), $user);
        foreach ($files as $file) {
            writer::with_context($exportdata->get_context())->export_file($exportdata->get_subcontext(), $file);
        }
    }

    /**
     * Any call to this method should delete all user data for the context defined in the deletion_criteria.
     * assign_plugin_request_data contains:
     * - context
     * - assign object
     *
     * @param assign_plugin_request_data $requestdata Information to use to delete user data for this submission.
     */
    public static function delete_submission_for_context(assign_plugin_request_data $requestdata) {
        // Delete records from assignsubmission_file table.
        $s3submissions = assignsubmission_s3::get_records([
            'assignment' => $requestdata->get_assign()->get_instance()->id,
        ]);
        foreach ($s3submissions as $s3submission) {
            $s3submission->delete();
        }
    }

    /**
     * A call to this method should delete user data (where practicle) from the userid and context.
     * assign_plugin_request_data contains:
     * - context
     * - submission object
     * - user object
     * - assign object
     *
     * @param  assign_plugin_request_data $exportdata Details about the user and context to focus the deletion.
     */
    public static function delete_submission_for_userid(assign_plugin_request_data $exportdata) {
        $submissionid = $exportdata->get_pluginobject()->id;

        // Delete records from assignsubmission_file table.
        $s3submissions = assignsubmission_s3::get_records([
            'submission' => $submissionid,
        ]);
        foreach ($s3submissions as $s3submission) {
            $s3submission->delete();
        }
    }

    /**
     * Deletes all submissions for the submission ids / userids provided in a context.
     * assign_plugin_request_data contains:
     * - context
     * - assign object
     * - submission ids (pluginids)
     * - user ids
     * @param  assign_plugin_request_data $deletedata A class that contains the relevant information required for deletion.
     */
    public static function delete_submissions(assign_plugin_request_data $deletedata) {
        global $DB;
        if (empty($deletedata->get_submissionids())) {
            return;
        }

        [$sql, $params] = $DB->get_in_or_equal($deletedata->get_submissionids(), SQL_PARAMS_NAMED);

        // Delete records from assignsubmission_file table.
        $s3submissions = assignsubmission_s3::get_records_select("submission $sql", $params);
        foreach ($s3submissions as $s3submission) {
            $s3submission->delete();
        }
    }

    /**
     * Return meta data about this plugin.
     *
     * @param  collection $collection A list of information to add to.
     * @return collection Return the collection after adding to it.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->link_subsystem('core_files', 'privacy:metadata:filepurpose');
        return $collection;
    }
}
