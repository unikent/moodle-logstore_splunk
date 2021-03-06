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
    private $ready;

    /**
     * Constructor.
     */
    private function __construct() {
        $this->ready = false;
        try {
            if ($this->setup()) {
                $this->ready = true;
            }
        } catch (\Exception $e) { }
    }

    /**
     * Setup the connection.
     */
    private function setup() {
        require_once(dirname(__FILE__) . '/../lib/splunk/Splunk.php');

        $this->config = get_config('logstore_splunk');
        if (!isset($this->config->servername)) {
            return false;
        }

        $this->service = new \Splunk_Service(array(
            'host' => $this->config->servername,
            'port' => $this->config->port,
            'username' => $this->config->username,
            'password' => $this->config->password
        ));

        // Login to Splunk.
        $this->service->login();

        return true;
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
     * Flush buffers.
     */
    public function dispose() {
        if (!empty($this->buffer)) {
            $this->flush();
        }
    }

    /**
     * Destructor.
     */
    public function __destruct() {
        $this->dispose();
    }

    /**
     * Are we ready?
     */
    public function is_ready() {
        return $this->ready;
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

        if (empty($this->buffer) || !$this->is_ready()) {
            return;
        }

        // Send to Splunk.
        $reciever = $this->service->getReceiver();
        $reciever->submit(implode("\n", $this->buffer), array(
            'host' => $this->config->hostname,
            'index' => $this->config->indexname,
            'source' => $this->config->source,
            'sourcetype' => 'json'
        ));

        $this->buffer = array();
    }
}