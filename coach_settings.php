<?php

if (!isset($CFG)) {
    require '../../config.php';
}
require_once($CFG->dirroot.'/mod/coach/coach_settings_form.php');
require_once($CFG->dirroot.'/mod/coach/classes/CoachSettings.php');
$id = required_param('id', PARAM_INT);
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/mod/coach/coach_settings.php');
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('settings', 'mod_coach'));

$mform = new mod_coach_settings_form(new moodle_url('/mod/coach/coach_settings.php', ['id' => $id]));

$defaultData = new stdClass();
$defaultData->mindays = CoachSettings::getMin();
$defaultData->maxdays = CoachSettings::getMax();
$mform->set_data($defaultData);
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/coach/view.php', ['id' => $id]));
} else if ($mform->is_submitted()) {
    if ($data = $mform->get_data()) {
        try {
            CoachSettings::setMin($data->mindays);
            CoachSettings::setMax($data->maxdays);
            redirect(new moodle_url('/mod/coach/view.php', ['id' => $id]), get_string('success'), null, 'success');
        } catch(\Exception $e) {
            redirect(new moodle_url('/mod/coach/view.php', ['id' => $id]), $e->getMessage(), null, 'error');
        }
	}    
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
