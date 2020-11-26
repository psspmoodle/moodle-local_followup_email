<?php


namespace local_followup_email;


use coding_exception;
use completion_info;
use core_date;
use DateTime;
use Exception;
use moodle_exception;
use stdClass;

class event_activity_completion extends event_base
{

    /**
     * @var $completiondata object Info about whether the user completed the course module
     */

    public $completiondata;

    /**
     * event_activity_completion constructor.
     * @param persistent_status $status Database record
     * @param persistent_base $persistent Database record
     * @throws coding_exception
     * @throws moodle_exception
     */

    public function __construct(persistent_status $status, persistent_base $persistent)
    {
        $this->status = $status;
        $this->persistent = $persistent;
        $this->eventtime = $this->get_event_time();
    }

    /**
     * @returns int|DateTime
     * @throws coding_exception
     * @throws moodle_exception
     * @throws Exception
     */

    protected function get_event_time()
    {
        $eventtime = 0;
        $courseid = $this->persistent->get('courseid');
        $cm = new stdClass();
        $cm->id = $this->persistent->get('cmid');
        $course = new stdClass();
        $course->id = $courseid;
        $completioninfo = new completion_info($course);
        $this->completiondata = $completioninfo->get_data($cm, false, $this->status->get('userid'));
        if ($this->completiondata->timemodified > 0) {
            $eventtime = (new DateTime(null, core_date::get_server_timezone_object()))->setTimestamp($this->completiondata->timemodified);
        }
        return $eventtime;
    }

}

//
//    /**
//     * @param followup_email_status_persistent $status
//     * @param int|null $courseid
//     * @param bool $infoonly
//     * @return bool|string
//     * @throws coding_exception
//     * @throws dml_exception
//     */
//    public function is_sendable(followup_email_status_persistent $status, int $courseid = null, bool $infoonly = false) {
//        $sendable = false;
//        $eventtime = 0;
//        // This will get completiondata if event type is FOLLOWUP_EMAIL_ACTIVITY_COMPLETION
//        if ($eventtimeobj = $this->get_event_time($status)) {
//            $eventtime = $eventtimeobj->getTimestamp();
//        }
//        $sendtime = $this->get_send_time($status->get('userid'));
//        $interval = $this->get('followup_interval');
//        $monitorstart = $this->get('monitorstart');
//        $monitorend = $this->get('monitorend');
//        $completion = $status->completiondata->is_course_complete($status->get('userid'));
//        $now = (new DateTime("now", core_date::get_server_timezone_object()))->getTimestamp();
//        $willnotsendinfo = '';
//        if ($eventtime) {
//            if (($monitorstart && $monitorstart < $eventtime) && ($monitorend && $monitorend < $sendtime)) {
//                $willnotsendinfo = get_string('sendaftermonitoring', 'local_followup_email');
//            } elseif ($monitorstart && $monitorstart > $eventtime)  {
//                $willnotsendinfo = get_string('eventbeforemonitoring', 'local_followup_email');
//            } elseif ($monitorend && $monitorend < $sendtime) {
//                $willnotsendinfo = get_string('sendaftermonitoring', 'local_followup_email');
//            } elseif ($completion && $this->get('event') == FOLLOWUP_EMAIL_SINCE_LAST_LOGIN) {
//                $willnotsendinfo = get_string('alreadycompletedcourse', 'local_followup_email');
//            } elseif (($eventtime + $interval) > $now) {
//                return false;
//            } else {
//                $sendable = $status->get('email_sent') ? false : true;
//            }
//        }
//        return $infoonly ? $willnotsendinfo :  $sendable;
//    }