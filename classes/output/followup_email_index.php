<?php

namespace local_followup_email\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use stdClass;
use core\output\notification;
use local_followup_email\followup_email_status_persistent;


class followup_email_index implements renderable, templatable
{
    public $id;
    public $cmid;
    public $title;
    public $coursemodule;
    public $editlink;
    public $courseid;

    public function __construct($persistents, array $properties)
    {
        $this->id = $properties['id'];
        $this->cmid = $properties['cmid'];
        $this->title = $properties['title'];
        $this->coursemodule = $properties['coursemodule'];
        $this->courseid = $properties['courseid'];
        $this->editlink = new \moodle_url("/local/followup_email/edit.php", array('id' => $this->id, 'courseid' => $this->courseid));
    }

    public function export_for_template(renderer_base $output)
    {
        $data = new stdClass();
        $data->id = $this->id;
        $data->title = $this->title;
        $data->coursemodule = $this->coursemodule;
        $data->editlink = $this->editlink->out(false);
        return $data;
    }
}

// OLD Index.php code

$courseinfo = get_fast_modinfo($course->id);
$output = '';

//
if (!$persistents = (new followup_email_persistent())::get_records()) {
    $editurl = new moodle_url('/local/followup_email/edit.php', array('courseid' => $courseid));
    $output .= 'There are no Followup emails configured.  <a href="' . $editurl . '">Add one</a>';
} else {
    if ($deleteid) {
        $fe_table = new followup_email_persistent($deleteid);
        $fe_table->delete();
        $success = new notification('Followup Email deleted.', 'success');
        $output .= $OUTPUT->render($success);
    }
    $rows = array();
    foreach ($persistents as $record) {
        $params = array('followupid' => $record->get('id'), 'courseid' => $courseid);
        $row = array(
            'title' => $record->get('email_subject'),
            'coursemodule' => ($courseinfo->get_cm($record->get('cmid')))->name,
            'group' => $record->get('groupid'),
            'editurl' => (new moodle_url('/local/followup_email/edit.php', $params))->out(false),
            'deleteurl' => (new moodle_url('/local/followup_email/index.php', $params))->out(false)
        );
        if ($params['followupid'] != $deleteid) {
            $rows[] = $row;
        }
    }
    $wrapper = new stdClass();
    $wrapper->rows = $rows;
    $renderer = $PAGE->get_renderer('core');
    $output .= $renderer->render_from_template('local_followup_email/followup_table', $wrapper);
}