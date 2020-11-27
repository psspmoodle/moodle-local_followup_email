<?php

$tasks = [
    [
        'classname' => 'local_followup_email\task\send_activity_completion',
        'blocking' => 0,
        'minute' => '*',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ],
    [
        'classname' => 'local_followup_email\task\send_enrolment',
        'blocking' => 0,
        'minute' => 1,
        'hour' => 1,
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ],
];