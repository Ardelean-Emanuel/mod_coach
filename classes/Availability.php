<?php

class Availability {
    
    const TABLE = 'coach_availability';
    const TABLE_SD = 'coach_specific_date';
    const WEEK_DAYS = [
        0 => 'sun',
        1 => 'mon',
        2 => 'tue',
        3 => 'wed',
        4 => 'thu',
        5 => 'fri',
        6 => 'sat',
    ];
    public static function create($data)
    {
        global $USER, $DB;
        
        $record = new stdClass();
        $record->teacher = $data->teacher;
        $record->week_day = $data->week_day;
        $record->start_time = $data->start_time;
        $record->end_time = $data->end_time;
        $record->usermodified = $USER->id;
        $record->timecreated = time();
        $record->timemodified = 0;
        
        return $DB->insert_record(self::TABLE, $record);
    }

    public static function update($data)
    {
        global $USER, $DB;

        $record = new stdClass();
        $record->id = $data->id;
        $record->week_day = $data->week_day;
        $record->start_time = $data->start_time;
        $record->end_time = $data->end_time;
        $record->usermodified = $USER->id;
        $record->timemodified = time();
        
        return $DB->update_record(self::TABLE, $record);
    }

    public static function delete($teacher, $day)
    {
        global $DB;
        $DB->delete_records(self::TABLE, ['teacher' => $teacher, 'week_day' => $day]);
    }

    public static function handle($data) {
        global $DB, $USER;
    
        // Update timezone 
        if($data->timezone != $USER->timezone) {
            $DB->execute('UPDATE {user} SET timezone=? WHERE id=?', array($data->timezone, $USER->id));
            $USER->timezone = $data->timezone;
        }
    
        foreach (self::WEEK_DAYS as $key => $day) {
            $exists = $DB->get_record_sql('SELECT * FROM {'.Availability::TABLE.'} WHERE teacher = ? AND week_day = ?', [$USER->id, $day]);
    
            if ($exists && $data->{$day.'_enabled'} == '0') {
                // Set start time and end time to "09:00" without deleting the record
                $record = new stdClass();
                $record->id = $exists->id;
                $record->week_day = $day;
                $record->start_time = '09:00';
                $record->end_time = '09:00';
                $record->usermodified = $USER->id;
                $record->timemodified = time();
                self::update($record);
            } elseif ($exists && $data->{$day.'_enabled'} == '1') {
                // Update existing record with provided start and end times
                $record = new stdClass();
                $record->id = $exists->id;
                $record->teacher = $USER->id;
                $record->week_day = $day;
                $record->start_time = $data->{$day.'_from'};
                $record->end_time = $data->{$day.'_to'};
                $record->usermodified = $USER->id;
                $record->timecreated = time();
                $record->timemodified = 0;
                self::update($record);
            } elseif (!$exists && $data->{$day.'_enabled'} == '1') {
                // Create a new record
                $record = new stdClass();
                $record->teacher = $USER->id;
                $record->week_day = $day;
                $record->start_time = $data->{$day.'_from'};
                $record->end_time = $data->{$day.'_to'};
                $record->usermodified = $USER->id;
                $record->timecreated = time();
                $record->timemodified = 0;
                self::create($record);
            } elseif (!$exists && $data->{$day.'_enabled'} == '0') {
                // Create a new record with start time and end time set to "09:00"
                $record = new stdClass();
                $record->teacher = $USER->id;
                $record->week_day = $day;
                $record->start_time = '09:00';
                $record->end_time = '09:00';
                $record->usermodified = $USER->id;
                $record->timecreated = time();
                $record->timemodified = 0;
                self::create($record);
            }
        }
    }    

    public static function setDefault()
    {
        global $DB, $USER;
        $data = new stdClass();
        foreach (self::WEEK_DAYS as $key => $day) {
            $exists = $DB->get_record_sql('SELECT * FROM {'.Availability::TABLE.'} WHERE teacher = ? AND week_day = ?', [$USER->id, $day]);
            if ($exists) {
                if ($exists->start_time == $exists->end_time) {
                    $data->{$day.'_enabled'} = 0;
                } else {
                    $data->{$day.'_enabled'} = 1;
                }
                $data->{$day.'_from'} = $exists->start_time;
                $data->{$day.'_to'} = $exists->end_time;
            } else {
                $data->{$day.'_enabled'} = 0;
                $data->{$day.'_from'} = '09:00';
                $data->{$day.'_to'} = '09:00';
            }
        }
    
        // $specificDates = $DB->get_records_sql('SELECT * FROM {'.self::TABLE_SD.'} WHERE teacher = ?', [$USER->id]);
        // $i = 0;
        // foreach ($specificDates as $value) {
        //     $data->{'add_'.$i} = 1;
        //     $data->{'date_'.$i} = $value->date;
        //     $data->{'available_'.$i} = $value->available;
        //     $data->{'starttime_'.$i} = $value->starttime;
        //     $data->{'endtime_'.$i} = $value->endtime;
        //     $i++;
        // }
    
        return $data;
    }
    

    public static function isThisAvailable($date, $teacher, $start_time)
    {
        global $DB, $USER;
        
        // Get user's timezone
        $userTimezone = new \DateTimeZone($USER->timezone != '99' ? $USER->timezone : \core_date::get_server_timezone());
        
        // Get coach's timezone
        $coachTimezone = new \DateTimeZone($DB->get_field_sql('SELECT timezone FROM {user} WHERE id = ?', [$teacher]));
        
        // Convert start_time to a DateTime object in user's timezone
        $userDateTime = new \DateTime($date . ' ' . $start_time, $userTimezone);
        
        // Convert user's datetime to coach's timezone for date checks
        $coachDateTime = clone $userDateTime;
        $coachDateTime->setTimezone($coachTimezone);
        
        // Specific date check
        $specificDate = $DB->get_record_sql('SELECT * FROM {' . self::TABLE_SD . '} WHERE teacher = ? AND date = ?', [$teacher, $coachDateTime->format('Y-m-d')]);
        if ($specificDate) {
            if (!$specificDate->available) return false;
            $availableStart = new \DateTime($coachDateTime->format('Y-m-d') . ' ' . $specificDate->starttime, $coachTimezone);
            $availableEnd = new \DateTime($coachDateTime->format('Y-m-d') . ' ' . $specificDate->endtime, $coachTimezone);
            
            // Convert to user's timezone
            $availableStart->setTimezone($userTimezone);
            $availableEnd->setTimezone($userTimezone);
            
            return $userDateTime >= $availableStart && $userDateTime < $availableEnd;
        }
        
        // Week days check
        $week_day = strtolower($coachDateTime->format('D'));
        $weeklyAvailability = $DB->get_record_sql('SELECT * FROM {'.self::TABLE.'} WHERE teacher = ? AND week_day = ?', [$teacher, $week_day]);
        
        if ($weeklyAvailability) {
            $availableStart = new \DateTime($coachDateTime->format('Y-m-d') . ' ' . $weeklyAvailability->start_time, $coachTimezone);
            $availableEnd = new \DateTime($coachDateTime->format('Y-m-d') . ' ' . $weeklyAvailability->end_time, $coachTimezone);
            
            // Convert to user's timezone
            $availableStart->setTimezone($userTimezone);
            $availableEnd->setTimezone($userTimezone);
            
            return $userDateTime >= $availableStart && $userDateTime < $availableEnd;
        }
        
        // If no availability is found, return false
        return false;
    }



    public static function createSpecificDate ($data)
    {
        global $DB;
        return $DB->insert_record(self::TABLE_SD, $data);
    }

    public static function handleSpecificDate($dates)
    {
        global $DB, $USER;

        $specificDates = $DB->get_records_sql('SELECT * FROM {'.self::TABLE_SD.'} WHERE teacher = ?', [$USER->id]);
        foreach ($specificDates as $key => $date) {
            $DB->delete_records(self::TABLE_SD, ['id' => $date->id]);
        }

        $dates = json_decode($dates);
        foreach($dates as $item) {
            $record = new stdClass();
            $record->teacher = $USER->id;
            $record->date = strtotime($item->date);
            $record->available = $item->available;
            $record->starttime = $item->starttime;
            $record->endtime = $item->endtime;

            $record->id = $DB->insert_record(self::TABLE_SD, $record);
        }
    }
}