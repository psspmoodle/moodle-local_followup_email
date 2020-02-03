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
 * Followup email admin page
 *
 * @copyright 2020 Matt Donnelly
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_followup_email\followup_email_form;

require_once("../../config.php");
require_once("classes/followup_email_form.php");

$followup_id = required_param('id', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);


$PAGE->set_url('/local/followup_email.php', array('id'=>$followup_id, 'courseid' => $courseid));

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login($course);
$context = context_course::instance($course->id);

$title = get_string('pluginname', 'local_followup_email');

$PAGE->set_pagelayout('incourse');

$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($title);

$groupobjects = groups_get_all_groups($course->id);
$groups = array();
foreach ($groupobjects as $group) {
    $groups[$group->id] = $group->name;
}

$courseinfo = get_fast_modinfo($course->id);
$cms = array();
foreach ($courseinfo->get_cms() as $cm) {
    $cms[$cm->id] = $cm->name;
}


$customdata = [
    'persistent' => null,  // An instance, or null.
    'userid' => $USER->id,         // For the hidden userid field.
    'courseid' => $course->id,
    'cms' => $cms,
    'groups' => $groups
];

$feform = new followup_email_form($PAGE->url->out(false), $customdata);

if ($feform->is_cancelled()) {
    // You need this section if you have a cancel button on your form
    // here you tell php what to do if your user presses cancel
    // probably a redirect is called for!
    // PLEASE NOTE: is_cancelled() should be called before get_data().
    redirect(new moodle_url('/local/followup_email/index.php', array('id' => $courseid)));
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
$feform->display();
echo $OUTPUT->footer();