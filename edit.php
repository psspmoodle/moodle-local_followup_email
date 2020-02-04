<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Followup Email edit page
 *
 * @copyright 2020 Matt Donnelly
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\notification;
use local_followup_email\followup_email_form;
use local_followup_email\followup_email_persistent;

require_once("../../config.php");
require_once("classes/followup_email_form.php");

$courseid = required_param('courseid', PARAM_INT);
// if followup_id is 0, we are creating a new followup email
$followup_id = optional_param('id', null, PARAM_INT);

$PAGE->set_url('/local/followup_email/edit.php', array('courseid' => $courseid, 'id'=>$followup_id));
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
require_login($course);
$context = context_course::instance($course->id);

// Instantiate persistent
$persistent = null;
if (!empty($followup_id)) {
    $persistent = new followup_email_persistent($followup_id);
}

// Page setup
$PAGE->set_pagelayout('incourse');
$str = $followup_id ? 'edititem' : 'addnewitem';
$title = get_string($str, 'local_followup_email');
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($title);

// Assemble customdata for persistent
$customdata = [
    'persistent' => $persistent,  // An instance, or null.
    'userid' => $USER->id,         // For the hidden userid field.
    'courseid' => $course->id
];

$followup_form = new followup_email_form($PAGE->url->out(false), $customdata);

if ($followup_form->is_cancelled()) {
    redirect(new moodle_url('/local/followup_email/index.php', array('courseid' => $courseid)));
} else {
    // Get the data. This ensures that the form was validated.
    if (($data = $followup_form->get_data())) {

        try {
            if (empty($data->id)) {
                // If we don't have an ID, we know that we must create a new record.
                $persistent = new followup_email_persistent(0, $data);
                $persistent->create();
            } else {
                // We had an ID, this means that we are going to update a record.
                $persistent->from_record($data);
                $persistent->update();
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
$followup_form->display();
echo $OUTPUT->footer();