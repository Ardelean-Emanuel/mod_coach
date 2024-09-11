<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/coach/locallib.php');
require_once($CFG->libdir.'/filelib.php');

class mod_coach_availability_form extends moodleform {
    function definition() {
        global $CFG, $DB, $USER, $specificDates;

        $mform = $this->_form;
        $defaultData = $this->_customdata;

        $choices = core_date::get_list_of_timezones($USER->timezone, true);
        $mform->addElement('select', 'timezone', 'Your current time zone', $choices);
        $mform->setType('timezone', PARAM_TEXT);
        $mform->setDefault('timezone', $USER->timezone);

        $mform->addElement('header', 'weeklyhours', get_string('weeklyhours', 'mod_coach'));
        
        $days = array('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat');


        foreach ($days as $day) {
            $dayLabel = ucfirst($day);
            
            $mform->setDefault("{$day}_enabled", 0);

            $mform->setType("{$day}_from", PARAM_TEXT);
            $mform->setDefault("{$day}_from", '09:00');

            $mform->setType("{$day}_to", PARAM_TEXT);
            $mform->setDefault("{$day}_to", '17:00');

            $mform->addGroup(
                array(
                    $mform->createElement('static', "{$day}_label", $dayLabel),
                    $mform->createElement('advcheckbox', "{$day}_enabled", '', null, null, array(0, 1)),
                    $mform->createElement('text', "{$day}_from", ''),
                    $mform->createElement('text', "{$day}_to", ''),
                ),
                "{$day}availabilitygroup",
                $dayLabel,
                array(' '),
                false
            );
            
            $mform->hideIf("{$day}from", "{$day}_enabled", 'notchecked');
            $mform->hideIf("{$day}to", "{$day}_enabled", 'notchecked');
        }


       //date-specific
       $mform->addElement('header', 'datespecific', get_string('datespecific', 'mod_coach'));
       $html = '';
       if(!empty($specificDates)) {
           foreach ($specificDates as $specificDate) {
               if(date('Y-m-d') > date('Y-m-d', $specificDate->date)){
                   $DB->execute('DELETE FROM {coach_specific_date} WHERE id=?', array($specificDate->id));
                   continue;
               }
               $html .= '
               <div class="specificdateparent" data-id="'.$specificDate->id.'">
                   <div class="form-group row fitem">
                       <div class="col-md-3 col-form-label d-flex pb-0 pr-md-0"></div>
                       <div class="col-md-8 form-inline align-items-start felement" data-fieldtype="group">
                           <fieldset class="w-100 m-0 p-0 border-0">
                               <div class="d-flex flex-wrap align-items-center">
                                   <div class="form-group fitem mr-5">
                                       <label class="mr-2">Date</label>
                                       <input type="date" id="date" class="datevalue form-control" data-id="'.$specificDate->id.'" name="date" value="'.date('Y-m-d', $specificDate->date).'" min="1900-01-01" max="2050-12-31"/>
                                   </div>
                                   <div class="form-group fitem mr-5">
                                       <input type="radio" id="available" name="availability'.$specificDate->id.'" '.($specificDate->available == 1 ? 'checked' : '').' value="1">
                                       <label for="available">Available</label><br>
                                       <div class="mr-3"></div>
                                       <input type="radio" id="unavailable" name="availability'.$specificDate->id.'" '.($specificDate->available == 0 ? 'checked' : '').' value="0">
                                       <label for="unavailable">Not available</label><br>
                                   </div>
                                   <div class="form-group fitem mr-5">
                                       <div class="form-inline felement" data-fieldtype="select">
                                           <span class="mr-1">From</span>
                                           <select class="from_hour custom-select" data-id="'.$specificDate->id.'">';
                                               for ($i=0; $i<24; $i++){
                                                   $value = '';
                                                   if($i < 10){
                                                       $value .='0';
                                                   }
                                                   $value .= (string)$i;
                                                   $selected = substr($specificDate->starttime, 0, 2) == $value ? 'selected' : '';
                                                   $html .='<option value="'.$value.'" '.$selected.'>'.$value.'</option>';
                                               }
                                           $html.='
                                           </select>:
                                           <select class="from_minutes custom-select" data-id="'.$specificDate->id.'">';
                                               for ($i=0; $i<60; $i++){
                                                   $value = '';
                                                   if($i < 10){
                                                       $value .='0';
                                                   }
                                                   $value .= $i;
                                                   $selected = substr($specificDate->starttime, 3, 2) == $value ? 'selected' : '';
                                                   $html .='<option value="'.$value.'" '.$selected.'>'.$value.'</option>';
                                               }
                                           $html.='
                                           </select>
                                           <div class="mr-3"></div>
                                           <span class="mr-1">To</span>
                                           <select class="to_hour custom-select" data-id="'.$specificDate->id.'">';
                                               for ($i=0; $i<24; $i++){
                                                   $value = '';
                                                   if($i < 10){
                                                       $value .='0';
                                                   }
                                                   $value .= $i;
                                                   $selected = substr($specificDate->endtime, 0, 2) == $value ? 'selected' : '';
                                                   $html .='<option value="'.$value.'" '.$selected.'>'.$value.'</option>';
                                               }
                                           $html.='
                                           </select>:
                                           <select class="to_minutes custom-select" data-id="'.$specificDate->id.'">';
                                               for ($i=0; $i<60; $i++){
                                                   $value = '';
                                                   if($i < 10){
                                                       $value .='0';
                                                   }
                                                   $value .= $i;
                                                   $selected = substr($specificDate->endtime, 3, 2) == $value ? 'selected' : '';
                                                   $html .='<option value="'.$value.'" '.$selected.'>'.$value.'</option>';
                                               }
                                           $html.='
                                           </select>
                                       </div>
                                   </div>
                               </div>
                           </fieldset>
                       </div>
                       <div class="col-md-1 form-inline align-items-start felement">
                           <a href="#!" class="removeitem mt-2" data-id="'.$specificDate->id.'">Remove</a>
                       </div>
                   </div>
               </div>';
           }
           $html .='
           <div id="fitem_id_addmorecriterias" class="form-group row fitem mt-5">
               <div class="col-md-3 col-form-label d-flex pb-0 pr-md-0">
                   <label id="id_addmorecriterias_label" class="d-inline word-break"></label>
               </div>
               <div class="col-md-9 form-inline align-items-start felement" data-fieldtype="text">
                   <a href="#!" class="btn btn-outline-info addmorefields"><i class="fa-solid fa-plus"></i> Add</a>
               </div>
           </div>';
       } else {
           $html .='
           <div id="fitem_id_addmorecriterias" class="form-group row fitem mt-5">
               <div class="col-md-3 col-form-label d-flex pb-0 pr-md-0">
                   <label id="id_addmorecriterias_label" class="d-inline word-break"></label>
               </div>
               <div class="col-md-9 form-inline align-items-start felement" data-fieldtype="text">
                   <a href="#!" class="btn btn-outline-info addmorefields"><i class="fa-solid fa-plus"></i> Add</a>
               </div>
           </div>';
       }
       

       $mform->addElement('html', $html); 
       

        $mform->registerNoSubmitButton('addfields');

        $this->add_action_buttons();
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $days = array('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat');

        foreach ($days as $day) {
            if (!empty($data["{$day}_enabled"])) {
                if (!preg_match('/^(2[0-3]|[01][0-9]):([0-5][0-9])$/', $data["{$day}_from"])) {
                    $errors["{$day}_from"] = get_string('invalidtimeformat', 'mod_coach');
                }
                if (!preg_match('/^(2[0-3]|[01][0-9]):([0-5][0-9])$/', $data["{$day}_to"])) {
                    $errors["{$day}_to"] = get_string('invalidtimeformat', 'mod_coach');
                }
            }
        }

        for ($i=0; $i < 10 ; $i++) { 
            if (!empty($data["starttime_{$i}"])) {
                if (!preg_match('/^(2[0-3]|[01][0-9]):([0-5][0-9])$/', $data["starttime_{$i}"])) {
                    $errors["starttime_$i}"] = get_string('invalidtimeformat', 'mod_coach');
                }
                if (!preg_match('/^(2[0-3]|[01][0-9]):([0-5][0-9])$/', $data["endtime_{$i}"])) {
                    $errors["endtime_$i}"] = get_string('invalidtimeformat', 'mod_coach');
                }
            }
        }

        return $errors;
    }
}

