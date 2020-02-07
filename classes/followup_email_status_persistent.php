<?php


namespace local_followup_email;

use core\persistent;

class followup_email_status_persistent extends persistent
{

    /** Table name for the persistent. */
    const TABLE = 'followup_email_status';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'userid' => array(
                'type' => PARAM_INT,
            ),
            'followup_email_id' => array(
                'type' => PARAM_INT,
            ),
            'email_sent' => array(
                'type' => PARAM_BOOL,
            ),
            'email_subject' => array(
                'type' => PARAM_TEXT,
            ),

        );
    }


}