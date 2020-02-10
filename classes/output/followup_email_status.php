<?php

namespace local_followup_email\output;

defined('MOODLE_INTERNAL') || die();

use local_followup_email\followup_email_persistent;
use renderable;
use renderer_base;
use templatable;
use stdClass;

class followup_email_status implements renderable, templatable
{
    public $followupemail;
    public $records;

    public function __construct(followup_email_persistent $followupemail, $records)
    {
        $this->followupemail = $followupemail;
        $this->records = $this->process_records($records);
    }

    public function process_records($records)
    {
        if (!$records) {
            return null;
        }
        $rows = array();
        foreach ($records as $record) {
            $row = array(
                'userid' => $record->get_fullname($record->get('userid')),
                'email_to_be_sent' => $record->get_time_to_be_sent($this->followupemail),
                'email_time_sent' => $record->get('email_time_sent'),
            );
            $rows[] = $row;
        }
        return $rows;
    }

    public function export_for_template(renderer_base $output)
    {
        $data = new stdClass();
        $data->rows = $this->records;
        return $data;
    }
}