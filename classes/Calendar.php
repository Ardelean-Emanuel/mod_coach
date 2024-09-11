<?php

namespace mod_coach;

require_once($CFG->dirroot.'/mod/coach/classes/GoogleCalendarApi.php');
require_once($CFG->dirroot.'/mod/coach/classes/OutlookCalendarApi.php');

use \mod_coach\GoogleCalendarApi;
use \mod_coach\OutlookCalendarApi;

class Calendar {

    private $id;
    private $cm_id;
    private $userid;
    private $calendar_type;
    public  $access_token;
    public  $refresh_token;
    private $token_type;
    private $expires_in;
    private $scope;
    private $timemodified;
    private $timecreated;

    const TYPE_GOOGLE = 'google';
    const TYPE_OUTLOOK = 'outlook';
    const TYPE_ICOULD = 'icloud';
    const TYPE_LOCAL = 'local';

    public function __construct($id) {
        $this->reloadData($id);
    }

    public function __get($name) {
        return $this->{$name};
    }

    public function reloadData($id) {
        global $DB;

        $calendar = $DB->get_record_sql('SELECT * FROM {coach_calendar_sync} WHERE id=?', array($id), MUST_EXIST);

        $this->id = $calendar->id;
        $this->cm_id = $calendar->cm_id;
        $this->userid = $calendar->userid;
        $this->calendar_type = $calendar->calendar_type;
        $this->access_token = $calendar->access_token;
        $this->refresh_token = $calendar->refresh_token;
        $this->token_type = $calendar->token_type;
        $this->expires_in = $calendar->expires_in;
        $this->scope = $calendar->scope;
        $this->timemodified = $calendar->timemodified;
        $this->timecreated = $calendar->timecreated;
    }

    public static function create($data) {
        global $DB, $USER;

        $record = new \stdClass();
        $record->cm_id = $data->cm_id;
        $record->userid = $data->userid;
        $record->calendar_type = $data->calendar_type;
        $record->access_token = $data->access_token;
        $record->refresh_token = $data->refresh_token;
        $record->token_type = $data->token_type;
        $record->expires_in = $data->expires_in;
        $record->scope = $data->scope;
        $record->timecreated = time();
        $record->timemodified = time();
        
        $record->id = $DB->insert_record('coach_calendar_sync', $record);

        return $record->id;
    }

    public function getCoachCalendarSync() {
        global $DB;

        return $DB->get_record_sql('SELECT * FROM {coach_calendar_sync} WHERE id=?', array($this->id));
    }

    public function update($data) {
        global $DB, $USER;

        $record = new \stdClass();
        $record->access_token = $data->access_token;
        $record->refresh_token = $data->refresh_token;
        $record->expires_in = $data->expires_in;
        $record->timemodified = time();

        $record->id = $this->id;
        $DB->update_record('coach_calendar_sync', $record);
    }

    public function delete() {
        global $DB, $USER;

        $DB->delete_records('coach_calendar_sync', array('id' => $this->id));
    }

    public static function getCalendarsSyned($userid) {
        global $DB;

        $Items = $DB->get_records_sql('SELECT * FROM {coach_calendar_sync} WHERE userid=? ORDER BY timecreated DESC', array($userid));

        $results = array();
        foreach($Items as $item) {
            $results[$item->id] = new Calendar($item->id);
        }

        return $results;
    }

    public static function getActiveCalendar($userid, $cmid = 0) {
        global $DB, $CFG;
    
        // Get coachid based on optional_param('id', 0, PARAM_INT) which is cmid
        if (!$cmid) {
            $cmid = optional_param('cmid', 0, PARAM_INT);
        } else if (!$cmid) {
            $cmid = optional_param('id', 0, PARAM_INT);
        }
        
        if ($cmid) {
            $calendarSyned = $DB->get_record_sql('SELECT * FROM {coach_calendar_sync} WHERE cm_id=? ORDER BY timecreated DESC', array($cmid));
        } else {
            $calendarSyned = $DB->get_record_sql('SELECT * FROM {coach_calendar_sync} WHERE userid=? ORDER BY timecreated DESC', array($userid));
        }

        if($calendarSyned) {
 
            if($calendarSyned->calendar_type == self::TYPE_GOOGLE) {
            
                // if(true) {
                if($calendarSyned->timemodified + $calendarSyned->expires_in < time()) {
                    $data = GoogleCalendarApi::updateRefreshToken($calendarSyned->refresh_token);

                    if(!empty($data) && !empty($data['access_token'])) {

                        $record = new \stdClass();
                        $record->access_token = $data['access_token'];
                        $record->refresh_token = $data['refresh_token'];
                        $record->expires_in = $data['expires_in'];

                        // timemodified is updated in update method
                        $calendar = new \mod_coach\Calendar($calendarSyned->id);
                        $calendar->update($record);

                        return new Calendar($calendar->id);
                    }
                }


            } else if ($calendarSyned->calendar_type == self::TYPE_OUTLOOK) {

                //if(true) {
                if($calendarSyned->timemodified + $calendarSyned->expires_in < time()) {
                    $refreshTokenApiResponse = OutlookCalendarApi::updateRefreshToken($calendarSyned->refresh_token);
                    $data = $refreshTokenApiResponse[0];
                    $http_code = $refreshTokenApiResponse[1];

                    if(!empty($data) && !empty($data['access_token'])) {

                        $record = new \stdClass();
                        $record->access_token = $data['access_token'];
                        $record->refresh_token = $data['refresh_token'];
                        $record->expires_in = $data['expires_in'];

                        // timemodified is updated in update method
                        $calendar = new \mod_coach\Calendar((int)$calendarSyned->id);
                        $calendar->update($record);

                        return new Calendar($calendar->id);
                    } else if ($http_code !== 200 && $data['error'] == 'invalid_grant') {
                        // email coach to re-authenticate to the outlook calendar and throw exception
                        // this case is when the refresh token is expired or invalided
                        // is the same as the access token expired, it means the user needs to re-authenticate to the outlook calendar
                        // it should happen only when the user has not used the calendar sync for a long time

                        $CoachModule = $DB->get_record_sql('SELECT * FROM {coach} WHERE id IN (SELECT instance FROM {course_modules} WHERE id=?)', array($calendarSyned->cm_id));
                        $course = $DB->get_record_sql('SELECT * FROM {course} WHERE id=?', array($CoachModule->course));

                        $notifySubject = 'Coach calendar sync expired';
                        $notifyMessage = 'Your calendar sync with the coach has expired for the course <b>'.$course->fullname.'</b> and module <b>'.$CoachModule->name.'</b>.<br>
                        Please re-authenticate to the outlook calendar to continue using the calendar sync feature.<br>
                        <a href="'.$CFG->wwwroot.'/mod/coach/calendarsync.php?id='.$calendarSyned->cm_id.'">Click here to re-authenticate</a>';
                        $messagetext = html_to_text($notifyMessage);
                        $userObject = $DB->get_record_sql('SELECT * FROM {user} WHERE id=?', array($calendarSyned->userid));
                        $emailtouser = \mod_coach\Event::sendNotification($userObject, mod_coach_noreply_user(), $notifySubject, $messagetext ,$notifyMessage);
                        throw new \Exception('Error : '.$data['error'].', '.$data['error_description'].'. Email sent to the user to re-authenticate to the outlook calendar');
                    }
                }
            }

            // return new instance with new access_token
            return new Calendar($calendarSyned->id);
        }

        return false;
    }

    public function isAccessTokenExpired() {

        if($this->timemodified + $this->expires_in < time()) {
            return true;
        }
        return false;
    }

    public static function getEvents($date) {
        global $DB, $USER;

        $events = array();
        $calendar = self::getActiveCalendar($USER->id);
        if($calendar->isAccessTokenExpired()) {
            throw new \Exception('Error : Access token expired');
        }

        if($calendar->calendar_type == self::TYPE_GOOGLE) {
            $googleCalendar = GoogleCalendarApi::getCalendar($calendar->access_token);
            $events = GoogleCalendarApi::getCalendarEvents($calendar->access_token, $googleCalendar['id'], $date);
        } else if ($calendar->calendar_type == self::TYPE_OUTLOOK) {
            $events = OutlookCalendarApi::getCalendarEvents($calendar->access_token, $date);
        }

        return $events;
    }

    public static function addEvent($newEventData) {
        global $DB, $USER;

        // check required fields for new event
        // if(empty($newEventData['subject']) || empty($newEventData['description']) || empty($newEventData['user_email'])) {
        //     throw new \Exception('Error : Required fields are missing');
        // }

        $new_event_id = null;
        $calendar = self::getActiveCalendar($USER->id, $newEventData['cmid']);
        if($calendar->calendar_type == self::TYPE_LOCAL){
            return 'local_calendar';
        }

        if($calendar->isAccessTokenExpired()) {
            throw new \Exception('Error : Access token expired');
        }

        $utcTimeZone = new \DateTimeZone('UTC');

        $startDateTime = new \DateTime($newEventData['start_date'] . ' ' . $newEventData['start_time']);
        // $startDateTime->setTimezone($utcTimeZone);
    
        $endDateTime = new \DateTime($newEventData['end_date'] . ' ' . $newEventData['end_time']);
        // $endDateTime->setTimezone($utcTimeZone);

        if ($calendar->calendar_type == self::TYPE_GOOGLE) {
            $googleCalendar = GoogleCalendarApi::getCalendar($calendar->access_token);


            $newEventData = array(
                'summary' => $newEventData['subject'],
                'description' => $newEventData['description'] ? $newEventData['description'] : '',
                'start' => array(
                    'dateTime' => $startDateTime->format('Y-m-d\TH:i:s'),
                    'timeZone' => 'UTC'
                ),
                'end' => array(
                    'dateTime' => $endDateTime->format('Y-m-d\TH:i:s'),
                    'timeZone' => 'UTC'
                ),
                'attendees' => array(
                    array(
                        'email' => $newEventData['user_email']
                    )
                )
            );

            $new_event_id = GoogleCalendarApi::createCalendarEvent($calendar->access_token, $googleCalendar['id'], $newEventData);


        } else if ($calendar->calendar_type == self::TYPE_OUTLOOK) {

            $newEventData = array(
                'subject' => $newEventData['subject'],
                'body' => array(
                    'contentType' => 'HTML',
                    'content' => $newEventData['description'] ? $newEventData['description'] : '',
                ),
                'start' => array(
                    'dateTime' => $startDateTime->format('Y-m-d\TH:i:s'),
                    // 'timeZone' => 'UTC'
                ),
                'end' => array(
                    'dateTime' => $endDateTime->format('Y-m-d\TH:i:s'),
                    // 'timeZone' => 'UTC'
                ),
                'attendees' => array(
                    array(
                        'emailAddress' => array(
                            'address' => $newEventData['user_email'],
                            'name' => $newEventData['user_name'],
                        ),
                        'type' => 'required'
                    )
                )
            );

            $new_event_id = OutlookCalendarApi::createCalendarEvent($calendar->access_token, $newEventData);
        }

        return $new_event_id;
    }

    public static function editEvent($newEventData, $external) {
        global $DB, $USER;

        // check required fields for new event
        if(empty($newEventData['subject']) || empty($newEventData['description']) || empty($newEventData['user_email'])) {
            throw new \Exception('Error : Required fields are missing');
        }

        $calendar = self::getActiveCalendar($USER->id);

        if($calendar->calendar_type == self::TYPE_LOCAL){
            return true;
        }

        if($calendar->isAccessTokenExpired()) {
            throw new \Exception('Error : Access token expired');
        }

        $Event = $DB->get_record_sql("SELECT * FROM {coach_event} WHERE external_calendar_id = ? LIMIT 1", [$external]);
        if ($Event->timestamp < time()) {
            throw new \Exception("Error : Only upcoming events can be updated");
        }
        $utcTimeZone = new \DateTimeZone('UTC');

        $startDateTime = new \DateTime($newEventData['start_date'] . ' ' . $newEventData['start_time']);
        // $startDateTime->setTimezone($utcTimeZone);
    
        $endDateTime = new \DateTime($newEventData['end_date'] . ' ' . $newEventData['end_time']);
        // $endDateTime->setTimezone($utcTimeZone);

        if($calendar->calendar_type == self::TYPE_GOOGLE) {

            $googleCalendar = GoogleCalendarApi::getCalendar($calendar->access_token);

            $newEventData = array(
                'summary' => $newEventData['subject'],
                'description' => $newEventData['description'] ? $newEventData['description'] : '',
                'start' => array(
                    'dateTime' => $startDateTime->format('Y-m-d\TH:i:s'),
                    'timeZone' => 'UTC'
                ),
                'end' => array(
                    'dateTime' => $endDateTime->format('Y-m-d\TH:i:s'),
                    'timeZone' => 'UTC'
                ),
                'attendees' => array(
                    array(
                        'email' => $newEventData['user_email']
                    )
                )
            );

            GoogleCalendarApi::editCalendarEvent($calendar->access_token, $googleCalendar['id'], $newEventData, $external);


        } else if ($calendar->calendar_type == self::TYPE_OUTLOOK) {

            $newEventData = array(
                'subject' => $newEventData['subject'],
                'body' => array(
                    'contentType' => 'HTML',
                    'content' => $newEventData['description'] ? $newEventData['description'] : '',
                ),
                'start' => array(
                    'dateTime' => $startDateTime->format('Y-m-d\TH:i:s'),
                    // 'timeZone' => 'UTC'
                ),
                'end' => array(
                    'dateTime' => $endDateTime->format('Y-m-d\TH:i:s'),
                    // 'timeZone' => 'UTC'
                ),
                'attendees' => array(
                    array(
                        'emailAddress' => array(
                            'address' => $newEventData['user_email'],
                            'name' => $newEventData['user_name'],
                        ),
                        'type' => 'required'
                    )
                )
            );

            OutlookCalendarApi::editCalendarEvent($calendar->access_token, $newEventData, $external);
        }
    }

    public static function deleteCalendarEvent($external) {
        global $DB, $USER, $CFG;

        $calendar = self::getActiveCalendar($USER->id);
        if($calendar->isAccessTokenExpired()) {
            throw new \Exception('Error : Access token expired');
        }
 
        $Event = $DB->get_record_sql("SELECT * FROM {coach_event} WHERE external_calendar_id = ? LIMIT 1", [$external]);
        if ($Event->timestamp < time()) {
            throw new \Exception("Error : Only upcoming events can be deleted");
        }

        $deleteEvent = false;

        if($calendar->calendar_type == self::TYPE_GOOGLE) {
            $googleCalendar = GoogleCalendarApi::getCalendar($calendar->access_token);
            $deleteEvent = GoogleCalendarApi::deleteCalendarEvent($calendar->access_token, $googleCalendar['id'], $external);
        } else if ($calendar->calendar_type == self::TYPE_OUTLOOK) {
            $deleteEvent = OutlookCalendarApi::deleteCalendarEvent($calendar->access_token, $external);
        }

        if ($deleteEvent) {
            Event::deleteEvent($Event->id);
        }
    }

    public static function deleteLocalEvent($eventid) {
        global $DB;
        Event::deleteEvent($eventid);
    }

    public static function getEventsLocal($date) {
        global $DB,$USER, $CFG;
        
        $date = date('d-m-Y', strtotime($date));
        return $DB->get_records_sql('SELECT * FROM {coach_event} WHERE date=? AND userid=?', array($date, $USER->id));
    }

    public static function getAvailabilityLocal($date, $sessions, $intervalDurationMinutes = 30) {
        global $USER;
        $freeIntervals = [];
        $intervalDurationSeconds = $intervalDurationMinutes * 60;
    
        // Current user's timezone (could be coach or volunteer)
        $userTimezone = new \DateTimeZone($USER->timezone != '99' ? $USER->timezone : \core_date::get_server_timezone());
    
        // Create DateTime objects for the start and end of the day in the user's timezone
        $dayStart = new \DateTime($date . ' 00:00:00', $userTimezone);
        $dayEnd = new \DateTime($date . ' 23:59:59', $userTimezone);
    
        $utcTimezone = new \DateTimeZone('UTC');
        $dayStart->setTimezone($utcTimezone);
        $dayEnd->setTimezone($utcTimezone);
    
        $currentTime = $dayStart->getTimestamp();
        $endTime = $dayEnd->getTimestamp();
    
        while ($currentTime + $intervalDurationSeconds <= $endTime) {
            $intervalStart = $currentTime;
            $intervalEnd = $currentTime + $intervalDurationSeconds;
            $isIntervalFree = true;
            
            foreach ($sessions as $event) {
                if (isset($event->hour) && isset($event->event_duration_min) && isset($event->timezone)) {
                    // Create event datetime in the event's original timezone
                    $eventTimezone = new \DateTimeZone($event->timezone);
                    $eventDate = new \DateTime($event->date . ' ' . $event->hour, $eventTimezone);
                    $eventDate->setTimezone($utcTimezone);
                    $timestamp = $eventDate->getTimestamp();
                    if ($timestamp < $intervalEnd && ($timestamp + $event->event_duration_min * 60) > $intervalStart) {
                        $isIntervalFree = false;
                        break;
                    }
                }
            }
    
            if ($isIntervalFree) {
                $intervalDateTime = new \DateTime('@' . $intervalStart);
                $intervalDateTime->setTimezone($userTimezone);
                $startDisplay = $intervalDateTime->format('H:i');
                $key = $intervalDateTime->format('Hi');
                $freeIntervals[$key] = $startDisplay;
            }
    
            $currentTime += $intervalDurationSeconds;
        }
    
        return $freeIntervals;
    }

    public static function getAvailability($date,$cmid = 0 ,$intervalDurationMinutes = 30, $coachId = 0) {
        global $DB, $USER, $CFG;
        $calendar = self::getActiveCalendar($coachId);
    
        if($calendar->calendar_type != self::TYPE_LOCAL) {
            $events = Calendar::getEvents($date);
            if($calendar->isAccessTokenExpired()) {
                throw new \Exception('Error : Access token expired');
            }
        } else {
            // Fetch ALL events for this date, not just for the current user
            $events = $DB->get_records_sql('SELECT * FROM {coach_event} WHERE date = ? and coachid = ?', array($date,$coachId));
        }
        
        debugging('Calendar type: ' . $calendar->calendar_type, DEBUG_DEVELOPER);
        debugging('Events: ' . print_r($events, true), DEBUG_DEVELOPER);
    
        $availability = [];
    
        if($calendar->calendar_type == self::TYPE_GOOGLE) {
            $availability = GoogleCalendarApi::getAvailability($date, $events, $intervalDurationMinutes);
        } else if ($calendar->calendar_type == self::TYPE_OUTLOOK) {
            $availability = OutlookCalendarApi::getAvailability($date, $events, $intervalDurationMinutes);
        } else if ($calendar->calendar_type == self::TYPE_LOCAL) {
            $availability = self::getAvailabilityLocal($date, $events, $intervalDurationMinutes);
        }
    
        return $availability;
    }

}