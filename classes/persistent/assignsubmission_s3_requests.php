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

namespace assignsubmission_s3\persistent;

use core\persistent;
use core\user;
use stdClass;

/**
 * Persistent class for assignsubmission_s3_requests table.
 *
 * @package   assignsubmission_s3
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignsubmission_s3_requests extends persistent {
    /** @var string Table name this class is mapped to. */
    public const TABLE = 'assignsubmission_s3_requests';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'ass3id' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'requester' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
        ];
    }

    /**
     * Get the user record of the requester.
     *
     * @return stdClass
     */
    protected function get_requester(): stdClass {
        return user::get_user($this->raw_get('requester'));
    }
}
