<?php

namespace local_followup_email;

use context_course;
use core\notification;
use local_followup_email\output\followup_email_status;
use moodle_url;

class followup_email {

    public static function user_enrolment_created($event)
    {
        $data = $event->get_data();
        $courseid = $data['courseid'];
        // Get all the followup instances associated with this course
        $persistents = followup_email_persistent::get_records(['courseid' => $courseid]);
        // Add the new user to all of them.
        foreach ($persistents as $persistent) {
            followup_email_status_persistent::add_tracked_users($persistent);
        }

//        $event = welcome_email_sent::create(array(
//            'context' => context_course::instance($courseid),
//            'relateduserid' => $data['relateduserid']
//        ));
//
//        $event->trigger();

        $followupurl = (new moodle_url('/local/followup_email/index.php', array('courseid' => $data['courseid'])))->out(false);
        $notification = "The newly enrolled user may have been added to one or more Followup Emails in this course. 
        Check the <a href=\"{$followupurl}\" target=\"_blank\">Followup Email admin page</a> for details.";
        notification::warning($notification);

        return true;
    }

    public static function group_member_added($event)
    {
        $data = $event->get_data();
        $courseid = $data['courseid'];
        $groupid = $data['objectid'];
        $groups = groups_get_all_groups($data['courseid']);
        // Get all the followup instances associated with this course AND this group
        $persistents = followup_email_persistent::get_records(['courseid' => $courseid, 'groupid' => $groupid]);
        if ($persistents) {
            foreach ($persistents as $persistent) {
                if (in_array($persistent->get('groupid'), array_keys($groups))) {
                    followup_email_status_persistent::add_tracked_users($persistent);
                }
            }

        }
    }
}


