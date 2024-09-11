<?php
namespace mod_coach;

use mod_coach\Event;

require('../../config.php');
require_login();
require_once($CFG->dirroot.'/mod/coach/classes/acceptTos_form.php');
$PAGE->set_context(\context_system::instance());
$PAGE->requires->css('/mod/coach/styles.css');

$PAGE->set_url('/mod/coach/tos.php');
$PAGE->set_title('Supplementary Terms of Use for Coaching Services Platform');

$ref = optional_param('ref', '', PARAM_NOTAGS);

if(!empty($ref)){
    if($EventData = json_decode(base64_decode($ref))){
        // var_dump($EventData); die;

        if($DB->get_field_sql('SELECT tos_accepted FROM {coach_event} WHERE id=?', array($EventData->eventid)) == 1){
            redirect(new \moodle_url('/mod/coach/view.php?id='.$EventData->cmid), 'Terms of use have been accepted!', null, 'success');
        }
    
        $mform = new \acceptTos_form($CFG->wwwroot.'/mod/coach/tos.php?ref='.$ref);
    
        
        $defaultData = new \stdClass();
        $defaultData->title = 'Do you accept these terms?';
    
        $mform->set_data($defaultData);
    
        if ($mform->is_cancelled()) {
            redirect(new \moodle_url('/mod/coach/view.php?id='.$EventData->cmid));
        } else if ($mform->is_submitted()) {
            if ($data = $mform->get_data()) {
               
                    $Event = new Event($EventData->eventid);
                    $Event->AcceptTos();
                    redirect(new \moodle_url('/mod/coach/view.php?id='.$EventData->cmid), 'Terms of use have been accepted', null, 'success');
                
            }    
        }
    }
}


echo $OUTPUT->header();
echo '
    <h1>Supplementary Terms of Use for Coaching Services Platform</h1>
    <p>These Supplementary Terms of Use ("Supplementary Terms") are provided in addition to, and not in substitution of, any existing Terms of Use including Special Terms, governing the use of all UNV.org sites (“Primary Terms”). By using the Coaching Services Platform, you agree to be bound by both these Supplementary Terms and the Primary Terms. In the event of any conflict between these Supplementary Terms and the Primary Terms, the provisions of these Supplementary Terms shall prevail solely to the extent of such conflict.</p>
    
    <h2>1. Definitions</h2>
    <ul>
        <li><strong>Registered Volunteer User:</strong> is a serving UN Volunteer who has registered on the UNV E-Campus site to receive coaching services.</li>
        <li><strong>Coaching Provider:</strong> is an organization or an individual who has been accepted by UNV to provide coaching services to serving UN Volunteers.</li>
        <li><strong>User:</strong> refers to any individual or organization accessing the Coaching Services Platform.</li>
        <li><strong>Coaching Services Platform:</strong> means the [insert description of the Platform]</li>
    </ul>

    <h2>2. General</h2>
    <p>The Coaching Services Platform (hereinafter “The Platform”) is a venue for Coaching Providers to provide free online coaching opportunities to Registered Volunteer Users. UNV maintains the Platform as a courtesy to those who may choose to access it. UNV administers the technical aspects of the Platform. The information presented in the Platform is for informative purposes only.</p>
    <p>The User may need to register and/or create a User account to access or use the Platform. Use of any password-protected area of the Platform is restricted to the Registered User who has registered or otherwise been given permission and a password to enter such area.</p>
    <p>The User may never use another User account without permission. By registering or creating a User account, the User represents and warrants that (i) the User is of legal age or possess legal parental or guardian consent and fully able and competent to abide by and comply with these Supplementary Terms; and (ii) if the User represents a legal entity, the User has authority to bind such legal entity to these Terms of Use. The User is responsible for keeping the password and other relevant login information confidential and shall inform UNV if at any point the User considers that his or her password or login information have been compromised and/or is being misused by someone else and shall take such action as is required and/or is requested by UNV to prevent such misuse. The User is responsible for all activities conducted under his or her User account.</p>
    <p>UNV is not involved in the actual arrangements between Registered Volunteer Users and Coaching Providers and declines all liability regarding the relationship between them. UNV assumes no control or liability over the quality, safety or legality of the Coaching Services offered and provided or the ability of the Coaching Provider to offer and provide Coaching Services to the Registered Volunteer Users.</p>

    <h2>3. Disclaimers & Indemnification by User</h2>
    <p>The User specifically acknowledges and agrees that UNV is not liable for any conduct of any User. UNV does not represent or endorse the accuracy or reliability of any advice, opinion, statement or other information provided by any User or any other person or entity. Reliance upon any such advice, opinion, statement, or other information shall be at the User’s own risk. The Platform may contain links and references to third-party websites. UNV does not control the linked sites, and UNV is not responsible for the content of any linked site or any link contained in a linked site. UNV provides these links only as a convenience, and the inclusion of a link or reference does not imply a UNV endorsement of the linked site.</p>
    <p>UNV accepts no responsibility or liability in respect of the conduct of any User in connection with the Platform and User waives all rights to bring any claim against UNV for any cause of action that may arise out of User’s use of the Platform. Under no circumstances shall UNV be liable for any loss, damage, liability or expense incurred or suffered that is claimed to have resulted from the use of the Platform including, without limitation, any inaccuracy, error, omission, interruption or delay, deletion, defect, alteration or use, with respect thereto, infection by virus or any other contamination of by anything which has destructive properties, communication line failure, regardless of cause. Under no circumstances, including but not limited to negligence, shall UNV be liable for any direct, indirect, incidental, special or consequential damages, even if UNV has been advised of the possibility of such damages. As a condition of use of the Platform, the User agrees to indemnify UNV and its affiliates from and against all actions, claims, losses, damages, liabilities and expenses (including reasonable attorneys’ fees) arising out of the User’s use of the Platform, including, without limitation, any claims alleging facts that if true would constitute a breach by the User of these Supplementary Terms.</p>

    <h2>4. Miscellaneous</h2>
    <p>UNV periodically adds, changes, improves, and/or updates the Platform without notice. UNV reserves its exclusive right in its sole discretion to (i) alter, limit or discontinue the Platform in any respect; (ii) modify these Supplementary Terms. UNV shall have no obligation to take the needs of any User into consideration in connection therewith nor to alert any User of such actions. UNV reserves the right to deny in its sole discretion any user access to the Platform or any portion thereof without notice and to suspend, revoke or stop providing User accounts if the User does not comply with the Supplementary Terms.</p>
    <p>Any dispute, controversy or claim arising out of or relating to these Supplementary Terms, or the breach, termination or invalidity hereof or to the use of the Platform the User or any content available therein and their particular terms of use shall be settled by arbitration in accordance with the UNCITRAL Arbitration Rules then in force. The arbitral tribunal shall be empowered to order: (i) the return or destruction of goods or any property, whether tangible or intangible, or of any information provided hereunder or (ii) that any other protective measures be taken with respect to the goods, services or any other property, whether tangible or intangible, or of any confidential information provided hereunder, as appropriate, all in accordance with the authority of the arbitral tribunal pursuant to Article 26 (‘the Interim Measures’) and Article 34 (‘the Form and Effect of the Award’) of the UNCITRAL Arbitration Rules. The arbitral tribunal shall have no authority to award punitive damages. UNV and the User shall be bound by any arbitration award rendered as a result of such arbitration as the final adjudication of any such dispute. To the extent that there exists more than one language version of these Terms of Use and there is any inconsistency between the versions, the English version shall always prevail.</p>

    <h2>5. Privileges and Immunities</h2>
    <p>Nothing herein shall constitute or be considered to be a limitation upon or a waiver of the privileges and immunities of UNV or the United Nations, which are specifically reserved.</p>

';

if(!empty($ref) && $EventData){
    $mform->display();
}

echo $OUTPUT->footer();
