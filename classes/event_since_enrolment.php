<?php


namespace local_followup_email;


use coding_exception;
use moodle_exception;

class event_since_enrolment extends event_base
{


    /**
     * event_activity_completion constructor.
     * @param persistent_status $status
     * @throws coding_exception
     * @throws moodle_exception
     */

    public function __construct(persistent_status $status)
    {
        $this->status = $status;
        $this->eventtime = $this->get_event_time();
    }

    /**
     * @inheritDoc
     */
    protected function get_event_time()
    {
        $courseid = $this->status->get('courseid')
        $sql = "SELECT ue.timecreated
                        FROM {user_enrolments} ue
                        JOIN {enrol} e
                        ON ue.enrolid = e.id
                        WHERE e.courseid = {$courseid}
                        AND ue.userid = {$userid}";
        $record = $DB->get_record_sql($sql, null, MUST_EXIST);
        $eventtime = (new DateTime(null, core_date::get_server_timezone_object()))->setTimestamp($record->timecreated);
    }

    /**
     * @inheritDoc
     */
    protected function is_sendable()
    {
        // TODO: Implement is_sendable() method.
    }
}



