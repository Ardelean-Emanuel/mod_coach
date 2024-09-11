<?php
require_once('../../config.php');
require_login();

// Check if the user is an admin
if (!is_siteadmin()) {
    // If not an admin, redirect to the Moodle homepage with a notice
    redirect(new moodle_url('/'), get_string('nopermissions', 'error', 'view the report'));
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/mod/coach/report.php');
$PAGE->set_title(get_string('coachreport', 'mod_coach'));
$PAGE->set_heading(get_string('coachreportheading', 'mod_coach'));

// Simple SQL query to retrieve necessary data from coach_event table where tos_accepted = 1
// join {user} table to get the coach's name
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

echo $OUTPUT->header();
echo html_writer::tag('h2', get_string('coachreportheading', 'mod_coach'));

// Generate the table
$table = new html_table();
$table->head = array(
    get_string('coach', 'mod_coach'),
    get_string('month', 'mod_coach'),
    get_string('totalhours', 'mod_coach')
);

foreach ($reportdata as $coach_id => $months) {
    foreach ($months as $month => $total_hours) {
        // replace coach_id with coach's full name
        $coach_name = $records[$coach_id]->firstname . ' ' . $records[$coach_id]->lastname;
        $table->data[] = array(
            $coach_name,
            $month,
            round($total_hours, 2)
        );
    }
}

echo html_writer::table($table);

// Add export to Excel button
$downloadurl = new moodle_url('/mod/coach/report_download.php');
echo $OUTPUT->single_button($downloadurl, get_string('exportexcel', 'mod_coach'));

echo $OUTPUT->footer();
