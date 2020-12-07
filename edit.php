<?php

/**
 * Followup Email edit page
 *
 * @copyright 2020 Matt Donnelly
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\notification;
use local_followup_email\form;
use local_followup_email\persistent_base;
use local_followup_email\persistent_status;

require_once("../../config.php");
require_once("classes/form.php");

$courseid = required_param('courseid', PARAM_INT);
// if followup_id is 0, we are creating a new followup email
$followupid = optional_param('followupid', null, PARAM_INT);

$PAGE->set_url('/local/followup_email/edit.php', array('courseid' => $courseid, 'followupid'=>$followupid));
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
require_login($course);
$context = context_course::instance($course->id);
require_capability('local/followup_email:managefollowupemail', $context);


// Instantiate persistent
$persistent = null;
if (!empty($followupid)) {
    $persistent = new persistent_base($followupid);
}

// Page setup
$PAGE->set_pagelayout('incourse');
$str = $followupid ? 'edititem' : 'addnewfollowupemail';
$title = get_string($str, 'local_followup_email');
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($title);

// Assemble customdata for persistent
$customdata = [
    'persistent' => $persistent,  // An instance, or null
    'userid' => $USER->id,         // For the hidden userid field
    'courseid' => $course->id,   // For the hidden courseid field
];

$followup_form = new form($PAGE->url->out(false), $customdata);

if ($followup_form->is_cancelled()) {
    redirect(new moodle_url('/local/followup_email/index.php', array('courseid' => $courseid)));
} else {
    // Get the data. This ensures that the form was validated.
    if ($data = $followup_form->get_data()) {
        try {
            if (empty($data->id)) {     // No ID: create a new record.
                $persistent = new persistent_base(0, $data);
                $persistent->create();
            } else {    // We have an ID: update the record.
                // We only want to flush tracked users if the related event or group have been changed
                $flush = $data->event != $persistent->get('event')
                    || (object_property_exists($data, 'groupid')
                    && $data->groupid != $persistent->get('groupid'));
                $persistent->from_record($data);
                $persistent->update();
                if ($flush) {
                    persistent_status::remove_users($persistent);
                    persistent_status::add_enrolled_users($persistent);
                }
            }
            notification::success(get_string('changessaved'));
        } catch (Exception $e) {
            notification::error($e->getMessage());
        }
        redirect(new moodle_url('/local/followup_email/index.php', array('courseid' => $courseid)));
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
echo html_writer::div(get_string('editnotice', 'local_followup_email'), 'bg-y50 border-y300 p-3 mb-3');
$followup_form->display();
echo $OUTPUT->footer();