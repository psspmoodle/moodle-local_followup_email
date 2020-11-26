<?php

namespace local_followup_email\task;

use coding_exception;
use context_course;
use core\persistent;
use core\task\scheduled_task;
use core_user;
use DateTime;
use local_followup_email\event\followup_email_sent;
use local_followup_email\persistent_base;
use moodle_exception;
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
     *
     */
    public function execute()
    {

    }

    /**
     * @param persistent_base $persistent
     * @param $user
     * @return bool
     * @throws coding_exception
     * @throws moodle_exception
     */
    protected function send_followup_email(persistent_base $persistent, $user)
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
     * @return bool
     * @throws coding_exception
     */

    protected function outside_monitoring_time(persistent $persistent): bool
    {
        return $persistent->get('monitorend') && $persistent->get('monitorend') < new DateTime('now');
    }

    /**
     * @param persistent $persistent
     * @param stdClass $user
     * @param int $courseid
     * @param $eventname
     * @throws coding_exception
     */
    protected function log_followup_email(persistent $persistent, stdClass $user, int $courseid, $eventname)
    {
        $event = followup_email_sent::create(array(
            'context' => context_course::instance($courseid),
            'relateduserid' => $user->id,
            'other' => ['relatedevent' => get_string($eventname, 'local_followup_email')]
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