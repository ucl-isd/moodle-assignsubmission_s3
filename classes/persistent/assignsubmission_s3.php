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
 * Persistant class for assignsubmission_s3 table.
 *
 * @package   assignsubmission_s3
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2025
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_s3\persistent;

use core\persistent;
use core\uuid;

class assignsubmission_s3 extends persistent {

    /** @var string Table name this class is mapped to. */
    public const TABLE = 'assignsubmission_s3';

    /** @var int Status to indicate file is stored locally. */
    public const STATUS_LOCAL = 0;

    /** @var int Status to indicate file is stored in standard S3 storage. */
    public const STATUS_REMOTE_STANDARD = 1;

    /** @var int Status to indicate file is stored in S3 Glacier deep storage. */
    public const STATUS_REMOTE_GLACIER = 2;

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'assignment' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'submission' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'status' => [
                'type' => PARAM_INT,
                'default' => self::STATUS_REMOTE_STANDARD,
            ],
            'location' => [
                'type' => PARAM_TEXT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'expiry' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'uuid' => [
                'type' => PARAM_TEXT,
                'default' => function() {
                    return self::generate_uuid();
                }
            ],
        ];
    }

    /**
     * Get a UUID that has not been used in the table already.
     * The chances of generating one that has already been used is infinitesimal, but never zero.
     *
     * @return string
     */
    public static function generate_uuid(): string {
        $uuid = uuid::generate();
        while (self::get_record(['uuid' => $uuid]) !== false) {
            $uuid = uuid::generate();
        }
        return $uuid;
    }
}
