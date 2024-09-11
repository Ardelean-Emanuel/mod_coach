<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/page/locallib.php');
require_once($CFG->dirroot.'/repository/lib.php');
require_once($CFG->dirroot.'/lib/adminlib.php');

$context = context_system::instance();

class EventType_form extends moodleform {
    public function definition() {

        global $DB, $CFG, $PAGE, $USER, $context, $instance, $FormType;
			
		$mform = $this->_form;
       
        //Header
        if($FormType === 'edit'){
            $mform->addElement('header', 'mformheader', 'Edit Event Type');
        } else {
            $mform->addElement('header', 'mformheader', 'Create New Event Type');
        }

        //cm id for validation
        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);		

        if($FormType === 'edit'){
            $mform->addElement('hidden', 'id');
            $mform->setType('id', PARAM_INT);	
        }

        //Name
        $mform->addElement('text', 'name', 'Name', array('placeholder' => 'Event type name', 'size' => 50, 'maxlength = "255"'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required', 'local_shop'), 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', 'local_shop', 255), 'maxlength', 255, 'client');

        //UniqueKey
        $mform->addElement('text', 'uniquekey','Unique shortname', array('placeholder' => 'Unique shortname', 'size' => 50, 'maxlength = "255"'));
        $mform->setType('uniquekey', PARAM_TEXT);
        $mform->addRule('uniquekey', get_string('required', 'local_shop'), 'required', null, 'client');
        $mform->addRule('uniquekey', get_string('maximumchars', 'local_shop', 255), 'maxlength', 255, 'client');

        $options = array(
            'free' => 'Free',
        );
        $mform->addElement('select', 'payment_type', 'Payment type', $options);
        $mform->setType('payment_type', PARAM_TEXT);

        //Price
        // $mform->addElement('text', 'price', 'Price', array('placeholder' => 'Price', 'size' => 50, 'maxlength = "255"'));
        // $mform->setType('price', PARAM_TEXT);
        // // $mform->addRule('price', get_string('required', 'local_shop'), 'required', null, 'client');
        // $mform->addRule('price', get_string('maximumchars', 'local_shop', 255), 'maxlength', 255, 'client');

        // //currency
        // $options = array(
        //     'USD' => 'USD',
        //     'CAD' => 'CAD',
        //     'EUR' => 'EUR',
        //     'GBP' => 'GBP',
        // );
        // $mform->addElement('select', 'currency', get_string('currency', 'local_shop'), $options);
        // $mform->setType('currency', PARAM_TEXT);
        // $mform->setDefault('currency', 'CAD');

        // //Payee
        // $options = array(
        //     'ajax' => 'core_user/form_user_selector',
        //     'multiple' => false,
        // );
        // $mform->addElement('autocomplete', 'payee', 'Designated payee', array(), $options);

        // $mform->addElement('checkbox', 'firstsessionfree', 'First session free');
        // // $mform->addRule('firstsessionfree', null, 'required', null, 'client');		
        // $mform->setType('firstsessionfree', PARAM_INT);
        // $mform->setDefault('firstsessionfree', 0);	

        //currency
        $options = array(
            // '15' => '15 min',
            // '30' => '30 min',
            // '45' => '45 min',
            '60' => '60 min',
        );
        $mform->addElement('select', 'duration', 'Duration', $options);
        $mform->setType('duration', PARAM_TEXT);
        $mform->setDefault('duration', '15');

        //Disabled
        $mform->addElement('checkbox', 'disabled', 'Disabled');
        // $mform->addRule('disabled', null, 'required', null, 'client');		
        $mform->setType('disabled', PARAM_INT);
        $mform->setDefault('disabled', 0);	

        //Long Description
        // $mform->addElement('editor', 'custom_receipt', 'Custom email receipt', null, page_get_editor_options($context));
        // $mform->setType('custom_receipt', PARAM_RAW);
        // $mform->addRule('custom_receipt', get_string('required', 'local_shop'), 'required', null, 'client');
     
		// Action buttons.
        $buttonarray = array();
        if($FormType === 'edit'){
            $buttonarray[] = $mform->createElement('submit', 'submitbutton', 'Edit event type');
        } else {
            $buttonarray[] = $mform->createElement('submit', 'submitbutton', 'Add event type');
        }
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', '', false);
    }
	
    /**
     *
     * The validation() function defines the form validation.
     *
     * @param My_Type $data
     * @param My_Type $files
     */
    public function validation($data, $files) {
        global $DB, $CFG, $FormType;
        $errors = parent::validation($data, $files);

		// Conditions for dates.
       
        
        if($data['uniquekey']){
            if($FormType === 'edit'){
                if($DB->get_field_sql('SELECT id FROM {coach_event_type} WHERE uniquecode=? AND timedeleted=0 AND id!=?', array($data['uniquekey'], $data['id']))){
                    $errors['uniquekey'] = get_string('uniquekeyistaken', 'local_shop');
                }
            } else {
                if($DB->get_field_sql('SELECT id FROM {coach_event_type} WHERE uniquecode=? AND timedeleted=0', array($data['uniquekey']))){
                    $errors['uniquekey'] = get_string('uniquekeyistaken', 'local_shop');
                }
            }
        }
		return $errors;
    }
	
    public function data_preprocessing($defaultvalues) {
        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('custom_receipt');
            $defaultvalues['custom_receipt']['format'] = $defaultvalues['contentformat'];
            $defaultvalues['custom_receipt']['text']   = file_prepare_draft_area($draftitemid, $context->id,
                                                'local_shop', 'content', 0,
                                                page_get_editor_options($context), $defaultvalues['content']);
            $defaultvalues['custom_receipt']['itemid'] = $draftitemid;
        }
        if (!empty($defaultvalues['displayoptions'])) {
            $displayoptions = unserialize($defaultvalues['displayoptions']);
            if (isset($displayoptions['printintro'])) {
                $defaultvalues['printintro'] = $displayoptions['printintro'];
            }
            if (isset($displayoptions['printheading'])) {
                $defaultvalues['printheading'] = $displayoptions['printheading'];
            }
            if (!empty($displayoptions['popupwidth'])) {
                $defaultvalues['popupwidth'] = $displayoptions['popupwidth'];
            }
            if (!empty($displayoptions['popupheight'])) {
                $defaultvalues['popupheight'] = $displayoptions['popupheight'];
            }
        }
    }
}