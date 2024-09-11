<?php
namespace mod_coach;

require('../../config.php');

$PAGE->set_context(\context_system::instance());
$PAGE->requires->css('/mod/coach/style/welcome.css');
$PAGE->set_url('/mod/coach/welcome.php');
$PAGE->set_title('UN Volunteer Coaching Initiative');

echo $OUTPUT->header();

echo '<div class="container">
    <header class="header">
        <img src="pix/image.jpg" alt="UN Volunteers" class="header-img img-fluid">
    </header>
    <main>
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="welcome-tab" data-toggle="tab" href="#welcome" role="tab" aria-controls="welcome" aria-selected="true">Welcome</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="benefits-tab" data-toggle="tab" href="#benefits" role="tab" aria-controls="benefits" aria-selected="false">Benefits</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="eligibility-tab" data-toggle="tab" href="#eligibility" role="tab" aria-controls="eligibility" aria-selected="false">Eligibility</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="steps-tab" data-toggle="tab" href="#steps" role="tab" aria-controls="steps" aria-selected="false">Steps</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="support-tab" data-toggle="tab" href="#support" role="tab" aria-controls="support" aria-selected="false">Support</a>
            </li>
        </ul>
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active p-2" id="welcome" role="tabpanel" aria-labelledby="welcome-tab">
                <section class="welcome-section">
                    <h1>Discover Your Potential with Personal Coaching</h1>
                    <p>Are you looking to grow both personally and professionally? The UN Volunteer Coaching initiative, an inter-agency collaboration, offers a unique opportunity to engage in one-on-one sessions with certified coaches from across the UN system and beyond. In a confidential and secure setting, you will gain valuable insights, overcome challenges, enhance skills, achieve career goals, and much more.</p>
 
<p>Explore the tabs above to learn more about the initiative. To sign up for a coaching session, simply click on "Choose your coach" and follow our <a href="https://learning.unv.org/pluginfile.php/455591/mod_data/content/3850/Coaching%20User%20Guide%20%20.pdf">step-by-step guide.</a></p>
                    <a href="'.$CFG->wwwroot.'/mod/coach/coach.php" class="btn btn-primary">Choose your coach</a>
                </section>
            </div>
            <div class="tab-pane fade p-2" id="benefits" role="tabpanel" aria-labelledby="benefits-tab">
                <section class="benefits-section">
                    <h2>Why Coaching?</h2>
                    <ul>
                        <li>Get insights on your strengths and areas for improvement.</li>
                        <li>Find effective strategies to overcome challenges.</li>
                        <li>Accelerate your personal and professional development.</li>
                        <li>Develop as an individual and a professional.</li>
                        <li>Build resilience and improve well-being.</li>
                    </ul>
                    <h2>What Can Coaching Help You With?</h2>
                    <ul>
                        <li>Take steps forward in your career management.</li>
                        <li>Improve your interpersonal communication skills.</li>
                        <li>Foster effective relationships with colleagues.</li>
                        <li>Enhance your ability to manage yourself and your work.</li>
                        <li>Handle difficult situations and conversations with ease.</li>
                    </ul>
                </section>
            </div>
            <div class="tab-pane fade p-2" id="eligibility" role="tabpanel" aria-labelledby="eligibility-tab">
                <section class="eligibility-section">
                    <h2>Eligibility</h2>
                    <ul>
                        <li>All serving UN Volunteers (except online volunteers) may apply for maximum 3 coaching sessions for assignments of 12 months.</li>
                        <li>You must complete all 3 coaching sessions within 3 months of application.</li>
                        <li>Note that unjustified no-shows will result in you being barred from future coaching opportunities.</li>
                    </ul>
                </section>
            </div>
            <div class="tab-pane fade p-2" id="steps" role="tabpanel" aria-labelledby="steps-tab">
                <section class="steps-section">
                    <h2>How to Sign Up</h2>
                    <ol>
                        <li>Apply Online: Use the <a href="https://learning.unv.org/pluginfile.php/455591/mod_data/content/3850/Coaching%20User%20Guide%20%20.pdf">step-by-step guide</a> on eCampus to apply for coaching.</li>
                        <li>Schedule Your Session: Agree on a date and time for your one-hour online session.</li>
                        <li>Prepare Your Goals: Clearly define your goals to make the most of your coaching session.</li>
                        <li>Attend Your Session: Respect the time of your coaches and attend scheduled sessions.</li>
                        <li>Notify if Unable to Attend: Inform your coach at least 48 hours in advance if you cannot attend. Failure to do so will result in being barred from future sessions.</li>
                        <li>Create an Action Plan: Work with your coach to develop a personalized action plan.</li>
                        <li>Apply New Skills: Implement the skills you’ve acquired and seek feedback.</li>
                    </ol>
                    <a href="'.$CFG->wwwroot.'/mod/coach/coach.php" class="btn btn-primary">Choose your coach</a>
                </section>
            </div>
            <div class="tab-pane fade p-2" id="support" role="tabpanel" aria-labelledby="support-tab">
                <section class="support-section">
                    <h2>Help Desk Support</h2>
                    <ul>
                        <li>Contact your coach directly for your development-related queries.</li>
                        <li>For information and feedback on UNV Coaching services, contact us through the <a href="https://learning.unv.org/mod/page/view.php?id=5"> e-Campus contact form. </a> Select "Coaching" as the subject, and include your official UN email address and roster number.</li>
                    </ul>
                </section>
            </div>
        </div>
    </main>
</div>
';
echo $OUTPUT->footer();

