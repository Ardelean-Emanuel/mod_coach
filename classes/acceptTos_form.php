<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/page/locallib.php');


class acceptTos_form extends moodleform {
    public function definition() {
        global $DB, $CFG, $PAGE, $USER;
			
        $mform = $this->_form;

        $mform->addElement('html', '<br><br>');
        $mform->addElement('static', 'title', '');

        $this->add_action_buttons(true, get_string('accept', 'mod_coach'));
    }
}