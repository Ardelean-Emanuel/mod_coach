<?php
require_once('../../config.php');
require_once($CFG->libdir.'/outputrenderers.php');
require_once($CFG->dirroot.'/user/profile/lib.php');

// Set up the page.
$PAGE->set_url(new moodle_url('/mod/coach/coach.php'));
$PAGE->set_title(get_string('bookcoaching', 'mod_coach'));
$PAGE->set_heading(get_string('bookcoaching', 'mod_coach'));
$PAGE->set_context(context_system::instance());

// Check if the user is logged in and is a volunteer.
require_login();
if (isguestuser()) {
    print_error('noguest');
}

// Get custom profile fields.
$profile_fields = profile_get_custom_fields();

// Define the custom profile fields we are interested in.
$specialization_field = 'specialization';
$language_field = 'language';  // Assuming 'language' is a custom profile field

// Get form data if form is submitted.
$selected_specialization = optional_param('specialization', '', PARAM_TEXT);
$selected_language = optional_param('language', '', PARAM_TEXT);
$selected_coach_id = optional_param('coach', 0, PARAM_INT);

if ($selected_coach_id) {
    // Query the mdl_coach_calendar_sync table to get cmid and typeid.
    $record = $DB->get_record('coach_calendar_sync', ['userid' => $selected_coach_id], 'cm_id, id');

    if ($record) {
        $cmid = $record->cm_id;
        $typeid = $record->id;
        // Redirect to the event page.
        redirect(new moodle_url('/mod/coach/view.php', ['id' => $cmid]));
        exit;
    } else {
        print_error('nocmtypeid', 'mod_coach');
    }
}

$where_conditions = [];
$params = ['spec_field' => $specialization_field, 'lang_field' => $language_field];

if ($selected_specialization) {
    $where_conditions[] = $DB->sql_like('up1.data', ':specialization');
    $params['specialization'] = '%' . $DB->sql_like_escape($selected_specialization) . '%';
}
if ($selected_language) {
    $where_conditions[] = $DB->sql_like('up2.data', ':language');
    $params['language'] = '%' . $DB->sql_like_escape($selected_language) . '%';
}

$where_sql = $where_conditions ? ' AND ' . implode(' AND ', $where_conditions) : '';

// Fetch users who are coaches and have synced their calendars.
$coaches = $DB->get_records_sql("
    SELECT u.id, u.firstname, u.lastname, 
           up1.data AS specialization, 
           up2.data AS language
    FROM {user} u
    JOIN {user_info_data} up1 ON u.id = up1.userid
    JOIN {user_info_data} up2 ON u.id = up2.userid
    JOIN {user_info_field} uf1 ON up1.fieldid = uf1.id
    JOIN {user_info_field} uf2 ON up2.fieldid = uf2.id
    JOIN {role_assignments} ra ON u.id = ra.userid
    JOIN {role} r ON ra.roleid = r.id
    JOIN {coach_calendar_sync} ccs ON u.id = ccs.userid
    WHERE uf1.shortname = :spec_field AND uf2.shortname = :lang_field 
      AND r.shortname = 'coach' $where_sql
    GROUP BY u.id, u.firstname, u.lastname, up1.data, up2.data
", $params);

echo $OUTPUT->header();
echo '<h2>' . get_string('bookcoaching', 'mod_coach') . '</h2>';
echo '<form method="post" action="">';
echo '<label for="specialization">' . get_string('specialization', 'mod_coach') . ':</label>';
echo '<select name="specialization" id="specialization">';
echo '<option value="">' . get_string('all', 'mod_coach') . '</option>';
foreach ($profile_fields as $field) {
    if ($field->shortname == $specialization_field) {
        foreach (explode("\n", $field->param1) as $option) {
            $selected = ($option == $selected_specialization) ? 'selected' : '';
            echo '<option value="' . $option . '" ' . $selected . '>' . $option . '</option>';
        }
    }
}
echo '</select><br><br>';

echo '<label for="language">' . get_string('language', 'mod_coach') . ':</label>';
echo '<select name="language" id="language">';
echo '<option value="">' . get_string('all', 'mod_coach') . '</option>';
foreach ($profile_fields as $field) {
    if ($field->shortname == $language_field) {
        foreach (explode("\n", $field->param1) as $option) {
            $selected = ($option == $selected_language) ? 'selected' : '';
            echo '<option value="' . $option . '" ' . $selected . '>' . $option . '</option>';
        }
    }
}
echo '</select><br><br>';

echo '<button type="submit">' . get_string('filtercoaches', 'mod_coach') . '</button>';
echo '</form><br>';

if (!empty($coaches)) {
    echo '<form method="post" action="">';
    echo '<label for="coach">' . get_string('selectcoach', 'mod_coach') . ':</label>';
    echo '<select name="coach" id="coach">';
    foreach ($coaches as $coach) {
        echo '<option value="' . $coach->id . '">' . $coach->firstname . ' ' . $coach->lastname . ' (' . $coach->specialization . ', ' . $coach->language . ')</option>';
    }
    echo '</select><br><br>';
    echo '<button type="submit">' . get_string('booksession', 'mod_coach') . '</button>';
    echo '</form>';
} else {
    echo '<p>' . get_string('nocoachesfound', 'mod_coach') . '</p>';
}

echo $OUTPUT->footer();
?>
