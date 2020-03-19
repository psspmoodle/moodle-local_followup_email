<?php

namespace local_followup_email;

use core\form\persistent;

require_once("../../lib/formslib.php");

class followup_email_form extends persistent {

    /** @var string Persistent class name. */
    protected static $persistentclass = 'local_followup_email\\followup_email_persistent';

    /**
     * Define the form.
     */
    public function definition()
    {
        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];
        // User ID
        $mform->addElement('hidden', 'userid');
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
        $mform->addElement('duration', 'followup_interval', get_string('whentosend', 'local_followup_email'), $options);

        // Email subject
        $mform->addElement('text', 'email_subject', get_string('emailsubject', 'local_followup_email'), $attributes = array('size' => '50'));

        // Message
        $mform->addElement('editor', 'email_body', get_string('emailbody', 'local_followup_email'));

        // Groups
        if ($groups = $this->get_groups($courseid)) {
            $mform->addElement('select', 'groupid', get_string('limittogroup', 'local_followup_email'), $this->get_groups($courseid));
        };

        $this->add_action_buttons();
    }

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

    private function get_events() {
        return [
            FOLLOWUP_EMAIL_ACTIVITY_COMPLETION => get_string('activitycompletion', 'local_followup_email'),
            FOLLOWUP_EMAIL_SINCE_ENROLLMENT => get_string('enrolment', 'local_followup_email'),
            FOLLOWUP_EMAIL_SINCE_LAST_LOGIN => get_string('sincelastcourselogin', 'local_followup_email')
        ];
    }
}