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
define('FOLLOWUP_EMAIL_ENROLMENT', 1);
define('FOLLOWUP_EMAIL_SINCE_LAST_COURSE_LOGIN', 2);

class persistent_base extends persistent
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
     * This is called after the form is saved and a new record is created in the database.
     *
     * @return void
     * @throws invalid_persistent_exception
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */

    public function after_create()
    {
        persistent_status::add_enrolled_users($this);
    }

    /**
     * Get the records of all users associated with a followup email record
     *
     * @return persistent_status[] Array of records
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
            $statusrecords[] = new persistent_status(0, $record);
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
     * @param bool $result
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    protected function after_update($result)
    {
        $statuses = $this->get_tracked_users();
        foreach ($statuses as $status) {
            $eventobj = event_factory::create($this, $status);
            $eventobj->update_times();
        }
        parent::after_update($result);
    }


   /**
     * This does nothing: it's here only to remind me to find a way of returning persistent_base objects instead of
     * persistents. I could overwrite this method with a cut & paste of the parent's, but that feels unwholesome.
     *
     * @param array $filters
     * @param string $sort
     * @param string $order
     * @param int $skip
     * @param int $limit
     * @return persistent[]
     */
    public static function get_records($filters = array(), $sort = '', $order = 'ASC', $skip = 0, $limit = 0) {
        return parent::get_records($filters, $sort, $order, $skip, $limit);
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