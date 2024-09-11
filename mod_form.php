<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/coach/locallib.php');
require_once($CFG->libdir.'/filelib.php');

class mod_coach_mod_form extends moodleform_mod {
    function definition() {
        global $CFG, $DB;

        $mform = $this->_form;

        $config = get_config('userprofileupdate');

        //-------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $this->standard_intro_elements();

        //-------------------------------------------------------
        $mform->addElement('editor', 'customdescription', get_string('contentblockbeforeuserinputfields', 'mod_coach'), null,
            array (
                'subdirs' => 1,
                'maxbytes' => 0,
                'maxfiles' => EDITOR_UNLIMITED_FILES,
                'changeformat' => 0,
                'context' => \context_system::instance(),
                'noclean' => 0,
                'trusttext' => 0,
                'enable_filemanagement' => true
            )
        );
        $mform->setType('fieldcustomdescriptionname', PARAM_RAW);
        
        //-------------------------------------------------------
        $this->standard_coursemodule_elements();

        //-------------------------------------------------------
        $this->add_action_buttons();

        //-------------------------------------------------------
        $mform->addElement('hidden', 'revision');
        $mform->setType('revision', PARAM_INT);
        $mform->setDefault('revision', 1);
    }

    /**
     * Enforce defaults here.
     *
     * @param array $defaultvalues Form defaults
     * @return void
     **/
    public function data_preprocessing(&$defaultvalues) {
        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('userprofileupdate');
            $defaultvalues['userprofileupdate']['format'] = $defaultvalues['contentformat'];
            $defaultvalues['userprofileupdate']['text']   = file_prepare_draft_area($draftitemid, $this->context->id, 'mod_coach',
                    'content', 0, userprofileupdate_get_editor_options($this->context), $defaultvalues['content']);
            $defaultvalues['userprofileupdate']['itemid'] = $draftitemid;

            $draftid = file_get_submitted_draft_itemid('customdescription');
            $currenttext = file_prepare_draft_area($draftid, $this->context->id, 'mod_coach', 'customdescription', 1, userprofileupdate_get_editor_options($this->context), $defaultvalues['customdescription']);
            $defaultvalues['customdescription'] = array('text' => $currenttext, 'format' => FORMAT_HTML, 'itemid' => $draftid);
        }

        if (!empty($defaultvalues['displayoptions'])) {
            $displayoptions = (array) unserialize_array($defaultvalues['displayoptions']);
            if (isset($displayoptions['printintro'])) {
                $defaultvalues['printintro'] = $displayoptions['printintro'];
            }
            if (isset($displayoptions['printheading'])) {
                $defaultvalues['printheading'] = $displayoptions['printheading'];
            }
            if (isset($displayoptions['printlastmodified'])) {
                $defaultvalues['printlastmodified'] = $displayoptions['printlastmodified'];
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

