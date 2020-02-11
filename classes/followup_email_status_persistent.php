<?php


namespace local_followup_email;

use completion_info;
use context_course;
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
        $context = context_course::instance($persistent->get('courseid'));
        // We need to limit the users by group if there is one supplied
        $users = get_enrolled_users($context, null, $groupid = $persistent->get('groupid'));
        // If this is an add only (and not preceded by a delete_tracked_users, i.e. in the edit.php form)
        // we need to check if the user is already tracked
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

//    public static function add_tracked_user($userid)

    public static function delete_tracked_users(followup_email_persistent $persistent)
    {
        $users = static::get_tracked_users($persistent);
        foreach ($users as $user) {
            $user->delete();
        }
        return true;
    }

    /**
     * Gets all users in a course or group who are tracked by the followup email task.
     *
     *
     * @param $userid
     * @return array of followupids
     */
    public static function get_tracked_users(followup_email_persistent $persistent)
    {
        global $DB;
        $statusrecords = [];
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
            $statusrecords[] = new static(0, $record);
        }
        return $statusrecords;
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