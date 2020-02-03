<?php


namespace local_followup_email;

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
            'time_abstract_followup' => array(
                'type' => PARAM_INT,
            ),
            'time_completed' => array(
                'type' => PARAM_INT,
            ),
            'time_actual_followup' => array(
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


}