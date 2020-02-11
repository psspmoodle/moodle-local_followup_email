<?php


namespace local_followup_email\task;

use completion_info;
use context_course;
use core\task\scheduled_task;
use core_user;
use local_followup_email\event\followup_email_sent;
use local_followup_email\followup_email_persistent;
use local_followup_email\followup_email_status_persistent;


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
     * @throws \moodle_exception
     */
    public function execute() {
        global $DB;
        // Get all instances of followup emails
        $records = (new followup_email_persistent())::get_records();
        foreach ($records as $record) {
            // Get the course records database object
            $course = $DB->get_record('course', array('id' => $record->get('courseid')), '*', MUST_EXIST);
            // Get the
            $cm = (get_fast_modinfo($course->id))->get_cm($record->get('cmid'));
            // This will be 0 if a group wasn't selected
            $groupid = $record->get('groupid');
            $completioninfo = new completion_info($course);
            $users = $completioninfo->get_tracked_users(null, null, $groupid ?? null);
            foreach ($users as $user) {
                // Get completion data for user for specified course module
                $cmdata = $completioninfo->get_data($cm, false, $user->id);
                // Get the interval
                $interval = $record->get('followup_interval');
                // Has the interval since the user completed the course module elapsed?
                if (($cmdata->timemodified + $interval) > time()) {
                    // Instantiate a persistent for the status table
                    $email_status = new followup_email_status_persistent();
                    // Get the record of the user for whom we want to update email_sent
                    $email_status->get_record_by_userid($user->id, $groupid);
                    $email_status->set('email_sent', 1);
                    $contact = core_user::get_noreply_user();
                    $subject = $record->get('email_subject');
                    $messagehtml = format_text($record->get('email_body'), FORMAT_HTML, array('trusted' => true));
                    $messagetext = html_to_text($messagehtml);

                    // Directly emailing welcome message rather than using messaging.
                    email_to_user($user, $contact, $subject, $messagetext, $messagehtml);

                    $event = followup_email_sent::create(array(
                        'context' => context_course::instance($course->id),
                        'relateduserid' => $user->id
                    ));

                    $event->trigger();
                }
            }
        }
    }

}