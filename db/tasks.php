<?php

$tasks = [
    [
        'classname' => 'local_followup_email\task\send_followup_email_activity',
        'blocking' => 0,
        'minute' => '*',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ],
    [
        'classname' => 'local_followup_email\task\send_followup_email_enrolment',
        'blocking' => 0,
        'minute' => 1,
        'hour' => 1,
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ],
];