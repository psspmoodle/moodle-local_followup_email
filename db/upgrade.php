<?php

defined('MOODLE_INTERNAL') || die;

function xmldb_local_followup_email_upgrade($oldversion)

{
    global $DB;
    $result = TRUE;
    $dbman = $DB->get_manager();

    if ($oldversion < 2020020301) {

        // Define table followup_email to be created.
        $table = new xmldb_table('followup_email');

        // Adding fields to table followup_email.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('email_subject', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('email_body', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('time_abstract_followup', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('time_completed', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('time_actual_followup', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table followup_email.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('cmid', XMLDB_KEY_FOREIGN, ['cmid'], 'course_modules', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for followup_email.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Followup_email savepoint reached.
        upgrade_plugin_savepoint(true, 2020020301, 'local', 'followup_email');
    }

    return $result;

}