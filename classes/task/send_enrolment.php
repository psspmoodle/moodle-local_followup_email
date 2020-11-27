<?php


namespace local_followup_email\task;

use coding_exception;
use dml_exception;
use moodle_exception;

/**
 * Class send_enrolment
 * @package local_followup_email\task
 */
class send_enrolment extends send_base
{

    const EVENT_TYPE = 1;

    /**
     * Return the task's name as shown in admin screens
     *
     * @return string
     * @throws coding_exception
     */
    public function get_name()
    {
        return get_string('sendenrolment', 'local_followup_email');
    }

    /**
     * Run the task
     *
     * @throws dml_exception
     * @throws moodle_exception
     * @throws coding_exception
     */
    public function execute()
    {
        parent::execute();
    }
}
