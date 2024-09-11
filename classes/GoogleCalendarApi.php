<?php

namespace mod_coach;

class GoogleCalendarApi {
	
	// const CLIENT_ID = '41595683697-1ianmslaqrm1kgp5ii7slobolsrpve5d.apps.googleusercontent.com';
	// const CLIENT_SECRET = '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';
	// const REDIRECT_URI = 'https://redirectmeto.com/http://localhost/churchx/mod/coach/google_callback.php';
	const API_URL = 'https://www.googleapis.com/calendar/v3';

	public static function getAccessToken($code) {	
		global $CFG;

		$CLIENT_ID = $CFG->mod_coach_google_cliend_id;
		$CLIENT_SECRET = $CFG->mod_coach_google_cliend_secret;
		$REDIRECT_URI = $CFG->mod_coach_google_redirect_uri;

		$url = 'https://accounts.google.com/o/oauth2/token';
		
		$curlPost = 'client_id=' . $CLIENT_ID . '&redirect_uri=' . $REDIRECT_URI . '&client_secret=' . $CLIENT_SECRET . '&code='. $code . '&grant_type=authorization_code';
		$ch = curl_init();		
		curl_setopt($ch, CURLOPT_URL, $url);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);		
		curl_setopt($ch, CURLOPT_POST, 1);		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);	
		$data = json_decode(curl_exec($ch), true);
		$http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);	
		
		// var_dump($data);
		// var_dump($http_code);die();
		if($http_code != 200) 
			throw new \Exception('Error : Failed to receieve access token');
			
		return $data;
	}

	public static function updateRefreshToken($refresh_token) {	
		global $CFG;

		$CLIENT_ID = $CFG->mod_coach_google_cliend_id;
		$CLIENT_SECRET = $CFG->mod_coach_google_cliend_secret;

		$url = 'https://accounts.google.com/o/oauth2/token';
		
		$curlPost = 'client_id=' . $CLIENT_ID . '&client_secret=' . $CLIENT_SECRET . '&refresh_token='. $refresh_token . '&grant_type=refresh_token';
		$ch = curl_init();		
		curl_setopt($ch, CURLOPT_URL, $url);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);		
		curl_setopt($ch, CURLOPT_POST, 1);		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);	
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));	
		$data = json_decode(curl_exec($ch), true);
		$http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);	
		
		if($http_code != 200) {
			throw new \Exception('Error : Failed to receieve access token');
        }

        return $data;
	}

	public static function getUserCalendarTimezone($access_token) {
		$url_settings = self::API_URL.'/users/me/settings/timezone';
		
		$ch = curl_init();		
		curl_setopt($ch, CURLOPT_URL, $url_settings);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '. $access_token));	
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);	
		$data = json_decode(curl_exec($ch), true); //echo '<pre>';print_r($data);echo '</pre>';
		$http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);		
		if($http_code != 200) 
			throw new \Exception('Error : Failed to get timezone');

		return $data['value'];
	}

	public static function getCalendar($access_token) {
		$url_parameters = array();

		// $url_parameters['fields'] = 'items(id,summary,timeZone, primary)';
		$url_parameters['minAccessRole'] = 'owner';

		$url_calendars = self::API_URL.'/users/me/calendarList?'. http_build_query($url_parameters);
		
		$ch = curl_init();		
		curl_setopt($ch, CURLOPT_URL, $url_calendars);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '. $access_token));	
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);	
		$data = json_decode(curl_exec($ch), true); 
		// echo '<pre>';print_r($data);echo '</pre>';
		$http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);		

		if($http_code != 200) {
			throw new \Exception('Error : Failed to get calendars list');
		}

		return $data['items'][0]; // return the first calendar, should be primary one
	}

	public static function getCalendarEvents($access_token, $calendar_id, $date) {

		// $date = '2024-01-16';
		// Set the start and end time for the specified date
		$startTime = urlencode($date . 'T00:00:00Z');
		$endTime = urlencode($date . 'T23:59:59Z');

		$url_events = self::API_URL.'/calendars/'.$calendar_id.'/events?timeMin='.$startTime.'&timeMax='.$endTime;
		
		// var_dump($url_events);die();

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url_events);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '. $access_token));	
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);	
		$data = json_decode(curl_exec($ch), true); 
		$http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);		

		// var_dump('<pre>', $data, $http_code, '</pre>');die();

		if($http_code != 200) {
			throw new \Exception('Error : Failed to get calendars list');
		}

		return $data; // return the first calendar, should be primary one
	}

	public static function createCalendarEvent($access_token, $calendar_id, $newEventData) {		
		$url_events = self::API_URL.'/calendars/' . $calendar_id . '/events';

		$curlPost = $newEventData;

		$ch = curl_init(); // Initializes a new session and return a cURL handle	
		curl_setopt($ch, CURLOPT_URL, $url_events);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return the transfer as a string of the return value of curl_exec() instead of outputting it directly.	
		curl_setopt($ch, CURLOPT_POST, 1); // http post	
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // stop cURL from verifying the peer's certificate
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '. $access_token, 'Content-Type: application/json'));	
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($curlPost));	
		$data = json_decode(curl_exec($ch), true);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if($http_code != 200 && $http_code != 201) 
			throw new \Exception('Error : Failed to create event');

		return $data['id'];
	}

	public static function editCalendarEvent($access_token, $calendar_id, $newEventData, $event_id) {		
		$url_editEvents = self::API_URL.'/calendars/' . $calendar_id . '/events/' . $event_id;;

		$curlPost = $newEventData;

		$ch = curl_init(); // Initializes a new session and return a cURL handle	
		curl_setopt($ch, CURLOPT_URL, $url_editEvents);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return the transfer as a string of the return value of curl_exec() instead of outputting it directly.
		curl_setopt($ch, CURLOPT_POST, 1); // http post	
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '. $access_token, 'Content-Type: application/json'));	
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // stop cURL from verifying the peer's certificate
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($curlPost));	
		$data = json_decode(curl_exec($ch), true);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if($http_code != 200 && $http_code != 201) 
			throw new \Exception('Error : Failed to update event');

		return true;
	}

	public static function deleteCalendarEvent($access_token, $calendar_id, $event_id) {
		$url_deleteEvent = self::API_URL . '/calendars/' . $calendar_id . '/events/' . $event_id;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url_deleteEvent);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $access_token));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		$data = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
		if ($http_code != 204) {
			throw new \Exception('Error : Failed to delete calendar event');
		}
	
		return true;
	}

	public static function getAvailability($date, $events, $intervalDurationMinutes = 30, $dayStart = '06:00', $dayEnd = '18:00', $targetTimezone = 'UTC') {
		global $USER;

		$intervalDurationSeconds = $intervalDurationMinutes * 60;
		$freeIntervals = [];
		$targetTimezone = $USER->timezone != '99' ? $USER->timezone : \core_date::get_server_timezone();

		$targetTz = new \DateTimeZone($targetTimezone);
	
		$currentTime = strtotime($date . " " . $dayStart . ' UTC');
		$endTime = strtotime($date . " " . $dayEnd . ' UTC');
	
		while ($currentTime + $intervalDurationSeconds <= $endTime) {
			$intervalStart = $currentTime;
			$intervalEnd = $currentTime + $intervalDurationSeconds;
			$isIntervalFree = true;
			
			foreach ($events['items'] as $event) {
				if (isset($event['start']['dateTime']) && isset($event['end']['dateTime'])) {
					$meetingStart = (new \DateTime($event['start']['dateTime']))->getTimestamp();
					$meetingEnd = (new \DateTime($event['end']['dateTime']))->getTimestamp();
	
					if ($meetingStart < $intervalEnd && $meetingEnd > $intervalStart) {
						$isIntervalFree = false;
						break;
					}
				}
			}
	
			if ($isIntervalFree) {
				$startDisplay = (new \DateTime('@'.$intervalStart))->setTimeZone($targetTz)->format('H:i');
				// $endDisplay = (new \DateTime('@'.$intervalEnd))->setTimeZone($targetTz)->format('H:i');

				$key = (new \DateTime('@'.$intervalStart))->setTimeZone($targetTz)->format('Hi');
				// $freeIntervals[$key] = $startDisplay . ' - ' . $endDisplay;
				$freeIntervals[$key] = $startDisplay;
			}
	
			$currentTime += $intervalDurationSeconds;
		}
	
		return $freeIntervals;
	}
}

?>