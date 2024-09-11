<?php
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// is admin
require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 30, PARAM_INT);
$coachid = optional_param('coachid', 0, PARAM_INT);

$PAGE->set_url(new moodle_url('/mod/coach/coach_user_report.php', array('page' => $page, 'perpage' => $perpage, 'coachid' => $coachid)));
$PAGE->set_title(get_string('coachuserreport', 'mod_coach'));
$PAGE->set_heading(get_string('coachuserreport', 'mod_coach'));

echo $OUTPUT->header();

// Get all coaches
$coaches = $DB->get_records_sql("
    SELECT DISTINCT u.id, u.firstname, u.lastname
    FROM {user} u
    JOIN {coach_event} ce ON ce.coachid = u.id
");

// Coach filter form
$coachfilter = new single_select(
    new moodle_url('/mod/coach/coach_user_report.php', array('page' => 0, 'perpage' => $perpage)),
    'coachid',
    array_map(function($coach) {
        return fullname($coach);
    }, $coaches),
    $coachid,
    array(0 => get_string('allcoaches', 'mod_coach'))
);
echo $OUTPUT->render($coachfilter);

// Build the SQL query
$sql = "SELECT u.id, u.firstname, u.lastname, u.email, 
               COUNT(ce.id) as session_count, 
               MAX(ce.timecreated) as last_session,
               GROUP_CONCAT(ce.date ORDER BY ce.timecreated DESC SEPARATOR ', ') as session_dates,
               MIN(ce.tos_accepted) as tos_accepted,
               c.id as coachid, c.firstname as coachfirstname, c.lastname as coachlastname
        FROM {user} u
        JOIN {coach_event} ce ON ce.userid = u.id
        JOIN {user} c ON ce.coachid = c.id";
$params = array();

if ($coachid) {
    $sql .= " WHERE ce.coachid = :coachid";
    $params['coachid'] = $coachid;
}

$sql .= " GROUP BY u.id, u.firstname, u.lastname, u.email, c.id, c.firstname, c.lastname
          ORDER BY last_session DESC";

// Count total records for pagination
$countsql = "SELECT COUNT(DISTINCT u.id) 
             FROM {user} u 
             JOIN {coach_event} ce ON ce.userid = u.id";

if ($coachid) {
    $countsql .= " WHERE ce.coachid = :coachid";
}

$total = $DB->count_records_sql($countsql, $params);

$users = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

// Output the results in a table
$table = new html_table();
$table->head = array(
    get_string('fullname'),
    get_string('email'),
    get_string('coach', 'mod_coach'),
    get_string('sessioncount', 'mod_coach'),
    get_string('lastsession', 'mod_coach'),
    get_string('sessiondates', 'mod_coach'),
    get_string('tosstatus', 'mod_coach')
);

foreach ($users as $user) {
    $table->data[] = array(
        fullname($user),
        $user->email,
        fullname((object)['firstname' => $user->coachfirstname, 'lastname' => $user->coachlastname]),
        $user->session_count,
        userdate($user->last_session),
        $user->session_dates,
        $user->tos_accepted ? get_string('tosaccepted', 'mod_coach') : '<span style="color: red;">' . get_string('tosnotaccepted', 'mod_coach') . '</span>'
    );
}

echo html_writer::table($table);

echo $OUTPUT->paging_bar($total, $page, $perpage, $PAGE->url);

echo $OUTPUT->footer();