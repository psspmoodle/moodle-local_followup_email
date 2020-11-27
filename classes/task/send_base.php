<?php


namespace local_followup_email\task;


use coding_exception;
use context_course;
use core\persistent;
use core\task\scheduled_task;
use core_user;
use DateTime;
use dml_exception;
use local_followup_email\event\followup_email_sent;
use local_followup_email\event_activity_completion;
use local_followup_email\persistent_base;
use moodle_exception;
use stdClass;

/**
 * Class send_base
 * @package local_followup_email\task
 */
class send_base extends scheduled_task
{

    private $eventtype;

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
     * @throws dml_exception
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function execute() {
        $persistents = (new persistent_base())::get_records(['event' => static::EVENT_TYPE]);
        foreach ($persistents as $persistent) {
            if ($this->outside_monitoring_time($persistent)) {
                continue;
            }
            $statuses = $persistent->get_tracked_users(static::EVENT_TYPE);
            foreach ($statuses as $status) {
                $event = new event_activity_completion($status, $persistent);
                if ($event->is_sendable()) {
                    $user = core_user::get_user($status->get('userid'));
                    $this->send_followup_email($persistent, $user);
                    $this->log_followup_email($persistent, $user, $persistent->get('courseid'), 'event_activitycompletion');
                    $status->set('email_sent', 1);
                    $status->update();
                }
            }
        }
    }

    /**
     * Invoke native Moodle function to send the email
     *
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
     * Is there a monitor end time set, and is it AFTER the current time?
     *
     * @param persistent $persistent
     * @return bool
     * @throws coding_exception
     */

    protected function outside_monitoring_time(persistent $persistent): bool
    {
        return $persistent->get('monitorend') && $persistent->get('monitorend') < new DateTime('now');
    }

    /**
     * Log a followup email sent event
     *
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