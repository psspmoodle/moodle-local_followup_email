<?php

namespace local_followup_email;

use coding_exception;
use context_system;
use core\event\course_module_completion_updated;
use core\event\course_module_deleted;
use core\event\group_deleted;
use core\event\group_member_added;
use core\event\group_member_removed;
use core\event\user_enrolment_created;
use core\event\user_enrolment_deleted;
use core\invalid_persistent_exception;
use core\notification;
use dml_exception;
use moodle_exception;
use moodle_url;

/**
 * Class observer: responds to various events
 * @package local_followup_email
 */
class observer
{
    /**
     * Mechanism for the enrolment event type.
     *
     * @param user_enrolment_created $event
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_persistent_exception
     * @throws moodle_exception
     */
    public static function user_enrolment_created(user_enrolment_created $event)
    {
        $data = $event->get_data();
        // Get all the followup instances associated with this course
        $persistents = persistent_base::get_records(['courseid' => $data['courseid']]);
        foreach ($persistents as $persistent) {
            // Add the new user to all of them that don't have a groupid
            if (!$persistent->get('groupid')) {
                [$addeduser] = persistent_status::add_users(array($data['relateduserid']), $persistent);
            }
            // Check if there is an enrolment event followup email to update with a new 'timetosend' value
            if (isset($addeduser) && $persistent->get('event') == 1) {
                $timetosend = $data['timecreated'] + $persistent->get('followup_interval');
                $addeduser->set('timetosend', $timetosend);
                $addeduser->update();
            }
        }
        // Notify the admin about users being added to followup emails
        $url = (new moodle_url('/local/followup_email/index.php', array('courseid' => $data['courseid'])))->out(false);
        $context = context_system::instance();
        if (has_capability('local/followup_email:managefollowupemail', $context)) {
            $notification = get_string('userenrolmentcreated', 'local_followup_email', $url);
            notification::warning($notification);
        }
    }

    /**
     * Housekeeping function that removes a user from the followup email status table when their
     * enrolment has been deleted.
     *
     * @param user_enrolment_deleted $event
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function user_enrolment_deleted(user_enrolment_deleted $event)
    {
        global $DB;
        $data = $event->get_data();
        $userid = $data['relateduserid'];
        // Get all the followup instances associated with this course
        $persistents = persistent_base::get_records(['courseid' => $data['courseid']]);
        foreach ($persistents as $persistent) {
            // Is the user tracked in this followup email instance?
            if ($persistent->is_user_tracked($userid)) {
                persistent_status::remove_users($persistent, $userid);
                // Notify admin that a user from removed from a followup email
                $context = context_system::instance();
                if (has_capability('local/followup_email:managefollowupemail', $context)) {
                    $userobj = $DB->get_record('user', ['id' => $userid], 'firstname, lastname');
                    $fullname = $userobj->firstname . ' ' . $userobj->lastname;
                    $a = ['name' => $fullname, 'followupemail' => $persistent->get('email_subject')];
                    $notification = get_string('userremoved', 'local_followup_email', $a);
                    notification::warning($notification);
                }
            }
        }
    }

    /**
     * Housekeeping function that adds a user to a followup email if they've been added to a group
     * associated with a followup email.
     *
     * @param group_member_added $event
     * @returns void
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_persistent_exception
     */
    public static function group_member_added(group_member_added $event)
    {
        $data = $event->get_data();
        $courseid = $data['courseid'];
        $groupid = $data['objectid'];
        $userid = $data['relateduserid'];
        $groups = groups_get_all_groups($data['courseid']);
        // Get all the followup instances associated with this course AND this group
        $records = persistent_base::get_records(['courseid' => $courseid, 'groupid' => $groupid]);
        foreach ($records as $persistent) {
            if (in_array($persistent->get('groupid'), array_keys($groups))) {
                persistent_status::add_users([$userid], $persistent);
            }
        }
    }

    /**
     * Housekeeping function that removes a user from a followup email if they've been removed from a group
     * associated with a followup email.
     *
     * @param group_member_removed $event
     * @returns void
     * @throws coding_exception
     */
    public static function group_member_removed(group_member_removed $event)
    {
        $data = $event->get_data();
        $courseid = $data['courseid'];
        $groupid = $data['objectid'];
        $userid = $data['relateduserid'];
        // Get all the followup instances associated with this course AND this group
        if ($persistents = persistent_base::get_records(['courseid' => $courseid, 'groupid' => $groupid])) {
            foreach ($persistents as $persistent) {
                persistent_status::remove_users($persistent, $userid);
            }
        }
    }

    /**
     * Housekeeping function that purges the followup status table of users whose group has been deleted.
     *
     * @param group_deleted $event
     * @returns void
     * @throws coding_exception
     */
    public static function group_deleted(group_deleted $event)
    {
        $data = $event->get_data();
        $courseid = $data['courseid'];
        $groupid = $data['objectid'];
        if ($persistents = persistent_base::get_records(['courseid' => $courseid, 'groupid' => $groupid])) {
            foreach ($persistents as $persistent) {
                persistent_status::remove_users($persistent);
            }
        }
    }

    /**
     * Housekeeping function that removes any followup email associated with a deleted course module.
     *
     * @param course_module_deleted $event
     * @returns void
     * @throws coding_exception
     */
    public static function course_module_deleted(course_module_deleted $event)
    {
        $data = $event->get_data();
        $cmid = $data['objectid'];
        if ($persistents = persistent_base::get_records(['cmid' => $cmid])) {
            foreach ($persistents as $persistent) {
                $persistent->delete();
            }
        }
    }

    /**
     * Checks the event data for a userid match, and updates the record with a timetosend value
     * unless 1) an email has already been sent or 2) there's already a timetosend (which means
     * the activity has already been marked as completed).
     *
     * Mechanism for the activity completion type.
     *
     * @param course_module_completion_updated $event
     * @returns void
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_persistent_exception
     */
    public static function course_module_completion_updated(course_module_completion_updated $event)
    {
        global $DB;
        $data = $event->get_data();
        $base_fields = persistent_base::get_sql_fields('fe', 'fe_');
        $status_fields = persistent_status::get_sql_fields('fes', 'fes_');
        $sql = "SELECT $base_fields, $status_fields
                FROM {" . persistent_base::TABLE . "} fe
                JOIN {" . persistent_status::TABLE . "} fes 
                ON fes.followup_email_id = fe.id
                WHERE fe.cmid = {$data['contextinstanceid']}
                AND fes.userid = {$data['relateduserid']}
                AND fe.event = 0";
        $rows = $DB->get_records_sql($sql, []);
        foreach ($rows as $row) {
            $base_data = persistent_base::extract_record($row, 'fe_');
            $status_data = persistent_status::extract_record($row, 'fes_');
            if (!$status_data->email_sent || $status_data->timetosend) {
                $status_data->timetosend = $data['timecreated'] + $base_data->followup_interval;
                $status = new persistent_status(0, $status_data);
                $status->update();
            }
        }
    }
}

