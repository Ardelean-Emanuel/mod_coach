<?php

require('../../config.php');
require_once($CFG->dirroot.'/mod/coach/classes/addEvent_form.php');
require_once($CFG->dirroot.'/mod/coach/classes/Event.php');

$id = required_param('cmid', PARAM_INT);
$eventid = required_param('eventid', PARAM_INT);
$date = required_param('date', PARAM_TEXT);
$hour = required_param('hour', PARAM_TEXT);

$Event = new \mod_coach\Event($eventid);
// var_dump($Event); die;
if (!$cm = get_coursemodule_from_id('coach', $id)) {
    print_error('invalidcoursemodule');
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
$url = new moodle_url($CFG->wwwroot.'/mod/coach/editEvent.php', ['cmid' => $id, 'eventid' => $eventid, 'date' => $date, 'hour' => $hour]);

$PAGE->set_url($url);
$PAGE->set_title('Edit Event');
// $PAGE->requires->jquery();
$mform = new addEvent_form($url);

$defaultData = new stdClass();
$defaultData->id = $id;
$defaultData->eventid = $eventid;
$defaultData->userid = $Event->userid;
$dateString = $date. ' ' . $hour;
$timestamp = strtotime($dateString);
$defaultData->name = $Event->name;
$defaultData->email = $Event->email;
$draftitemid = file_get_submitted_draft_itemid('message');
$currenttext = file_prepare_draft_area($draftitemid, $context->id, 'mod_coach', 'eventmessage', $Event->id, ['subdirs' => 0, 'maxbytes' => 10485760], $Event->message);
$defaultData->message = array('text' => $currenttext, 'format' => 1 , 'itemid' => $draftitemid);
$defaultData->date = $date;
$defaultData->hour = $hour;
$defaultData->timestamp = $timestamp;
$defaultData->eventtype = $Event->event_type;
$defaultData->timezone = \core_date::get_server_timezone();

$mform->set_data($defaultData);
if($mform->is_cancelled()) {
        redirect(new moodle_url('/mod/coach/view.php?id='.$id));
} else if ($data = $mform->get_data()) {
    try {
        \mod_coach\Event::editEvent($data, $Event->external_calendar_id);
        redirect(new moodle_url('/mod/coach/view.php?id='.$Event->cmid), 'Event has been edited', null, 'success');
    } catch(\Exception $e) {
        redirect(new moodle_url('/mod/coach/view.php?id='.$Event->cmid), $e->getMessage(), null, 'error');
    }
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();