<?php


require('../../config.php');
require_once($CFG->dirroot.'/mod/coach/lib.php');
require_once($CFG->dirroot.'/mod/coach/locallib.php');
require_once($CFG->dirroot.'/mod/coach/classes/GoogleCalendarApi.php');
require_once($CFG->dirroot.'/mod/coach/classes/Calendar.php');
require_once($CFG->dirroot.'/mod/coach/classes/OutlookCalendarApi.php');

use \mod_coach\GoogleCalendarApi;
use \mod_coach\OutlookCalendarApi;

$id      = optional_param('id', 0, PARAM_INT); // Course Module ID

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

$PAGE->set_url('/mod/coach/view.php', array('id' => $cm->id));

$PAGE->requires->css('/mod/coach/styles.css');
$PAGE->requires->jquery();

$PAGE->set_title(get_string('calendarsync', 'mod_coach'));
$PAGE->set_heading(get_string('calendarsync', 'mod_coach'));
$PAGE->set_activity_record($coach);

$_SESSION['mod_coach_cmid'] = $id;

$delete = optional_param('delete', 0, PARAM_INT);
if($delete) {
	$calendar = new \mod_coach\Calendar($delete);
	$calendar->delete();
	redirect(new moodle_url('/mod/coach/calendarsync.php?id='.$id), 'Calendar deleted successfully', 0, 'success');
	exit();
}

echo $OUTPUT->header();

$content = '';

$content.= '<div class="d-flex">
        <a class="btn btn-secondary mr-3" href="'.$CFG->wwwroot.'/mod/coach/view.php?id='.$id.'">'.get_string('back', 'mod_coach').'</a>
    </div>    
    <br><br>';

$content .= '<h2>Calendar sync</h2>';

$Items = \mod_coach\Calendar::getCalendarsSyned($USER->id);

if($Items) {

	$content .= '<table class="table">
					<thead>
						<tr>
							<th scope="col">Calendar</th>
							<th scope="col">Actions</th>
						</tr>
					</thead>
					<tbody id="tabel-calendarsync">';

		foreach($Items as $calendarItem) {
			
			// if($calendarItem->calendar_type == \mod_coach\Calendar::TYPE_GOOGLE) {
			// 	$calendarName = 'Google Calendar';
			// 	$calendarIcon = $CFG->wwwroot.'/mod/coach/pix/google-calendar.svg';
			// } else 
			if ($calendarItem->calendar_type == \mod_coach\Calendar::TYPE_OUTLOOK) {
				$calendarName = 'Outlook Calendar';
				$calendarIcon = $CFG->wwwroot.'/mod/coach/pix/outlook.svg';
			} else if ($calendarItem->calendar_type == \mod_coach\Calendar::TYPE_ICOULD) {
				$calendarName = 'iCloud Calendar';
				$calendarIcon = $CFG->wwwroot.'/mod/coach/pix/icloud.svg';
			} else if ($calendarItem->calendar_type == \mod_coach\Calendar::TYPE_LOCAL) {
				$calendarName = 'Local Calendar';
				$calendarIcon = $CFG->wwwroot.'/mod/coach/pix/local.svg';
			}

			$content .= '<tr id="calendaritem'.$calendarItem->id.'" data-itemid="' . $calendarItem->id . '">
							<td><img class="mr-3" src="'.$calendarIcon.'"><span>'.$calendarName.'</span></td>
							<td>
								<a href="'.$CFG->wwwroot.'/mod/coach/calendarsync.php?id='.$id.'&delete='.$calendarItem->id.'"><i class="fa fa-trash" aria-hidden="true"></i></a>
							</td>
						</tr>';
		}
		
	$content .= '</tbody>
				</table>';
} else {
	$content .= '<div class="alert alert-warning text-center mt-2">No connected calendars, connect your calendar below!</div>';


	$content .= '<table class="table">
					<tbody id="tabel-calendarsync">';


	$GOOGLE_REDIRECT_URI = $CFG->mod_coach_google_redirect_uri;
	$OUTLOOK_REDIRECT_URI = $CFG->mod_coach_outlook_redirect_uri;

	// Google Calendar
	// $login_url = 'https://accounts.google.com/o/oauth2/auth?scope=' . urlencode('https://www.googleapis.com/auth/calendar') . '&redirect_uri=' . urlencode($GOOGLE_REDIRECT_URI) . '&response_type=code&client_id=' . $CFG->mod_coach_google_cliend_id . '&access_type=offline';
	// $content .= '<tr>
	// 				<td>
	// 					<img class="mr-3" src="'.$CFG->wwwroot.'/mod/coach/pix/google-calendar.svg'.'"><span>Google Calendar</span>
	// 				</td>
	// 				<td>
	// 					<a href="'.$login_url.'" class="btn btn-lg btn-primary">Connect</a>
	// 				</td>
	// 			</tr>';

	// Outlook Calendar
	$autorizationURL = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?client_id='.$CFG->mod_coach_outlook_cliend_id.'&response_type=code&redirect_uri='.urlencode($OUTLOOK_REDIRECT_URI).'&response_mode=query&scope='.urlencode('offline_access Calendars.ReadWrite');
	$content .= '<tr>
				<td>
					<img class="mr-3" src="'.$CFG->wwwroot.'/mod/coach/pix/outlook.svg'.'"><span>Outlook Calendar</span>
				</td>
				<td>
					<a href="'.$autorizationURL.'" class="btn btn-lg btn-primary">Connect</a>
				</td>
			</tr>';

	$localURL = $CFG->wwwroot.'/mod/coach/ajax/localcalendarsync.php?cmid='.$id;
	$content .= '
			<tr>
				<td>
					<img class="mr-3" src="'.$CFG->wwwroot.'/mod/coach/pix/local.svg'.'"><span>Local Calendar</span>
				</td>
				<td>
					<a href="'.$localURL.'" class="btn btn-lg btn-primary">Connect</a>
				</td>
			</tr>';
		
	$content .= '</tbody>
				</table>';



}

echo $content;
echo $OUTPUT->footer();