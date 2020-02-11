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
                'type' => PARAM_BOOL,
                'default' => 0
            ),
            'email_time_sent' => array(
                'type' => PARAM_INT,
                'default' => 0
            )
        );
    }

    /**
     * Add users that should be sent a follow up email. This could be every user in the course (no groupid specified)
     * or just users in a group.
     *
     * @param followup_email_persistent
     * @return bool
     * @throws \dml_exception
     */
    public static function add_tracked_users(followup_email_persistent $persistent)
    {
        global $DB;
        $courseid = $persistent->get('courseid');
        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $completioninfo = new completion_info($course);
        $groupid = $persistent->get('groupid');
        $users = $completioninfo->get_tracked_users(null, null, $groupid ?? null);
        $userids = static::get_tracked_userids($persistent);
        foreach ($users as $user) {
            if (!in_array($user->id, $userids)) {
                $status = new static();
                $status->set('userid', $user->id);
                $status->set('followup_email_id', $persistent->get('id'));
                $status->create();
            }
        }
        return true;
    }

    public static function delete_tracked_users(followup_email_persistent $persistent)
    {
        global $DB;
        $users = static::get_tracked_users($persistent);
        foreach ($users as $user) {
            $user->delete();
        }
        return true;
    }


    public static function determine_tracked_users(followup_email_persistent $persistent)
    {
        static::delete_tracked_users($persistent);
        static::add_tracked_users($persistent);
    }


    /**
     * Gets all users in a course who are tracked by the followup email task.
     *
     *
     * @param $userid
     * @return array of followupids
     */
    public static function get_tracked_users(followup_email_persistent $persistent)
    {
        global $DB;
        $statuses = [];
        $courseid = $persistent->get('courseid');
        $followupid = $persistent->get('id');
        $sql = "SELECT fes.*
                FROM {" . static::TABLE . "} fes
                JOIN {followup_email} fe
                ON fe.id = fes.followup_email_id
                WHERE fe.courseid = {$courseid} 
                AND fe.id = {$followupid}";
        // If the groupid != 0, students could be in more than one group in the course,
        // so we need further specificity.
        if ($groupid = $persistent->get('groupid')) {
            $sql .= " AND fe.groupid = {$groupid}";
        }
        $records = $DB->get_records_sql($sql);
        foreach ($records as $record) {
            $statuses[] = new static(0, $record);
        }
        return $statuses;
    }

    /**
     * Checks if a user is in any followup email instances in a course
     *
     * @param $userid
     * @return array of followupids
     */

    public static function get_tracked_user($courseid, $userid)
    {

    }

    public static function get_tracked_userids(followup_email_persistent $persistent)
    {
        $userids = array();
        $users = static::get_tracked_users($persistent);
        foreach($users as $user) {
            $userids[] = $user->get('userid');
        }
        return $userids;
    }

}