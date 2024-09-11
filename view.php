<?php

require('../../config.php');
require_once($CFG->dirroot.'/mod/coach/lib.php');
require_once($CFG->dirroot.'/mod/coach/locallib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/mod/coach/classes/Calendar.php');
require_once($CFG->dirroot.'/mod/coach/classes/GoogleCalendarApi.php');
require_once($CFG->dirroot.'/mod/coach/classes/OutlookCalendarApi.php');

use mod_coach\Calendar;
use mod_coach\GoogleCalendarApi;
use mod_coach\OutlookCalendarApi;
use cm_info;
use core\activity_dates;
use core\completion\cm_completion_details;

$id      = optional_param('id', 0, PARAM_INT); // Course Module ID
$tab      = optional_param('tab', 'upcoming', PARAM_TEXT); // Course Module ID
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 20, PARAM_INT);

if (!$cm = get_coursemodule_from_id('coach', $id)) {
    print_error('invalidcoursemodule');
}
$coach = $DB->get_record('coach', array('id'=>$cm->instance), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/coach:view', $context);

// Completion and trigger events.
coach_view($coach, $course, $cm, $context);

$PAGE->set_url('/mod/coach/view.php', array('id' => $cm->id, 'tab' => $tab));

$PAGE->requires->css('/mod/coach/styles.css');
$PAGE->requires->jquery();

$PAGE->set_title($course->shortname.': '.$coach->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($coach);

echo $OUTPUT->header();

// Display any activity information (eg completion requirements / dates).
$cminfo = cm_info::create($cm);

// Restriction logic: Check if the user has booked 3 events in the last 12 months
$twelve_months_ago = time() - (365 * 24 * 60 * 60); // 12 months ago timestamp
$booked_events_count = $DB->count_records_sql(
    'SELECT COUNT(*) FROM {coach_event} 
     WHERE userid = ? AND timestamp >= ?',
    [$USER->id, $twelve_months_ago]
);

if ($booked_events_count >= 3 && !is_siteadmin()) {
    // Display a message and do not show the booking options
    echo $OUTPUT->notification(get_string('event_booking_limit_reached', 'mod_coach'), 'error');
} else {
    $content = '';

    if($coach->customdescription) {
        $htmlContent = $coach->customdescription;
        echo $htmlContent;
    }

    $courseContext = context_course::instance($course->id);
    $buttons = '<div class="d-flex flex-wrap">';

    // Admin buttons
    if(has_capability('mod/coach:addinstance', $courseContext)) {
        $buttons .= '
            <a class="btn btn-primary mr-3 mb-2" href="'.$CFG->wwwroot.'/mod/coach/calendarsync.php?id='.$cm->id.'">Calendar Sync</a>
            <a class="btn btn-primary mr-3 mb-2" href="'.$CFG->wwwroot.'/mod/coach/coach_settings.php?id='.$cm->id.'">Coach settings</a>
            <a class="btn btn-primary mr-3 mb-2" href="'.$CFG->wwwroot.'/mod/coach/availability.php?id='.$cm->id.'">Availability</a>';
        
        if(is_siteadmin()){
            $buttons .= '<a class="btn btn-primary mr-3 mb-2" href="'.$CFG->wwwroot.'/mod/coach/report.php">Report</a>';
        }
        
        $buttons .= '<div class="w-100"></div>'; // Line break for flexbox
    }

    // Booking buttons (available to all users with access to the course)
    $EventTypes = $DB->get_records_sql('SELECT id, name FROM {coach_event_type} WHERE cm_id=? AND timedeleted=0 AND disabled=0 ORDER BY timecreated DESC', array($id));
    foreach($EventTypes as $EvType){
        $buttons .= '<a class="btn btn-secondary mr-3 mb-2" href="'.$CFG->wwwroot.'/mod/coach/event.php?id='.$cm->id.'&typeid='.$EvType->id.'">Book session - '.$EvType->name.'</a>';
    }

    $buttons .= '</div><br><br>';

    echo $buttons;
}

$eventslist = '';
$upcomingActive = '';
$pastActive= '';

$tab != 'upcoming' ? $whereCondition = '<' : $whereCondition = '>';
$tab != 'upcoming' ? $pastActive = 'show active' : $upcomingActive = 'show active';
$userTimezone = new DateTimeZone($USER->timezone != '99' ? $USER->timezone : core_date::get_server_timezone());

// Determine if the current user is a coach
$isCoach = $DB->record_exists('coach_calendar_sync', ['userid' => $USER->id, 'cm_id' => $cm->id]);

$events = $DB->get_records_sql(
    'SELECT ce.*,cet.name AS event_name, cet.payment_type FROM {coach_event} ce
     JOIN {coach_event_type} cet ON cet.id = ce.event_type
     WHERE (ce.userid = ? OR ce.coachid = ?) AND ce.timestamp ' .$whereCondition. ' ' . time(), [$USER->id, $USER->id]);
$totalcount = count($events);

$start = $page * $perpage;
if ($start > count($events)) {
    $page = 0;
    $start = 0;
}
$events = array_values(array_slice($events, $start, $perpage, true));
foreach ($events as $key => $event) {
    if ($isCoach) {
        // For coaches, use the event's stored date and time
        $displayDate = $event->date;
        $displayTime = $event->hour;
        $displayTimezone = $event->timezone;
    } else {
        // For regular users, convert to their timezone
        $eventDateTime = new DateTime('@' . $event->timestamp);
        $eventDateTime->setTimezone($userTimezone);
        $displayDate = $eventDateTime->format('Y-m-d');
        $displayTime = $eventDateTime->format('H:i');
        $displayTimezone = $userTimezone->getName();
    }
    
    $eventslist .= '
        <tr>
            <td>'.s($event->name).'</td>
            <td>'.s($event->email).'</td>
            <td>'.s($event->event_name).'</td>
            <td>'.$displayDate.'</td>
            <td>'.$displayTime.'</td>
            <td>'.$displayTimezone.'</td>
            <td>'.format_string($event->message).'</td>
            <td>
                <a class="deleteaction" href="'.$CFG->wwwroot.'/mod/coach/delete.php?id='.$id.'&external='.$event->external_calendar_id.'&eventid='.$event->id.'"><i class="fa fa-trash" aria-hidden="true"></i></a>
                <span style="margin-right: 15px;"></span>
                <a class="deleteaction" href="'.$CFG->wwwroot.'/mod/coach/event.php?id='.$id.'&typeid='.$event->event_type.'&eventid='.$event->id.'"><i class="fa fa-pencil" aria-hidden="true"></i></a>
            </td>
        </tr>';
}

$table = '
    <table class="table">
    <thead>
    <tr>
        <th scope="col">Name</th>
        <th scope="col">Email</th>
        <th scope="col">Type</th>
        <th scope="col">Date</th>
        <th scope="col">Hour</th>
        <th scope="col">Timezone</th>
        <th scope="col">Message</th>
        <th scope="col">Action</th>
    </tr>
    </thead>
        <tbody>
        '.$eventslist.'
        </tbody>
    </table>';
$content .= '
    <div class="">
        <ul class="nav nav-tabs" id="EventsList" role="tablist">
            <li class="nav-item">
                <a class="nav-link '.$upcomingActive.'" id="list2-tab" href="' . $CFG->wwwroot.'/mod/coach/view.php?id='.$id.'&tab=upcoming#EventsList" role="tab" aria-controls="list2" aria-selected="false">Upcoming Events</a>
            </li>
            <li class="nav-item">
                <a class="nav-link '.$pastActive.'" id="list1-tab"  href="' . $CFG->wwwroot.'/mod/coach/view.php?id='.$id.'&tab=past#EventsList" role="tab" aria-controls="list1" aria-selected="true">Past Events</a>
            </li>
        </ul>
        <div class="tab-content" id="EventsListContent">
            <div class="pastlist tab-pane fade '.$pastActive.'" id="list1" role="tabpanel" aria-labelledby="list1-tab">
                '.$table.'
            </div>
            <div class="tab-pane fade  '.$upcomingActive.'" id="list2" role="tabpanel" aria-labelledby="list2-tab">
                '.$table.'
            </div>
        </div>
    </div>
    <style>
        .pastlist .deleteaction {
            display: none;
        }
    </style>
    ';
$content .= $OUTPUT->paging_bar($totalcount, $page, $perpage, $CFG->wwwroot.'/mod/coach/view.php?id='.$id.'&tab=past&page='.$page.'#EventsList', 'page');

echo $content;
echo $OUTPUT->footer();