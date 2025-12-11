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
 * S3 class to handle object processing and tagging.
 *
 * @package   assignsubmission_s3
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2025
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_s3;

use assign;
use assignsubmission_s3\event\connection_issue;
use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Aws\MockHandler;
use context_module;
use Psr\Http\Message\RequestInterface;
use stdClass;

class s3 {

    public ?S3Client $client = null;

    private string $endpoint;

    private string $region;

    private string $bucket;

    private string $keyid;

    private string $secret;

    private bool $haserror = false;

    private string $error = '';

    private ?MockHandler $handler;

    private assign $assignment;

    /**
     * Constructor class.
     *
     * @param assign $assignment The assignment we'll be processing.
     * @param ?MockHandler $handler Allows us to pass a mock handler for Unit testing.
     */
    public function __construct(assign $assignment, ?MockHandler $handler = null) {
        $config = (array) get_config('assignsubmission_s3');
        $this->handler = $handler;
        $this->assignment = $assignment;
        $instanceconfig = $this->assignment->get_submission_plugin_by_type('s3')->get_config();

        if ($config['forceglobal']) {
            // Using forceglobal so set args from global config.
            $this->endpoint = $config['baseurl'];
            $this->region = $config['region'];
            $this->bucket = $config['bucket'];
            $this->keyid = $config['key'];
            $this->secret = $config['secret'];
        } else {
            // Otherwise use the passed instance config where set.
            $this->endpoint = $instanceconfig['baseurl'] ?? $config['baseurl'];
            $this->region = $instanceconfig['region'] ?? $config['region'];
            $this->bucket = $instanceconfig['bucket'] ?? $config['bucket'];
            $this->keyid = $instanceconfig['key'] ?? $config['key'];
            $this->secret = $instanceconfig['secret'] ?? $config['secret'];
        }

        if ($this->is_configured()) {
            // Site been configured, so attempt connection.
            $this->client = new S3Client([
                'endpoint' => $this->endpoint,
                'version' => 'latest',
                'region' => $this->region,
                'credentials' => [
                    'key' => $this->keyid,
                    'secret' => $this->secret
                ],
                'handler' => $this->handler,
            ]);
            // If we fail the permission check flag an error.
            if (!$this->has_permissions()) {
                $this->haserror = true;
            }
        }
    }

    private function is_configured(): bool {
        $configured = !empty($this->region) && !empty($this->bucket) && !empty($this->keyid) && !empty($this->secret);
        if (!$configured) {
            $this->trigger_error_event("Permission check failed: Region/Bucket/KeyID/Secret not defined in config.");
        }
        return $configured;
    }

    public function has_permissions(): bool {
        try {
            $result = $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => 'permissions_check_file',
                'Body' => 'test content',
            ]);
        } catch (S3Exception $e) {
            $this->trigger_error_event("Permission check failed: {$e->getMessage()}");
            return false;
        }
        $statuscode = $result['@metadata']['statusCode'];
        if ($statuscode === 200) {
            return true;
        } else {
            $this->trigger_error_event("Permission check failed: Status code $statuscode");
            return false;
        }
    }

    public function create_tag($tagname, $object) {
        $this->client->putObjectTagging();
    }

    /**
     * Return presigned URL for the GetObject request.
     *
     * @param stdClass $object
     * @return RequestInterface|null
     */
    public function retrieve_object(stdClass $object): ?RequestInterface {
        if ($this->is_available($object->uuid)) {
            return $this->client->createPresignedRequest(
                $this->client->getCommand('GetObject', [
                    'Bucket' => $this->bucket,
                    'Key' => $object->uuid,
                    'ResponseContentDisposition' => 'attachment; filename="' . $object->name . '"',
                    'ResponseContentType' => $object->mimetype,
                ]),
                '+1 hour'
            );
        }
        return null;
    }

    /**
     * Delete the object in the bucket.
     *
     * @param $key
     * @return bool
     */
    public function delete_object($key): bool {
        $this->client->deleteObject([
            'Bucket' => $this->bucket,
            'Key' => $key
        ]);
        return !$this->is_available($key);
    }

    private function is_available($key): bool {
        return $this->client->doesObjectExist($this->bucket, $key);
    }

    /**
     * Return presigned URL for the PutObject request.
     *
     * @param string $key
     * @return RequestInterface|null
     */
    public function create_presigned_request(string $key): ?RequestInterface {
        return $this->client->createPresignedRequest(
            $this->client->getCommand('PutObject', [
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]),
            '+1 hour'
        );
    }

    /**
     * Has an error been generated?
     *
     * @return bool
     */
    public function has_error(): bool {
        return $this->haserror;
    }

    /**
     * Return the error generated.
     *
     * @return string
     */
    public function get_error(): string {
        return $this->error;
    }

    private function trigger_error_event(string $error): void {
        global $USER;

        $this->haserror = true;
        $this->error = $error;
        $params = [
            'context' => context_module::instance($this->assignment->get_course_module()->id),
            'courseid' => $this->assignment->get_course()->id,
            'other' => [
                'error' => $this->get_error(),
            ],
            'relateduserid' => $USER->id,
        ];
        $event = connection_issue::create($params);
        $event->trigger();
    }
}
