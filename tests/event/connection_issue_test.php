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

namespace assignsubmission_s3\event;

use advanced_testcase;
use assign_submission_plugin;
use assignsubmission_s3\s3;
use Aws\CommandInterface;
use Aws\MockHandler;
use Aws\S3\Exception\S3Exception;
use mod_assign_generator;
use mod_assign_test_generator;
use Psr\Http\Message\RequestInterface;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/tests/generator.php');

/**
 * Connection issue event test.
 *
 * @package   assignsubmission_s3
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2025
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \assignsubmission_s3\event\connection_issue
 */
final class connection_issue_test extends advanced_testcase
{
    // Use the generator helper.
    use mod_assign_test_generator;

    public function test_trigger_no_config(): void {
        // Initial setup.
        $this->resetAfterTest();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a user.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $this->getDataGenerator()->get_plugin_generator('mod_assign');
        // Create an assign instance and get the course module.
        $assign = $this->create_instance($course);

        // Enable the s3 submission plugin.
        // phpcs:ignore Squiz.PHP.CommentedOutCode
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

        // Initial call without any config.
        new s3($assign);

        $triggeredevents = $sink->get_events();

        // Event 0 should be connection_issue.
        $this->assertEquals(1, $sink->count());

        $event = $triggeredevents[0];
        $this->assertInstanceOf('\assignsubmission_s3\event\connection_issue', $event);
        $this->assertEquals($assign->get_context(), $event->get_context());
        $this->assertEventContextNotUsed($event);
        $description = 'The assignment activity with course module id "' .
            $assign->get_course_module()->id .
            '" was unable to connect with S3 due to the error: ' .
            '"Permission check failed: Region/Bucket/KeyID/Secret not defined in config."';
        $this->assertEquals($description, $event->get_description());
    }

    public function test_trigger_fake_config(): void {
        // Initial setup.
        $this->resetAfterTest();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a user.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        // phpcs:ignore Squiz.PHP.CommentedOutCode
        /* @var $assigngenerator mod_assign_generator */
        $this->getDataGenerator()->get_plugin_generator('mod_assign');

        // Create an assign instance and get the course module.
        $assign = $this->create_instance($course);

        // Enable the s3 submission plugin.
        // phpcs:ignore Squiz.PHP.CommentedOutCode
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

        $handler = new MockHandler();
        $handler->append(function (CommandInterface $cmd) {
            return new S3Exception('The specified bucket does not exist', $cmd);
        });

        // Set some fake global config to pass the "configured" check.
        set_config('region', 'fake-region', 'assignsubmission_s3');
        set_config('bucket', 'fake-bucket', 'assignsubmission_s3');
        set_config('secret', 'fake-secret', 'assignsubmission_s3');
        set_config('key', 'fake-key', 'assignsubmission_s3');
        new s3($assign, $handler);

        $triggeredevents = $sink->get_events();

        // Event 0 should be connection_issue.
        $this->assertEquals(1, $sink->count());

        $event = $triggeredevents[0];
        $this->assertInstanceOf('\assignsubmission_s3\event\connection_issue', $event);
        $this->assertEquals($assign->get_context(), $event->get_context());
        $this->assertEventContextNotUsed($event);
        $description = 'The assignment activity with course module id "' .
            $assign->get_course_module()->id .
            '" was unable to connect with S3 due to the error: "Permission check failed: The specified bucket does not exist"';
        $this->assertEquals($description, $event->get_description());
    }
}
