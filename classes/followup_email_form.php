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

        // User ID.
        $mform->addElement('hidden', 'userid');
        $mform->setConstant('userid', $this->_customdata['userid']);
        // Course ID.
        $mform->addElement('hidden', 'courseid');
        $mform->setConstant('courseid', $this->_customdata['courseid']);

        //List of course modules
        $mform->addElement('select', 'cmid', 'Activity', $this->get_activities($this->_customdata['courseid']));

        // When it should be sent.
        $mform->addElement('duration', 'interval', 'When do you want to send the followup email?');

        // Location.
        $mform->addElement('text', 'email_subject', 'Email subject', $attributes = array('size'=>'50'));

        // Message.
//        $mform->addElement('editor', 'email_body', 'Email body');

        // Groups
        $mform->addElement('select', 'groupid', 'Group', $this->get_groups($this->_customdata['courseid']));

        $this->add_action_buttons();
    }

    function get_activities($courseid) {
        $courseinfo = get_fast_modinfo($courseid);
        $cms = array();
        foreach ($courseinfo->get_cms() as $cm) {
            $cms[$cm->id] = $cm->name;
        }
        $selectoption = array(0 => get_string('selectoption', 'local_followup_email'));
        $cms = $selectoption + $cms;
        return $cms;
    }

    function get_groups($courseid) {
        $groupobjects = groups_get_all_groups($courseid);
        $groups = array();
        foreach ($groupobjects as $group) {
            $groups[$group->id] = $group->name;
        }
        $selectoption = array(0 => get_string('selectoption', 'local_followup_email'));
        $groups = $selectoption + $groups;
        return $groups;
    }




}