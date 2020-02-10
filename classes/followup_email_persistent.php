<?php


namespace local_followup_email;

use completion_info;
use core\persistent;

class followup_email_persistent extends persistent
{

    /** Table name for the persistent. */
    const TABLE = 'followup_email';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'courseid' => array(
                'type' => PARAM_INT,
            ),
            'cmid' => array(
                'type' => PARAM_INT,
            ),
            'email_subject' => array(
                'type' => PARAM_TEXT,
            ),
            'email_body' => array(
                'type' => PARAM_RAW,
            ),
            'email_bodyformat' => array(
                'type' => PARAM_INT,
            ),
            'followup_interval' => array(
                'type' => PARAM_INT,
            ),
            'groupid' => array(
                'type' => PARAM_INT,
            ),
            'userid' => array(
                'type' => PARAM_INT,
            )
        );
    }

    public function before_delete()
    {
        $status = new followup_email_status_persistent();
        if ($records = $status::get_records(array('followup_email_id' => $this->get('id')))) {
            foreach ($records as $record) {
                $record->delete();
            }
            return true;
        }
        return false;
    }

    public function after_create()
    {
        global $DB;
        $courseid = $this->get('courseid');
        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        // This will be 0 if a group wasn't selected
        $groupid = $this->get('groupid');
        $completioninfo = new completion_info($course);
        $users = $completioninfo->get_tracked_users(null, null, $groupid ?? null);
        $status = new followup_email_status_persistent();
        foreach ($users as $user) {

        }

    }

}