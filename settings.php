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
 * Splunk log store settings.
 *
 * @package    logstore_splunk
 * @copyright  2016 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $healthurl = new moodle_url('/admin/tool/log/store/splunk/index.php', array('sesskey' => sesskey()));
    $ADMIN->add('reports', new admin_externalpage(
        'logstoresplunkhealth',
        new lang_string('reporttitle', 'logstore_splunk'),
        $healthurl,
        'moodle/site:config'
    ));

    $settings->add(new admin_setting_configtext(
        'logstore_splunk/servername',
        new lang_string('servername', 'logstore_splunk'),
        '', 'localhost', PARAM_HOST
    ));

    $settings->add(new admin_setting_configtext(
        'logstore_splunk/port',
        new lang_string('port', 'logstore_splunk'),
        '', '8089', PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'logstore_splunk/username',
        new lang_string('username'),
        '', 'admin', PARAM_ALPHANUMEXT
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'logstore_splunk/password',
        new lang_string('password'),
        '', '', PARAM_ALPHANUMEXT
    ));

    $settings->add(new admin_setting_configtext(
        'logstore_splunk/indexname',
        new lang_string('indexname', 'logstore_splunk'),
        '', 'moodle', PARAM_ALPHANUMEXT
    ));

    $settings->add(new admin_setting_configtext(
        'logstore_splunk/hostname',
        new lang_string('hostname', 'logstore_splunk'),
        '', gethostname(), PARAM_HOST
    ));

    $settings->add(new admin_setting_configtext(
        'logstore_splunk/source',
        new lang_string('source', 'logstore_splunk'),
        '', 'Moodle', PARAM_TEXT
    ));

    $settings->add(new admin_setting_configselect(
        'logstore_splunk/mode',
        new lang_string('mode', 'logstore_splunk'),
        '', 'realtime', array(
            'realtime' => new lang_string('realtime', 'logstore_splunk'),
            'background' => new lang_string('background', 'logstore_splunk')
        )));
}
