<?php


namespace local_followup_email;


use coding_exception;
use core_date;
use DateTime;
use dml_exception;

/**
 * Class event_enrolment
 * @package local_followup_email
 */
class event_enrolment extends event_base
{

    /**
     * event_since_enrolment constructor.
     * @param persistent_status $status
     * @param persistent_base $persistent
     * @throws coding_exception
     * @throws dml_exception
     */
    public function __construct(persistent_status $status, persistent_base $persistent)
    {
        $this->status = $status;
        $this->persistent = $persistent;
        $this->eventtime = $this->get_event_time();
    }

    /**
     * @return DateTime
     * @throws dml_exception
     * @throws coding_exception
     */
    protected function get_event_time()
    {
        global $DB;
        $courseid = $this->persistent->get('courseid');
        $userid = $this->status->get('userid');
        $sql = "SELECT ue.timecreated
                FROM {user_enrolments} ue
                JOIN {enrol} e
                ON ue.enrolid = e.id
                WHERE e.courseid = {$courseid}
                AND ue.userid = {$userid}";
        $record = $DB->get_record_sql($sql, null, MUST_EXIST);
        return (new DateTime(null, core_date::get_server_timezone_object()))->setTimestamp($record->timecreated);
    }

}



