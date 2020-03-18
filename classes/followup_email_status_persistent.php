<?php


namespace local_followup_email;

use coding_exception;
use context_course;
use core\invalid_persistent_exception;
use core\persistent;
use dml_exception;

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
            )
        );
    }

    /**
     * @param $userid
     * @param $persistent
     * @throws invalid_persistent_exception
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function add_user($userid, $persistent) {
        $user = [['id' => $userid]];
        static::add_users($user, $persistent);
    }

    /**
     * Add users that should be sent a follow up email. This could be every user in the course (no groupid specified)
     * or just users in a group.
     *
     * @param $userids array Array of userids
     * @param $persistent followup_email_persistent
     * @return bool
     * @throws dml_exception
     * @throws coding_exception
     * @throws invalid_persistent_exception
     */
    public static function add_users(array $userids, persistent $persistent)
    {
        // Is the user is already tracked by this followup email instance?
        foreach ($userids as $user) {
            if (!$persistent->is_user_tracked($user['id'])) {
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
     * @param persistent
     * @return bool
     * @throws dml_exception
     */
    public static function add_enrolled_users(persistent $persistent)
    {
        //Context to pass to get_enrolled_users()
        $context = context_course::instance($persistent->get('courseid'));
        // We need to limit the users by group if there is one supplied, and we only need the user ids
        $users = get_enrolled_users($context, null, $groupid = $persistent->get('groupid'), 'u.id');
        $usersarray = [];
        foreach($users as $user) {
            $usersarray[] = (array) $user;
        }
        return static::add_users($usersarray, $persistent);
    }

    /**
     * Remove user(s) from a follow up email instance.
     *
     * @param persistent
     * @return bool
     * @throws dml_exception
     */
    public static function remove_users(persistent $persistent, $userid = null)
    {
        $status = new static();
        $filter = ['followup_email_id' => $persistent->get('id')];
        if ($userid) {
            $filter['userid'] = $userid;
        }
        if ($records = $status::get_records($filter)) {
            foreach ($records as $record) {
                $record->delete();
            }
            return true;
        }
        return false;
    }

}