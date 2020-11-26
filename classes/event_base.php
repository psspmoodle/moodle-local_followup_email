<?php


namespace local_followup_email;


use coding_exception;
use core_date;
use DateTime;

abstract class event_base
{

    /**
     * @var $status persistent_status Database record
     */

    public $status;

    /**
     * @var $status persistent_base Database record
     */

    public $persistent;

    /**
     * @var $eventtime int|DateTime The event time that starts the followup interval
     */

    public $eventtime;

    /**
     * @return string
     */

    public $willnotsendinfo;

    /**
     * @return int
     */

    abstract protected function get_event_time();

    /**
     * @return int
     * @throws coding_exception
     */

    protected function get_send_time(): int
    {
        $sendtime = 0;
        $eventtime = $this->eventtime;
        $interval = $this->persistent->get('followup_interval');
        if (is_object($eventtime) && $eventtime->getTimestamp() > 0) {
            $sendtime = $eventtime->getTimestamp() + $interval;
        }
        return $sendtime;
    }

    /**
     * @return bool
     * @throws coding_exception
     */

    public function is_sendable()
    {
        if ($this->eventtime) {
            $eventtime = $this->eventtime->getTimestamp();
        } else {
            return false;
        }
        $sendable = false;
        $sendtime = $this->get_send_time();
        $interval = $this->persistent->get('followup_interval');
        $monitorstart = $this->persistent->get('monitorstart');
        $monitorend = $this->persistent->get('monitorend');
        $now = (new DateTime("now", core_date::get_server_timezone_object()))->getTimestamp();
        if ($eventtime) {
            if (($monitorstart && $monitorstart < $eventtime) && ($monitorend && $monitorend < $sendtime)) {
                $this->willnotsendinfo = get_string('sendaftermonitoring', 'local_followup_email');
            } elseif ($monitorstart && $monitorstart > $eventtime)  {
                $this->willnotsendinfo = get_string('eventbeforemonitoring', 'local_followup_email');
            } elseif ($monitorend && $monitorend < $sendtime) {
                $this->willnotsendinfo = get_string('sendaftermonitoring', 'local_followup_email');
            } elseif (($eventtime + $interval) > $now) {
                return false;
            } else {
                $sendable = $this->status->get('email_sent') ? false : true;
            }
        }
        return $sendable;
    }

}