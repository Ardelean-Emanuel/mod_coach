<?php

use \mod_coach\GoogleCalendarApi;

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/coach/lib.php');
require_once($CFG->dirroot.'/mod/coach/locallib.php');
require_once($CFG->dirroot.'/mod/coach/classes/GoogleCalendarApi.php');
require_once($CFG->dirroot.'/mod/coach/classes/Calendar.php');

require_login();
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/mod/coach/google_callback.php');
$PAGE->set_pagelayout('base');

$cmid = (isset($_SESSION['mod_coach_cmid'])) ? $_SESSION['mod_coach_cmid'] : 0;

if(isset($_GET['code'])) {

	try {

		$data = \mod_coach\GoogleCalendarApi::getAccessToken($_GET['code']);

		// print_r($data);die();

        $calendarid = $DB->get_field_sql('SELECT id FROM {coach_calendar_sync} WHERE userid=? and calendar_type=?', array($USER->id, \mod_coach\Calendar::TYPE_GOOGLE));
        if(!empty($calendarid)) {
            $calendar = new \mod_coach\Calendar($calendarid);
            $calendar->access_token = $data['access_token'];
            $calendar->refresh_token = isset($data['refresh_token']) ? $data['refresh_token'] : $calendar->refresh_token;
            $calendar->token_type = $data['token_type'];
            $calendar->expires_in = $data['expires_in'];
            $calendar->scope = $data['scope'];
            $calendar->timemodified = time();
            $calendar->update();
        } else {
            // Save the access token in the database
            $record = new \stdClass();
            $record->cm_id = $cmid;
            $record->userid = $USER->id;
            $record->calendar_type = \mod_coach\Calendar::TYPE_GOOGLE;
            $record->access_token = $data['access_token'];
            $record->refresh_token = isset($data['refresh_token'])? $data['refresh_token'] : '';
            $record->token_type = $data['token_type'];
            $record->expires_in = $data['expires_in'];
            $record->scope = $data['scope'];
            \mod_coach\Calendar::create($record);
        }

		// Redirect to the page where user can create event
        redirect(new moodle_url('/mod/coach/calendarsync.php?id='.$cmid), 'Calendar synced successfully', 0, 'success');
		exit();
	}
	catch(Exception $e) {
        redirect(new moodle_url('/mod/coach/calendarsync.php?id='.$cmid), $e->getMessage(), 0, 'error');
		echo $e->getMessage();
		exit();
	}
}