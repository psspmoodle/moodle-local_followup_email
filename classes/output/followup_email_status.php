<?php

namespace local_followup_email\output;

defined('MOODLE_INTERNAL') || die();

use coding_exception;
use completion_info;
use DateTime;
use dml_exception;
use local_followup_email\followup_email_persistent;
use local_followup_email\followup_email_status_persistent;
use moodle_exception;
use moodle_url;
use renderable;
use renderer_base;
use templatable;
use stdClass;

class followup_email_status implements renderable, templatable
{
    // The related followup_email persistent
    public $followupemail;
    // Array of table headings
    public $headings;
    // Array of status persistent records
    public $records;

    /**
     * followup_email_status constructor.
     * @param followup_email_persistent $followupemail Related followup_email instance
     * @param followup_email_status_persistent[] $records Records associated with followup_email instance
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function __construct(followup_email_persistent $followupemail, array $records)
    {
        $this->followupemail = $followupemail;
        $this->records = $this->process_records($records);
        $this->headings = $this->get_status_table_headings();
    }

    /**
     * @param followup_email_status_persistent[] $records Records associated with followup_email instance
     * @return array|null
     * @throws coding_exception
     */
    private function process_records(array $records)
    {
        if (!$records) {
            return null;
        }
        $rows = array();
        foreach ($records as $record) {
            $userid = $record->get('userid');
            $starttime = $this->followupemail->get_start_time($userid, true);
            $sendtime = $this->followupemail->get_send_time($userid, true);
            $emailsent = $record->get('email_sent') ? "Yes" : "No";
            $row = array(
                'fullname' => $this->get_fullname($userid),
                'starttime' => $starttime ? $starttime : '---',
                'sendtime' => $sendtime ? $sendtime : '---',
                'emailsent' => $emailsent
            );
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * @return array
     * @throws coding_exception
     */
    private function get_status_table_headings()
    {
        $event = $this->followupemail->get('event');
        return array(
            'user' => get_string('user','local_followup_email'),
            'event' => get_string($this->get_event_label($event),'local_followup_email'),
            'sendtime_heading' => get_string('datetobesent','local_followup_email'),
            'emailsent_heading' => get_string('emailsent','local_followup_email')
        );
    }

    /**
     * @param $event
     * @return string
     */
    public static function get_event_label($event)
    {
        $label = '';
        switch ($event) {
            case FOLLOWUP_EMAIL_ACTIVITY_COMPLETION:
                $label = 'event_activitycompletion';
                break;
            case FOLLOWUP_EMAIL_SINCE_ENROLLMENT:
                $label = 'event_sinceenrollment';
                break;
            case FOLLOWUP_EMAIL_SINCE_LAST_LOGIN:
                $label = 'event_sincelastlogin';
                break;
        }
        return $label;
    }

    public function get_fullname($userid)
    {
        global $DB;
        $sql = "SELECT CONCAT(u.lastname, ', ', u.firstname) as fullname
                FROM {user} u
                WHERE u.id = {$userid}";
        return ($DB->get_record_sql($sql))->fullname;
    }

    public function export_for_template(renderer_base $output)
    {
        $data = [];
        $courseid = $this->followupemail->get('courseid');
        $data['rows'] = $this->records;
        $data['returntext'] = get_string('returntoindex', 'local_followup_email');
        $data['indexurl'] = new moodle_url('/local/followup_email/index.php', array('courseid' => $courseid));
        return (object) array_merge($data, $this->get_status_table_headings());
    }
}