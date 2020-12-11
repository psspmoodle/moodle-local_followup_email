<?php


namespace local_followup_email;


use coding_exception;
use moodle_exception;

class event_factory
{

    /**
     * Create an object depending on the event type.
     *
     * @param $base persistent_base
     * @param $status persistent_status
     * @return event_base
     * @throws coding_exception
     * @throws moodle_exception
     */
    public static function create(persistent_base $base, persistent_status $status)
    {
        $eventtype = null;
        switch ($base->get('event')) {
            case FOLLOWUP_EMAIL_ACTIVITY_COMPLETION:
                $eventtype = new event_activity_completion($base, $status);
                break;
            case FOLLOWUP_EMAIL_ENROLMENT:
                $eventtype = new event_enrolment($base, $status);
                break;
            case FOLLOWUP_EMAIL_SINCE_LAST_COURSE_LOGIN:
                $eventtype = new event_since_last_course_login($base, $status);
                break;
        }
        return $eventtype;
    }
}