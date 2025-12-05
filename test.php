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
 * @package   TODO Add package name
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2025
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use assignsubmission_s3\s3;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Stream;

require_once(dirname(__FILE__, 5) . '/config.php');

if ($filename = optional_param('filename', '', PARAM_TEXT)) {
    $s3 = new s3();
    $request = $s3->create_presigned_request($filename);
    $presignedurl = (string) $request->getUri();
    mtrace($presignedurl);
}

echo <<< HTML
    <form action="test.php" method="post" enctype="multipart/form-data">
        <input type="file" name="file" id="file">
        <input type="submit" name="submit" value="Submit">
    </form>
HTML;

if (optional_param('submit', false, PARAM_BOOL)) {

    $filename = $_FILES['file']['name'];
    $filepath = $_FILES['file']['tmp_name'];
    $filesize = $_FILES['file']['size'];
    $filetype = $_FILES['file']['type'];

    // Create a stream from the file.
    //$filestream = new Stream(fopen($filepath, 'r'));
    $filestream = file_get_contents($filepath, 'r');

    $s3 = new s3();
    $request = $s3->create_presigned_request($filename);
    $presignedurl = (string) $request->getUri();

    $curl = new curl();
    $request = $curl->put($presignedurl, $filestream);

    if ($curl->get_errno()) {
        mtrace("Error $curl->error");
    } else {
        mtrace("Upload successful");
    }
    mtrace($presignedurl);
}