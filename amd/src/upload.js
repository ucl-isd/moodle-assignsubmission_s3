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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2025
 */
// <reference types="aws-sdk" />

define(['core/notification', 'core/ajax'], function(notification, ajax) {

    let module = {};

    /**
     * Init function to send the file to a pre-signed URL to S3.
     *
     * @param {number} assignmentId
     */
    module.init = function(assignmentId) {
        // Get the various elements and containers.
        const form = document.querySelector('.editsubmissionform form');
        const file = document.querySelector('input[name="s3_file"]');
        const fileContainer = document.getElementById('s3-file-container');
        const progress = document.getElementById('progress-bar');
        const progresspercent = document.getElementById('progress-bar-percent');
        const progressContainer = document.getElementById('progress-bar-container');

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const upload = file.files[0];
            let request = ajax.call([{
                methodname: 'assignsubmission_s3_get_s3_presigned',
                args: {assignmentid: assignmentId, filename: upload.name, mimetype: upload.type}
            }]);
            request[0].done(function(result) {
                fileContainer.classList.add("hidden");
                progressContainer.classList.remove("hidden");
                const payload = new FormData();
                payload.append('upload', upload, upload.name);

                const req = new XMLHttpRequest();
                req.open('PUT', result.s3_url);
                // Update the progress indicator for upload.
                req.upload.addEventListener('progress', function(e) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    progress.setAttribute('aria-valuenow', percentComplete);
                    progress.style.width = percentComplete + "%";
                    progresspercent.innerText = Math.round(percentComplete) + "%";
                });

                // Fires when upload is complete
                req.addEventListener('load', function() {
                    file.value = null;
                    form.submit();
                });

                req.send(payload);
            }).fail(notification.exception);
        });
    };

    return module;
});
