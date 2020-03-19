<?php


namespace local_followup_email\task;

use coding_exception;
use completion_info;
use context_course;
use core\persistent;
use core\task\scheduled_task;
use core_user;
use dml_exception;
use local_followup_email\event\followup_email_sent;
use local_followup_email\followup_email_persistent;
use local_followup_email\followup_email_status_persistent;
use local_followup_email\output\followup_email_status;
use moodle_exception;


class send_followup_email extends scheduled_task
{
    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('sendfollowupemail', 'local_followup_email');
    }

    /**
     * Execute the task.
     * @throws moodle_exception
     */
    public function execute()
    {
        global $DB;
        // Get all instances of followup emails
        $persistents = (new followup_email_persistent())::get_records();
        $versiondate = substr(get_config('local_followup_email', 'version'), 0, -1);
        $installtime = strtotime($versiondate);
        foreach ($persistents as $persistent) {
            $courseid = $persistent->get('courseid');
            $statuses = $persistent->get_tracked_users();
            foreach ($statuses as $status) {
                $user = $DB->get_record('user', array('id' => $status->get('userid')));
                if ($this->is_sendable($persistent, $status)) {
                    $contact = core_user::get_noreply_user();
                    $subject = $persistent->get('email_subject');
                    $messagehtml = format_text($persistent->get('email_body'), FORMAT_HTML, array('trusted' => true));
                    $messagetext = html_to_text($messagehtml);
                    // Email the user
                    //email_to_user($user, $contact, $subject, $messagetext, $messagehtml);
                    // Trigger event for log
                    $eventlabel = followup_email_status::get_event_label($persistent->get('event'));
                    $event = followup_email_sent::create(array(
                        'context' => context_course::instance($courseid),
                        'relateduserid' => $user->id,
                        'other' => ['relatedevent' => get_string($eventlabel, 'local_followup_email')]
                    ));
                    $event->trigger();
                    // Update DB record
                    $status->set('email_sent', 1);
                    $status->update();
                }
            }
        }
    }

    /**
     * 1. starttime needs to greater than zero else students who haven't logged in yet will be sent email
     * 2. current time has to have passed the summed timestamp of start time and interval
     * 3. email should not have been sent to the user already
     * 4. if the event is SINCE_ENROLMENT, and the specified interval
     *
     * @param persistent $persistent
     * @param persistent $status
     * @return bool
     * @throws coding_exception
     */

    private function is_sendable($persistent, $status) {
        $sendable = false;
        $interval = (int) $persistent->get('followup_interval');
        $starttime = (int) $persistent->get_start_time($status->get('userid'));
        if ($starttime > 0 && ($starttime + $interval) < time() && $status->get('email_sent') == 0) {
            $sendable = true;
            if ($persistent->get('event') == FOLLOWUP_EMAIL_SINCE_ENROLLMENT) {

            }
        }
        return $sendable;
    }

}