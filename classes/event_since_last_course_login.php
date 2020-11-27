<?php


namespace local_followup_email;


use coding_exception;
use completion_info;
use core_date;
use DateTime;
use dml_exception;
use stdClass;

/**
 * Class event_since_last_course_login
 * @package local_followup_email
 */
class event_since_last_course_login extends event_base
{

    /**
     * Event time is the last time the user accessed the course
     *
     * @return int|void
     * @throws dml_exception
     */
    protected function get_event_time()
    {
        global $DB;
        $eventtime = 0;
        $userid = $this->persistent->get('userid');
        $courseid = $this->persistent->get('courseid');
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
        $course->id = $this->persistent->get('courseid');
        $completioninfo = new completion_info($course);
        if ($completioninfo->is_course_complete($this->status->get('userid'))) {
            $this->willnotsendinfo = get_string('alreadycompletedcourse', 'local_followup_email');
            return false;
        } else {
            return parent::is_sendable();
        }
    }
}