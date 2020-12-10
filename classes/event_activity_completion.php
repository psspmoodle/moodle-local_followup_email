<?php


namespace local_followup_email;


use coding_exception;
use completion_info;
use core\invalid_persistent_exception;
use core_date;
use DateTime;
use Exception;
use moodle_exception;
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
     * Determines activity completion time
     *
     * @param int $eventtime
     * @return void
     * @throws invalid_persistent_exception
     * @throws coding_exception
     */

    public function update_times($eventtime = 0)
    {
        if (!$eventtime) {
            $cm = new stdClass();
            $course = new stdClass();
            $cm->id = $this->base->get('cmid');
            $course->id = $this->base->get('courseid');
            $completioninfo = new completion_info($course);
            $completiondata = $completioninfo->get_data($cm, false, $this->status->get('userid'));
            $eventtime = $completiondata->timemodified;
        }
        $this->status->set('eventtime', $eventtime);
        $this->update_timetosend();
        $this->status->update();
    }
}