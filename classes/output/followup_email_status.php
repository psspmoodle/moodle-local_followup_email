<?php

namespace local_followup_email\output;

defined('MOODLE_INTERNAL') || die();

use coding_exception;
use core\persistent;
use dml_exception;
use lang_string;
use local_followup_email\event_factory;
use local_followup_email\persistent_base;
use local_followup_email\persistent_status;
use moodle_exception;
use moodle_url;
use renderable;
use renderer_base;
use templatable;
use stdClass;
use Exception;

class followup_email_status implements renderable, templatable
{
    // The related followup_email persistent
    public $base;
    // Array of status persistent records
    public $records;
    // Related course module title
    public $activity;

    /**
     * followup_email_status constructor.
     * @param persistent_base $base Related followup_email instance
     * @param persistent_status[] $records Records associated with followup_email instance
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function __construct(persistent_base $base, array $records)
    {
        $this->base = $base;
        $this->records = $this->process_records($records);
        if ($cmid = $base->get('cmid')) {
            $activity = (get_fast_modinfo($base->get('courseid'))->get_cm($cmid));
            $this->activity = $activity->name;
        }
    }

    /**
     * Status table output
     *
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
            $stat = $this->format_email_status($record);
            $row = array(
                'fullname' => $this->get_user_fullname($userid),
                'eventtime' => $stat['eventtime'],
                'sendtime' => $stat['sendtime'],
                'logurl' => $stat['logurl'] ?? null,
                'viewlog' => $stat['viewlog'] ?? null,
                'sendinfo' => $stat['sendinfo'] ?? null,
                'cellcolor' => $stat['cellcolor'] ?? null,
                'iconcolor' => $stat['iconcolor'] ?? 'r600'
            );
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Determine
     *
     * @param persistent $record
     * @return array
     * @throws moodle_exception
     * @throws coding_exception
     * @throws Exception
     */
    public function format_email_status(persistent $record) {
        $emailstatus = array();
        $eventobj = event_factory::create($this->base, $record);
        $eventobj->is_sendable();
        switch ($eventobj->sendinfo) {
            case 'noeventrecorded':
                $emailstatus['eventtime'] = $this->gs('noeventrecorded');
                $emailstatus['sendtime'] = '---';
                break;
            case 'followupemailsent':
                $params = array(
                    'chooselog' => 1,
                    'id' => $this->base->get('courseid'),
                    'origin' => 'cli',
                    'edulevel' => 0
                );
                $emailstatus['sendtime'] = $this->gs('followupemailsent');
                $emailstatus['viewlog'] = $this->gs('viewlog');
                $emailstatus['logurl'] = (new moodle_url('/report/log/index.php', $params))->out(false);
                $emailstatus['cellcolor'] = 'bg-g50';
                break;
            case 'sendaftermonitoring':
                $emailstatus['sendtime'] = $this->gs('sendaftermonitoring');
                $emailstatus['sendinfo'] = $this->gs('emailsentoutsidemonitoring');
                $emailstatus['iconcolor'] = 'g600';
                break;
            case 'eventbeforemonitoring':
                $emailstatus['sendtime'] = $this->gs('eventbeforemonitoring');
                $emailstatus['sendinfo'] = $this->gs('emailsentoutsidemonitoring');
                $emailstatus['iconcolor'] = 'g600';
                break;
            case 'sending':
                $emailstatus['sendtime'] = $this->gs('sending') . userdate($record->get('timetosend'));
                $emailstatus['cellcolor'] = 'bg-y50';
                break;
            case 'sendingasap':
                $emailstatus['sendtime'] = $this->gs('sendingasap') . userdate($record->get('timetosend'));
                $emailstatus['sendinfo'] = $this->gs('sendingasapinfo');
                $emailstatus['cellcolor'] = 'bg-y50';
                break;
            case 'alreadycompletedcourse':
                $emailstatus['sendinfo'] = $this->gs('alreadycompletedcourse');
                break;
        }
        if (!array_key_exists('eventtime', $emailstatus)) {
            $emailstatus['eventtime'] = userdate($record->get('eventtime'));
        }
        return $emailstatus;
    }

    /**
     * Convenience function to mitigate repetition in format_email_status()
     *
     * @param $identifier
     * @return lang_string|string
     * @throws coding_exception
     */
    protected function gs($identifier)
    {
        return get_string($identifier, 'local_followup_email');
    }

    /**
     * Get language string for event type
     *
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
            case FOLLOWUP_EMAIL_ENROLMENT:
                $label = 'event_sinceenrolment';
                break;
            case FOLLOWUP_EMAIL_SINCE_LAST_COURSE_LOGIN:
                $label = 'event_sincelastlogin';
                break;
        }
        return $label;
    }

    /**
     * Get user's full name
     *
     * @param $userid
     * @return string
     * @throws dml_exception
     */
    public function get_user_fullname($userid)
    {
        global $DB;
        $sql = "SELECT CONCAT(u.lastname, ', ', u.firstname) as fullname
                FROM {user} u
                WHERE u.id = {$userid}";
        return ($DB->get_record_sql($sql))->fullname;
    }

    /**
     * Prepare context for mustache template
     *
     * @param renderer_base $output
     * @return array|stdClass
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function export_for_template(renderer_base $output)
    {
        $courseid = $this->base->get('courseid');
        $eventlabel = self::get_event_label($this->base->get('event'));
        $data = new stdClass();
        $data->monitorstarttime = $this->base->get('monitorstart') ? userdate($this->base->get('monitorstart')) : 0;
        $data->monitorendtime = $this->base->get('monitorend') ? userdate($this->base->get('monitorend')) : 0;
        $data->eventlabel = get_string($eventlabel, 'local_followup_email', $this->activity);
        $data->rows = $this->records;
        $data->indexurl = new moodle_url('/local/followup_email/index.php', array('courseid' => $courseid));
        return $data;
    }
}