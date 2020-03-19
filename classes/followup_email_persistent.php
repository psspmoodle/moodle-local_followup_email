<?php


namespace local_followup_email;

use coding_exception;
use completion_info;
use core\persistent;
use DateTime;
use dml_exception;
use Exception;
use lang_string;
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
            'groupid' => array(
                'type' => PARAM_INT,
                'default' => 0
            ),
            'userid' => array(
                'type' => PARAM_INT,
            )
        );
    }

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
     * @param followup_email_persistent $persistent
     * @param bool $prettify Return formatted date
     * @return bool|string
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_start_time($userid, $prettify = false)
    {
        global $DB;
        $courseid = $this->get('courseid');
        $cm = new stdClass();
        $cm->id = $this->get('cmid');
        $starttime = 0;
        switch ($this->get('event')) {
            case FOLLOWUP_EMAIL_ACTIVITY_COMPLETION:
                $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
                $completioninfo = new completion_info($course);
                $completiondata = $completioninfo->get_data($cm, false, $userid);
                $starttime =  $completiondata->timemodified > 0 ? $completiondata->timemodified : 0;
                break;
            case FOLLOWUP_EMAIL_SINCE_ENROLLMENT:
                $sql = "SELECT ue.timestart
                        FROM {user_enrolments} ue
                        JOIN {enrol} e
                        ON ue.enrolid = e.id
                        WHERE e.courseid = {$courseid}
                        AND ue.userid = {$userid}";
                $record = $DB->get_record_sql($sql, null, MUST_EXIST);
                $starttime = $record->timestart;
                break;
            case FOLLOWUP_EMAIL_SINCE_LAST_LOGIN:
                if ($lastaccess = $DB->get_record('user_lastaccess', array('userid' => $userid, 'courseid' => $courseid))) {
                    $starttime = $lastaccess->timeaccess;
                }
                break;
        }
        if ($starttime > 0) {
            if ($prettify) {
                return $this->prettify_timestamp($starttime);
            }
        }
        return $starttime;
    }

    /**
     * @param followup_email_persistent $persistent
     * @param bool $prettify
     * @return bool|string
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_send_time($userid, $prettify = false)
    {
        $starttime = $this->get_start_time($userid);
        $sendtime = 0;
        if ($starttime > 0) {
            $sendtime = $starttime + $this->get('followup_interval');
            if ($prettify) {
                return $this->prettify_timestamp($sendtime);
            }
        }
        return $sendtime;
    }

    /**
     * @param string $timestamp
     * @return string
     * @throws Exception
     */
    public static function prettify_timestamp($timestamp) {
        $datetime = new DateTime("@$timestamp");
        return $datetime->format('M d, Y');
    }

}