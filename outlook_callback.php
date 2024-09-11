<?php

use \mod_coach\GoogleCalendarApi;
use \mod_coach\OutlookCalendarApi;

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/coach/lib.php');
require_once($CFG->dirroot.'/mod/coach/locallib.php');
require_once($CFG->dirroot.'/mod/coach/classes/GoogleCalendarApi.php');
require_once($CFG->dirroot.'/mod/coach/classes/OutlookCalendarApi.php');
require_once($CFG->dirroot.'/mod/coach/classes/Calendar.php');

require_login();
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/mod/coach/outlook_callback.php');
$PAGE->set_pagelayout('base');

$cmid = (isset($_SESSION['mod_coach_cmid'])) ? $_SESSION['mod_coach_cmid'] : 0;

if(isset($_GET['code'])) {

	try {

		$data = \mod_coach\OutlookCalendarApi::getAccessToken($_GET['code']);

        $calendarid = $DB->get_field_sql('SELECT id FROM {coach_calendar_sync} WHERE userid=? and calendar_type=?', array($USER->id, \mod_coach\Calendar::TYPE_OUTLOOK));
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
            $record->calendar_type = \mod_coach\Calendar::TYPE_OUTLOOK;
            $record->access_token = $data['access_token'];
            $record->refresh_token = isset($data['refresh_token'])? $data['refresh_token'] : '';
            $record->token_type = $data['token_type'];
            $record->expires_in = $data['expires_in'];
            $record->scope = $data['scope'];
            \mod_coach\Calendar::create($record);
        }

        if(!$DB->get_field_sql('SELECT id FROM {coach_event_type} WHERE cm_id=? AND name=?', array($cmid, $USER->firstname.' '.$USER->lastname))) {
            $record = new \stdClass();
            $record->cm_id = $cmid;
            $record->name = $USER->firstname.' '.$USER->lastname;
            $record->uniquecode = \mod_coach\Coach::getRandomString();
            $record->payment_type = 'free';
            $record->price = 0;
            $record->currency = 'USD';
            $record->firstsessionfree = 0;
            $record->disabled = 0;
            $record->duration = '60';
            $record->customreceipt =  '';
            $record->customreceipt_format = '';
            $record->timecreated = time();
            $record->createdby = $USER->id;
            $record->timemodified = 0;
            $record->modifiedby = 0;
            $record->timedeleted = 0;
            $record->deletedby = 0;
            
            $record->id = $DB->insert_record('coach_event_type', $record);
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