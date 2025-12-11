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
 * Scheduled task to add glacier tags to objects.
 *
 * @package   assignsubmission_s3
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2025
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_s3\task;

use assignsubmission_s3\persistent\assignsubmission_s3;
use assignsubmission_s3\s3;
use Aws\MockHandler;
use core\task\scheduled_task;
use DateTime;

class object_tag extends scheduled_task {

    /**
     * Get a descriptive name for the task (shown to admins)
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task:obect_tag', 'assignsubmission_s3');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute(?MockHandler $handler = null): void {
        // Get all standard status items where the items have been graded and 90 days have passed since upload.
        $time = new DateTime('90 days ago');

        $tag = get_config('assignsubmission_s3', 'glaciertag');

        /** @var assignsubmission_s3[] $records */
        $records = assignsubmission_s3::get_standard_graded_records_before($time->getTimestamp());
        foreach ($records as $record) {
            $assignment = $record->get_assign();
            $s3 = new s3($assignment, $handler);
            // Check the initial connection.
            if ($s3->has_error()) {
                mtrace("Unable to process tag for {$assignment->get_course_module()->id} due to: {$s3->get_error()}");
                continue;
            }
            // Add the tag and check for errors.
            $s3->add_tag($tag, $record->get('uuid'));
            if ($s3->has_error()) {
                mtrace("Unable to process tag for {$assignment->get_course_module()->id} due to: {$s3->get_error()}");
                continue;
            }
            // Update the status to glacier.
            $record->set('status', assignsubmission_s3::STATUS_REMOTE_GLACIER)->save();
            mtrace("Object: {$record->get('uuid')} tagged successfully");
        }
    }
}