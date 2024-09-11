<?php

use mod_coach\Coach;

require('../../config.php');
require_once($CFG->dirroot.'/mod/coach/classes/EventType_form.php');

$id = required_param('id', PARAM_INT);
$EventType = Coach::getEventType($id);

if (!$cm = get_coursemodule_from_id('coach', $EventType->cm_id)) {
    print_error('invalidcoursemodule');
}
$coach = $DB->get_record('coach', array('id'=>$cm->instance), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);

$PAGE->set_url('/mod/coach/editeventtype.php', array('id' => $EventType->cm_id));
$PAGE->set_title('Edit Event Type');
$PAGE->requires->jquery();

$FormType = 'edit';
$mform = new EventType_form($CFG->wwwroot.'/mod/coach/editeventtype.php?id='.$EventType->cm_id);

$defaultData = new stdClass();
$defaultData->id = $id;
$defaultData->name = $EventType->name;
$defaultData->uniquekey = $EventType->uniquecode;
$defaultData->payment_type = $EventType->payment_type;
$defaultData->price = $EventType->price;
$defaultData->currency = $EventType->currency; 
$defaultData->duration = $EventType->duration;
$defaultData->firstsessionfree = $EventType->firstsessionfree;
$defaultData->disabled = $EventType->disabled;
$draftitemid = file_get_submitted_draft_itemid('custom_receipt');
//force context 1 for easier access elsewhere maybe idk
$currenttext = file_prepare_draft_area($draftitemid, 1, 'mod_coach', 'custom_receipt', $EventType->id, ['subdirs' => 0, 'maxbytes' => 10485760,], $EventType->customreceipt);
$defaultData->custom_receipt = array('text' => $currenttext, 'format' => $EventType->customreceipt_format , 'itemid' => $draftitemid);

$mform->set_data($defaultData);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/coach/eventtypes.php?id='.$EventType->cm_id));
} else if ($mform->is_submitted()) {
    if ($data = $mform->get_data()) {
        try {
            Coach::editEventType($data);
            redirect(new moodle_url('/mod/coach/eventtypes.php?id='.$EventType->cm_id), 'Event type has been modified', null, 'success');
        } catch(\Exception $e) {
            redirect(new moodle_url('/mod/coach/view.php?id='.$EventType->cm_id), $e->getMessage(), null, 'error');
        }
	}    
}
echo $OUTPUT->header();
$mform->display();


echo $OUTPUT->footer();