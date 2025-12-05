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

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Psr\Http\Message\RequestInterface;
use stdClass;

class s3 {

    public ?S3Client $client = null;

    private string $endpoint;

    private string $region;

    private string $bucket;

    private string $keyid;

    private string $secret;

    public function __construct(array $instanceconfig = []) {
        $config = (array) get_config('assignsubmission_s3');

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
            $this->client = new S3Client([
                'endpoint' => $this->endpoint,
                'version' => 'latest',
                'region' => $this->region,
                'credentials' => [
                    'key' => $this->keyid,
                    'secret' => $this->secret
                ]
            ]);
        }
    }

    private function is_configured(): bool {
        return !empty($this->region) && !empty($this->bucket) && !empty($this->keyid) && !empty($this->secret);
    }

    public function create_tag($tagname, $object) {
        $this->client->putObjectTagging();
    }

    public function create_object($object): array {
        if (!$this->is_configured()) {
            return [
                'result' => false,
                'message' => 'Not configured', // TODO Lang string
            ];
        }
        try {
            $result = $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => 'permissions_check_file',
                'Body' => 'test content',
            ]);
        } catch (S3Exception $e) {
            return [
                'result' => false,
                'message' => $e->getMessage(),
            ];
        }
        $statuscode = $result['@metadata']['statusCode'];
        if ($statuscode === 200) {
            return [
                'result' => true,
                'message' => '',
            ];
        } else {
            return [
                'result' => true,
                'message' => "$statuscode recieved", // TODO Lang string
            ];
        }
        //$this->client->putObject([
        //    'Bucket' => 'ucl-stg-wr473181',
        //    'Key'    => 'file-sample_1MB.doc',
        //    'Body'   => $object,
        //]);

        return true;
    }

    public function retrieve_object(stdClass $object): ?RequestInterface {
        if ($this->is_configured()) {
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

    public function update_object($object) {

    }

    public function delete_object($object) {

    }

    private function is_available(): bool {
        return true;
    }

    public function create_presigned_request(string $key): ?RequestInterface {
        if ($this->is_configured()) {
            return $this->client->createPresignedRequest(
                $this->client->getCommand('PutObject', [
                    'Bucket' => $this->bucket,
                    'Key' => $key,
                ]),
                '+1 hour'
            );
        }
        return null;
    }
}
