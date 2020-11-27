<?php


namespace local_followup_email\task;


use coding_exception;

/**
 * Class send_activity_completion
 * @package local_followup_email\task
 */
class send_activity_completion extends send_base
{

    const EVENT_TYPE = 0;

    /**
     * Return the task's name as shown in admin screens
     *
     * @return string
     * @throws coding_exception
     */
    public function get_name()
    {
        return get_string('sendactivitycompletion', 'local_followup_email');
    }

    /**
     * Execute the task
     */
    public function execute()
    {
        parent::execute();
    }

}