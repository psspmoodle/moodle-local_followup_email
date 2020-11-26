<?php

/**
 * Followup email index page
 *
 * @copyright 2020 Matt Donnelly
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_followup_email\persistent_base;
use local_followup_email\output\followup_email_index;

require_once("../../config.php");
require_once("classes/form.php");

$courseid = required_param('courseid', PARAM_INT);
$deleteid = optional_param('followupid',  null,PARAM_INT);
$PAGE->set_url('/local/followup_email/index.php', array('courseid'=>$courseid));

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
require_login($course);
$context = context_system::instance();
require_capability('local/followup_email:managefollowupemail', $context);

$persistent = new persistent_base();
if ($deleteid) {
    if ($todelete = $persistent::get_record(array('id' => $deleteid))) {
        $todelete->delete();
    }
}
$records = $persistent::get_records(['courseid' => $courseid]);
$index = new followup_email_index($courseid, $records, $deleteid);

$title = get_string('pluginnameplural', 'local_followup_email');
$PAGE->set_pagelayout('incourse');
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($title);
echo $OUTPUT->header();
echo $OUTPUT->heading($title);
echo $OUTPUT->render($index);
echo $OUTPUT->footer();