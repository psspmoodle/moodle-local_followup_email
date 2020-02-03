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
        $mform->addElement('select', 'type', 'Activity', $this->_customdata['cms']);

        // When it should be sent.
        $mform->addElement('duration', 'time_abstract_followup', 'When do you want to send the followup email?');

        // Location.
        $mform->addElement('text', 'email_subject', 'Email subject', $attributes = array('size'=>'50'));

        // Message.
        $mform->addElement('editor', 'email_body', 'Email body');

        // Groups
        $mform->addElement('select', 'type', 'Group', $this->_customdata['groups']);

        $this->add_action_buttons();
    }


}