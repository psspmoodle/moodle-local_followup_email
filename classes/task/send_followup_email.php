<?php


namespace local_followup_email\task;

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
        foreach ($persistents as $persistent) {
            $courseid = $persistent->get('courseid');
            $statuses = $persistent->get_tracked_users();
            foreach ($statuses as $status) {
                $user = $DB->get_record('user', array('id' => $status->get('userid')));
                $interval = (int) $persistent->get('followup_interval');
                $startime = (int) $persistent->get_start_time($user->id);
                // Has the interval since the user completed the course module elapsed?
                //
                if (($startime + $interval) < time() && $status->get('email_sent') == 0) {
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
}