<?php

class CoachSettings {

    const DEFAULT_MIN = 0;
    const DEFAULT_MAX = 30;
    const DAY_SECCONDS = 86400;

    public static function getMin($user = 0)
    {
        global $USER;
        if ($user == 0) $user = $USER->id;
        $config = get_config('mod_coach', 'mod_coach_min_'.$user);
        return $config ? $config : self::DEFAULT_MIN;
    }

    public static function getMax($user = 0)
    {
        global $USER;
        if ($user == 0) $user = $USER->id;
        $config = get_config('mod_coach', 'mod_coach_max_'.$user);
        return $config ? $config : self::DEFAULT_MAX;
    }

    public static function setMin($value, $user = 0)
    {
        global $USER;
        if ($user == 0) $user = $USER->id;
        set_config('mod_coach_min_'.$user, $value, 'mod_coach');
    }

    public static function setMax($value, $user = 0)
    {
        global $USER;
        if ($user == 0) $user = $USER->id;
        set_config('mod_coach_max_'.$user, $value, 'mod_coach');
    }

    public static function isDateUsable($user, $date)
    {
        $date = strtotime($date);
        $minDay = time() + (self::DAY_SECCONDS * self::getMin($user));
        $maxDay = time() + (self::DAY_SECCONDS * self::getMax($user));
        return 
        (object)[
            'status' => (($date > $minDay) && ($date < $maxDay)),
            'minDay' => $minDay,
            'maxDay' => $maxDay
        ];
    }
}