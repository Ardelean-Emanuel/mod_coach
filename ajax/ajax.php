<?php

require '../../../config.php';

$PAGE->set_context(context_system::instance());
$result = array(
    'status' => '',
    'content' => ''
);
// We do not write anything to session in AJAX calls
session_write_close();
try {
    $action = required_param('action', PARAM_ALPHANUMEXT);
    if(file_exists($CFG->dirroot.'/mod/coach/ajax/'.$action.'.php')) {
        require $CFG->dirroot.'/mod/coach/ajax/'.$action.'.php';
    }
    else {
        throw new Exception('Invalid action');
    }
} catch (Exception $ex) {
    $result['status'] = 'error';
    $result['error'] = $ex->getMessage();
}
header('Content-Type: application/json');
echo json_encode($result);
