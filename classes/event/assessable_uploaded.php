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

use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Assessable uploaded event.
 *
 * @package   assignsubmission_s3
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2025
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assessable_uploaded extends \core\event\assessable_uploaded {

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description(): string {
        $context = [
            'userid' => $this->userid,
            'contextinstanceid' => $this->contextinstanceid,
            'objectid' => $this->objectid,
        ];
        return get_string('event:assessable_uploaded:description', 'assignsubmission_s3', $context);
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
     * Init method.
     *
     * @return void
     */
    protected function init(): void {
        parent::init();
        $this->data['objecttable'] = 'assign_submission';
    }

    /**
     * Return the object mapping for the event.
     *
     * @return array
     */
    public static function get_objectid_mapping(): array {
        return ['db' => 'assign_submission', 'restore' => 'submission'];
    }
}
