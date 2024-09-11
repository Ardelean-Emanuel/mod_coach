<?php

defined('MOODLE_INTERNAL') || die();

$settings->add(new admin_setting_configtext('mod_coach_google_cliend_id', get_string('googleclientid', 'mod_coach'), '', ''));
$settings->add(new admin_setting_configpasswordunmask('mod_coach_google_cliend_secret', get_string('googleclientsecret', 'mod_coach'), '', ''));
$settings->add(new admin_setting_configtext('mod_coach_google_redirect_uri', get_string('googleredirecturi', 'mod_coach'), '', '/mod/coach/google_callback.php'));


$settings->add(new admin_setting_configtext('mod_coach_outlook_cliend_id', get_string('outlookclientid', 'mod_coach'), '', ''));
$settings->add(new admin_setting_configpasswordunmask('mod_coach_outlook_cliend_secret', get_string('outlookclientsecret', 'mod_coach'), '', ''));
$settings->add(new admin_setting_configtext('mod_coach_outlook_redirect_uri', get_string('outlookredirecturi', 'mod_coach'), '', '/mod/coach/outlook_callback.php'));

// Add this to settings.php
$ADMIN->add('reports', new admin_externalpage('coachuserreport',
    get_string('coachuserreport', 'mod_coach'),
    new moodle_url('/mod/coach/coach_user_report.php'),
    'mod/coach:viewuserreport'
));
