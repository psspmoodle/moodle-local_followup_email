<?php

namespace local_followup_email\output;

defined('MOODLE_INTERNAL') || die();

use completion_info;
use context_course;
use DateTime;
use local_followup_email\followup_email_persistent;
use moodle_url;
use renderable;
use renderer_base;
use templatable;
use stdClass;

class followup_email_status implements renderable, templatable
{
    // The related followup_email persistent
    public $followupemail;
    // Related course object
    public $course;
    // Related course module object
    public $cm;
    // Related completion info
    public $completioninfo;
    // Array of table headings
    public $headings;
    // Array of status persistent records
    public $records;

    public function __construct(followup_email_persistent $followupemail, $records)
    {
        global $DB;
        $this->followupemail = $followupemail;
        $courseid = $this->followupemail->get('courseid');
        $this->course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $course_modinfo = get_fast_modinfo($this->followupemail->get('courseid'));
        if ($followupemail->get('cmid')) {
            $this->cm = $course_modinfo->get_cm($this->followupemail->get('cmid'));
            $this->completioninfo = new completion_info($this->course);
        }
        $this->records = $this->process_records($records);
        $this->headings = $this->get_status_table_headings();
    }

    public function process_records($records)
    {
        if (!$records) {
            return null;
        }
        $rows = array();
        foreach ($records as $record) {
            $userid = $record->get('userid');
            $eventtime = $this->get_event_time($userid, $this->followupemail->get('event'), true);
            $row = array(
                'fullname' => $this->get_fullname($userid),
                'completion_time' => $eventtime ? $eventtime : '---',
                'email_to_be_sent' => $this->get_time_to_be_sent($userid),
                'email_time_sent' => $record->get('email_time_sent')
            );
            $rows[] = $row;
        }
        return $rows;
    }

    private function get_status_table_headings() {
        $event = $this->followupemail->get('event');
        return array(
            'user' => get_string('user','local_followup_email'),
            'event' => get_string($this->get_event_label($event),'local_followup_email'),
            'datetobesent' => get_string('datetobesent','local_followup_email'),
            'emailsent' => get_string('emailsent','local_followup_email')
        );
    }

    public static function get_event_label($event) {
        switch ($event) {
            case FOLLOWUP_EMAIL_ACTIVITY_COMPLETION:
                return 'event_activitycompletion';
                break;
            case FOLLOWUP_EMAIL_SINCE_ENROLLMENT:
                return 'event_sinceenrollment';
                break;
            case FOLLOWUP_EMAIL_SINCE_LAST_LOGIN:
                return 'event_sincelastlogin';
                break;
        }
    }

    public function get_time_to_be_sent($userid)
    {
//        $completiontime = $this->get_event_time($userid);
//        if ($completiontime > 0) {;
//            $timetosend = $completiontime + $this->followupemail->get('followup_interval');
//            $datetime = new DateTime("@$timetosend");
//            return $datetime->format('M d, Y');
//        }
        return null;
    }

    public function get_fullname($userid)
    {
        global $DB;
        $sql = "SELECT CONCAT(u.lastname, ', ', u.firstname) as fullname
                FROM {user} u
                WHERE u.id = {$userid}";
        return ($DB->get_record_sql($sql))->fullname;
    }

    public function get_event_time($userid, $type, $formatted=false)
    {
        global $DB;
        switch ($type) {
            case FOLLOWUP_EMAIL_ACTIVITY_COMPLETION:
                $completioninfo = $this->completioninfo->get_data($this->cm, false, $userid);
                if ($completioninfo->timemodified > 0) {
                    if (!$formatted) {
                        return $completioninfo->timemodified;
                    } else {
                        $datetime = new DateTime("@$completioninfo->timemodified");
                        return $datetime->format('M d, Y');
                    }
                }
                break;
            case FOLLOWUP_EMAIL_SINCE_ENROLLMENT:
                $sql =  "SELECT ue.timestart
                        FROM {user_enrolments} ue
                        JOIN {enrol} e
                        ON ue.enrolid = e.id
                        WHERE e.courseid = {$this->course->id}
                        AND ue.userid = {$userid}";
                $record = $DB->get_record_sql($sql, null, MUST_EXIST);
                $datetime = new DateTime("@$record->timestart");
                return $datetime->format('M d, Y');
            case FOLLOWUP_EMAIL_SINCE_LAST_LOGIN:

        }
        return false;
    }

    public function export_for_template(renderer_base $output)
    {
        $data = [];
        $data['rows'] = $this->records;
        $data['returntext'] = get_string('returntoindex', 'local_followup_email');
        $data['indexurl'] = new moodle_url('/local/followup_email/index.php', array('courseid' => $this->course->id));
        return (object) array_merge($data, $this->get_status_table_headings());
    }
}