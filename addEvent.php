<?php

require('../../config.php');
require_once($CFG->dirroot.'/mod/coach/classes/addEvent_form.php');
require_once($CFG->dirroot.'/mod/coach/classes/Event.php');

$id = required_param('cmid', PARAM_INT);
$date = required_param('date', PARAM_TEXT);
$hour = required_param('hour', PARAM_TEXT);

$eventtype = required_param('eventtype', PARAM_INT);

//Clear temp var
unset($_SESSION['eventdata'.$USER->id]);

if (!$cm = get_coursemodule_from_id('coach', $id)) {
    print_error('invalidcoursemodule');
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
$url = new moodle_url($CFG->wwwroot.'/mod/coach/addEvent.php', ['cmid' => $id, 'eventtype' => $eventtype, 'date' => $date, 'hour' => $hour]);

$PAGE->set_url($url);
$PAGE->set_title('New Event');
// $PAGE->requires->jquery();
$editing = 0;
$mform = new addEvent_form($url);

$defaultData = new stdClass();
$defaultData->id = $id;
$defaultData->userid = $USER->id;
$dateString = $date. ' ' . $hour;
$timestamp = strtotime($dateString);

$defaultData->name = $USER->firstname . ' ' . $USER->lastname;
$defaultData->email = $USER->email;
$defaultData->date = $date;
$defaultData->hour = $hour;
$defaultData->timestamp = $timestamp;
$defaultData->eventtype = $eventtype;
$defaultData->timezone = \core_date::get_server_timezone();
$mform->set_data($defaultData);
if($mform->is_cancelled()) {
        redirect(new moodle_url('/mod/coach/view.php?id='.$id));
}
else if ($data = $mform->get_data()) {
    try {
        // var_dump($DB->get_field_sql('SELECT payment_type FROM {coach_event_type} WHERE id=?', array($eventtype))); die;
        if($DB->get_field_sql('SELECT payment_type FROM {coach_event_type} WHERE id=?', array($eventtype)) == 'paid') {
            $_SESSION['eventdata'.$USER->id] = $data;
            redirect(new moodle_url('/mod/coach/checkout.php?id='.$eventtype));
        } else {
            \mod_coach\Event::addEvent($data);
            redirect(new moodle_url('/mod/coach/view.php?id='.$id), 'New event has been added', null, 'success');
        }
    } catch(\Exception $e) {
        redirect(new moodle_url('/mod/coach/view.php?id='.$id), $e->getMessage(), null, 'error');
    }
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();