<?php

/**
 * Followup email status page
 *
 * @copyright 2020 Matt Donnelly
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_followup_email\persistent_base;
use local_followup_email\output\followup_email_status;

require_once("../../config.php");
require_once("classes/form.php");

$courseid = required_param('courseid', PARAM_INT);
$followupid = required_param('followupid', PARAM_INT);
$PAGE->set_url('/local/followup_email/status.php', array('courseid'=>$courseid, 'followupid' => $followupid));

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
require_login($course);
$context = context_system::instance();
require_capability('local/followup_email:managefollowupemail', $context);

$persistent = new persistent_base($followupid);
$records = $persistent->get_tracked_users();
$statuspage = new followup_email_status($persistent, $records);

$title = $persistent->get('email_subject');
$PAGE->set_pagelayout('incourse');
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($title);
echo $OUTPUT->header();
echo $OUTPUT->heading($title);
echo $OUTPUT->render($statuspage);
echo $OUTPUT->footer();