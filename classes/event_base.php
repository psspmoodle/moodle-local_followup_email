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
     * @var $sendinfo string Why a followup email will not be sent
     */
    public $sendinfo;

    /**
     * Event time among child classes differs.
     *
     * @return void
     */
    abstract protected function update_times();

    /**
     * @returns void
     * @throws coding_exception
     */
    protected function update_timetosend()
    {
        $timetosend = $this->is_sendable() ? $this->calculate_timetosend() : 0;
        $this->status->set('timetosend', $timetosend);
    }

    /**
     * Default return type is float: need to cast to int or else it will fail persistent validation
     *
     * @return int
     * @throws coding_exception
     */
    protected function calculate_timetosend()
    {
        return (int) $this->status->get('eventtime') + $this->base->get('followup_interval');
    }

    /**
     * Generic check to see if an email should be sent, and if not, updates the instance's willnotsendinfo property
     * with a reason why.
     *
     * @return bool
     * @throws coding_exception
     * @throws Exception
     */
    public function is_sendable()
    {
        $sendable = false;
        if ($this->status->get('email_sent')) {
           $this->sendinfo = 'followupemailsent';
           return $sendable;
        }
        if ($this->status->get('eventtime')) {
            $eventtime = $this->status->get('eventtime');
        } else {
            $this->sendinfo = 'noeventrecorded';
            return $sendable;
        }
        $sendtime = $this->calculate_timetosend();
        $monitorstart = $this->base->get('monitorstart');
        $monitorend = $this->base->get('monitorend');
        $now = (new DateTime("now", core_date::get_server_timezone_object()))->getTimestamp();
        if (($monitorstart && $monitorstart < $eventtime) && ($monitorend && $monitorend < $sendtime)) {
            $this->sendinfo = 'sendaftermonitoring';
        } elseif ($monitorstart && $monitorstart > $eventtime)  {
            $this->sendinfo = 'eventbeforemonitoring';
        } elseif ($monitorend && $monitorend < $sendtime) {
            $this->sendinfo = 'sendaftermonitoring';
        }

        else {
            $this->sendinfo = 'sending';
            $sendable = true;
        }

        return $sendable;
    }

}