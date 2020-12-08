<?php


namespace local_followup_email;


use coding_exception;
use completion_info;
use core_date;
use DateTime;
use dml_exception;
use Exception;
use moodle_exception;
use stdClass;

/**
 * Class event_since_last_course_login
 * @package local_followup_email
 */
class event_since_last_course_login extends event_base
{

    /**
     * event_since_last_course_login constructor.
     * @param persistent_base $base Database record
     * @param persistent_status $status Database record
     * @throws moodle_exception
     */
    public function __construct(persistent_base $base, persistent_status $status)
    {
        $this->base = $base;
        $this->status = $status;
        $this->eventtime = $this->get_event_time();
    }

    /**
     * Event time is the last time the user accessed the course
     *
     * @return int|void
     * @throws dml_exception
     * @throws Exception
     */
    protected function get_event_time()
    {
        global $DB;
        $eventtime = 0;
        $userid = $this->base->get('userid');
        $courseid = $this->base->get('courseid');
        if ($lastaccess = $DB->get_record('user_lastaccess', array('userid' => $userid, 'courseid' => $courseid))) {
            if ($timestamp = $lastaccess->timeaccess) {
                $eventtime = (new DateTime(null, core_date::get_server_timezone_object()))->setTimestamp($timestamp);
            }
        }
        return $eventtime;
    }

    /**
     * Don't send an email if the user has already completed the course
     *
     * @return bool
     * @throws coding_exception
     */
    public function is_sendable()
    {
        $course = new stdClass();
        $course->id = $this->base->get('courseid');
        $completioninfo = new completion_info($course);
        if ($completioninfo->is_course_complete($this->status->get('userid'))) {
            $this->willnotsendinfo = get_string('alreadycompletedcourse', 'local_followup_email');
            return false;
        } else {
            return parent::is_sendable();
        }
    }
}