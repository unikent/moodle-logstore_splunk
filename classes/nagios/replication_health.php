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

namespace logstore_splunk\nagios;

/**
 * Checks cache.
 */
class replication_health extends \local_nagios\base_check
{
    public function execute() {
        global $DB;

        $config = get_config('logstore_splunk');

        // Check we last ran within 5 minutes.
        if (time() - $config->lastrun > 300) {
            $timemessage = "Splunk log store replication hasn't run since " . date(\DateTime::ISO8601, $config->lastrun);

            // Check we last ran within 15 minutes, if we did, just throw a warning.
            if (time() - $config->lastrun > 900) {
                $this->error($timemessage);
            } else {
                $this->warning($timemessage);
            }
        }

        // Check replication lag.
        $maxid = $DB->get_field('logstore_standard_log', 'MAX(id)', array());
        $percent = ((float)$config->lastentry / (float)$maxid) * 100.0;
        $percent = number_format($percent, 2);

        // Now check we aren't lagging too far behind (I.e. the next cron run is likely to pick all the entries up).
        if (($maxid - $config->lastentry) > 10000) {
            $this->error("Splunk replication lag detected: {$config->lastentry} / {$maxid} ({$percent}%)");
        }
    }
}
