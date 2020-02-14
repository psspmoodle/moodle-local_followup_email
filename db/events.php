<?php

/**
 * followup_email event handler definition.
 *
 * @package local_followup_email
 * @category event
 * @copyright 2020 Matt Donnelly
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


// List of observers.

$observers = array(

    array(
        'eventname'   => '\core\event\user_enrolment_created',
        'callback'    => '\local_followup_email\followup_email::user_enrolment_created'
    ),
    array(
        'eventname'   => '\core\event\group_member_added',
        'callback'    => '\local_followup_email\followup_email::group_member_added'
    ),
    array(
        'eventname'   => '\core\event\group_member_removed',
        'callback'    => '\local_followup_email\followup_email::group_member_removed'
    )

);