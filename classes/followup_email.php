<?php

namespace local_followup_email;

use context_course;
use local_cohort_welcome\event\welcome_email_sent;
use local_followup_email\output\followup_email_status;

class followup_email {

    public static function user_enrolment_created($event) {
        global $CFG, $DB;

        $data = $event->get_data();
        $courseid = $data['courseid'];
        $course = $DB->get_record('course', array('id' => $courseid), '*',MUST_EXIST);
        followup_email_status::is_user_tracked($courseid, $data['relateduserid']);



//        $event = welcome_email_sent::create(array(
//            'context' => context_course::instance($courseid),
//            'relateduserid' => $data['relateduserid']
//        ));
//
//        $event->trigger();
//
//        $notification = "Cohort welcome email sent to " . $user->email . ' for course ' .  $course->fullname . '.';
//        \core\notification::success($notification);

        return true;
    }
}


