<?php


namespace local_followup_email\task;

use completion_info;
use core\task\scheduled_task;
use local_followup_email\event\followup_email_sent;
use local_followup_email\followup_email_persistent;


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
     */
    public function execute() {
        global $DB;
        $records = (new followup_email_persistent())::get_records();
        foreach ($records as $record) {
            $course = $DB->get_record('course', array('id' => $record->get('courseid')), '*', MUST_EXIST);
            $cm = (get_fast_modinfo($course->id))->get_cm($record->get('cmid'));
            $groupid = $record->get('groupid');
            $completioninfo = new completion_info($course);
            $users = $completioninfo->get_tracked_users(null, null, $groupid);
            foreach ($users as $user) {
                $cmdata = $completioninfo->get_data($cm, false, $user->id);
                $interval = $record->get('followup_interval');
                $intervalelapsed = ($cmdata->timemodified + $interval) > time();
                if ($cmdata->completionstate == 1 && $intervalelapsed) {
                    $contact = core_user::get_noreply_user();
                    $subject = $record->get('email_subject');
                    $messagehtml = format_text($record->get('email_body'), FORMAT_HTML, array('trusted' => true));
                    $messagetext = html_to_text($messagehtml);

                    // Directly emailing welcome message rather than using messaging.
                    email_to_user($user, $contact, $subject, $messagetext, $messagehtml);

                    $event = followup_email_sent::create(array(
                        'context' => context_course::instance($courseid),
                        'relateduserid' => $user->id
                    ));

                    $event->trigger();
                }
            }
        }
    }

}