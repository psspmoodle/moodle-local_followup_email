<?php


namespace local_followup_email;

use coding_exception;
use core\invalid_persistent_exception;
use core\persistent;
use core_date;
use DateTime;
use dml_exception;
use Exception;
use lang_string;
use moodle_exception;
use moodle_url;

require_once($CFG->libdir.'/completionlib.php');

define('FOLLOWUP_EMAIL_ACTIVITY_COMPLETION', 0);
define('FOLLOWUP_EMAIL_SINCE_ENROLLMENT', 1);
define('FOLLOWUP_EMAIL_SINCE_LAST_LOGIN', 2);

class persistent_base extends persistent
{

    /** Table name for the persistent. */
    const TABLE = 'followup_email';

    protected $trackedusers;

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
        $status = new persistent_status();
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
     * @throws invalid_persistent_exception
     * @throws coding_exception
     * @throws dml_exception
     */

    public function after_create()
    {
        return persistent_status::add_enrolled_users($this);
    }

    /**
     * Get the records of all users associated with a followup email record of a particular event type
     *
     * @param $eventtype null|int Specify for event-related subset of tracked users
     * @return persistent_status[] Array of records
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_tracked_users($eventtype = null)
    {
        global $DB;
        $statusrecords = [];
        $followupid = $this->get('id');
        $sql = "SELECT fes.*
                FROM {followup_email_status} fes
                JOIN {followup_email} fe
                ON fe.id = fes.followup_email_id
                WHERE fe.id = {$followupid}";
        if (!is_null($eventtype)) {
            $sql .= " AND fe.event = {$eventtype}";
        }
        $records = $DB->get_records_sql($sql);
        foreach ($records as $record) {
            $statusrecords[] = new persistent_status(0, $record);
        }
        return $statusrecords;
    }

    /**
     * @param $userid
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     * @TODO: prob use array_filter here
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
     * @param $followup_interval
     * @return bool|lang_string
     */
    protected function validate_followup_interval($followup_interval) {
        if (!$followup_interval > 0) {
            return new lang_string('intervalerror', 'local_followup_email');
        }
        return true;
    }

}