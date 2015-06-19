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
 * Splunk log store.
 *
 * @package    logstore_splunk
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_splunk\task;

defined('MOODLE_INTERNAL') || die();

class export_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskexport', 'logstore_splunk');
    }

    /**
     * Export logs to Splunk.
     */
    public function execute() {
        global $DB;

        $config = get_config('logstore_splunk');
        if ($config->mode == 'realtime') {
            return true;
        }

        $lastid = $config->lastentry;
        if (!$lastid) {
            $lastid = -1;
        }

        // Grab the recordset.
        $rs = $DB->get_recordset_select('logstore_standard_log', 'id > ?', array($lastid), '', '*', 0, 100000);
        foreach ($rs as $row) {
            $row->timecreated = date('d/M/Y:H:i:s O', $row->timecreated);
            \logstore_splunk\splunk::log(json_encode($row));

            $lastid = $row->id;
        }
        $rs->close();

        set_config('lastentry', $lastid, 'logstore_splunk');

        return true;
    }
}
