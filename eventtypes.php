<?php
require('../../config.php');
require_once($CFG->dirroot.'/mod/coach/classes/Coach.php');

$id = required_param('id', PARAM_INT);

if (!$cm = get_coursemodule_from_id('coach', $id)) {
    print_error('invalidcoursemodule');
}
$coach = $DB->get_record('coach', array('id'=>$cm->instance), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);

$PAGE->set_url('/mod/coach/eventtypes.php', array('id' => $id));
$PAGE->set_title('Event types');
$PAGE->requires->jquery();

$sortby = optional_param('sortby', 'timecreated', PARAM_TEXT);
$sortdir = optional_param('sortdir', 'desc', PARAM_TEXT);
$page = optional_param('page', 0, PARAM_INT);

$perpage = 5;
$baseurl = new moodle_url('/mod/coach/eventtypes.php', array('id' => $id, 'sortby' => $sortby, 'sortdir' => $sortdir));

$content = '';

$Types = $DB->get_records_sql('SELECT * FROM {coach_event_type} WHERE cm_id=? AND timedeleted=0 ORDER BY '.$sortby.' '.$sortdir, array($id));

$originalLogs = $Types;
$nrOfPages = ceil(count($Types) / $perpage);
if($page + 1 > $nrOfPages && $page > 0) {
    $page = 0;
}

$Types = array_slice($Types, $page*$perpage, $perpage, true);

if($Types) {
    $content .='
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Name
                    <span class="sortarrows">
                        <a href="?id='.$id.'&sortby=name&sortdir=asc&page='.$page.'" class="'.($sortdir === 'asc' && $sortby === 'name' ? 'hidden' : '').'"><i class="fa fa-caret-up" aria-hidden="true"></i></a>
                        <a href="?id='.$id.'&sortby=name&sortdir=desc&page='.$page.'" class="'.($sortdir === 'desc' && $sortby === 'name' ? 'hidden' : '').'"><i class="fa fa-caret-down" aria-hidden="true"></i></a>
                    </span>
                </th>
                <th scope="col">Date added
                    <span class="sortarrows">
                        <a href="?id='.$id.'&sortby=timecreated&sortdir=asc&page='.$page.'" class="'.($sortdir === 'asc' && $sortby === 'timecreated' ? 'hidden' : '').'"><i class="fa fa-caret-up" aria-hidden="true"></i></a>
                        <a href="?id='.$id.'&sortby=timecreated&sortdir=desc&page='.$page.'" class="'.($sortdir === 'desc' && $sortby === 'timecreated' ? 'hidden' : '').'"><i class="fa fa-caret-down" aria-hidden="true"></i></a>
                    </span>
                </th>
                <th scope="col">Payment type</th>
               

                <th scope="col">Duration</th>
                <th scope="col">Disabled</th>
                <th scope="col" class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>';

        foreach($Types as $item) {
        
            $currencySymbol = (($item->currency === 'CAD' || $item->currency === 'USD') ? '$' : ($item->currency === 'GBP' ? '£' : '€'));
            
            $content .= '
            <tr>
                <td class="align-middle">'.$item->name.'</td>
                <td class="align-middle">'.date('Y-m-d', $item->timecreated).'</td>
                <td class="align-middle">'.$item->payment_type.'</td>
               
              
                <td class="align-middle">'.$item->duration.' Minutes</td>
                <td class="align-middle">'.($item->disabled ? 'Yes' : '').'</td>

           
                <td class="align-middle text-center">';
                    // <a class="" style="color: black;" href="'.$CFG->wwwroot.'/mod/coach/editeventtype.php?id='.$item->id.'">'.($OUTPUT->pix_icon('t/editinline', 'Edit event type')).'</a>
                    // <a class="" style="color: black;" href="'.$CFG->wwwroot.'/mod/coach/deleteeventtype.php?id='.$item->id.'">'.($OUTPUT->pix_icon('t/delete', 'Delete event type')).'</a>
                    $content .= '
                </td>
            </tr>';
        }
    
        
    $content .= '
        </tbody>
    </table>';
    $content .= $OUTPUT->paging_bar(count($originalLogs), $page, $perpage, $baseurl, 'page');

} else {
    $content .= '<div class="alert alert-warning text-center mt-2">No event types</div>';
}

echo $OUTPUT->header();
echo '<div class="d-flex">
<a class="btn btn-primary" href="'.$CFG->wwwroot.'/mod/coach/addeventtype.php?id='.$id.'">New event type</a>
</div>    
<br><br>';

echo $content;

echo $OUTPUT->footer();