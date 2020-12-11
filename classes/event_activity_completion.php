<?php


namespace local_followup_email;


use coding_exception;
use completion_info;
use core\invalid_persistent_exception;
use dml_exception;
use stdClass;

/**
 * Class event_activity_completion
 * @package local_followup_email
 */
class event_activity_completion extends event_base
{

    /**
     * event_activity_completion constructor.
     * @param persistent_base $base Database record
     * @param persistent_status $status Database record
     */
    public function __construct(persistent_base $base, persistent_status $status)
    {
        $this->base = $base;
        $this->status = $status;
    }

    /**
     * Determines activity completion time and time to send email
     *
     * @param int $eventtime
     * @return void
     * @throws invalid_persistent_exception
     * @throws coding_exception
     * @throws dml_exception
     */
    public function set_eventtime_and_sendtime($eventtime = 0)
    {
        if (!$eventtime) {
            $cm = new stdClass();
            $course = new stdClass();
            $cm->id = $this->base->get('cmid');
            $course->id = $this->base->get('courseid');
            $completioninfo = new completion_info($course);
            $completiondata = $completioninfo->get_data($cm, false, $this->status->get('userid'));
            if ($completiondata->completionstate > 0) {
                $eventtime = $completiondata->timemodified;
            }
        }
        $this->status->set('eventtime', $eventtime);
        $this->update_sendtime();
        $this->status->update();
    }
}