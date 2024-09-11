<?php

require('../../config.php');
require_once($CFG->dirroot.'/mod/coach/lib.php');
require_once($CFG->dirroot.'/mod/coach/classes/Event.php');
require_once($CFG->dirroot.'/mod/coach/locallib.php');

$id      = optional_param('id', 0, PARAM_INT); // Course Module ID
$typeid  = optional_param('typeid', 0, PARAM_INT); // Event type id
$eventid = optional_param('eventid', 0, PARAM_INT); // Event id

if (!$cm = get_coursemodule_from_id('coach', $id)) {
    print_error('invalidcoursemodule');
}
$coach = $DB->get_record('coach', array('id'=>$cm->instance), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/coach:view', $context);

$PAGE->set_url('/mod/coach/event.php', array('id' => $cm->id, 'typeid' => $typeid, 'eventid' => $eventid));

$PAGE->requires->css('/mod/coach/styles.css');
$PAGE->requires->css('/mod/coach/style/calendar.css');
$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/coach/javascript/calendar.js'));

$PAGE->set_title(get_string('calendar', 'mod_coach'));
$PAGE->set_heading(get_string('calendar', 'mod_coach'));
$PAGE->set_activity_record($coach);

// Get the current user's ID
$userid = $USER->id;

// Calculate the date 12 months ago
$time_12_months_ago = strtotime('-12 months');

// Count the number of events the user has booked in the last 12 months
$event_count = $DB->count_records_sql(
    'SELECT COUNT(*) FROM {coach_event} WHERE userid = ? AND timestamp >= ?',
    array($userid, $time_12_months_ago)
);

// If the user has booked 3 or more events, deny access
if ($event_count >= 3  && !is_siteadmin()) {
    print_error('You have already booked 3 events in the last 12 months. You cannot book more events.');
}

echo $OUTPUT->header();
echo '<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>';

if($eventid){
    $Event = new \mod_coach\Event($eventid);
}
$content = '<input type="hidden" name="cmid" value="'.$id.'">';
$content .= '<input type="hidden" name="typeid" value="'.$typeid.'">';

$eventType = $DB->get_record_sql('SELECT * FROM {coach_event_type} WHERE id=?', array($typeid));
$Coach = $DB->get_record_sql('SELECT * FROM {user} WHERE id IN (SELECT userid FROM {coach_calendar_sync} WHERE cm_id=?) LIMIT 1', array($id));
$content .='<div class="row">';
$content .='
    <div class="col-3">
        <h5 style="color: gray; margin-bottom: 0;">'.$Coach->firstname.' '.$Coach->lastname.'</h5>
        <h3>'.$eventType->name.' Meeting</h3>
        <div class="mb-5"></div>
        <span style="color: gray;"><i class="fa fa-clock-o" aria-hidden="true"></i>  '.$eventType->duration.' min</span>
    </div>';
$content .= '
    <div class="col-9">
        '.($eventid ? '<h2>Edit event</h2><hr>' : '').'
        <div class="alert alert-info" role="alert">
            '.get_string('timezone_warning', 'mod_coach', $USER->timezone).'
        </div>
        <div class="calendar-wrapper d-flex">
            <div id="calendar"></div>
            <div class="calendar-dates mx-3">
                <h3 id="selected-date">'.($eventid ? date('Y-m-d', strtotime($Event->date)) : '').'</h3>
                <div class="calendar-hours"></div>
            </div>
        </div>
    <div class="col-9">
</div>
';

if($eventid){
    $content .='<input type="hidden" class="eventdate" value="'.date('Y-m-d', $Event->timestamp).'">';
    $content .='<input type="hidden" class="eventid" value="'.$Event->id.'">';
}

echo $content;
echo $OUTPUT->footer();