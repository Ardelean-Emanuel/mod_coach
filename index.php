<?php

require('../../config.php');

$id = required_param('id', PARAM_INT); // course id

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);

require_course_login($course, true);
$PAGE->set_userprofileupdatelayout('incourse');

// Trigger instances list viewed event.
$event = \mod_coach\event\course_module_instance_list_viewed::create(array('context' => context_course::instance($course->id)));
$event->add_record_snapshot('course', $course);
$event->trigger();

$struserprofileupdate         = get_string('modulename', 'mod_coach');
$struserprofileupdates        = get_string('modulenameplural', 'mod_coach');
$strname         = get_string('name');
$strintro        = get_string('moduleintro');
$strlastmodified = get_string('lastmodified');

$PAGE->set_url('/mod/coach/index.php', array('id' => $course->id));
$PAGE->set_title($course->shortname.': '.$struserprofileupdates);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($struserprofileupdates);
echo $OUTPUT->header();
echo $OUTPUT->heading($struserprofileupdates);
if (!$userprofileupdates = get_all_instances_in_course('userprofileupdate', $course)) {
    notice(get_string('thereareno', 'moodle', $struserprofileupdates), "$CFG->wwwroot/course/view.php?id=$course->id");
    exit;
}

$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_'.$course->format);
    $table->head  = array ($strsectionname, $strname, $strintro);
    $table->align = array ('center', 'left', 'left');
} else {
    $table->head  = array ($strlastmodified, $strname, $strintro);
    $table->align = array ('left', 'left', 'left');
}

$modinfo = get_fast_modinfo($course);
$currentsection = '';
foreach ($userprofileupdates as $userprofileupdate) {
    $cm = $modinfo->cms[$userprofileupdate->coursemodule];
    if ($usesections) {
        $printsection = '';
        if ($userprofileupdate->section !== $currentsection) {
            if ($userprofileupdate->section) {
                $printsection = get_section_name($course, $userprofileupdate->section);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $userprofileupdate->section;
        }
    } else {
        $printsection = '<span class="smallinfo">'.userdate($userprofileupdate->timemodified)."</span>";
    }

    $class = $userprofileupdate->visible ? '' : 'class="dimmed"'; // hidden modules are dimmed

    $table->data[] = array (
        $printsection,
        "<a $class href=\"view.php?id=$cm->id\">".format_string($userprofileupdate->name)."</a>",
        format_module_intro('userprofileupdate', $userprofileupdate, $cm->id));
}

echo html_writer::table($table);

echo $OUTPUT->footer();
