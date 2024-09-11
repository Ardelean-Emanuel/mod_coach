<?php

namespace mod_coach;

class OutlookCalendarApi {
	
	// const CLIENT_ID = '229f9337-db6d-4086-957f-5b54b706829d';
    // const CLIENT_SECRET = 'tH38Q~NWe.sci5Zc4AjHSNdkeDpMS-6TELbIYc5.';
	// const REDIRECT_URI = 'https://redirectmeto.com/http://localhost/churchx/mod/coach/outlook_callback.php';
	const API_URL = 'https://graph.microsoft.com/v1.0';

	public static function getAccessToken($code) {	
		global $CFG;

		$CLIENT_ID = $CFG->mod_coach_outlook_cliend_id;
		$CLIENT_SECRET = $CFG->mod_coach_outlook_cliend_secret;
		$REDIRECT_URI = $CFG->mod_coach_outlook_redirect_uri;

		$url = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
		
		$curlPost = 'client_id=' . $CLIENT_ID . '&redirect_uri=' . $REDIRECT_URI . '&client_secret=' . $CLIENT_SECRET . '&code='. $code . '&grant_type=authorization_code&scope=offline_access Calendars.ReadWrite';
		$ch = curl_init();		
		curl_setopt($ch, CURLOPT_URL, $url);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);		
		curl_setopt($ch, CURLOPT_POST, 1);		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);	
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));	
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

		/*
		For refresh tokens, the maximum validity period is 90 days, and we can't set token lifetime policies for refresh tokens. 
		When a refresh token expires, the user must re-authenticate to get a new refresh token.
		*/

		$CLIENT_ID = $CFG->mod_coach_outlook_cliend_id;
		$CLIENT_SECRET = $CFG->mod_coach_outlook_cliend_secret;
		$REDIRECT_URI = $CFG->mod_coach_outlook_redirect_uri;

		$url = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
		
		$curlPost = 'client_id=' . $CLIENT_ID . '&client_secret=' . urlencode($CLIENT_SECRET) . '&refresh_token='. $refresh_token . '&grant_type=refresh_token&redirect_uri=' . $REDIRECT_URI.'&scope=offline_access Calendars.ReadWrite';
		$ch = curl_init();		
		curl_setopt($ch, CURLOPT_URL, $url);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);		
		curl_setopt($ch, CURLOPT_POST, 1);		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);	
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));	
		$data = json_decode(curl_exec($ch), true);
		$http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);

		// if($http_code != 200) 
		// 	throw new \Exception('Error : Failed to receieve access token');
		// }

		if($http_code == 400) {
			$data = ['error'=>'invalid_grant','error_description'=>'The user could not be authenticated or the grant is expired. The user must first sign in and if needed grant the client application access to the requested scope.'];
			$http_code = 400;
		}

		/*
		We could receive invalid_grant error, in that case we need to ask user to re-authenticate.

		Documentation: https://learn.microsoft.com/en-us/advertising/guides/authentication-oauth-get-tokens.
		Refresh tokens are, and always will be, completely opaque to your application. 
		They are long-lived e.g., 90 days for public clients, but the app should not be written to expect that a refresh token will last for any period of time. 
		Refresh tokens can be invalidated at any moment, and the only way for an app to know if a refresh token is valid is to attempt to redeem it by making a token request. 
		Even if you continuously refresh the token on the same device with the most recent refresh token, 
		you should expect to start again and request user consent if for example, the Microsoft Advertising user changed their password, 
		removed a device from their list of trusted devices, or removed permissions for your application to authenticate on their behalf. 
		At any time without prior warning Microsoft may determine that user consent should again be granted. 
		In that case, the authorization service would return an invalid grant error as shown in the following example.

		{"error":"invalid_grant","error_description":"The user could not be authenticated or the grant is expired. The user must first sign in and if needed grant the client application access to the requested scope."}
		*/

        return [$data, $http_code];
	}

	public static function getCalendarEvents($access_token, $date) {

		// $date = '2024-01-16';
		$startTime = $date . 'T00:00:00';
		$endTime = $date . 'T23:59:59';

		// Specify the date for which you want to retrieve events
		// $startTime = '2024-01-24T00:00:00';
		// $endTime = '2024-01-24T23:59:59';

		$api_url = self::API_URL.'/me/calendar/calendarView';
		$api_url.='?startDateTime=' . urlencode($startTime) . '&endDateTime=' . urlencode($endTime);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $api_url);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '. $access_token));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		$data = json_decode(curl_exec($ch), true);
		$http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);

		// var_dump('response:');
		// var_dump('<pre>', $data, $http_code, '</pre>');die();

		if($http_code != 200) {
			throw new \Exception('Error : Failed to get calendars list');
		}
		return $data; // return the first calendar, should be primary one
	}

	public static function createCalendarEvent($access_token, $newEventData) {
				
		$url_events = self::API_URL.'/me/calendar/events';

		$curlPost = $newEventData;

		// $curlPost = array(
		// 	'subject' => 'Test event outlook',
		// 	'start' => array('dateTime' => '2024-02-03T12:00:00', 'timeZone' => 'Europe/Bucharest'),
		// 	'end' => array('dateTime' => '2024-02-03T14:00:00', 'timeZone' => 'Europe/Bucharest'),
		// 	'location' => array('displayName' => 'Test location'),
		// 	'body' => array('contentType' => 'HTML', 'content' => 'Test event description'),
		// 	'attendees' => array(
		// 		array('emailAddress' => array('address' => 'test@test.com', 'name' => 'Test User'), 'type' => 'required')
		// 	),
		// );

		$ch = curl_init(); // Initializes a new session and return a cURL handle	
		curl_setopt($ch, CURLOPT_URL, $url_events);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return the transfer as a string of the return value of curl_exec() instead of outputting it directly.	
		curl_setopt($ch, CURLOPT_POST, 1); // http post	
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // stop cURL from verifying the peer's certificate
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '. $access_token, 'Content-Type: application/json'));	
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($curlPost));	
		$data = json_decode(curl_exec($ch), true);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);		

		if($http_code != 201) 
			throw new \Exception('Error : Failed to create event');

		return $data['id'];
	}

	public static function editCalendarEvent($access_token, $newEventData, $event_id) {
		$url_editEvent = self::API_URL . '/me/events/' . $event_id;	

		$curlPost = $newEventData;

		$ch = curl_init(); // Initializes a new session and return a cURL handle	
		curl_setopt($ch, CURLOPT_URL, $url_editEvent);		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return the transfer as a string of the return value of curl_exec() instead of outputting it directly.	
		curl_setopt($ch, CURLOPT_POST, 1); // http post	
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // stop cURL from verifying the peer's certificate
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '. $access_token, 'Content-Type: application/json'));	
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($curlPost));	
		$data = json_decode(curl_exec($ch), true);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);		

		if($http_code != 200) {
			throw new \Exception('Error : Failed to update event');
		}

		return true;
	}

	public static function deleteCalendarEvent($access_token, $event_id) {
		$url_deleteEvent = self::API_URL . '/me/events/' . $event_id;
	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url_deleteEvent);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $access_token));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		$result = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		if ($http_code != 204) {
			throw new \Exception('Error : Failed to delete calendar event');
		}
	
		return true;
	}

	public static function getAvailability($date, $events, $intervalDurationMinutes = 30, $dayStart = '06:00', $dayEnd = '18:00') {
		
		global $USER;
		
		$intervalDurationSeconds = $intervalDurationMinutes * 60;
        $freeIntervals = [];
		$targetTimezone = $USER->timezone != '99' ? $USER->timezone : \core_date::get_server_timezone();
		$utcTimeZone = new \DateTimeZone($targetTimezone);

		$currentTime = strtotime($date . " " . $dayStart);
		$endTime = strtotime($date . " " . $dayEnd);
		
        while ($currentTime + $intervalDurationSeconds <= $endTime) {
            $intervalStart = $currentTime;
            $intervalEnd = $currentTime + $intervalDurationSeconds;
            $isIntervalFree = true;
            foreach ($events['value'] as $event) {
				$meetingStartDT = new \DateTime($event['start']['dateTime'], $utcTimeZone);
				$meetingEndDT = new \DateTime($event['end']['dateTime'], $utcTimeZone);
	
				$meetingStart = $meetingStartDT->getTimestamp();
				$meetingEnd = $meetingEndDT->getTimestamp();
                if ($meetingStart < $intervalEnd && $meetingEnd > $intervalStart) {
                    $isIntervalFree = false;
                    break;
                }
            }
    
            if ($isIntervalFree) {
                $key = date('Hi', $intervalStart);
                // $freeIntervals[$key] = date('H:i', $intervalStart) . ' - ' . date('H:i', $intervalEnd);
                $freeIntervals[$key] = date('H:i', $intervalStart);
            }
    
            $currentTime += $intervalDurationSeconds;
        }

        return $freeIntervals;
	}
	
}