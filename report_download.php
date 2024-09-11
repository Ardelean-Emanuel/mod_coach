<?php
require_once('../../config.php');
require_once($CFG->libdir.'/excellib.class.php');
require_login();

// Check if the user is an admin
if (!is_siteadmin()) {
    redirect(new moodle_url('/'), get_string('nopermissions', 'error', 'download the report'));
}

$context = context_system::instance();
$PAGE->set_context($context);

// Simple SQL query to retrieve necessary data from coach_event table where tos_accepted = 1
// Join {user} table to get the coach's name
$sql = "
    SELECT 
        e.coachid AS coach_id,
        u.firstname,
        u.lastname,
        e.date,
        e.hour,
        e.event_duration_min
    FROM 
        {coach_event} e
    JOIN 
        {user} u
    ON 
        e.coachid = u.id
    WHERE 
        e.tos_accepted = 1";

$records = $DB->get_records_sql($sql);

// Process data to calculate total hours per coach per month
$reportdata = [];
foreach ($records as $record) {
    $coach_id = $record->coach_id;
    // Get the month in the format "2024 - September"
    $month = date('Y - F', strtotime($record->date));
    $duration_hours = $record->event_duration_min / 60; // Convert minutes to hours

    if (!isset($reportdata[$coach_id])) {
        $reportdata[$coach_id] = [];
    }

    if (!isset($reportdata[$coach_id][$month])) {
        $reportdata[$coach_id][$month] = 0;
    }

    // Add duration to the total hours for the coach in that month
    $reportdata[$coach_id][$month] += $duration_hours;
}

// Create the Excel file
$filename = 'coach_report_' . date('Ymd_His') . '.xls';
$workbook = new MoodleExcelWorkbook("-");
$workbook->send($filename);
$worksheet = $workbook->add_worksheet('Coach Report');

// Write header
$worksheet->write(0, 0, get_string('coach', 'mod_coach'));
$worksheet->write(0, 1, get_string('month', 'mod_coach'));
$worksheet->write(0, 2, get_string('totalhours', 'mod_coach'));

// Write data
$rownum = 1;
foreach ($reportdata as $coach_id => $months) {
    foreach ($months as $month => $total_hours) {
        // Replace coach_id with coach's full name
        $coach_name = $records[$coach_id]->firstname . ' ' . $records[$coach_id]->lastname;
        $worksheet->write($rownum, 0, $coach_name);
        $worksheet->write($rownum, 1, $month);
        $worksheet->write($rownum, 2, round($total_hours, 2));
        $rownum++;
    }
}

$workbook->close();
exit;
