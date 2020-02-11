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
    // Array of status persistent records
    public $records;

    public function __construct(followup_email_persistent $followupemail, $records)
    {
        global $DB;
        $this->followupemail = $followupemail;
        $courseid = $this->followupemail->get('courseid');
        $this->course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $course_modinfo = get_fast_modinfo($this->followupemail->get('courseid'));
        $this->cm = $course_modinfo->get_cm($this->followupemail->get('cmid'));
        $this->completioninfo = new completion_info($this->course);
        $this->records = $this->process_records($records);
    }

    public function process_records($records)
    {
        if (!$records) {
            return null;
        }
        $rows = array();
        $context = context_course::instance($this->course->id);
        foreach ($records as $record) {
            $userid = $record->get('userid');
            $completiontime = $this->get_completion_time($userid, true);
            $row = array(
                'fullname' => $this->get_fullname($userid),
                'completion_time' => $completiontime ? $completiontime : '---',
                'email_to_be_sent' => $this->get_time_to_be_sent($userid),
                'email_time_sent' => $record->get('email_time_sent'),
                'is_enrolled' => is_enrolled($context, $userid)
            );
            $rows[] = $row;
        }
        return $rows;
    }

    public function get_time_to_be_sent($userid)
    {
        $completiontime = $this->get_completion_time($userid);
        if ($completiontime > 0) {;
            $timetosend = $completiontime + $this->followupemail->get('followup_interval');
            $datetime = new DateTime("@$timetosend");
            return $datetime->format('M d, Y');
        }
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

    public function get_completion_time($userid, $formatted=false)
    {
        $completioninfo = $this->completioninfo->get_data($this->cm, false, $userid);
        if ($completioninfo->timemodified > 0) {
            if (!$formatted) {
                return $completioninfo->timemodified;
            } else {
                $datetime = new DateTime("@$completioninfo->timemodified");
                return $datetime->format('M d, Y');
            }
        }
        return false;
    }

    public function export_for_template(renderer_base $output)
    {
        $data = new stdClass();
        $data->rows = $this->records;
        $data->returntext = get_string('returntoindex', 'local_followup_email');
        $data->indexurl = new moodle_url('/local/followup_email/index.php', array('courseid' => $this->course->id));
        return $data;
    }
}