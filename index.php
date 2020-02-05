<?php

/**
 * Followup email admin page
 *
 * @copyright 2020 Matt Donnelly
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_followup_email\followup_email_persistent;

require_once("../../config.php");
require_once("classes/followup_email_form.php");

$courseid = required_param('courseid', PARAM_INT);
$PAGE->set_url('/local/followup_email/index.php', array('courseid'=>$courseid));

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
require_login($course);
$context = context_course::instance($course->id);

$courseinfo = get_fast_modinfo($course->id);

if (!$persistents = (new followup_email_persistent())::get_records()) {
    $editurl = new moodle_url('/local/followup_email/edit.php', array('courseid' => $courseid));
    $output = 'There are no Followup emails configured.  <a href="' . $editurl . '">Add one</a>';
} else {
    $rows = array();
    foreach ($persistents as $record) {
        $params = array('id' => $record->get('id'), 'courseid' => $courseid);
        $row = array(
            'title' => $record->get('email_subject'),
            'coursemodule' => ($courseinfo->get_cm($record->get('cmid')))->name,
            'group' => $record->get('groupid'),
            'editurl' => (new moodle_url('/local/followup_email/edit.php', $params))->out(false)
        );
        $rows[] = $row;
    }
    $wrapper = new stdClass();
    $wrapper->rows = $rows;
    $renderer = $PAGE->get_renderer('core');
    $output = $renderer->render_from_template('local_followup_email/followup_table', $wrapper);
}

$title = get_string('pluginname', 'local_followup_email');
$PAGE->set_pagelayout('incourse');
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($title);
echo $OUTPUT->header();
echo $OUTPUT->heading($title);
echo $output;
echo $OUTPUT->footer();