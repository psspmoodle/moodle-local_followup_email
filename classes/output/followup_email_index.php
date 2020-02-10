<?php

namespace local_followup_email\output;

defined('MOODLE_INTERNAL') || die();

use moodle_url;
use renderable;
use renderer_base;
use templatable;
use stdClass;
use core\output\notification;


class followup_email_index implements renderable, templatable
{
    public $cminfo;
    public $records;
    public $deleteid;

    public function __construct($courseid, $records, $deleteid)
    {
        $this->cminfo = get_fast_modinfo($courseid);
        $this->records = $this->process_records($records);
        $this->deleteid = $deleteid;
    }

    public function process_records($records) {
        if (!$records) {
            return null;
        }
        $rows = array();
        foreach ($records as $record) {
            $params = array('courseid' => $this->cminfo->courseid, 'followupid' => $record->get('id'));
            $row = array(
                'title' => $record->get('email_subject'),
                'coursemodule' => ($this->cminfo->get_cm($record->get('cmid')))->name,
                'group' => $record->get('groupid'),
                'statusurl' => (new moodle_url('/local/followup_email/status.php', $params))->out(false),
                'editurl' => (new moodle_url('/local/followup_email/edit.php', $params))->out(false),
                'deleteurl' => (new moodle_url('/local/followup_email/index.php', $params))->out(false)
            );
            $rows[] = $row;
        }
        return $rows;
    }

    public function export_for_template(renderer_base $output)
    {
        $param = array('courseid' => $this->cminfo->courseid);
        $data = new stdClass();
        $data->rows = $this->records;
        $data->addtext = get_string('addnewfollowupemail','local_followup_email');
        $data->addurl = new moodle_url('/local/followup_email/edit.php', $param);
        $data->statustext = get_string('status','local_followup_email');
        $data->edittext = get_string('edititem','local_followup_email');
        $data->deletetext = get_string('deleteitem','local_followup_email');
        $data->deleteid = $this->deleteid;
        $deleted = get_string('itemdeleted','local_followup_email');
        $data->issuccess = (new notification($deleted, 'success'))->export_for_template($output);
        return $data;
    }
}