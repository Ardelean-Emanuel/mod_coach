<?php

require_once($CFG->dirroot . '/mod/coach/classes/Calendar.php');
require_once($CFG->dirroot . '/mod/coach/classes/CoachSettings.php');
require_once($CFG->dirroot.'/mod/coach/classes/Availability.php');

$date = required_param('date', PARAM_TEXT);
$cmid = required_param('cmid', PARAM_TEXT);
$typeid = required_param('typeid', PARAM_TEXT);
$eventid = optional_param('eventid', 0, PARAM_INT);

$eventtype = @$DB->get_record_sql('SELECT * FROM {coach_event_type} WHERE id = ? LIMIT 1', array($typeid)); // problem with Undefined property: stdClass::$debugdeveloper
$duration = $eventtype->duration;
// try {
    $validation = CoachSettings::isDateUsable($eventtype->createdby, $date);
    if ($validation->status) {
        $coachId = $DB->get_field_sql('SELECT userid FROM {coach_calendar_sync} WHERE cm_id = ?', [$cmid]);
        $freeIntervals = \mod_coach\Calendar::getAvailability($date, $cmid, $duration, $coachId);
        $content = '';
    
        $startHour = 9;
        $endHour = 17;
        $content .= '<div class="d-flex flex-column">';
        if($eventid){
            $Event = new \mod_coach\Event($eventid);
            if(strtotime($date) == strtotime($Event->date)) {
                $url = new moodle_url($CFG->wwwroot.'/mod/coach/editEvent.php', ['cmid' => $cmid, 'eventid' => $eventid, 'date' => $date, 'hour' => $Event->hour]);
                $content .= '<div class="d-flex flex-row">';
                $content .= '<div class="hour mb-2 w-100"><a class="btn btn-outline-primary w-100" style="background-color: whitesmoke; color: #0f6cbf;" href="'.$url.'">'.$Event->hour.'</a></div>';
                $content .= '</div>';
            }
        }
        foreach ($freeIntervals as $key => $value) {
            if(!Availability::isThisAvailable($date, $eventtype->createdby, $value)) continue;
            $time = DateTime::createFromFormat('Hi', $key);
            $formattedTime = $time->format('H:i');
            $url = new moodle_url($CFG->wwwroot.'/mod/coach/addEvent.php', ['cmid' => $cmid, 'eventtype' => $eventtype->id, 'date' => $date, 'hour' => $formattedTime]);
            if($eventid){
                $url = new moodle_url($CFG->wwwroot.'/mod/coach/editEvent.php', ['cmid' => $cmid, 'eventid' => $eventid, 'date' => $date, 'hour' => $formattedTime]);
            }
            $content .= '<div class="d-flex flex-row">';
            $content .= '<div class="hour mb-2 w-100"><a class="btn btn-outline-primary w-100" href="' .$url. '">'.$value.'</a></div>';
            $content .= '</div>';
        }
        
        $content .= '</div>';
    
        $result['status'] = 'ok';
        $result['content'] = $content;
    } else {
        $result['status'] = 'invaliddate';
        $result['content'] = $validation;
    }
// } catch (Exception $ex) {
//     $result['status'] = 'error';
//     $result['content'] = $ex->getMessage();
// }