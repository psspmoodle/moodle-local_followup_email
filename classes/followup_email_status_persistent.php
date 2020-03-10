<?php


namespace local_followup_email;

use completion_info;
use context_course;
use core\persistent;
use stdClass;

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

    public static function add_user($userid, $persistent) {
        $user = ['id' => $userid];
        static::add_users([$user], $persistent);
    }

    /**
     * Add users that should be sent a follow up email. This could be every user in the course (no groupid specified)
     * or just users in a group.
     *
     * @param $userids array Array of userids
     * @param followup_email_persistent
     * @return bool
     * @throws \dml_exception
     */
    public static function add_users(array $userids, followup_email_persistent $persistent)
    {
        // Is the user is already tracked by this followup email instance?
        foreach ($userids as $user) {
            if (!static::is_user_tracked($user['id'], $persistent)) {
                $status = new static();
                $status->set('userid', $user['id']);
                $status->set('followup_email_id', $persistent->get('id'));
                $status->create();
            }
        }
        return true;
    }

    /**
     * Add users enrolled in course––and possibly just members of a group——that should be sent a follow up email.
     *
     * @param followup_email_persistent
     * @return bool
     * @throws \dml_exception
     */
    public static function add_enrolled_users(followup_email_persistent $persistent)
    {
        //Context to pass to get_enrolled_users()
        $context = context_course::instance($persistent->get('courseid'));
        // We need to limit the users by group if there is one supplied, and we only need the user ids
        $users = get_enrolled_users($context, null, $groupid = $persistent->get('groupid'), 'u.id');
        $usersarray = [];
        foreach($users as $user) {
            $usersarray[] = ['id' => $user->id];
        }
        return static::add_users($usersarray, $persistent);
    }

    /**
     * Gets all users in a course or group who are tracked by the given followup email.
     *
     *
     * @param $persistent followup_email_persistent
     * @return array of followupids
     */
    public static function get_tracked_users(followup_email_persistent $persistent)
    {
        global $DB;
        $statusrecords = [];
        $followupid = $persistent->get('id');
        $sql = "SELECT fes.*
                FROM {" . static::TABLE . "} fes
                JOIN {followup_email} fe
                ON fe.id = fes.followup_email_id
                WHERE fe.id = {$followupid}";
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

    public static function is_user_tracked($userid, followup_email_persistent $persistent)
    {
        if ($trackedusers = static::get_tracked_users($persistent)) {
            foreach($trackedusers as $trackeduser) {
                if ($trackeduser->get('userid') == $userid) {
                    return true;
                }
            }
        }
        return false;
    }




    /**
     * Get all Folloup Emails associated with a user in a course
     * @param $userid
     * @return array of persistents
     */
    public static function get_user($userid)
    {

    }

    public static function delete_user($userid, $persistent) {

        $status = new followup_email_status_persistent();
        if ($records = $status::get_records(array('followup_email_id' => $this->get('id')))) {
            foreach ($records as $record) {
                $record->delete();
            }
            return true;
        }
        //    public static function ($todelete)
//    {
//        if (!is_array($todelete)) {
//            $todelete = static::get_tracked_users($todelete);
//        }
//        foreach ($todelete as $user) {
//            $user->delete();
//        }
//        return true;
//    }
    }



}