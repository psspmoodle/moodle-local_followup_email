<?php


namespace local_followup_email;

use cm_info;
use coding_exception;
use completion_info;
use context_course;
use core\invalid_persistent_exception;
use core\persistent;
use DateTime;
use dml_exception;
use Exception;
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

    /**
     * @param followup_email_persistent $persistent
     * @param bool $prettify Return formatted date
     * @return bool|string
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_start_time(followup_email_persistent $persistent, $prettify = false)
    {
        global $DB;
        $userid = $this->get('userid');
        $courseid = $persistent->get('courseid');
        $cm = new stdClass();
        $cm->id = $persistent->get('cmid');
        $starttime = 0;
        switch ($persistent->get('event')) {
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
                    $starttime = $lastaccess;
                }
                break;
        }
        if ($starttime > 0) {
            return $prettify ? $this->prettify_timestamp($starttime) : $starttime;
        }
        return 0;
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