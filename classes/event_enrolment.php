<?php


namespace local_followup_email;


use coding_exception;
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
     */
    public function __construct(persistent_base $base, persistent_status $status)
    {
        $this->base = $base;
        $this->status = $status;
    }

    /**
     * Determines user's enrolment time and time to send email
     *
     * @return void
     * @throws dml_exception
     * @throws coding_exception
     * @throws Exception
     */
    public function set_eventtime_and_sendtime()
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
        $this->update_sendtime();
        $this->status->update();
    }
}


