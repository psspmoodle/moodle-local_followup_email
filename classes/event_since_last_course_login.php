<?php


namespace local_followup_email;


use coding_exception;
use completion_info;
use dml_exception;
use Exception;
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
     */
    public function __construct(persistent_base $base, persistent_status $status)
    {
        $this->base = $base;
        $this->status = $status;
    }

    /**
     * Checks the user_lastaccess table for last course login time.
     *
     * @return void
     * @throws dml_exception
     * @throws Exception
     */
    public function update_times()
    {
        global $DB;
        $userid = $this->status->get('userid');
        $courseid = $this->base->get('courseid');
        $lastaccess = $DB->get_record('user_lastaccess', array('userid' => $userid, 'courseid' => $courseid));
        $eventtime = $lastaccess ? $lastaccess->timeaccess : 0;
        $this->status->set('eventtime', $eventtime);
        $this->update_timetosend();
        $this->status->update();
    }

    /**
     * Don't send an email if the user has already completed the course…though that may be useful…
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
            $this->sendinfo = 'alreadycompletedcourse';
            return false;
        } else {
            return parent::is_sendable();
        }
    }
}