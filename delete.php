<?php
namespace mod_coach;

use \mod_coach\GoogleCalendarApi;
use \mod_coach\OutlookCalendarApi;
use \mod_coach\Calendar;

require('../../config.php');
require_once($CFG->dirroot.'/mod/coach/lib.php');
require_once($CFG->dirroot.'/mod/coach/locallib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/mod/coach/classes/Calendar.php');
require_once($CFG->dirroot.'/mod/coach/classes/GoogleCalendarApi.php');
require_once($CFG->dirroot.'/mod/coach/classes/OutlookCalendarApi.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/page/locallib.php');

$external = required_param('external', PARAM_TEXT);
$id = required_param('id', PARAM_INT);
$eventid = optional_param('eventid', 0, PARAM_INT);
$PAGE->set_context(\context_system::instance());
$PAGE->requires->css('/mod/coach/styles.css');
$PAGE->requires->jquery();
$PAGE->set_url('/mod/coach/delete.php', array('external' => $external, 'eventid' => $eventid));

$PAGE->set_title('Delete');

class deleteEventForm extends \moodleform {
    public function definition() {
        global $DB, $CFG, $PAGE, $USER;
			
        $mform = $this->_form;

        $mform->addElement('static', 'name', get_string('event', 'mod_coach'));
        $mform->addElement('static', 'warning', '');

        $this->add_action_buttons(true, get_string('delete', 'mod_coach'));
    }
}

$mform = new deleteEventForm($CFG->wwwroot.'/mod/coach/delete.php?id=' . $id . '&external=' . $external. '&eventid=' . $eventid);


$defaultData = new \stdClass();
$defaultData->warning = $OUTPUT->notification(get_string('deletewarningevent', 'mod_coach'));
$mform->set_data($defaultData);

if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot . '/mod/coach/view.php?id=' . $id);
} else if ($mform->get_data()) {
    if ($data = $mform->get_data()) {
        try {
            $calendar = Calendar::getActiveCalendar($USER->id);
            if($calendar->calendar_type == \mod_coach\Calendar::TYPE_LOCAL){
                \mod_coach\Calendar::deleteLocalEvent($eventid);
                redirect($CFG->wwwroot . '/mod/coach/view.php?id=' . $id, get_string('eventdelete', 'mod_coach'));
            } else {
                if($calendar->isAccessTokenExpired()) {
                    throw new \Exception('Error : Access token expired');
                }
    
                \mod_coach\Calendar::deleteCalendarEvent($external);
                redirect($CFG->wwwroot . '/mod/coach/view.php?id=' . $id, get_string('eventdelete', 'mod_coach'));
            }
        } catch(\Exception $e) {
            redirect(new \moodle_url('/mod/coach/view.php?id=' . $id), $e->getMessage(), null, 'error');
        }
	}    
}

echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();
