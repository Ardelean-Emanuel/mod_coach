<?php

$Lastindex = optional_param('Lastindex', 0, PARAM_INT);
$NextIndex = $Lastindex+1;
try {
    $content = '
    <div class="specificdateparent" data-id="'.$NextIndex.'">
        <div class="form-group row fitem">
            <div class="col-md-3 col-form-label d-flex pb-0 pr-md-0"></div>
            <div class="col-md-8 form-inline align-items-start felement" data-fieldtype="group">
                <fieldset class="w-100 m-0 p-0 border-0">
                    <div class="d-flex flex-wrap align-items-center">
                        <div class="form-group fitem mr-5">
                            <label class="mr-2">Date</label>
                            <input type="date" id="date" class="datevalue form-control" data-id="'.$NextIndex.'" name="date" value="'.date('Y-m-d').'" min="1900-01-01" max="2050-12-31"/>
                        </div>
                        <div class="form-group fitem mr-5">
                            <input type="radio" id="available" name="availability'.$NextIndex.'" checked value="1">
                            <label for="available">Available</label><br>
                            <div class="mr-3"></div>
                            <input type="radio" id="unavailable" name="availability'.$NextIndex.'" value="0">
                            <label for="unavailable">Not available</label><br>
                        </div>
                        <div class="form-group fitem mr-5">
                            <div class="form-inline felement" data-fieldtype="select">
                                <span class="mr-1">From</span>
                                <select class="from_hour custom-select" data-id="'.$NextIndex.'">';
                                    for ($i=0; $i<24; $i++){
                                        $value = '';
                                        if($i < 10){
                                            $value .='0';
                                        }
                                        $value .= (string)$i;
                                        $content .='<option value="'.$value.'">'.$value.'</option>';
                                    }
                                $content.='
                                </select>:
                                <select class="from_minutes custom-select" data-id="'.$NextIndex.'">';
                                    for ($i=0; $i<60; $i++){
                                        $value = '';
                                        if($i < 10){
                                            $value .='0';
                                        }
                                        $value .= $i;
                                        $content .='<option value="'.$value.'">'.$value.'</option>';
                                    }
                                $content.='
                                </select>
                                <div class="mr-3"></div>
                                <span class="mr-1">To</span>
                                <select class="to_hour custom-select" data-id="'.$NextIndex.'">';
                                    for ($i=0; $i<24; $i++){
                                        $value = '';
                                        if($i < 10){
                                            $value .='0';
                                        }
                                        $value .= $i;
                                        $content .='<option value="'.$value.'">'.$value.'</option>';
                                    }
                                $content.='
                                </select>:
                                <select class="to_minutes custom-select" data-id="'.$NextIndex.'">';
                                    for ($i=0; $i<60; $i++){
                                        $value = '';
                                        if($i < 10){
                                            $value .='0';
                                        }
                                        $value .= $i;
                                        $content .='<option value="'.$value.'">'.$value.'</option>';
                                    }
                                $content.='
                                </select>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
            <div class="col-md-1 form-inline align-items-start felement">
                <a href="#!" class="removeitem mt-2" data-id="'.$NextIndex.'">Remove</a>
            </div>
        </div>
    </div>';
   
    
    $result['status'] = 'ok';
    $result['content'] = $content;
   
} catch (Exception $ex) {
    $result['status'] = 'error';
    $result['error'] = $ex->getMessage();
}