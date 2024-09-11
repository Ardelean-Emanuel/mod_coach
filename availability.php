<?php

if (!isset($CFG)) {
    require '../../config.php';
}
require_once($CFG->dirroot.'/mod/coach/availability_form.php');
require_once($CFG->dirroot.'/mod/coach/classes/Availability.php');
require_once($CFG->dirroot.'/mod/coach/classes/CoachSettings.php');

$id = required_param('id', PARAM_INT);

if (!$cm = get_coursemodule_from_id('coach', $id)) {
    print_error('invalidcoursemodule');
}

$coach = $DB->get_record('coach', array('id'=>$cm->instance), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);

$PAGE->set_url('/mod/coach/availability.php', array('id' => $id));
$PAGE->set_title(get_string('availability'));

$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/coach/javascript/availability.js'));

$specificDates = $DB->get_records_sql('SELECT * FROM {'.Availability::TABLE_SD.'} WHERE teacher = ?', [$USER->id]);

$defaultData = Availability::setDefault();
$mform = new mod_coach_availability_form(new moodle_url('/mod/coach/availability.php', ['id' => $id]), $defaultData);
$mform->set_data($defaultData);
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/coach/view.php', ['id' => $id]));
} else if ($mform->is_submitted()) {
    if ($data = $mform->get_data()) {
        try {
            Availability::handle($data);
            // Specific dates are handled with ajax call now
            // Availability::handleSpecificDate($data); 
            redirect(new moodle_url('/mod/coach/view.php', ['id' => $id]), get_string('success'), null, 'success');
        } catch(\Exception $e) {
            redirect(new moodle_url('/mod/coach/view.php', ['id' => $id]), $e->getMessage(), null, 'error');
        }
	}    
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
