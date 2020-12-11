<?php

defined('MOODLE_INTERNAL') || die;


/**
 * This function extends the course navigation on the course admin page
 *
 * @param $navigation
 * @param $course
 * @param $context
 * @throws coding_exception
 * @throws moodle_exception
 */
function local_followup_email_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('report/participation:view', $context)) {
        $url = new moodle_url('/local/followup_email/index.php', array('courseid'=>$course->id));
        $navigation->add(get_string('pluginname', 'local_followup_email'),
            $url, navigation_node::TYPE_SETTING, null, null,
            new pix_icon('i/inbox-in', 'inbox', 'local_followup_email'));
    }
}