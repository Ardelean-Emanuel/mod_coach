<?php

namespace mod_coach;

class Event {

    private $id;
    private $cmid;
    private $userid;
    private $coachid;
    private $event_type;
    private $date;
    private $hour;
    private $timezone;
    private $timestamp;
    private $event_duration_min;
    private $name;
    private $email;
    private $message;
    private $external_calendar_id;
    private $usermodified ;
    private $timecreated;
    private $timemodified;

    const TABLE = 'coach_event';

    public function __construct($ObjectOrId) {
        $this->reloadData($ObjectOrId);
    }

    public function reloadData($ObjectOrId) {
        global $DB;

        if($ObjectOrId && is_numeric($ObjectOrId)) {
            $Event = $DB->get_record_sql('SELECT * FROM {coach_event} WHERE id=?', array($ObjectOrId), MUST_EXIST);
        }
        else if($ObjectOrId && is_object($ObjectOrId)) {
            $Event = clone($ObjectOrId);
        } else {
            throw new \Exception(get_string('objectoridinvalid', 'local_shop'));
        }
        
        $this->id = (int)$Event->id;
        $this->cmid = (int)$Event->cmid;
        $this->userid = (int)$Event->userid;
        $this->coachid = (int)$Event->coachid;
        $this->event_type = (string)$Event->event_type;
        $this->date = (string)$Event->date;
        $this->hour = (string)$Event->hour;
        $this->timezone = (string)$Event->timezone;
        $this->timestamp = (int)$Event->timestamp;
        $this->event_duration_min = (int)$Event->event_duration_min;
        $this->name = (string)$Event->name;
        $this->email = (string)$Event->email;
        $this->message = (string)$Event->message;
        $this->external_calendar_id = (string)$Event->external_calendar_id;
        $this->usermodified = (int)$Event->usermodified;
        $this->timecreated = (int)$Event->timecreated;
        $this->timemodified = (int)$Event->timemodified;
      
    }

    public function getData() {
        return (object)get_object_vars($this);
    }

    public function __get($name) {
        global $DB;

        if(isset($this->{$name})) {
            return $this->{$name};
        }
        else {
            throw new \Exception(get_string('invalidpropertyname', 'local_shop', $name));
        }
    }

    public static function getPriceString($event_type) {
        global $DB;
        $currency = $DB->get_field_sql('SELECT currency FROM {coach_event_type} WHERE id=?', array($event_type));
        $price = $DB->get_field_sql('SELECT price FROM {coach_event_type} WHERE id=?', array($event_type));
        return ($currency && $price ? (($currency === 'CAD' || $currency === 'USD') ? '$' : ($currency === 'GBP' ? '£' : '€')).( number_format($price, 2, '.', ',')).' '.$currency : '');
    }

    public function AcceptTos() {
        global $DB;
        $DB->execute('UPDATE {coach_event} SET tos_accepted=1 WHERE id=?', array($this->id));

        $newEventData = array(
            'subject' => 'Meeting with '.$this->name,
            'description' => $this->message,
            'start_date' => date('Y-m-d', $this->timestamp),
            'start_time' => date('H:i', $this->timestamp),
            'end_date' => date('Y-m-d', $this->timestamp + $this->event_duration_min*60),
            'end_time' => date('H:i', $this->timestamp + $this->event_duration_min*60),
            'user_email' => $this->email,
            'user_name' => $this->name,
        );
        $newEventID = \mod_coach\Calendar::addEvent($newEventData);

        if($newEventID){
            $UpdateRecord = new \stdClass();
            $UpdateRecord->id = $this->id;
            $UpdateRecord->external_calendar_id = $newEventID;
            $DB->update_record('coach_event', $UpdateRecord);
        }
    }

    public static function getEventType($event_type) {
        global $DB;
        return $DB->get_record_sql('SELECT * FROM {coach_event_type} WHERE id=?', array($event_type));
    }

    public static function addEvent($data){
        global $DB, $CFG, $USER;

        $context = \context_system::instance();

        $transaction = $DB->start_delegated_transaction();
        try {
            $record = new \stdClass();
            $record->cmid = $data->id;
            $record->userid = $data->userid;
            $record->event_type = $data->eventtype;
            $record->timestamp = $data->timestamp;
            $record->hour = date('H:i', $data->timestamp);
            $record->date = date('d-m-Y', $data->timestamp);
            $record->timezone = $data->timezone ?? $DB->get_field_sql('SELECT timezone FROM {user} WHERE id = ?', [$data->userid]);
            $record->name = $data->name;
            $record->email = $data->email;
            $record->message = $data->message['text'];
            $record->external_calendar_id = '';
            $record->coachid = $DB->get_field_sql('SELECT userid FROM {coach_calendar_sync} WHERE cm_id = ?', [$data->id]);
            $record->event_duration_min = $DB->get_field_sql('SELECT duration FROM {coach_event_type} WHERE id = ?', [$data->eventtype]);
            $record->timecreated = time();
            $record->createdby = $USER->id;
            $record->timemodified = 0;
            $record->usermodified = 0;

            $record->id = $DB->insert_record(self::TABLE, $record);

            if(!empty($data->message['itemid'])){
                $UpdateRecord = new \stdClass();
                $UpdateRecord->id = $record->id;
                $draftitemid = $data->message['itemid'];
                $UpdateRecord->message = file_save_draft_area_files($draftitemid, $context->id, 'mod_coach', 'eventmessage', $record->id, ['subdirs' => 0, 'maxbytes' => 10485760], $record->message);
                $DB->update_record('coach_event', $UpdateRecord);
            }

            //Only add after TOS accepted by coach
            // $newEventData = array(
            //     'subject' => 'Meeting with '.$data->name,
            //     'description' => $data->message['text'],
            //     'start_date' => date('Y-m-d', $data->timestamp),
            //     'start_time' => date('H:i', $data->timestamp),
            //     'end_date' => date('Y-m-d', $data->timestamp + $record->event_duration_min*60),
            //     'end_time' => date('H:i', $data->timestamp + $record->event_duration_min*60),
            //     'user_email' => $data->email,
            //     'user_name' => $data->name,
            // );
            // $newEventID = \mod_coach\Calendar::addEvent($newEventData);

            // if($newEventID){
            //     $UpdateRecord = new \stdClass();
            //     $UpdateRecord->id = $record->id;
            //     $UpdateRecord->external_calendar_id = $newEventID;
            //     $DB->update_record('coach_event', $UpdateRecord);
            // }


            $EventTypeName = $DB->get_field_sql('SELECT name FROM {coach_event_type} WHERE id=?', array($record->event_type));
            $notifySubject = 'New Coaching Session Booked';
            
            // Retrieve coach's name based on coachid
            $coachName = $DB->get_field_sql('SELECT CONCAT(firstname, " ", lastname) FROM {user} WHERE id=?', array($record->coachid));
            
            // Retrieve volunteer's name based on userid
            $volunteerName = $DB->get_field_sql('SELECT CONCAT(firstname, " ", lastname) FROM {user} WHERE id=?', array($data->userid));
            
            $toslink = $CFG->wwwroot.'/mod/coach/tos.php?ref='.base64_encode(json_encode(array(
                'eventid' => $record->id,
                'coachid' => $record->createdby,
                'cmid' => $record->cmid
            )));
            
            $notifyMessage = '<p>Dear '.$coachName.',</p>'
                . '<p>UN Volunteer '.$volunteerName.' has booked a coaching session with you online. Please follow these steps to prepare:</p>'
                . '<ol>'
                . '    <li>Log in to UNV eCampus: <a href="https://learning.unv.org/login/index.php">https://learning.unv.org/login/index.php</a></li>'
                . '    <li>Accept the terms of use – <a href="'.$toslink.'">Click here</a> or copy and paste this URL in your browser: '.$toslink.'</li>'
                . '    <li>Contact the volunteer to confirm the booking or reschedule if needed.</li>'
                . '    <li>Include a Zoom or Teams link for the session, based on your preference.</li>'
                . '    <li>Use the suggested <a href="https://docs.google.com/document/d/1peQwGCGqlYIYG_0xU6aSFnD3wy70NyeX/edit">Coaching Client Profile template</a> for UN Volunteers or use your own.</li>'
                . '    <li>Refer to the <a href="https://docs.google.com/document/d/1zfqQsiXqFikydK3BJeqxHKy4GG3pb4KJ/edit">Coaching Observation Sheet</a> or use your own version for guidance during the session.</li>'
                . '</ol>'
                . '<p>Please contact <a href="mailto:eardelea@emeal.nttdata.com">eardelea@emeal.nttdata.com</a> for any platform-related issues, and <a href="mailto:shubh.chakraborty@unv.org">shubh.chakraborty@unv.org</a> with your suggestions and feedback.</p>'
                . '<p>Best of luck for your next coaching session!</p>'
                . '<p>UNV Capacity Development Team</p>';
            
            $messagetext = html_to_text($notifyMessage);
            $userObject = $DB->get_record_sql('SELECT * FROM {user} WHERE id=?', array($record->coachid));
            
            $calendar = \mod_coach\Calendar::getActiveCalendar($USER->id);

            if($calendar->calendar_type === \mod_coach\Calendar::TYPE_LOCAL) {

                require_once($CFG->dirroot . "/lib/bennu/iCalendar_components.php");
                require_once($CFG->dirroot . "/lib/bennu/iCalendar_properties.php");
                require_once($CFG->dirroot . "/lib/bennu/iCalendar_rfc2445.php");
                require_once($CFG->dirroot . "/lib/bennu/bennu.class.php");

                if (!defined('_BENNU_VERSION')) {
                    define('_BENNU_VERSION', '0.1');
                }
                // Send ics file as attachment
                $timezone = $record->timezone;
                if (isset($timezone) && $timezone == 99) {
                    $timezone = \core_date::get_server_timezone();
                }
                $nowUTC = new \DateTime(date('Y-m-d H:i', time()), new \DateTimeZone($timezone));
                $ical = new \iCalendar;
                $ical->add_property('method', 'PUBLISH');
                $ical->add_property('prodid', '-//Moodle Pty Ltd//NONSGML Moodle Version ' . $CFG->version . '//EN');
                $ev = new \iCalendar_event; // To export in ical format.
                $ev->add_property('uid', '2');
                $ev->add_property('summary', 'Booked session - '.$EventTypeName);
                $ev->add_property('description', $record->message);
                $ev->add_property('class', 'PUBLIC'); // PUBLIC / PRIVATE / CONFIDENTIAL
                $ev->add_property('last-modified', $nowUTC->format('Ymd') . 'T' . $nowUTC->format('His') . 'Z');
                $ev->add_property('dtstamp', $nowUTC->format('Ymd') . 'T' . $nowUTC->format('His') . 'Z'); // now
                $ev->add_property('dtstart', date('Ymd', $record->timestamp) . 'T' . date('Hi', $record->timestamp).'00'); // when event starts.
                $ev->add_property('dtend', date('Ymd', $record->timestamp) . 'T' . date('Hi', $record->timestamp + $record->event_duration_min*60).'00');
                $ical->add_component($ev);

                $serialized = $ical->serialize();

                $tempdir = make_temp_directory('ics/attachment');
                $tempfile = $tempdir . '/' . md5(microtime() . $userObject->id) . '.ics';
                file_put_contents($tempfile, $serialized);

                $attachment = $tempfile;
                $attachname = 'Booked_session.ics';


                $emailcoach = email_to_user($userObject, mod_coach_noreply_user(), $notifySubject, $messagetext, $notifyMessage, $attachment, $attachname, true, '', '', 79); 
                // $emailtouser = self::sendNotification($userObject, mod_coach_noreply_user(), $notifySubject, $messagetext ,$notifyMessage);

                //Notify user
                $notifySubject = 'Next steps for your personalized coaching session';
                $notifyMessage = '<p>Dear '.$volunteerName.',</p>
<p>Thank you for booking a coaching session! Here are the next steps to prepare:</p>

<ol>
    <li>You will receive a confirmation from your coach soon. Please check your email, including the junk folder, for details.</li>
    <li>Upon receipt of confirmation from your coach, complete and share the <a href="https://docs.google.com/document/d/1peQwGCGqlYIYG_0xU6aSFnD3wy70NyeX/edit">Coaching Client Profile template</a> to help your coach understand your goals and needs.</li>
    <li>Attend the session via Zoom or Teams on the agreed date and time. The link will be provided by your coach in the confirmation email.</li>
    <li>Visit <a href="https://learning.unv.org/login/index.php">eCampus</a> to change or cancel your planned coaching session – see guide. UNV is unable to process change requests.</li>
    <li><strong>Also, remember to directly email your coach, at least 24 hours in advance, if you are unable to attend the booked coaching session.</strong></li>
</ol>

<p>All the very best for your next coaching session.</p>

<p>Best,</p>
<p>UNV Capacity Development Team</p>';

                $messagetext = html_to_text($notifyMessage);
                $userObject = $DB->get_record_sql('SELECT * FROM {user} WHERE id=?', array($USER->id));

                $emailuser = email_to_user($userObject, mod_coach_noreply_user(), $notifySubject, $messagetext, $notifyMessage, $attachment, $attachname, true, '', '', 79); 
                // $emailtouser = self::sendNotification($userObject, mod_coach_noreply_user(), $notifySubject, $messagetext ,$notifyMessage);

            } else {
                $emailtouser = self::sendNotification($userObject, mod_coach_noreply_user(), $notifySubject, $messagetext ,$notifyMessage);
            }

           

            $transaction->allow_commit();
            return $record;
        } catch (\Exception $e) {
            $transaction->rollback($e);
            throw $e;
        }
    }

    public static function editEvent($data, $external){
        global $DB, $CFG, $USER, $Event;

        $context = \context_system::instance();

        $transaction = $DB->start_delegated_transaction();
        try {
            if(!$DB->get_field_sql('SELECT tos_accepted FROM {coach_event} WHERE id=?', array($data->eventid))) {
                throw new \Exception('The coach has not yet accepted the terms of use for this event.');
            }
            
            $record = new \stdClass();
            $record->id = $data->eventid;
            $record->userid = $data->userid;
            $record->timestamp = $data->timestamp;
            $record->hour = date('H:i', $data->timestamp);
            $record->date = date('d-m-Y', $data->timestamp);
            $record->timezone = $data->timezone ?? $DB->get_field_sql('SELECT timezone FROM {user} WHERE id = ?', [$data->userid]);
            $record->name = $data->name;
            $record->email = $data->email;
            $record->message = $data->message['text'];
            $record->timemodified = time();

            $DB->update_record(self::TABLE, $record);
            $fs = get_file_storage();
            foreach($fs->get_area_files($context->id, 'mod_coach', 'eventmessage', $record->id) as $documentFile) {
                $documentFile->delete();
            }

            if(!empty($data->message['itemid'])){
                $UpdateRecord = new \stdClass();
                $UpdateRecord->id = $record->id;
                $draftitemid = $data->message['itemid'];
                $UpdateRecord->message = file_save_draft_area_files($draftitemid, $context->id, 'mod_coach', 'eventmessage', $record->id, ['subdirs' => 0, 'maxbytes' => 10485760], $record->message);
                $DB->update_record('coach_event', $UpdateRecord);
            }

            $event_duration = $DB->get_field_sql('SELECT duration FROM {coach_event_type} WHERE id = ?', [$data->eventtype]);
            $newEventData = array(
                'subject' => 'Meeting with '.$data->name,
                'description' => $data->message['text'],
                'start_date' => date('Y-m-d', $data->timestamp),
                'start_time' => date('H:i', $data->timestamp),
                'end_date' => date('Y-m-d', $data->timestamp + $event_duration*60),
                'end_time' => date('H:i', $data->timestamp + $event_duration*60),
                'user_email' => $data->email,
                'user_name' => $data->name,
            );
            
            \mod_coach\Calendar::editEvent($newEventData, $external);

            $EventTypeName = $DB->get_field_sql('SELECT name FROM {coach_event_type} WHERE id=?', array($data->eventtype));
            $notifySubject = 'Session updated';
            $notifyMessage = $record->name.' updated the coaching session, scheduled for '.$record->date.' at '.$record->hour. '<br>' .'Message: '.$record->message;
            $messagetext = html_to_text($notifyMessage);
            $userObject = $DB->get_record_sql('SELECT * FROM {user} WHERE id=?', array($Event->coachid));

            $calendar = \mod_coach\Calendar::getActiveCalendar($USER->id);

            if($calendar->calendar_type === \mod_coach\Calendar::TYPE_LOCAL) {

                require_once($CFG->dirroot . "/lib/bennu/iCalendar_components.php");
                require_once($CFG->dirroot . "/lib/bennu/iCalendar_properties.php");
                require_once($CFG->dirroot . "/lib/bennu/iCalendar_rfc2445.php");
                require_once($CFG->dirroot . "/lib/bennu/bennu.class.php");

                if (!defined('_BENNU_VERSION')) {
                    define('_BENNU_VERSION', '0.1');
                }
                // Send ics file as attachment
                $timezone = $record->timezone;
                if (isset($timezone) && $timezone == 99) {
                    $timezone = \core_date::get_server_timezone();
                }
                $nowUTC = new \DateTime(date('Y-m-d H:i', time()), new \DateTimeZone($timezone));
                $ical = new \iCalendar;
                $ical->add_property('method', 'PUBLISH');
                $ical->add_property('prodid', '-//Moodle Pty Ltd//NONSGML Moodle Version ' . $CFG->version . '//EN');
                $ev = new \iCalendar_event; // To export in ical format.
                $ev->add_property('uid', '2');
                $ev->add_property('summary', 'Booked session - '.$EventTypeName);
                $ev->add_property('description', $record->message);
                $ev->add_property('class', 'PUBLIC'); // PUBLIC / PRIVATE / CONFIDENTIAL
                $ev->add_property('last-modified', $nowUTC->format('Ymd') . 'T' . $nowUTC->format('His') . 'Z');
                $ev->add_property('dtstamp', $nowUTC->format('Ymd') . 'T' . $nowUTC->format('His') . 'Z'); // now
                $ev->add_property('dtstart', date('Ymd', $record->timestamp) . 'T' . date('Hi', $record->timestamp).'00'); // when event starts.
                $ev->add_property('dtend', date('Ymd', $record->timestamp) . 'T' . date('Hi', $record->timestamp + $event_duration*60).'00');
                $ical->add_component($ev);

                $serialized = $ical->serialize();

                $tempdir = make_temp_directory('ics/attachment');
                $tempfile = $tempdir . '/' . md5(microtime() . $userObject->id) . '.ics';
                file_put_contents($tempfile, $serialized);

                $attachment = $tempfile;
                $attachname = 'Booked_session.ics';


                $emailcoach = email_to_user($userObject, mod_coach_noreply_user(), $notifySubject, $messagetext, $notifyMessage, $attachment, $attachname, true, '', '', 79); 
                // $emailtouser = self::sendNotification($userObject, mod_coach_noreply_user(), $notifySubject, $messagetext ,$notifyMessage);

                //Notify user
                $notifySubject = 'Session modified';
                $notifyMessage = 'You modified the coaching session with "'.$EventTypeName.'", scheduled for '.$record->date.' at '.$record->hour;
                $messagetext = html_to_text($notifyMessage);
                $userObject = $DB->get_record_sql('SELECT * FROM {user} WHERE id=?', array($USER->id));

                $emailuser = email_to_user($userObject, mod_coach_noreply_user(), $notifySubject, $messagetext, $notifyMessage, $attachment, $attachname, true, '', '', 79); 
                // $emailtouser = self::sendNotification($userObject, mod_coach_noreply_user(), $notifySubject, $messagetext ,$notifyMessage);

            } else {
                $emailtouser = self::sendNotification($userObject, mod_coach_noreply_user(), $notifySubject, $messagetext ,$notifyMessage);
            }
            

            $transaction->allow_commit();
            return $record;
        } catch (\Exception $e) {
            $transaction->rollback($e);
            throw $e;
        }
    }
    public static function deleteEvent($id)
    {
        global $DB, $CFG;
    
        $Event = new Event($id);
        $EventTypeName = $DB->get_field_sql('SELECT name FROM {coach_event_type} WHERE id=?', array($Event->event_type));
        $notifySubject = 'Session removed';
        
        // Compose the HTML message
        $notifyMessage = '<p>Dear '.$Event->name.',</p>'
            . '<p>The "'.$EventTypeName.'" coaching session, scheduled for '.$Event->date.' at '.$Event->hour.' has been removed.</p>'
            . '<p>Message: '.$Event->message.'</p>'
            . '<p>Best regards,<br/>UNV Capacity Development Team</p>';
        
        // Convert to plain text
        $messagetext = html_to_text($notifyMessage);
        
        // Get coach's user object
        $userObject = $DB->get_record('user', array('id' => $Event->coachid));
        
        // Send the email
        email_to_user($userObject, mod_coach_noreply_user(), $notifySubject, $messagetext, $notifyMessage);
        
        // Delete the record if it exists
        if ($DB->record_exists(self::TABLE, ['id' => $id])) {
            $DB->delete_records(self::TABLE, ['id' => $id]);
        } 
    }
    

    public static function sendNotification($user, $from, $subject, $messagetext, $messagehtml = '')
    {
        $message = new \core\message\message();
        $message->component = 'mod_coach';
        $message->name = 'generic_notification';
        $message->userfrom = $from;
        $message->userto = $user;
        $message->subject = $subject;
        $message->fullmessage = $messagetext;
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml = $messagehtml;

        $message->notification = 1;
        $messageid = message_send($message);
    }

}
