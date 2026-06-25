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

    const parts = [];

    const concurrency = 6;

    let percentComplete = 0;
    /**
     * Init function to send the file to a pre-signed URL to S3.
     *
     * @param {number} cmid
     * @param {number} chunkSize
     */
    module.init = function(cmid, chunkSize) {
        // Get the various elements and containers.
        const form = document.querySelector('.editsubmissionform form');
        const statement = document.getElementById('id_submissionstatement');
        const buttons = document.getElementById('fgroup_id_buttonar');
        const file = document.querySelector('input[name="s3_file"]');
        const fileContainer = document.getElementById('s3-file-container');
        const progressContainer = document.getElementById('progress-bar-container');

        form.addEventListener('submit', function(e) {
            // Cancelled, so do nothing.
            if (e.submitter.name === 'cancel') {
                return;
            }
            // No file submitted, so do nothing.
            if (file.files.length <= 0) {
                return;
            }
            if (!statement || (statement && statement.checked)) {
                e.preventDefault();
                buttons.classList.add("hidden");
                const upload = file.files[0];
                let request = ajax.call([{
                    methodname: 'assignsubmission_s3_get_multipart_upload',
                    args: {
                        cmid: cmid,
                        filename: upload.name,
                        mimetype: upload.type,
                        filesize: upload.size
                    }
                }]);
                request[0].done(async function(result) {
                    if (result.error) {
                        notification.alert(
                            result.error_title,
                            result.error_msg
                        );
                    } else {
                        fileContainer.classList.add("hidden");
                        progressContainer.classList.remove("hidden");

                        const queue = [];
                        const count = result.s3urls.length;
                        let partNumber = 1;

                        for (const s3url of result.s3urls) {
                            const p = uploadPart(upload, partNumber, s3url, count, chunkSize);
                            partNumber++;
                            queue.push(p);

                            if (queue.length >= concurrency) {
                                await Promise.race(queue);
                                queue.splice(0, 1);
                            }
                        }
                        await Promise.all(queue);

                        // Trigger the completion.
                        let complete = ajax.call([{
                            methodname: 'assignsubmission_s3_send_multipart_complete',
                            args: {
                                filename: upload.name,
                                mimetype: upload.type,
                                cmid: cmid,
                                parts: JSON.stringify(parts),
                                uploadid: result.uploadid
                            }
                        }]);
                        complete[0].done(function(result) {
                            if (result.error) {
                                notification.alert(
                                    result.error_title,
                                    result.error_msg
                                );
                            } else {
                                // Fires when upload is complete
                                file.value = null;
                                form.submit();
                            }
                        });
                    }
                }).fail(notification.exception);
            }
        });
    };

    /**
     *
     * @param {File} file
     * @param {Number} partNumber
     * @param {String} url
     * @param {Number} count
     * @param {Number} chunkSize
     * @returns {Promise<void>}
     */
    async function uploadPart(file, partNumber, url, count, chunkSize) {
        const start = (partNumber - 1) * chunkSize;
        const end = Math.min(start + chunkSize, file.size);
        const blob = file.slice(start, end);
        const progress = document.getElementById('progress-bar');
        const progresspercent = document.getElementById('progress-bar-percent');

        const xhr = new XMLHttpRequest();
        await new Promise((resolve) => {
            let previousPercent = 0;
            xhr.upload.addEventListener("progress", (event) => {
                if (event.lengthComputable) {
                    let difference = ((event.loaded / event.total) * 100) - previousPercent;
                    percentComplete += (difference / count);
                    progress.setAttribute('aria-valuenow', percentComplete);
                    progress.style.width = percentComplete + "%";
                    progresspercent.innerText = Math.round(percentComplete) + "%";
                    previousPercent = (event.loaded / event.total) * 100;
                }
            });
            xhr.addEventListener("loadend", () => {
                resolve(xhr.readyState === 4 && xhr.status === 200);
            });

            xhr.open("PUT", url, true);
            xhr.setRequestHeader("Content-Type", "application/octet-stream");
            xhr.send(blob);
        });
        parts[partNumber - 1] = {ETag: xhr.getResponseHeader("ETag"), PartNumber: partNumber};
    }

    return module;
});
