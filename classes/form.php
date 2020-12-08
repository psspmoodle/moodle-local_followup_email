<?php

namespace local_followup_email;

use coding_exception;
use core\form\persistent;
use moodle_exception;

require_once("../../lib/formslib.php");

class form extends persistent {

    /** @var string Persistent class name. */
    protected static $persistentclass = 'local_followup_email\\persistent_base';

    /** @var array Fields to remove when getting the final data.
     * Note: not adding 'userid' results in the error "Unexpected property 'userid' requested."
     * This is because it doesn't exist as a field in the persistent.
     */

    protected static $fieldstoremove = array('submitbutton', 'userid');

    /**
     * Define the form.
     */
    public function definition()
    {
        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];

        // User ID
        $mform->addElement('hidden', 'userid');
        // Not adding this results in a debugging message
        $mform->setType('userid', PARAM_INT);
        $mform->setConstant('userid', $this->_customdata['userid']);
        // Course ID
        $mform->addElement('hidden', 'courseid');
        $mform->setConstant('courseid', $this->_customdata['courseid']);

        // Event that will trigger email
        $mform->addElement('select', 'event', get_string('eventtomonitor', 'local_followup_email'), $this->get_events());

        //List of course modules
        $mform->addElement('select', 'cmid', get_string('activitytomonitor', 'local_followup_email'), $this->get_activities($courseid));
        $mform->hideIf('cmid', 'event', 'eq', 1);
        $mform->hideIf('cmid', 'event', 'eq', 2);
        $mform->setDefault('cmid', 0);
        // When it should be sent
        $options = ['optional' => false, 'defaultunit' => "86400"];
        $mform->addElement('duration', 'followup_interval', get_string('followup_interval', 'local_followup_email'), $options);
        $mform->addHelpButton('followup_interval', 'followup_interval', 'local_followup_email');

        // Start time
        $mform->addElement('date_time_selector', 'monitorstart', get_string('monitorstart', 'local_followup_email'), array('optional' => true));
        $mform->addHelpButton('monitorstart', 'monitorstart', 'local_followup_email');

        // End time
        $mform->addElement('date_time_selector', 'monitorend', get_string('monitorend', 'local_followup_email'), array('optional' => true));
        $mform->addHelpButton('monitorend', 'monitorend', 'local_followup_email');

        // Email subject
        $mform->addElement('text', 'email_subject', get_string('emailsubject', 'local_followup_email'), $attributes = array('size' => '50'));
        $mform->addRule('email_subject', get_string('required', 'local_followup_email'), 'required', null, 'server', false, false);

        // Message
        $mform->addElement('editor', 'email_body', get_string('emailbody', 'local_followup_email'));
        $mform->addRule('email_body', get_string('required', 'local_followup_email'), 'required', null, 'server', false, false);

        // Groups
        if ($groups = $this->get_groups($courseid)) {
            $mform->addElement('select', 'groupid', get_string('limittogroup', 'local_followup_email'), $groups);
        };

        // Timestamp comparison
        $validate_monitorstart = function($times) {
            if ($st = $times[0]) {
                $starttime = make_timestamp($st['year'], $st['month'], $st['day'], $st['hour'], $st['minute']);
            }
            if ($et = $times[1]) {
                $monitorend = make_timestamp($et['year'], $et['month'], $et['day'], $et['hour'], $et['minute']);
            }
            if (isset($starttime) && isset($monitorend)) {
                return $starttime <= $monitorend;
            }
            return true;
        };

        $validate_monitorend = function($monitorend) {
            return $monitorend > time();
        };

        // This needs to be here at the end instead of with 'starttime' because otherwise Quickforms won't think 'monitorend' is defined yet…
        $mform->addRule(array('monitorstart', 'monitorend'), get_string('monitorstarterror', 'local_followup_email'), 'callback', $validate_monitorstart);
        $mform->addRule('monitorend', get_string('monitorenderror', 'local_followup_email'), 'callback', $validate_monitorend);

        $this->add_action_buttons();
    }

    /**
     * @param $courseid
     * @return array
     * @throws coding_exception
     * @throws moodle_exception
     */
    private function get_activities($courseid) {
        $courseinfo = get_fast_modinfo($courseid);
        $cms = [];
        foreach ($courseinfo->get_cms() as $cm) {
            $cms[$cm->id] = $cm->name;
        }
        $selectoption = [get_string('selectoption', 'local_followup_email')];
        $cms = $selectoption + $cms;
        return $cms;
    }

    /**
     * @param $courseid
     * @return array
     * @throws coding_exception
     */
    private function get_groups($courseid) {
        $groupobjects = groups_get_all_groups($courseid);
        $groups = [];
        foreach ($groupobjects as $group) {
            $groups[$group->id] = $group->name;
        }
        if ($groups) {
            $selectoption = array(get_string('selectoption', 'local_followup_email'));
            $groups = $selectoption + $groups;
        }
        return $groups;
    }

    /**
     * @return array
     * @throws coding_exception
     */
    private function get_events() {
        return [
            FOLLOWUP_EMAIL_ACTIVITY_COMPLETION => get_string('activitycompletion', 'local_followup_email'),
            FOLLOWUP_EMAIL_ENROLMENT => get_string('enrolment', 'local_followup_email'),
            FOLLOWUP_EMAIL_SINCE_LAST_COURSE_LOGIN => get_string('sincelastcourselogin', 'local_followup_email')
        ];
    }
}