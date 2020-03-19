<?php

defined('MOODLE_INTERNAL') || die;

/**
 * This function extends the navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 */
function local_followup_email_extend_navigation_course($navigation, $course, $context) {
    global $CFG, $OUTPUT;
    if (has_capability('report/participation:view', $context)) {
        $url = new moodle_url('/local/followup_email/index.php', array('courseid'=>$course->id));
        $navigation->add(get_string('pluginname', 'local_followup_email'),
            $url, navigation_node::TYPE_SETTING, null, null,
            new pix_icon('i/inbox-in', 'inbox', 'local_followup_email'));
    }
}