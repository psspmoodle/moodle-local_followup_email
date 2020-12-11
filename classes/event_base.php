<?php


namespace local_followup_email;


use coding_exception;
use core\invalid_persistent_exception;
use core_date;
use DateTime;
use dml_exception;
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
     * @var $sendinfo string Status info about email
     */
    public $sendinfo;

    /**
     * Determining event time differs among event types.
     *
     * @return void
     */
    abstract protected function set_eventtime_and_sendtime();

    /**
     * @returns void
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_persistent_exception
     */
    public function update_sendtime()
    {
        $sendtime = 0;
        if ($this->is_sendable()) {
            $sendtime = $this->check_for_past_event($this->calculate_sendtime());
        }
        $this->status->set('timetosend', $sendtime);
        $this->status->update();
    }

    /**
     * Default return type is float: need to cast to int or else it will fail persistent validation.
     *
     * @return int
     * @throws coding_exception
     * @throws Exception
     */
    protected function calculate_sendtime()
    {
        return (int) $this->status->get('eventtime') + (int) $this->base->get('followup_interval');
    }

    /**
     * Checks if the current time is greater than the calculated sendtime. We do this because
     * we don't want the email status page to (confusingly) display sendtime as a time/date in the past—–
     * instead, we show the sendtime as the next scheduled cron job.
     *
     * @param $sendtime
     * @return int
     * @throws dml_exception
     */
    protected function check_for_past_event($sendtime)
    {
        global $DB;
        $now = (new DateTime('now'))->getTimestamp();
        if ($sendtime < $now) {
            $task = $DB->get_record('task_scheduled', ['component' => 'local_followup_email'], 'nextruntime');
            $sendtime = (int) $task->nextruntime;
        }
        return $sendtime;
    }

    /**
     * Check to see if an email should be sent and provide status update.
     *
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     * @see followup_email_status::format_email_status()
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
        $sendtime = $this->calculate_sendtime();
        $monitorstart = $this->base->get('monitorstart');
        $monitorend = $this->base->get('monitorend');
        if (($monitorstart && $monitorstart < $eventtime) && ($monitorend && $monitorend < $sendtime)) {
            $this->sendinfo = 'sendaftermonitoring';
        } elseif ($monitorstart && $monitorstart > $eventtime)  {
            $this->sendinfo = 'eventbeforemonitoring';
        } elseif ($monitorend && $monitorend < $sendtime) {
            $this->sendinfo = 'sendaftermonitoring';
        } elseif ($sendtime != $this->check_for_past_event($sendtime)) {
            $this->sendinfo = 'sendingasap';
            $sendable = true;
        }
        else {
            $this->sendinfo = 'sending';
            $sendable = true;
        }
        return $sendable;
    }

}