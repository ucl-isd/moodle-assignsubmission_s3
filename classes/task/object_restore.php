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

namespace assignsubmission_s3\task;

use assignsubmission_s3\persistent\assignsubmission_s3;
use assignsubmission_s3\persistent\assignsubmission_s3_requests;
use assignsubmission_s3\s3;
use Aws\MockHandler;
use core\message\message;
use core\task\scheduled_task;
use core_user;
use moodle_url;

/**
 * Scheduled task to restore requested objects.
 *
 * @package   assignsubmission_s3
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2025
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class object_restore extends scheduled_task {
    /**
     * Get a descriptive name for the task (shown to admins)
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task:object_restore', 'assignsubmission_s3');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     *
     * @param MockHandler|null $handler
     * @return void
     */
    public function execute(?MockHandler $handler = null): void {
        // Get all requested items: STATUS_REMOTE_REQUESTED.
        $requestedobjects = assignsubmission_s3::get_requested();

        foreach ($requestedobjects as $requestedobject) {
            // Call the S3 API to retrieve the object.
            $s3 = new s3($requestedobject->get_assign(), $handler);
            if ($s3->has_error()) {
                mtrace($s3->get_error());
                return;
            }
            $restored = $s3->restore_object($requestedobject->get('uuid'));
            if ($restored['status']) {
                // If restored and have an expiration date update status to restored and add an expiration date.
                if ($restored['expiry-date']) {
                    mtrace("Object: {$requestedobject->get('uuid')} restore complete");
                    $requestedobject->set_many([
                        'status' => assignsubmission_s3::STATUS_REMOTE_RESTORED,
                        'expiry' => $restored['expiry-date'],
                    ]);
                    $requestedobject->save();
                    // Notify all requesters that the file is ready to be downloaded.
                    $requests = assignsubmission_s3_requests::get_records(['ass3id' => $requestedobject->get('id')]);
                    $messagesubject = get_string('message:restored:subject', 'assignsubmission_s3');
                    $assign = $requestedobject->get_assign();
                    $url = new moodle_url('/mod/assign/view.php', ['id' => $assign->get_course_module()->id]);
                    $messagebody = get_string(
                        'message:restored:body',
                        'assignsubmission_s3',
                        [
                            'course' => $assign->get_course()->fullname,
                            'assignment' => $assign->get_course_module()->get_name(),
                            'link' => $url->out(),
                            'expiry' => userdate($restored['expiry-date']),
                        ],
                    );
                    $message = new message();
                    $message->component = 'assignsubmission_s3';
                    $message->name = 'restored';
                    $message->userfrom = core_user::get_noreply_user();
                    $message->subject = $messagesubject;
                    $message->fullmessagehtml = $messagebody;
                    $message->fullmessageformat = FORMAT_HTML;
                    $message->fullmessage = html_to_text($messagebody);
                    foreach ($requests as $request) {
                        $message->userto = $request->get('requester');
                        message_send($message);
                    }
                } else {
                    mtrace("Object: {$requestedobject->get('uuid')} restore in progress");
                }
            }
        }

        // Get all restored items: STATUS_REMOTE_RESTORED.
        $restoredobjects = assignsubmission_s3::get_restored();
        // If the expiry date is in the past then reset the status back to glacier and remove the expiry in the table.
        foreach ($restoredobjects as $restoredobject) {
            if ($restoredobject->get('expiry') < time()) {
                $restoredobject->set_many([
                    'expiry' => null,
                    'status' => assignsubmission_s3::STATUS_REMOTE_GLACIER,
                ]);
                $restoredobject->save();
            }
        }
    }
}
