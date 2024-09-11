<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/coach/locallib.php');
require_once($CFG->libdir.'/filelib.php');

class mod_coach_settings_form extends moodleform {
    function definition() {
        global $CFG, $DB;

        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('settings', 'mod_coach'));

        $mform->addElement('text', 'mindays', get_string('mindays', 'mod_coach'));
        $mform->setType('mindays', PARAM_INT);
        $mform->setDefault('mindays', 0);
        $mform->addRule('mindays', null, 'numeric', null, 'client');

        $mform->addElement('text', 'maxdays', get_string('maxdays', 'mod_coach'));
        $mform->setType('maxdays', PARAM_INT);
        $mform->setDefault('maxdays', 30);
        $mform->addRule('maxdays', null, 'numeric', null, 'client');

        $this->add_action_buttons();

    }
}
