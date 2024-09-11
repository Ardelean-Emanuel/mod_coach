<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/page/locallib.php');
require_once($CFG->dirroot.'/repository/lib.php');
require_once($CFG->dirroot.'/lib/adminlib.php');

$context = context_system::instance();

class addEvent_form extends moodleform {
    public function definition() {

        global $DB, $CFG, $PAGE, $USER, $context, $instance, $editing;
			
		$mform = $this->_form;
        //Header
        if($editing) {
            $mform->addElement('header', 'mformheader', 'Edit Event');
            $mform->addElement('hidden', 'eventid');
            $mform->setType('eventid', PARAM_INT);
        } else {
            $mform->addElement('header', 'mformheader', 'Create Event');
        }

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);
        
        $mform->addElement('hidden', 'eventtype');
        $mform->setType('eventtype', PARAM_INT);
        
        $mform->addElement('hidden', 'timestamp');
        $mform->setType('timestamp', PARAM_INT);

        //Date
        $mform->addElement('static', 'date', get_string("date"));
        $mform->setType('date', PARAM_INT);	

        //Hour
        $mform->addElement('static', 'hour', get_string("hour"));
        $mform->setType('hour', PARAM_INT);	

        //Timezone
        $mform->addElement('static', 'timezone', get_string("timezone"));
        $mform->setType('timezone', PARAM_TEXT);	

        //Name
        $mform->addElement('text', 'name', get_string('name', 'mod_coach'), array('placeholder' => 'Name', 'size' => 50, 'maxlength = "255"'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        
        // Email
        $mform->addElement('text', 'email', get_string('email', 'mod_coach'), array('placeholder' => 'Email Address', 'size' => 50, 'maxlength' => 255));
        $mform->setType('email', PARAM_TEXT);
        $mform->addRule('email', get_string('required'), 'required', null, 'client');
        $mform->addRule('email', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addRule('email', get_string('invalidemail'), 'email', null, 'client');

  
        $mform->setType('message', PARAM_RAW);
        $mform->setDefault('message', array('text' => 'Introduce yourself briefly, including your current assignment details. Then, explain the specific area or theme you need coaching on and your goals. This will help your coach understand your needs and prepare accordingly. DO NOT INCLUDE ANY CONFIDENTIAL INFORMATION HERE.'));
        $mform->addRule('message', get_string('required'), 'required', null, 'client');

		// Action buttons.
        $buttonarray = array();
        if($editing) {
            $buttonarray[] = $mform->createElement('submit', 'submitbutton', 'Edit');
        } else {
            $buttonarray[] = $mform->createElement('submit', 'submitbutton', 'Create');
        }
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', '', false);

         // Terms of Use Agreement
         $mform->addElement('header', 'termsheader', 'Terms of Use Agreement');
         $mform->addElement('checkbox', 'accept_terms', 'I agree to the Supplementary Terms of Use for the Coaching Services Platform.');
         $mform->addRule('accept_terms', 'You must agree to the terms to proceed.', 'required', null, 'client');
 
         // Optionally add a link to the full terms of service document
         $mform->addElement('static', 'termslink', '', '<a href="'.$CFG->wwwroot.'/mod/coach/tos.php" target="_blank">Read the full terms of use</a>');
    }
	
    /**
     *
     * The validation() function defines the form validation.
     *
     * @param My_Type $data
     * @param My_Type $files
     */
    public function validation($data, $files) {
        global $DB, $CFG;
        $errors = parent::validation($data, $files);
		return $errors;
    }
}