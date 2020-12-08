<?php


namespace local_followup_email;


use coding_exception;
use completion_info;
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
     * @var $completiondata object Info about whether the user completed the course module
     */

    public $completiondata;

    /**
     * event_activity_completion constructor.
     * @param persistent_base $base Database record
     * @param persistent_status $status Database record
     * @throws coding_exception
     * @throws moodle_exception
     */

    public function __construct(persistent_base $base, persistent_status $status)
    {
        $this->base = $base;
        $this->status = $status;
        $this->eventtime = $this->get_event_time();
    }

    /**
     * @returns int|DateTime
     * @throws coding_exception
     * @throws moodle_exception
     * @throws Exception
     */

    protected function get_event_time()
    {
        $eventtime = 0;
        $cm = $course = new stdClass();
        $cm->id = $this->base->get('cmid');
        $course->id = $this->base->get('courseid');
        $completioninfo = new completion_info($course);
        $this->completiondata = $completioninfo->get_data($cm, false, $this->status->get('userid'));
        if ($this->completiondata->timemodified > 0) {
            $eventtime = (new DateTime(null, core_date::get_server_timezone_object()))->setTimestamp($this->completiondata->timemodified);
        }
        return $eventtime;
    }

}