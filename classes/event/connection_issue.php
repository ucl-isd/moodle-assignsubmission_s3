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

use core\event\base;
use moodle_url;

/**
 * Event triggered when connection fails.
 *
 * @package   assignsubmission_s3
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2025
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class connection_issue extends base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init(): void {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Get URL related to the action.
     *
     * @return moodle_url
     */
    public function get_url(): moodle_url {
        return new moodle_url('/mod/assign/view.php', ['id' => $this->contextinstanceid]);
    }

    /**
     * Return description of what happened.
     *
     * @return string
     */
    public function get_description(): string {
        $context = [
            'userid' => $this->userid,
            'contextinstanceid' => $this->contextinstanceid,
            'error' => $this->other['error'],
        ];
        return get_string('event:connection_issue:description', 'assignsubmission_s3', $context);
    }

    /**
     * Return the object mapping for the event.
     *
     * @return int
     */
    public static function get_objectid_mapping(): int {
        return base::NOT_MAPPED;
    }
}
