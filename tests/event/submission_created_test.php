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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/tests/generator.php');

/**
 * Submission created event test.
 *
 * @package   assignsubmission_s3
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2025
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submission_created_test extends advanced_testcase {

    // Use the generator helper.
    use mod_assign_test_generator;

    public function test_trigger_no_group() {
        global $CFG;

        // Initial setup.
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('forceglobal', '1', 'assignsubmission_s3');
        set_config('baseurl', $CFG->wwwroot . '/mod/assign/submission/s3/tests/fixtures/s3.php', 'assignsubmission_s3');
        set_config('region', 'eu-west-2', 'assignsubmission_s3');
        set_config('bucket', 'ucl-stg-wr473181', 'assignsubmission_s3');
        set_config('secret', '3g00rAuRTwk+dwQtD+EjFcsfX0nKS07zxUVD8Zu8', 'assignsubmission_s3');
        set_config('key', 'AKIA47YHZF637QLXU2HD', 'assignsubmission_s3');

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a user.
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        /* @var $assigngenerator mod_assign_generator */
        $assigngenerator = $this->getDataGenerator()->get_plugin_generator('mod_assign');

        // Create an assign instance and get the course module.
        $assign = $this->create_instance($course);

        // Enable the s3 submission plugin.
        /* @var assign_submission_plugin[] $submissionplugins */
        $submissionplugins = $assign->get_submission_plugins();
        foreach ($submissionplugins as $plugin) {
            if ($plugin->get_type() === 's3') {
                $plugin->enable();
            } else {
                $plugin->disable();
            }
        }

        // Capture the events.
        $sink = $this->redirectEvents();

        // Create a submission to trigger the event.
        $assigngenerator->create_submission([
            'cmid' => $assign->get_course_module()->id,
            'userid' => $user->id,
            's3' => true,
            'filepath' => 'mod/assign/submission/s3/tests/fixtures/file-sample_1MB.doc',
        ]);

        $triggeredevents = $sink->get_events();

        // Event 0 should be assessable_uploaded.
        // Event 1 should be assignsubmission_s3\event\submission_created.
        $this->assertEquals(2, $sink->count());

        $event = $triggeredevents[1];
        $this->assertInstanceOf('\assignsubmission_s3\event\submission_created', $event);
        $this->assertEquals($assign->get_context(), $event->get_context());
        $this->assertEventContextNotUsed($event);
        $description = 'The user with id "' .
            $user->id .
            '" created an S3 file submission and uploaded a file in the assignment with course module id "' .
            $assign->get_course_module()->id .
            '"';
        $this->assertEquals($description, $event->get_description());
    }

    public function test_trigger_group() {

        // Initial setup.
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a user.
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        // Create a group and add the user.
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group->id, 'userid' => $user->id]);

        /* @var $assigngenerator mod_assign_generator */
        $assigngenerator = $this->getDataGenerator()->get_plugin_generator('mod_assign');

        // Create an assign instance and get the course module.
        $assign = $this->create_instance($course, ['teamsubmission' => true]);

        // Enable the s3 submission plugin.
        /* @var assign_submission_plugin[] $submissionplugins */
        $submissionplugins = $assign->get_submission_plugins();
        foreach ($submissionplugins as $plugin) {
            if ($plugin->get_type() === 's3') {
                $plugin->enable();
            } else {
                $plugin->disable();
            }
        }

        // Capture the events.
        $sink = $this->redirectEvents();

        // Create a submission to trigger the event.
        $assigngenerator->create_submission([
            'cmid' => $assign->get_course_module()->id,
            'userid' => $user->id,
            'groupid' => $group->id,
            's3' => true,
            'filepath' => 'mod/assign/submission/s3/tests/fixtures/file-sample_1MB.doc',
        ]);

        $triggeredevents = $sink->get_events();

        // Event 0 should be assessable_uploaded.
        // Event 1 should be assignsubmission_s3\event\submission_created.
        $this->assertEquals(2, $sink->count());

        $event = $triggeredevents[1];
        $this->assertInstanceOf('\assignsubmission_s3\event\submission_created', $event);
        $this->assertEquals($assign->get_context(), $event->get_context());
        $this->assertEventContextNotUsed($event);
        $description = 'The user with id "' .
            $user->id .
            '" created an S3 file submission and uploaded a file in the assignment with course module id "' .
            $assign->get_course_module()->id .
            '" for the group with id "' .
            $group->id .
            '"';
        $this->assertEquals($description, $event->get_description());
    }
}
