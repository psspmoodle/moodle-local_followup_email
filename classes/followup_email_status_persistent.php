<?php


namespace local_followup_email;

use completion_info;
use core\persistent;

class followup_email_status_persistent extends persistent
{

    /** Table name for the persistent. */
    const TABLE = 'followup_email_status';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties()
    {
        return array(
            'userid' => array(
                'type' => PARAM_INT
            ),
            'followup_email_id' => array(
                'type' => PARAM_INT
            ),
            'email_sent' => array(
                'type' => PARAM_BOOL
            ),
            'email_time_sent' => array(
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED
            )
        );
    }

    /**
     * Add users that should be sent a follow up email. This could be every user in the course (no groupid specified)
     * or just users in a group.
     *
     * @param int $courseid Course id
     * @param int $groupid Group id Will be 0 if no group was selected
     * @return status[]
     * @throws \dml_exception
     */
    public static function add_tracked_users(followup_email_persistent $persistent)
    {
        global $DB;
        $courseid = $persistent->get('courseid');
        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $completioninfo = new completion_info($course);
        $users = $completioninfo->get_tracked_users(null, null, $groupid ?? null);
        foreach ($users as $user) {
            $status = new self();
            $status->set('userid', $user->id);
            $status->set('followup_email_id', $persistent->get('id'));
            $status->set('email_sent', 0);
            $status->set('email_time_sent', 0);
            $status->create();
        }

    }

    public static function get_users_by_followup_email_id($followupid)
    {
        global $DB;
        $persistents = [];
        $sql = "SELECT fes.*
                FROM {" . static::TABLE . "} fes
                JOIN {followup_email} fe
                ON fe.id = fes.followup_email_id
                WHERE fe.id = {$followupid}";

        $recordset = $DB->get_recordset_sql($sql);
        foreach ($recordset as $record) {
            $persistents[] = new static(0, $record);
        }
        $recordset->close();
        return $persistents;
    }

    /**
     * Get all records from a userid.
     *
     * @param string $username The userid.
     * @param int $groupid Group id, in case the user is in more than one group in a course
     * @return status[]
     * @throws \dml_exception
     */
    public static function get_record_by_userid($userid, $groupid = 0)
    {
        global $DB;

        $sql = "SELECT fes.*
              FROM {" . static::TABLE . "} fes
              JOIN {followup_email} fe
                ON fe.id = fes.userid
             WHERE fes.userid = {$userid}";
        // If the groupid != 0, students could be in more than one group in the course,
        // so we need further specificity.
        $sql .= $groupid ? " AND fe.groupid = {$groupid}" : '';
        $persistents = [];

        $recordset = $DB->get_recordset_sql($sql);
        foreach ($recordset as $record) {
            $persistents[] = new static(0, $record);
        }
        $recordset->close();

        return $persistents;
    }

    public function get_time_to_be_sent(followup_email_persistent $persistent)
    {
        global $DB;
        $course = $DB->get_record('course', array('id' => $persistent->get('courseid')), '*', MUST_EXIST);
        $completioninfo = new completion_info($course);
        $cmdata = $completioninfo->get_data($course, false, $this->get('userid'));
        return $cmdata->timemodified;

    }

    public function get_fullname($userid)
    {
        global $DB;
        $sql = "SELECT CONCAT(u.lastname, ', ', u.firstname) as fullname
                FROM {user} u
                WHERE u.id = {$userid}";
        return ($DB->get_record_sql($sql))->fullname;
    }

}