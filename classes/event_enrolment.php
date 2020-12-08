<?php


namespace local_followup_email;


use coding_exception;
use core_date;
use DateTime;
use dml_exception;
use Exception;

/**
 * Class event_enrolment
 * @package local_followup_email
 */
class event_enrolment extends event_base
{

    /**
     * event_enrolment constructor.
     * @param persistent_base $base
     * @param persistent_status $status
     * @throws coding_exception
     * @throws dml_exception
     */
    public function __construct(persistent_base $base, persistent_status $status)
    {
        $this->base = $base;
        $this->status = $status;
    }

    /**
     * @return void
     * @throws dml_exception
     * @throws coding_exception
     * @throws Exception
     */
    public function set_event_time()
    {
        global $DB;
        $courseid = $this->base->get('courseid');
        $userid = $this->status->get('userid');
        $sql = "SELECT ue.timecreated
                FROM {user_enrolments} ue
                JOIN {enrol} e
                ON ue.enrolid = e.id
                WHERE e.courseid = {$courseid}
                AND ue.userid = {$userid}";
        $record = $DB->get_record_sql($sql, null, MUST_EXIST);
        $this->status->set('eventtime', $record->timecreated);
        $this->status->update();
        //return (new DateTime(null, core_date::get_server_timezone_object()))->setTimestamp($record->timecreated);
    }
}


