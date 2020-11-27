<?php

namespace local_followup_email\output;

defined('MOODLE_INTERNAL') || die();

use coding_exception;
use core\persistent;
use dml_exception;
use local_followup_email\persistent_base;
use local_followup_email\persistent_status;
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
     * @param persistent_base $followupemail Related followup_email instance
     * @param persistent_status[] $records Records associated with followup_email instance
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function __construct(persistent_base $followupemail, array $records)
    {
        $this->followupemail = $followupemail;
        $this->monitorstart = $followupemail->get('monitorstart');
        $this->monitorend = $followupemail->get('monitorend');
        $this->records = $this->process_records($records);
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
     * @throws moodle_exception
     */
    private function process_records(array $records)
    {
        if (!$records) {
            return null;
        }
        $rows = array();
        foreach ($records as $record) {
            $userid = $record->get('userid');
            $stat = $this->followupemail->format_email_status($userid, $record);
            $row = array(
                'fullname' => $this->get_fullname($userid),
                'eventtime' => $stat['eventtime'],
                'sendtime' => $stat['sendtime'],
                'logurl' => $stat['logurl'] ?? null,
                'viewlog' => $stat['viewlog'] ?? null,
                'willnotsendinfo' => $stat['willnotsendinfo'] ?? null,
                'cellcolor' => $stat['cellcolor'] ?? null,
                'iconcolor' => $stat['iconcolor'] ?? 'r600'
            );
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * @param int $userid
     * @param persistent $record
     * @return array
     * @throws moodle_exception
     * @throws coding_exception
     * @throws dml_exception
     * @throws Exception
     */
    public function format_email_status(int $userid, persistent $record) {
        global $DB;
        $emailstatus = array();
        $eventtime = $sendtime = 0;
        // 1: $eventtime is either a timestamp or 0
        // If 0, no $sendtime
        if ($eventtimeobj = $this->get_event_time($userid)) {
            $eventtime = $eventtimeobj->getTimestamp();
            $emailstatus['eventtime'] = userdate($eventtime);
        } else {
            $emailstatus['eventtime'] = get_string('noeventrecorded', 'local_followup_email');
            $emailstatus['sendtime'] = '---';
        }
        // 2: $eventtime exists
        if ($eventtime) {
            // 2a: Email was sent
            if ($record->get('email_sent')) {
                $params = array(
                    'chooselog' => 1,
                    'id' => $this->get('courseid'),
                    'origin' => 'cli',
                    'edulevel' => 0
                );
                $emailstatus['sendtime'] = get_string('followupemailsent', 'local_followup_email');
                $emailstatus['viewlog'] = get_string('viewlog', 'local_followup_email');
                $emailstatus['logurl'] = (new moodle_url('/report/log/index.php', $params))->out(false);
                $emailstatus['cellcolor'] = 'bg-g50';
                // 2a-1: Email was sent before monitoring time was changed
                if (!$this->is_sendable($record) && ($this->get('monitorstart') || $this->get('monitorend')) ){
                    $emailstatus['willnotsendinfo'] = get_string('emailsentoutsidemonitoring', 'local_followup_email');
                    $emailstatus['iconcolor'] = 'g600';
                }
            } else {  // 2b: Email was not sent
                // is_sendable() only returns a string if the email isn't sendable
                $emailstatus['willnotsendinfo'] = $this->is_sendable($record, true);
                // If the event passed before the interval, and a monitorstart was not specified, show the sendtime as the next scheduled cron job.
                // Otherwise, 'Date to be sent' column will confusingly show a past date.
                $now = new DateTime(null, core_date::get_server_timezone_object());
                if ((($eventtime + $this->get('followup_interval')) < $now->getTimestamp()) && $this->is_sendable($record)) {
                    $task = $DB->get_record('task_scheduled', ['component' => 'local_followup_email'], 'nextruntime');
                    $sendtime = $task->nextruntime;
                } else {
                    $sendtime = $this->get_send_time($userid);
                }
                if (!$emailstatus['willnotsendinfo']) {
                    $emailstatus['sendtime'] = get_string('sending', 'local_followup_email') . userdate($sendtime);
                    $emailstatus['cellcolor'] = 'bg-y50';
                } else {
                    $emailstatus['sendtime'] = userdate($sendtime);
                    $emailstatus['cellcolor'] = 'bg-r50';
                }
            }
        }
        return $emailstatus;
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
     * @return array|stdClass
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function export_for_template(renderer_base $output)
    {
        $courseid = $this->followupemail->get('courseid');
        $eventlabel = self::get_event_label($this->followupemail->get('event'));
        $data = new stdClass();
        $data->monitorstarttime = $this->monitorstart ? userdate($this->monitorstart) : 0;
        $data->monitorendtime = $this->monitorend ? userdate($this->monitorend) : 0;
        $data->eventlabel = get_string($eventlabel, 'local_followup_email', $this->activity);
        $data->rows = $this->records;
        $data->indexurl = new moodle_url('/local/followup_email/index.php', array('courseid' => $courseid));
        return $data;
    }
}