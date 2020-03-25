<?php

$string['pluginname'] = 'Followup Email';
$string['pluginnameplural'] = 'Followup Emails';
$string['followupemailsent'] = 'Followup email sent';
$string['addnewfollowupemail'] = 'Add new Followup Email';
$string['edititem'] = 'Edit';
$string['deleteitem'] = 'Delete';
$string['selectoption'] = 'Select:';
$string['sendfollowupemail'] = 'Send followup email';
$string['editfollowupemail'] = 'Edit Followup Email';
$string['itemdeleted'] = 'Followup Email deleted.';
$string['status'] = 'Status';
$string['returntoindex'] = 'Back';
$string['userremoved'] = '{$a->name} has been removed from the Followup Email <b>{$a->followupemail}</b>.';
$string['event_activitycompletion'] = 'Completion of {$a}';
$string['event_sinceenrolment'] = 'Enrolment date';
$string['event_sincelastlogin'] = 'Date of last course login';
$string['datetobesent'] = 'Date to be sent';
$string['emailsent'] = 'Email sent';
$string['enroled'] = 'Enroled';
$string['user'] = 'User name';
$string['subjectline'] = 'Subject line';
$string['monitoredevent'] = 'Monitored event';
$string['group'] = 'Group';
$string['modify'] = 'Modify';
$string['specifycoursemodule'] = 'Please specify an activity to monitor.';
$string['neverloggedin'] = 'Never';
$string['monitoredeventtext'] = 'Monitored event';
$string['editnotice'] = 'Note: Changing the <b>Event to monitor</b> or <b>Limit to users of group</b> values and then saving the form will flush tracked users and remove any tracking data.';
$string['userenrolmentcreated'] = 'The newly enrolled user may have been added to one or more Followup Emails in this course. Check the <a href="{$a}" target="_blank">Followup Email admin page</a> for details.';
$string['eventtomonitor'] = 'Event to monitor:';
$string['activitytomonitor'] = 'Activity to monitor:';
$string['followup_interval'] = 'Interval:';
$string['followup_interval_help'] = 'How long after the monitored event is triggered the followup email should be sent.';
$string['emailsubject'] = 'Email subject:';
$string['emailbody'] = 'Email body:';
$string['activitycompletion'] = 'Activity completion';
$string['enrolment'] = 'Enrolment';
$string['sincelastcourselogin'] = 'Since last course login';
$string['limittogroup'] = 'Limit to users of group:';
$string['monitorstart'] = 'Monitoring start time:';
$string['monitorend'] = 'Monitoring end time:';
$string['monitorstarterror'] = 'Start time must be less than end time.';
$string['monitorenderror'] = 'End time cannot be in the past.';
$string['required'] = 'This field is required.';
$string['intervalerror'] = 'Interval cannot be zero.';
$string['monitorstart_help'] = <<<EOD
<span>A custom start time means that the plugin ignores default event start times and starts monitoring from the specified time. For example, if, in a running course, you create a welcome followup email that monitors the Enrolment event and set an interval of 1 day, users who enroled in the course prior to that time will not receive that (confusing) email. </span>
<span>Default start times are:</span>
<ul>
    <li><b>Activity completion:</b> time user completes activity</li>
    <li><b>Enrolment:</b> time user enroled in course</li>
    <li><b>Since last course login:</b> time user last logged into course</li>
</ul>
EOD;
$string['monitorend_help'] = 'Followup email monitoring goes on indefinitely. Specifying a custom end time will disable monitoring.';
$string['eventbeforemonitoring'] = 'Event occurs before monitor start';
$string['sendaftermonitoring'] = 'Send time occurs after monitor end';
