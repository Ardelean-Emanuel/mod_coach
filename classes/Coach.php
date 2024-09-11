<?php

namespace mod_coach;

class Coach {

    private $id;
    private $userid;
    private $cm_id;
    private $timecreated;
    private $createdby;
    private $timemodified;
    private $modifiedby;
    private $timedeleted;
    private $deletedby;


    public function __construct($ObjectOrId) {
        $this->reloadData($ObjectOrId);
    }

    public function reloadData($ObjectOrId) {
        global $DB;

        if($ObjectOrId && is_numeric($ObjectOrId)) {
            $Coach = $DB->get_record_sql('SELECT * FROM {coach_users} WHERE id=?', array($ObjectOrId), MUST_EXIST);
        }
        else if($ObjectOrId && is_object($ObjectOrId)) {
            $Coach = clone($ObjectOrId);
        } else {
            throw new \Exception(get_string('objectoridinvalid', 'local_shop'));
        }
        
        $this->id = (int)$Coach->id;
        $this->userid = (string)$Coach->userid;
        $this->cm_id = (string)$Coach->cm_id;
        $this->timecreated = (int)$Coach->timecreated;
        $this->createdby = (int)$Coach->createdby;
        $this->timemodified = (int)$Coach->timemodified;
        $this->modifiedby = (int)$Coach->modifiedby;
        $this->timedeleted = (int)$Coach->timedeleted;
        $this->deletedby = (int)$Coach->deletedby;
    }

    public function getData() {
        return (object)get_object_vars($this);
    }

    public function __get($name) {
        global $DB;

        if(isset($this->{$name})) {
            return $this->{$name};
        }
        else {
            throw new \Exception(get_string('invalidpropertyname', 'local_shop', $name));
        }
    }

    public static function addEventType($data){
        global $DB, $CFG, $USER;

        $context = \context_system::instance();
        // var_dump($data); die;
    
        $transaction = $DB->start_delegated_transaction();
        try {
            $record = new \stdClass();
            $record->cm_id = $data->cmid;
            $record->name = $data->name;
            $record->uniquecode = $data->uniquekey;
            $record->payment_type = $data->payment_type;
            $record->price = 0;
            $record->currency = 'USD';
            $record->firstsessionfree = (isset($data->firstsessionfree) ? $data->firstsessionfree : 0);
            $record->disabled = (isset($data->disabled) ? $data->disabled : 0);
            // $record->payee = $data->payee;
            $record->duration = $data->duration;
            $record->customreceipt =  '';
            $record->customreceipt_format = '';
            $record->timecreated = time();
            $record->createdby = $USER->id;
            $record->timemodified = 0;
            $record->modifiedby = 0;
            $record->timedeleted = 0;
            $record->deletedby = 0;
            
            $record->id = $DB->insert_record('coach_event_type', $record);

            if(!empty($data->customreceipt['itemid'])){
                $UpdateRecord = new \stdClass();
                $UpdateRecord->id = $record->id;
                $draftitemid = $data->customreceipt['itemid'];
                $UpdateRecord->customreceipt = file_save_draft_area_files($draftitemid, $context->id, 'mod_coach', 'custom_receipt', $record->id, ['subdirs' => 0, 'maxbytes' => 10485760], $record->customreceipt);
                $DB->update_record('coach_event_type', $UpdateRecord);
            }

            $transaction->allow_commit();
            return $record;
        } catch (\Exception $e) {
            $transaction->rollback($e);
            throw $e;
        }
    }

    public static function getRandomString($length = 15) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';
    
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[mt_rand(0, strlen($characters) - 1)];
        }
    
        return $string;
    }

    public static function editEventType($data){
        global $DB, $CFG, $USER;

        $context = \context_system::instance();
        // var_dump($data); die;
    
        $transaction = $DB->start_delegated_transaction();
        try {
            $record = new \stdClass();
            $record->id = $data->id;
            $record->name = $data->name;
            $record->uniquecode = $data->uniquekey;
            $record->payment_type = $data->payment_type;
            $record->price = 0;
            $record->currency = 'USD';
            $record->firstsessionfree = (isset($data->firstsessionfree) ? $data->firstsessionfree : 0);
            $record->disabled = (isset($data->disabled) ? $data->disabled : 0);
            // $record->payee = $data->payee;
            $record->duration = $data->duration;
            $record->customreceipt =  '';
            $record->customreceipt_format = '';
            $record->timemodified = time();
            $record->modifiedby = $USER->id;
            
            $DB->update_record('coach_event_type', $record);

            $fs = get_file_storage();

            foreach($fs->get_area_files($context->id, 'mod_coach', 'custom_receipt', $record->id) as $documentFile) {
                $documentFile->delete();
            }

            if(!empty($data->customreceipt['itemid'])){
                $UpdateRecord = new \stdClass();
                $UpdateRecord->id = $record->id;
                $draftitemid = $data->customreceipt['itemid'];
                $UpdateRecord->customreceipt = file_save_draft_area_files($draftitemid, $context->id, 'mod_coach', 'custom_receipt', $record->id, ['subdirs' => 0, 'maxbytes' => 10485760], $record->customreceipt);
                $DB->update_record('coach_event_type', $UpdateRecord);
            }

            $transaction->allow_commit();
            return $record;
        } catch (\Exception $e) {
            $transaction->rollback($e);
            throw $e;
        }
    }


    public static function getEventTypeCustomReceipt($customreceipt, $id) {
        return file_rewrite_pluginfile_urls($customreceipt, 'pluginfile.php', 1, 'mod_coach', 'custom_receipt', $id);
    }

    public static function getEventType($typeId){
        global $DB;
        return $DB->get_record_sql('SELECT * FROM {coach_event_type} WHERE id=?', array($typeId)) ?? '';
    }
}
