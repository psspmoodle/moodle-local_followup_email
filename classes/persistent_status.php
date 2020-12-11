<?php


namespace local_followup_email;

use coding_exception;
use context_course;
use core\invalid_persistent_exception;
use core\persistent;
use dml_exception;
use moodle_exception;

class persistent_status extends persistent
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
            'eventtime' => array(
                'type' => PARAM_INT,
                'default' => 0
            ),
            'timetosend' => array(
                'type' => PARAM_INT,
                'default' => 0
            ),
            'email_sent' => array(
                'type' => PARAM_BOOL,
                'default' => 0
            )
        );
    }

    /**
     * Add users enroled in course––and possibly just members of a group——that should be sent a follow up email.
     *
     * @param $persistent persistent_base
     * @return void
     * @throws dml_exception
     * @throws coding_exception|invalid_persistent_exception
     * @throws moodle_exception
     */
    public static function add_enroled_users(persistent_base $persistent)
    {
        $context = context_course::instance($persistent->get('courseid'));
        // We need to limit the users by group if there is one supplied, and we only need the user ids
        $users = get_enrolled_users($context, null, $groupid = $persistent->get('groupid'), 'u.id');
        static::add_users(array_keys($users), $persistent);
    }

    /**
     * Add users that should be sent a follow up email. This could be every user in the course (no groupid specified)
     * or just users in a group.
     *
     * NOTE: A student enroled via multiple enrolment methods may be tracked using only one method!
     * For example: student A is enroled via a cohort sync AND a manual enrolment (for whatever reason…error?) and then
     * unenroled from the manual method. This could remove student A from the list of tracked users, even though
     * student A is still enroled in the course via cohort sync.
     *
     * @param $userids array Array of userids
     * @param persistent_base $base
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_persistent_exception
     * @throws moodle_exception
     */
    public static function add_users(array $userids, persistent_base $base)
    {
        foreach ($userids as $user) {
            // Is the user is already tracked?
            if (!$base->is_user_tracked($user)) {
                $status = new static();
                $status->set('userid', $user);
                $status->set('followup_email_id', $base->get('id'));
                // Send to event_factory for further processing
                $eventobj = event_factory::create($base, $status->create());
                $eventobj->set_eventtime_and_sendtime();
            }
        }
    }

    /**
     * Remove user(s) from a follow up email instance.
     *
     * @param $persistent persistent
     * @param null $userid
     * @return void
     * @throws coding_exception
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
        }
    }
}