<?php

use mod_coach\Coach;
require('../../config.php');
require_once($CFG->dirroot.'/mod/coach/classes/EventType_form.php');

$id = required_param('id', PARAM_INT);

if (!$cm = get_coursemodule_from_id('coach', $id)) {
    print_error('invalidcoursemodule');
}
$coach = $DB->get_record('coach', array('id'=>$cm->instance), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);

$PAGE->set_url('/mod/coach/addeventtype.php', array('id' => $id));
$PAGE->set_title('New Event Type');
$PAGE->requires->jquery();

$FormType = 'add';
$mform = new EventType_form($CFG->wwwroot.'/mod/coach/addeventtype.php?id='.$id);

$defaultData = new stdClass();
$defaultData->cmid = $id;
$mform->set_data($defaultData);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/coach/view.php?id='.$id));
} else if ($mform->is_submitted()) {
    if ($data = $mform->get_data()) {
        try {
            Coach::addEventType($data);
            redirect(new moodle_url('/mod/coach/eventtypes.php?id='.$id), 'New event type has been added', null, 'success');
        } catch(\Exception $e) {
            redirect(new moodle_url('/mod/coach/view.php?id='.$id), $e->getMessage(), null, 'error');
        }
	}    
}
echo $OUTPUT->header();
$mform->display();


echo $OUTPUT->footer();