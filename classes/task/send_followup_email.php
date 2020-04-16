<?php

namespace local_followup_email\task;

use coding_exception;
use context_course;
use core\persistent;
use core\task\scheduled_task;
use core_user;
use dml_exception;
use local_followup_email\event\followup_email_sent;
use local_followup_email\followup_email_persistent;
use local_followup_email\output\followup_email_status;
use moodle_exception;
use moodle_url;
use stdClass;

class send_followup_email extends scheduled_task
{
    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_name()
    {
        return get_string('sendfollowupemail', 'local_followup_email');
    }

    /**
     * @throws coding_exception
     * @throws dml_exception
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
                if ($persistent->is_sendable($status)) {
                    $this->send_followup_email($persistent, $user);
                    $this->log_followup_email($persistent, $user, $courseid);
                    $status->set('email_sent', 1);
                    $status->update();
                }
            }
        }
    }

    /**
     * @param persistent $persistent
     * @param $user
     * @return bool
     * @throws coding_exception
     * @throws moodle_exception
     */
    private function send_followup_email(persistent $persistent, $user)
    {
        $contact = core_user::get_noreply_user();
        $subject = $persistent->get('email_subject');
        $message = $this->filter($user, $persistent->get('email_body'));
        $messagehtml = format_text($message, FORMAT_HTML, array('trusted' => true));
        $messagetext = html_to_text($messagehtml);
        email_to_user($user, $contact, $subject, $messagetext, $messagehtml);
        return true;
    }

    /**
     * @param persistent $persistent
     * @param stdClass $user
     * @param int $courseid
     * @throws coding_exception
     * @throws moodle_exception
     */
    private function log_followup_email(persistent $persistent, stdClass $user, int $courseid)
    {
        $eventlabel = followup_email_status::get_event_label($persistent->get('event'));
        $event = followup_email_sent::create(array(
            'context' => context_course::instance($courseid),
            'relateduserid' => $user->id,
            'other' => ['relatedevent' => get_string($eventlabel, 'local_followup_email')]
        ));
        $event->trigger();
    }

    /**
     * @param $body
     * @return mixed
     * @throws moodle_exception
     */
    private function filter($user, $body) {
        $fields = array(
            'firstname' => $user->firstname,
        );
        foreach ($fields as $k => $v) {
            $body = preg_replace('/\[\[' . $k . ']]/', $v, $body);
        }
        return $body;
    }

}