<?php


namespace local_followup_email;


use coding_exception;
use core_date;
use DateTime;
use Exception;

/**
 * Class event_base
 * @package local_followup_email
 */
abstract class event_base
{

    /**
     * @var $base persistent_base Database record in persistent form
     */
    public $base;

    /**
     * @var $status persistent_status Database record in persistent form
     */
    public $status;

    /**
     * @var $eventtime int|DateTime The event time that starts the followup interval
     */
    public $eventtime;

    /**
     * @var $willnotsendinfo string Why a followup email will not be sent
     */
    public $willnotsendinfo;

    /**
     * Event time among child classes differs.
     *
     * @return int
     */
    abstract protected function set_event_time();

    /**
     * Generic check to see if an email should be sent, and if not, updates the instance's willnotsendinfo property
     * with a reason why. This method is overridden in some child classes.
     *
     * @return bool
     * @throws coding_exception
     * @throws Exception
     */
    public function is_sendable()
    {
        if ($this->eventtime) {
            $eventtime = $this->eventtime->getTimestamp();
        } else {
            return false;
        }
        $sendable = false;
        $sendtime = $this->status->get('timetosend');
        $interval = $this->base->get('followup_interval');
        $monitorstart = $this->base->get('monitorstart');
        $monitorend = $this->base->get('monitorend');
        $datetime = new DateTime("now", core_date::get_server_timezone_object());
        $now = $datetime->getTimestamp();
        $readable = date("F jS, Y", strtotime($now));
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
        return $sendable;
    }

}