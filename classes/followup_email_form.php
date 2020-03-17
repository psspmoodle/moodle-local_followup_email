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
        $mform->addElement('select', 'event', 'Associated event:', $this->get_events());

        //List of course modules
        $mform->addElement('select', 'cmid', 'Activity:', $this->get_activities($courseid));
        $mform->hideIf('cmid', 'event', 'eq', 1);
        $mform->hideIf('cmid', 'event', 'eq', 2);
        $mform->setDefault('cmid', 0);
        // When it should be sent
        $mform->addElement('duration', 'followup_interval', 'When do you want to send the followup email?');

        // Email subject
        $mform->addElement('text', 'email_subject', 'Email subject:', $attributes = array('size' => '50'));

        // Message
        $mform->addElement('editor', 'email_body', 'Email body:');

        // Groups
        if ($groups = $this->get_groups($courseid)) {
            $mform->addElement('select', 'groupid', 'Group:', $this->get_groups($courseid));
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
            FOLLOWUP_EMAIL_ACTIVITY_COMPLETION => 'Activity completion',
            FOLLOWUP_EMAIL_SINCE_ENROLLMENT => 'Enrollment',
            FOLLOWUP_EMAIL_SINCE_LAST_LOGIN => 'Since last course login'
        ];
    }
}