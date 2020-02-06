<?php

defined('MOODLE_INTERNAL') || die;

function xmldb_local_followup_email_upgrade($oldversion)

{
    global $DB;
    $result = TRUE;
    $dbman = $DB->get_manager();

    if ($oldversion < 2020020611) {

        // Define table followup_email to be created.
        $table = new xmldb_table('followup_email');

        // Adding fields to table followup_email.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('email_subject', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('email_body', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('email_bodyformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('followup_interval', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table followup_email.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('cmid', XMLDB_KEY_FOREIGN, ['cmid'], 'course_modules', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('groupid', XMLDB_KEY_FOREIGN, ['groupid'], 'groups', ['id']);

        // Conditionally launch create table for followup_email.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Followup_email savepoint reached.
        upgrade_plugin_savepoint(true, 2020020611, 'local', 'followup_email');
    }

    if ($oldversion < 2020020611) {

        // Define table followup_email_status to be created.
        $table = new xmldb_table('followup_email_status');

        // Adding fields to table followup_email_status.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('followup_email_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('email_sent', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table followup_email_status.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('followup_email_id', XMLDB_KEY_FOREIGN, ['followup_email_id'], 'followup_email', ['id']);

        // Conditionally launch create table for followup_email_status.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Followup_email savepoint reached.
        upgrade_plugin_savepoint(true, 2020020611, 'local', 'followup_email');
    }

    return $result;

}