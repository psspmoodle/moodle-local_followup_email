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
$string['event_activitycompletion'] = 'Activity completion date';
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
$string['monitoredactivity'] = 'Monitoring completion of: {$a}';
$string['editnotice'] = 'Note: Changing the <b>Event to monitor</b> or <b>Limit to users of group</b> values and then saving the form will flush tracked users and remove any tracking data.';
$string['userenrolmentcreated'] = 'The newly enrolled user may have been added to one or more Followup Emails in this course. Check the <a href="{$a}" target="_blank">Followup Email admin page</a> for details.';
$string['eventtomonitor'] = 'Event to monitor:';
$string['eventtomonitorhelp'] = 'test';
$string['activitytomonitor'] = 'Activity to monitor:';
$string['interval'] = 'Interval';
$string['interval_help'] = 'How long after the monitored event is triggered the followup email should be sent.';
$string['emailsubject'] = 'Email subject:';
$string['emailbody'] = 'Email body:';
$string['activitycompletion'] = 'Activity completion';
$string['enrolment'] = 'Enrolment';
$string['sincelastcourselogin'] = 'Since last course login';
$string['limittogroup'] = 'Limit to users of group:';
$string['starttime'] = 'Start time:';
$string['endtime'] = 'End time:';
$string['starttimeerror'] = 'Start time must be less than end time.';
$string['endtimeerror'] = 'End time must be greater than start time.';
$string['customstarttime'] = 'Specify custom start time:';
$string['customendtime'] = 'Specify custom end time:';
$string['required'] = 'This field is required.';
$string['followup_intervalerror'] = 'Interval cannot be zero.';
$string['starttime_help'] = <<<EOD
<span>A custom start time means that the plugin ignores default event start times and measures the interval from the specified time. For instance, if you create a welcome followup email monitoring the Enrolment event with an interval of 1 day, users who enroled in the course prior to that time will not receive that (unnessary and confusing) email. </span>
<span>Default start times are:</span>
<ul>
    <li><b>Activity completion:</b> time user completes activity</li>
    <li><b>Enrolment:</b> time user enroled in course</li>
    <li><b>Since last course login:</b> time user last logged into course</li>
</ul>
EOD;
$string['endtime_help'] = 'Followup email monitoring goes on indefinitely. Specifying a custom end time will disable monitoring.';