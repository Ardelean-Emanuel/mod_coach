<?php

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/coach/locallib.php');
require_once($CFG->libdir . '/excellib.class.php'); // Load Moodle Excel library

$courseid = required_param('courseid', PARAM_INT); // The course ID should be passed as a parameter
$action = optional_param('action', '', PARAM_ALPHA);

require_login($courseid); // Ensure the user is logged in and has access to the course
$context = context_course::instance($courseid);

// Require admin capability
require_capability('moodle/site:config', $context);
// Check if the user has the capability to view this page
require_capability('mod/coach:view', $context);

// Set up the page
$PAGE->set_url('/mod/coach/synced_users.php', array('courseid' => $courseid));
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('synced_users', 'mod_coach'));
$PAGE->set_heading(get_string('synced_users', 'mod_coach'));

// Get the course name
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

// Get all users who have synced their calendar for the current course
$sql = "SELECT u.id, u.firstname, u.lastname, u.email, c.calendar_type, c.timemodified
        FROM {user} u
        JOIN {coach_calendar_sync} c ON u.id = c.userid
        JOIN {course_modules} cm ON cm.id = c.cm_id
        JOIN {modules} m ON cm.module = m.id
        JOIN {coach} co ON co.id = cm.instance
        WHERE cm.course = ? AND m.name = 'coach'
        ORDER BY c.timemodified DESC";
$synced_users = $DB->get_records_sql($sql, array($courseid));

// Check if the user requested the export action
if ($action === 'export') {
    export_to_excel($synced_users);
    exit;
}

// Output starts here
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('synced_users', 'mod_coach'));

// Add the export button
$export_url = new moodle_url('/mod/coach/synced_users.php', array('courseid' => $courseid, 'action' => 'export'));
echo $OUTPUT->single_button($export_url, get_string('export_to_excel', 'mod_coach'));

// Display the synced users in a table
if ($synced_users) {
    echo html_writer::start_tag('table', array('class' => 'generaltable'));
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('firstname', 'core'));
    echo html_writer::tag('th', get_string('lastname', 'core'));
    echo html_writer::tag('th', get_string('email', 'core'));
    echo html_writer::tag('th', get_string('calendar_type', 'mod_coach'));
    echo html_writer::tag('th', get_string('last_synced', 'mod_coach'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');
    foreach ($synced_users as $user) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', $user->firstname);
        echo html_writer::tag('td', $user->lastname);
        echo html_writer::tag('td', $user->email);
        echo html_writer::tag('td', $user->calendar_type);
        echo html_writer::tag('td', userdate($user->timemodified));
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
} else {
    echo $OUTPUT->notification(get_string('no_synced_users', 'mod_coach'), 'info');
}

// Output ends here
echo $OUTPUT->footer();

/**
 * Function to export the synced users data to an Excel file
 *
 * @param array $synced_users The list of synced users
 */
function export_to_excel($synced_users) {
    global $course;

    // Create a new Excel workbook
    $filename = 'Calendar_synced_users.xlsx';
    $workbook = new MoodleExcelWorkbook('-'); // Create a new workbook
    $worksheet = $workbook->add_worksheet('Synced Users'); // Add a worksheet to the workbook

    // Define the header
    $headers = array(
        get_string('firstname', 'core'),
        get_string('lastname', 'core'),
        get_string('email', 'core'),
        get_string('calendar_type', 'mod_coach'),
        get_string('last_synced', 'mod_coach')
    );

    // Write the header to the worksheet
    foreach ($headers as $col => $header) {
        $worksheet->write(0, $col, $header); // Write each header cell
    }

    // Write the data
    $row = 1;
    foreach ($synced_users as $user) {
        $worksheet->write($row, 0, $user->firstname); // Firstname
        $worksheet->write($row, 1, $user->lastname);  // Lastname
        $worksheet->write($row, 2, $user->email);     // Email
        $worksheet->write($row, 3, $user->calendar_type); // Calendar type
        $worksheet->write($row, 4, userdate($user->timemodified)); // Last synced date
        $row++;
    }

    // Send the workbook to the browser for download
    $workbook->close($filename);
}
