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

namespace logstore_splunk;

defined('MOODLE_INTERNAL') || die();

/**
 * Splunk interface.
 */
class splunk
{
    private static $instance;

    private $service;
    private $config;
    private $buffer = array();

    /**
     * Constructor.
     */
    private function __construct() {
        require_once(dirname(__FILE__) . '/../lib/splunk/Splunk.php');

        $this->config = get_config('logstore_splunk');

        $this->service = new \Splunk_Service(array(
            'host' => $this->config->servername,
            'port' => $this->config->port,
            'username' => $this->config->username,
            'password' => $this->config->password
        ));
        $this->service->login();

        $this->create_index();
    }

    /**
     * Singleton.
     */
    public static function instance() {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Destructor.
     */
    public function __destruct() {
        if (!empty($this->buffer)) {
            $this->flush();
        }
    }

    /**
     * Is Splunk enabled?
     */
    public static function is_enabled() {
        $enabled = get_config('tool_log', 'enabled_stores');
        $enabled = array_flip(explode(',', $enabled));

        return isset($enabled['logstore_splunk']) && $enabled['logstore_splunk'];
    }

    /**
     * Log an item with Splunk.
     * @param $data JSON
     */
    public static function log($data) {
        $splunk = static::instance();
        $splunk->buffer[] = $data;

        if (count($splunk->buffer) > 100) {
            $splunk->flush();
        }
    }

    /**
     * Store a standard log item with Splunk.
     * @param $data
     */
    public static function log_standardentry($data) {
        $data = (array)$data;

        $newrow = new \stdClass();
        $newrow->timestamp = date(\DateTime::ISO8601, $data['timecreated']);
        foreach ($data as $k => $v) {
            if ($k == 'other') {
                $tmp = unserialize($v);
                if ($tmp !== false) {
                    $v = $tmp;
                }
            }

            $newrow->$k = $v;
        }

        static::log(json_encode($newrow));
    }

    /**
     * End the buffer.
     */
    public function flush() {
        global $CFG;

        if (empty($this->buffer)) {
            return;
        }

        $reciever = $this->service->getReceiver();
        $reciever->submit(implode("\n", $this->buffer), array(
            'host' => $this->config->hostname,
            'index' => $this->config->indexname,
            'source' => $this->config->source,
            'sourcetype' => 'json'
        ));

        $this->buffer = array();
    }

    /**
     * Create our index.
     */
    private function create_index() {
        $index = $this->service->getIndexes();

        try {
            $index->get($this->config->indexname);
        } catch (\Splunk_NoSuchEntityException $e) {
            $index->create($this->config->indexname);
        }
    }
}