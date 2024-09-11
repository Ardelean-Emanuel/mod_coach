<?php 
require_once('../../../config.php');
require_once($CFG->dirroot.'/mod/coach/lib.php');
require_once($CFG->dirroot.'/mod/coach/classes/Calendar.php');

$cmid = optional_param('cmid', 0, PARAM_INT); 

$record = new \stdClass();
$record->cm_id = $cmid;
$record->userid = $USER->id;
$record->calendar_type = \mod_coach\Calendar::TYPE_LOCAL;
$record->access_token = '';
$record->refresh_token = '';
$record->token_type = '';
$record->expires_in = '';
$record->scope = '';

\mod_coach\Calendar::create($record);

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


redirect(new moodle_url('/mod/coach/calendarsync.php?id='.$cmid), 'Calendar synced successfully', 0, 'success');
exit();