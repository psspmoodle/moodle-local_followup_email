<?php


namespace local_followup_email\task;


use coding_exception;
use core_user;
use dml_exception;
use local_followup_email\event_activity_completion;
use local_followup_email\persistent_base;
use moodle_exception;

class send_followup_email_activity extends send_followup_email
{
    /**
     * Return the task's name as shown in admin screens
     *
     * @return string
     * @throws coding_exception
     */

    public function get_name()
    {
        return get_string('sendfollowupemailactivity', 'local_followup_email');
    }

    /**
     * Execute the task
     *
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */

    public function execute()
    {
        $persistents = (new persistent_base())::get_records(['event' => 0]);
        foreach ($persistents as $persistent) {
            if ($this->outside_monitoring_time($persistent)) {
                continue;
            }
            $statuses = $persistent->get_tracked_users(0);
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

}