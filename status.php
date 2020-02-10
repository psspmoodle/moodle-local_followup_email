<?php

/**
 * Followup email status page
 *
 * @copyright 2020 Matt Donnelly
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_followup_email\followup_email_persistent;
use local_followup_email\followup_email_status_persistent;
use local_followup_email\output\followup_email_index;
use local_followup_email\output\followup_email_status;

require_once("../../config.php");
require_once("classes/followup_email_form.php");

$courseid = required_param('courseid', PARAM_INT);
$followupid = required_param('followupid', PARAM_INT);
$PAGE->set_url('/local/followup_email/status.php', array('courseid'=>$courseid, 'followupid' => $followupid));

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
require_login($course);
$context = context_course::instance($course->id);

$persistent = new followup_email_persistent($followupid);
$status = followup_email_status_persistent::get_users_by_followup_email_id($persistent->get('id'));
$statuspage = new followup_email_status($persistent, $status);

$title = get_string('pluginname', 'local_followup_email');
$PAGE->set_pagelayout('incourse');
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($title);
echo $OUTPUT->header();
echo $OUTPUT->heading($title);
echo $OUTPUT->render($statuspage);
echo $OUTPUT->footer();