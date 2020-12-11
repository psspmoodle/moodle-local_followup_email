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
use local_followup_email\persistent_base;
use local_followup_email\persistent_status;
use moodle_exception;
use stdClass;

/**
 * Class send_followup_email
 * @package local_followup_email\task
 */
class send_followup_email extends scheduled_task
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
     * Executes the task. Because this will probably be running every cron (once a minute),
     * get everything needed in a single DB query and parse it out.
     *
     * @throws dml_exception
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function execute()
    {
        global $DB;
        $base_fields = persistent_base::get_sql_fields('fe', 'fe_');
        $status_fields = persistent_status::get_sql_fields('fes', 'fes_');
        // $status_fields has to go first here because unique IDs will be returned, and get_records_sql() below
        // requires this. If $base_fields was first, multiple associative arrays would come back with the same index.
        $sql = "SELECT $status_fields, $base_fields, u.*
            FROM {" . persistent_base::TABLE . "} fe
            JOIN {" . persistent_status::TABLE . "} fes 
            ON fes.followup_email_id = fe.id
            JOIN {user} u
            ON u.id = fes.userid";
        $rows = $DB->get_records_sql($sql, []);
        foreach ($rows as $row) {
            $base_data = persistent_base::extract_record($row, 'fe_');
            $base = new persistent_base(0, $base_data);
            $status_data = persistent_status::extract_record($row, 'fes_');
            $status = new persistent_status(0, $status_data);
            if (!$status->get('email_sent')
            && $status_data->sendtime <= (new DateTime('now'))->getTimestamp()
            && $status_data->sendtime !== 0) {
                $user = array_filter((array)$row, function ($key) {
                    return !preg_match('/^fes?_/', $key);
                }, ARRAY_FILTER_USE_KEY);
                $this->send_followup_email($base, (object)$user);
                $this->log_followup_email($base, (object)$user, $base->get('courseid'), 'event_activitycompletion');
                $status->set('email_sent', 1);
                $status->update();
            }
        }
    }

    /**
     * Invoke native Moodle function to send the email
     *
     * @param persistent $persistent
     * @param $user stdClass record from user table
     * @return bool
     * @throws coding_exception
     * @throws moodle_exception
     */
    protected function send_followup_email(persistent $persistent, stdClass $user)
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
     * Log a followup email sent event.
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
     * Simple filter for macro replacement. Needs development.
     *
     * @param $user
     * @param $body
     * @return mixed
     * @TODO: Add more macros.
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