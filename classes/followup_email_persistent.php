<?php


namespace local_followup_email;

use coding_exception;
use completion_info;
use core\persistent;
use core_date;
use DateTime;
use dml_exception;
use Exception;
use lang_string;
use moodle_exception;
use moodle_url;
use stdClass;

define('FOLLOWUP_EMAIL_ACTIVITY_COMPLETION', 0);
define('FOLLOWUP_EMAIL_SINCE_ENROLLMENT', 1);
define('FOLLOWUP_EMAIL_SINCE_LAST_LOGIN', 2);

class followup_email_persistent extends persistent
{

    /** Table name for the persistent. */
    const TABLE = 'followup_email';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'courseid' => array(
                'type' => PARAM_INT,
            ),
            'cmid' => array(
                'type' => PARAM_INT,
                'default' => 0
            ),
            'event' => array(
                'type' => PARAM_INT,
                'default' => 0
            ),
            'email_subject' => array(
                'type' => PARAM_TEXT,
            ),
            'email_body' => array(
                'type' => PARAM_RAW,
            ),
            'email_bodyformat' => array(
                'type' => PARAM_INT,
            ),
            'followup_interval' => array(
                'type' => PARAM_INT,
            ),
            'monitorstart' => array(
                'type' => PARAM_INT,
                'default' => 0
            ),
            'monitorend' => array(
                'type' => PARAM_INT,
                'default' => 0
            ),
            'groupid' => array(
                'type' => PARAM_INT,
                'default' => 0
            ),
            'userid' => array(
                'type' => PARAM_INT,
            )
        );
    }

    /**
     * @return bool|void
     * @throws coding_exception
     */
    public function before_delete()
    {
        $status = new followup_email_status_persistent();
        if ($records = $status::get_records(array('followup_email_id' => $this->get('id')))) {
            foreach ($records as $record) {
                $record->delete();
            }
            return true;
        }
        return false;
    }

    /**
     * @return bool|void
     * @throws dml_exception
     */
    public function after_create()
    {
        return followup_email_status_persistent::add_enrolled_users($this);
    }

    /**
     * @return followup_email_status_persistent[]
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_tracked_users()
    {
        global $DB;
        $statusrecords = [];
        $followupid = $this->get('id');
        $sql = "SELECT fes.*
                FROM {followup_email_status} fes
                JOIN {followup_email} fe
                ON fe.id = fes.followup_email_id
                WHERE fe.id = {$followupid}";
        $records = $DB->get_records_sql($sql);
        foreach ($records as $record) {
            $statusrecords[] = new followup_email_status_persistent(0, $record);
        }
        return $statusrecords;
    }

    /**
     * @param $userid
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     */
    public function is_user_tracked($userid)
    {
        if ($trackedusers = $this->get_tracked_users()) {
            foreach($trackedusers as $user) {
                if ($user->get('userid') == $userid) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param int $userid
     * @return DateTime|int
     * @throws coding_exception
     * @throws dml_exception
     * @throws Exception
     */
    public function get_event_time(int $userid)
    {
        global $DB;
        $eventtime = 0;
        $courseid = $this->get('courseid');
        $cm = new stdClass();
        $cm->id = $this->get('cmid');
        switch ($this->get('event')) {
            // $eventtime may be 0
            case FOLLOWUP_EMAIL_ACTIVITY_COMPLETION:
                $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
                $completioninfo = new completion_info($course);
                $completiondata = $completioninfo->get_data($cm, false, $userid);
                if ($completiondata->timemodified > 0) {
                    $eventtime = (new DateTime(null, core_date::get_server_timezone_object()))->setTimestamp($completiondata->timemodified);
                }
                break;
            // $eventtime will never be 0
            case FOLLOWUP_EMAIL_SINCE_ENROLLMENT:
                $sql = "SELECT ue.timestart
                        FROM {user_enrolments} ue
                        JOIN {enrol} e
                        ON ue.enrolid = e.id
                        WHERE e.courseid = {$courseid}
                        AND ue.userid = {$userid}";
                $record = $DB->get_record_sql($sql, null, MUST_EXIST);
                $eventtime = (new DateTime(null, core_date::get_server_timezone_object()))->setTimestamp($record->timestart);
                break;
            // $eventtime may be 0
            case FOLLOWUP_EMAIL_SINCE_LAST_LOGIN:
                if ($lastaccess = $DB->get_record('user_lastaccess', array('userid' => $userid, 'courseid' => $courseid))) {
                    if ($timestamp = $lastaccess->timeaccess) {
                        $eventtime = (new DateTime(null, core_date::get_server_timezone_object()))->setTimestamp($timestamp);
                    }
                }
                break;
        }
        return $eventtime;
    }

    /**
     * @param int $userid
     * @return int|mixed
     * @throws coding_exception
     * @throws dml_exception
     */

    public function get_send_time(int $userid)
    {
        $sendtime = 0;
        $eventtime = $this->get_event_time($userid);
        $interval = $this->get('followup_interval');
        if (is_object($eventtime) && $eventtime->getTimestamp() > 0) {
            $sendtime = $eventtime->getTimestamp() + $interval;
        }
        return $sendtime;
    }

    /**
     * @param persistent $status
     * @param bool $infoonly
     * @return bool|string
     * @throws coding_exception
     * @throws dml_exception
     */
    public function is_sendable(persistent $status, bool $infoonly = false) {
        $sendable = false;
        $eventtime = 0;
        if ($eventtimeobj = $this->get_event_time($status->get('userid'))) {
            $eventtime = $eventtimeobj->getTimestamp();
        }
        $sendtime = $this->get_send_time($status->get('userid'));
        $monitorstart = $this->get('monitorstart');
        $monitorend = $this->get('monitorend');
        $willnotsendinfo = '';
        if ($eventtime) {
            if (($monitorstart && $monitorstart < $eventtime) && ($monitorend && $monitorend < $sendtime)) {
                $willnotsendinfo = get_string('sendaftermonitoring', 'local_followup_email');
            } elseif ($monitorstart && $monitorstart > $eventtime)  {
                $willnotsendinfo = get_string('eventbeforemonitoring', 'local_followup_email');
            } elseif ($monitorend && $monitorend < $sendtime) {
                $willnotsendinfo = get_string('sendaftermonitoring', 'local_followup_email');
            } else {
                $sendable = $status->get('email_sent') ? false : true;
            }
        }
        if ($infoonly) {
            return $willnotsendinfo;
        } else {
            return $sendable;
        }
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
               $emailstatus['sendtime'] = userdate($sendtime);
               $emailstatus['cellcolor'] = $emailstatus['willnotsendinfo'] ? 'bg-r50' : 'bg-g50';
           }
        }
        return $emailstatus;
    }


    /**
     * @param $cmid
     * @return bool|lang_string
     * @throws coding_exception
     */
    protected function validate_cmid($cmid) {
        if ($this->get('event') != FOLLOWUP_EMAIL_ACTIVITY_COMPLETION) {
            $this->set('cmid', 0);
        } else {
            if (!$cmid) {
                return new lang_string('specifycoursemodule', 'local_followup_email');
            }
        }
        return true;
    }

    /**
     * @param $interval
     * @return bool|lang_string
     * @throws coding_exception
     */
    protected function validate_followup_interval($followup_interval) {
        if (!$followup_interval > 0) {
            return new lang_string('followup_intervalerror', 'local_followup_email');
        }
        return true;
    }

}