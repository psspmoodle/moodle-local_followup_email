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

use local_followup_email\followup_email_persistent;
use local_followup_email\output\followup_email_item;

require_once("../../config.php");
require_once("classes/followup_email_form.php");

$courseid = required_param('courseid', PARAM_INT);

$PAGE->set_url('/local/followup_email/index.php', array('courseid'=>$courseid));

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login($course);

$context = context_course::instance($course->id);
$courseinfo = get_fast_modinfo($course->id);

if (!$persistents = (new followup_email_persistent())::get_records()) {
    $output = 'There are no Followup emails configured. ';
    $output .= '<a href="/moodle/local/followup_email/edit.php?courseid=' . $courseid . '">Add one</a>';
} else {

    $output = '<div class="table"><table>';
    $renderer = $PAGE->get_renderer('core');

    $rows = array();
    foreach ($persistents as $record) {
        $row = array(
            'id' => $record->get('id'),
            'title' => $record->get('email_subject'),
            'cmid' => $record->get('cmid'),
            'courseid' => $courseid
        );

        $coursemodule = $courseinfo->get_cm($record->get('cmid'));
        $row['coursemodule'] = $coursemodule->name;
        $rows[] = $row;
    }

    foreach ($rows as $row) {
        $templatecontext = new followup_email_item($row);
        $output .= $renderer->render($templatecontext);
    }
    $output .= '</table></div>';
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