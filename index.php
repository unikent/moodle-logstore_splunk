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

require_once(dirname(__FILE__) . '/../../../../../config.php');
require_once($CFG->dirroot . '/lib/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

require_login();
require_capability('moodle/site:config', \context_system::instance());
require_sesskey();

admin_externalpage_setup('logstoresplunkhealth');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('reporttitle', 'logstore_splunk'));

$config = get_config('logstore_splunk');
$config->lastentry = isset($config->lastentry) ? $config->lastentry : 0;

$maxid = $DB->get_field('logstore_standard_log', 'MAX(id)', array());
$percent = ((float)$config->lastentry / (float)$maxid) * 100.0;
$percent = number_format($percent, 2);

$table = new \flexible_table("splunk_health");
$table->define_columns(array('variable', 'value'));
$table->define_headers(array(get_string('repstatus', 'logstore_splunk'), ''));
$table->define_baseurl($PAGE->url);
$table->setup();

$lastrun = isset($config->lastrun) ? date('D, d M Y H:i:s', $config->lastrun) : get_string('never', 'logstore_splunk');
$table->add_data(array(get_string('lastran', 'logstore_splunk'), $lastrun));

$table->add_data(array(get_string('progress', 'logstore_splunk'), "{$config->lastentry} / {$maxid} ({$percent}%)"));

$table->finish_output();

echo $OUTPUT->footer();
