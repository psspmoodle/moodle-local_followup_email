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
    // Related course module title
    public $activity;
    // Monitor start
    public $monitorstart;
    // Monitor end
    public $monitorend;

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
        $this->monitorstart = $followupemail->get('monitorstart');
        $this->monitorend = $followupemail->get('monitorend');
        $this->records = $this->process_records($records);
        $this->headings = $this->get_status_table_headings();
        if ($cmid = $followupemail->get('cmid')) {
            $activity = (get_fast_modinfo($followupemail->get('courseid'))->get_cm($cmid));
            $this->activity = $activity->name;
        }
    }

    /**
     * @param array $records
     * @return array|null
     * @throws coding_exception
     * @throws dml_exception
     */
    private function process_records(array $records)
    {
        if (!$records) {
            return null;
        }
        $rows = array();
        foreach ($records as $record) {
            $eventtime = $sendtime = 0;
            $sendtimeinfo = '';
            $userid = $record->get('userid');
            if ($eventtimeobj = $this->followupemail->get_event_time($userid)){
                $eventtime = $eventtimeobj->getTimestamp();
                $sendtime = $this->followupemail->get_send_time($userid);
            }
            if ($eventtime) {
                if (($this->monitorstart && $this->monitorstart < $eventtime) && ($this->monitorend && $this->monitorend < $sendtime)) {
                    $sendtimeinfo = get_string('sendaftermonitoring', 'local_followup_email');
                } elseif ($this->monitorstart && $this->monitorstart > $eventtime)  {
                    $sendtimeinfo = get_string('eventbeforemonitoring', 'local_followup_email');
                } elseif ($this->monitorend && $this->monitorend < $sendtime) {
                    $sendtimeinfo = get_string('sendaftermonitoring', 'local_followup_email');
                }
            }
            $row = array(
                'fullname' => $this->get_fullname($userid),
                'eventtime' => $eventtime ? userdate($eventtime) : '---',
                'sendtime' => $sendtime ? userdate($sendtime) : '---',
                'emailsent' => $record->get('email_sent') ? "Yes" : "No",
                'cellclass' => $sendtime && (!$sendtimeinfo) ? 'bg-g50' : 'bg-r50',
                'sendtimeinfo' => $sendtimeinfo,
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
            'event' => get_string($this->get_event_label($event),'local_followup_email', $this->activity),
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
                $label = 'event_sinceenrolment';
                break;
            case FOLLOWUP_EMAIL_SINCE_LAST_LOGIN:
                $label = 'event_sincelastlogin';
                break;
        }
        return $label;
    }

    /**
     * @param $userid
     * @return string
     * @throws dml_exception
     */
    public function get_fullname($userid)
    {
        global $DB;
        $sql = "SELECT CONCAT(u.lastname, ', ', u.firstname) as fullname
                FROM {user} u
                WHERE u.id = {$userid}";
        return ($DB->get_record_sql($sql))->fullname;
    }

    /**
     * @param renderer_base $output
     * @return array|object|stdClass
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function export_for_template(renderer_base $output)
    {
        $data = [];
        $courseid = $this->followupemail->get('courseid');
        $data['monitorstart'] = $this->monitorstart ? userdate($this->monitorstart) : 0;
        $data['monitorend'] = $this->monitorend ? userdate($this->monitorend) : 0;
        $data['rows'] = $this->records;
        $data['monitoredeventtext'] = get_string('monitoredeventtext', 'local_followup_email');
        $data['monitoredevent'] = get_string($this->get_event_label($this->followupemail->get('event')), 'local_followup_email', $this->activity);
        $data['returntext'] = get_string('returntoindex', 'local_followup_email');
        $data['indexurl'] = new moodle_url('/local/followup_email/index.php', array('courseid' => $courseid));
        return (object) array_merge($data, $this->get_status_table_headings());
    }
}