<?php

require_once($CFG->dirroot.'/mod/coach/classes/Availability.php');

$dates = required_param('dates', PARAM_RAW); 

try {
    Availability::handleSpecificDate($dates);
    $result['status'] = 'ok';
} catch (Exception $ex) {
    $result['status'] = 'error';
    $result['error'] = $ex->getMessage();
}