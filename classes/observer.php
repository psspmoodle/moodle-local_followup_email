<?php

namespace local_followup_email;

use coding_exception;
use core\invalid_persistent_exception;
use core\notification;
use dml_exception;
use moodle_exception;
use moodle_url;

class observer
{
    /**
     * @param $event
     * @return bool
     * @throws coding_exception
     * @throws invalid_persistent_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function user_enrolment_created($event)
    {
        $data = $event->get_data();
        $courseid = $data['courseid'];
        // Get all the followup instances associated with this course
        $persistents = followup_email_persistent::get_records(['courseid' => $courseid]);
        // Add the new user to all of them that don't have a groupid
        if ($persistents) {
            foreach ($persistents as $persistent) {
                if (!$persistent->get('groupid')) {
                    followup_email_status_persistent::add_user($data['relateduserid'], $persistent);
                }
            }
            $url = (new moodle_url('/local/followup_email/index.php', array('courseid' => $data['courseid'])))->out(false);
            $notification = get_string('userenrolmentcreated', 'local_followup_email', $url);
            notification::warning($notification);
        }
        return true;
    }

    public static function user_enrolment_deleted($event)
    {
        global $DB;
        $data = $event->get_data();
        $courseid = $data['courseid'];
        $userid = $data['relateduserid'];
        // Get all the followup instances associated with this course
        $persistents = followup_email_persistent::get_records(['courseid' => $courseid]);
        foreach ($persistents as $persistent) {
            // Is the user tracked in this followup email instance?
            if ($persistent->is_user_tracked($userid)) {
                followup_email_status_persistent::remove_users($persistent, $userid);
                $userobj = $DB->get_record('user', ['id' => $userid], 'firstname, lastname');
                $fullname = $userobj->firstname . ' ' . $userobj->lastname;
                $a = ['name' => $fullname, 'followupemail' => $persistent->get('email_subject')];
                $notification = get_string('userremoved', 'local_followup_email', $a);
                notification::warning($notification);
            }
        }
        return true;
    }

    public static function group_member_added($event)
    {
        $data = $event->get_data();
        $courseid = $data['courseid'];
        $groupid = $data['objectid'];
        $userid = $data['relateduserid'];
        $groups = groups_get_all_groups($data['courseid']);
        // Get all the followup instances associated with this course AND this group
        $records = followup_email_persistent::get_records(['courseid' => $courseid, 'groupid' => $groupid]);
        if ($records) {
            foreach ($records as $persistent) {
                if (in_array($persistent->get('groupid'), array_keys($groups))) {
                    followup_email_status_persistent::add_user($userid, $persistent);
                }
            }

        }
    }

    public static function group_member_removed($event)
    {
        $data = $event->get_data();
        $courseid = $data['courseid'];
        $groupid = $data['objectid'];
        $userid = $data['relateduserid'];
        // Get all the followup instances associated with this course AND this group
        $persistents = followup_email_persistent::get_records(['courseid' => $courseid, 'groupid' => $groupid]);
        foreach ($persistents as $persistent) {
            followup_email_status_persistent::remove_users($persistent, $userid);
        }
    }

    public static function group_deleted($event)
    {
        $data = $event->get_data();
        $courseid = $data['courseid'];
        $groupid = $data['objectid'];
        $persistents = followup_email_persistent::get_records(['courseid' => $courseid, 'groupid' => $groupid]);
        foreach ($persistents as $persistent) {
            followup_email_status_persistent::remove_users($persistent);
        }
    }

    public static function course_module_deleted($event)
    {
        $data = $event->get_data();
        $cmid = $data['objectid'];
        $persistents = followup_email_persistent::get_records(['cmid' => $cmid]);
        foreach ($persistents as $persistent) {
            $persistent->delete();
        }
    }

}

