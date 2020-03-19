<?php

namespace local_followup_email\output;

defined('MOODLE_INTERNAL') || die();

use coding_exception;
use core\persistent;
use moodle_exception;
use moodle_url;
use renderable;
use renderer_base;
use templatable;
use stdClass;
use core\output\notification;


class followup_email_index implements renderable, templatable
{
    public $courseid;
    public $records;
    public $deleteid;
    public $arerecords;

    /**
     * followup_email_index constructor.
     * @param int $courseid
     * @param persistent[] $records
     * @param int $deleteid
     */
    public function __construct($courseid, array $records, $deleteid)
    {
        $this->courseid = $courseid;
        $this->records = $this->process_records($records);
        $this->deleteid = $deleteid;
    }

    /**
     * @param persistent[] $records
     * @return array|null
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function process_records($records) {
        if (!$records) {
            return null;
        }
        $rows = array();
        foreach ($records as $record) {
            $params = array('courseid' => $this->courseid, 'followupid' => $record->get('id'));
            $event = followup_email_status::get_event_label($record->get('event'));
            $row = array(
                'title' => $record->get('email_subject'),
                'event' => get_string($event, 'local_followup_email'),
                'group' => groups_get_group_name($record->get('groupid')),
                'statusurl' => (new moodle_url('/local/followup_email/status.php', $params))->out(false),
                'editurl' => (new moodle_url('/local/followup_email/edit.php', $params))->out(false),
                'deleteurl' => (new moodle_url('/local/followup_email/index.php', $params))->out(false)
            );
            $rows[] = $row;
            $this->arerecords = !empty($rows);
        }
        return $rows;
    }

    private function get_status_table_headings() {
        return array(
            'subjectline' => get_string('subjectline','local_followup_email'),
            'monitoredevent' => get_string('monitoredevent','local_followup_email'),
            'group_heading' => get_string('group','local_followup_email'),
            'modify' => get_string('modify','local_followup_email')
        );
    }

    public function export_for_template(renderer_base $output)
    {
        $param = ['courseid' => $this->courseid];
        $data = new stdClass();
        $data->rows = $this->records;
        $data->arerecords = $this->arerecords;
        $data->addtext = get_string('addnewfollowupemail','local_followup_email');
        $data->addurl = new moodle_url('/local/followup_email/edit.php', $param);
        $data->statustext = get_string('status','local_followup_email');
        $data->edittext = get_string('edititem','local_followup_email');
        $data->deletetext = get_string('deleteitem','local_followup_email');
        $data->deleteid = $this->deleteid;
        $deleted = get_string('itemdeleted','local_followup_email');
        $data->issuccess = (new notification($deleted, 'success'))->export_for_template($output);
        return (object) array_merge((array) $data, $this->get_status_table_headings());
    }
}