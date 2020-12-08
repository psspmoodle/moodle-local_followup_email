**Monitor start time**
 
The intent of this setting is to make it feasible to create a followup email in an already running course. Enabling a monitor start time effectively replaces the course start date as the point in time from which the plugin begins watching for activity completion, enrolment, and last logged in events.

Envision this scenario: A course has already been open and running for some weeks. You want to create a followup email that tracks the enrolment event and sends an email after an interval of 1 minute (the next cron run after the user enrols). If you create a followup email and do not specify a custom monitor start time, the plugin will assume the default monitoring start time (i.e. the course start date), 
 