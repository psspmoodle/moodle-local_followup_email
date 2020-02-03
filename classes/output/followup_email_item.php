<?php

namespace local_followup_email\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use stdClass;

class followup_email_item implements renderable, templatable
{
    public $id;
    public $cmid;
    public $title;
    public $coursemodule;
    public $editlink;
    public $courseid;

    public function __construct(array $properties)
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