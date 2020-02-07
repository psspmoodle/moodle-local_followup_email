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
                'type' => PARAM_INT
            ),
            'followup_email_id' => array(
                'type' => PARAM_INT
            ),
            'email_sent' => array(
                'type' => PARAM_BOOL
            )
        );
    }

    /**
     * Get all records from a userid.
     *
     * @param string $username The userid.
     * @param int $groupid Group id, in case the user is in more than one group in a course
     * @return status[]
     * @throws \dml_exception
     */
    public static function get_record_by_userid($userid, $groupid = 0) {
        global $DB;

        $sql = 'SELECT fes.*
              FROM {' . static::TABLE . '} fes
              JOIN {followup_email} fe
                ON fe.id = fes.userid
             WHERE fes.userid = {$userid}';
        // If the groupid != 0, students could be in more than one group in the course,
        // so we need further specificity.
        $sql .= $groupid ? " AND fe.groupid = {$groupid}" : '';
        $persistents = [];

        $recordset = $DB->get_recordset_sql($sql);
        foreach ($recordset as $record) {
            $persistents[] = new static(0, $record);
        }
        $recordset->close();

        return $persistents;
    }

}