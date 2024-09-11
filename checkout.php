<?php
require('../../config.php');
require_once($CFG->dirroot.'/mod/coach/classes/Event.php');

require_once $CFG->dirroot.'/local/stripe/stripe-php/init.php';
require_once($CFG->dirroot.'/local/managepartner/classes/GoogleAnalytics.php');

require_login();
$eventtypeid = optional_param('id', 0, PARAM_INT);

// $event = checkout_viewed::create([
//   'objectid' => $eventid, 
//   'context' => \context_system::instance(),
// ]);
// $event->trigger();

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/mod/coach/checkout.php');
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('checkout', 'local_shop'));
// $PAGE->requires->jquery();
// $PAGE->requires->js('/local/shop/assets/js/custom.js');

$EventType = \mod_coach\Event::getEventType($eventtypeid);
if(!isset($_SESSION['eventdata'.$USER->id])){
    redirect(new moodle_url('/mod/coach/view.php?id='.$EventType->cm_id));
}

$Event = $_SESSION['eventdata'.$USER->id];

$formAttributes = array(
    'action' => $CFG->wwwroot . '/local/stripe/payevent.php',
    'method' => 'get',
    'id' => 'payment'
);
$paymentHTML = html_writer::start_tag('form', $formAttributes);
$paymentHTML .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'itemid', 'value' => $eventtypeid));

$submitButtonAttributes = array('type' => 'submit', 'class' => 'btn btn-primary mt-2 mb-2 float-right buynow');
$submitButton = html_writer::tag('button', get_string('buynow', 'local_shop'), $submitButtonAttributes);

$paymentHTML .= '<p class="card-text text-right pricetag">'.\mod_coach\Event::getPriceString($eventtypeid).'</p>' . $submitButton;

$paymentHTML .= html_writer::end_tag('form');

$content ='
<div class="container">
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="row m-1 d-flex justify-content-between align-items-center mb-5">
                        <div>
                            <h5 class="card-title">Meeting with '.$Event->name.'</h5>
                            <p class="card-title d-block">Event type: <strong>'.format_text($EventType->name, FORMAT_HTML).'</strong></p>
                            <p class="card-title d-block">Date: <strong>'.date('Y-m-d',$Event->timestamp).'</strong></p>
                            <p class="card-title d-block">Time: <strong>'.date('H:i', $Event->timestamp).'</strong></p>
                            <p class="card-title d-block">Duration: <strong>'.$EventType->duration.' minutes</strong></p>
                        </div>
                    </div>
                    '.$paymentHTML.'
                </div>
            </div>
        </div>
    </div>
</div>';


// $googleAnalytics = "
//     <script async src=\"https://www.googletagmanager.com/gtag/js?id=".$CFG->local_stripe_google_key."\"></script>
//     <script>
//     window.dataLayer = window.dataLayer || [];
//     function gtag(){dataLayer.push(arguments);}
//     gtag('js', new Date());
        
//     gtag('config', '".$CFG->local_stripe_google_key."');

//     function sendCheckoutData() {
//         gtag('event', 'checkout', {
//         'affiliation': '".$DB->get_field_sql("SELECT name FROM {course_categories} WHERE id = ?", [$moodleProduct->partner])."',
//         'currency': '".$moodleProduct->currency."',
//         'items': [{
//             'item_name': '".$moodleProduct->name."',
//             'price': ".$moodleProduct->price."
//         }]
//         });
//     }
//     sendCheckoutData()
//     </script>";

//   $partnerCode = GoogleAnalytics::getPartnerCode($moodleProduct->partner);
//   if($partnerCode){
//     $googleAnalyticsPartner = "
//     <script async src=\"https://www.googletagmanager.com/gtag/js?id=".$partnerCode."\"></script>
//     <script>
//     window.dataLayer = window.dataLayer || [];
//     function gtag(){dataLayer.push(arguments);}
//     gtag('js', new Date());
        
//     gtag('config', '".$partnerCode."');

//     function sendCheckoutData() {
//         gtag('event', 'checkout', {
//         'affiliation': '".$DB->get_field_sql("SELECT name FROM {course_categories} WHERE id = ?", [$moodleProduct->partner])."',
//         'currency': '".$moodleProduct->currency."',
//         'items': [{
//             'item_name': '".$moodleProduct->name."',
//             'price': ".$moodleProduct->price."
//         }]
//         });
//     }
//     sendCheckoutData()
//     </script>";
//   } else {
//     $googleAnalyticsPartner = '';
//   }

echo $OUTPUT->header();
// echo $googleAnalytics;
// echo $googleAnalyticsPartner;
echo '<link rel="stylesheet" href="'.$CFG->wwwroot.'/local/shop/assets/css/bootstrap-select.min.css">
<script src="'.$CFG->wwwroot.'/local/shop/assets/js/bootstrap-select.min.js"></script>';
echo $content;
echo $OUTPUT->footer();