<?php


defined('MOODLE_INTERNAL') || die;

function xmldb_coach_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2024011102) {
        $table = new xmldb_table('coach_event_type');
        
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('cm_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('uniquecode', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('payment_type', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('price', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('currency', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('firstsessionfree', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('duration', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('customreceipt', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('customreceipt_format', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('createdby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('modifiedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timedeleted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('deletedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
    
        upgrade_plugin_savepoint(true, 2024011102, 'mod', 'coach');
    }

    if ($oldversion < 2024011601) {

        $table = new xmldb_table('coach_calendar_sync');
        
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('cm_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('calendar_type', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('access_token', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('refresh_token', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('token_type', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('expires_in', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('scope', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
    
        upgrade_plugin_savepoint(true, 2024011601, 'mod', 'coach');
    }

    if ($oldversion < 2024012603) {

        // Define table coach_event to be created.
        $table = new xmldb_table('coach_event');

        // Adding fields to table coach_event.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('coachid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('event_type', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('date', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('hour', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('timezone', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('timestamp', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('event_duration_min', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('email', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('message', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table coach_event.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for coach_event.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Coach savepoint reached.
        upgrade_mod_savepoint(true, 2024012603, 'coach');
    }
   
    if ($oldversion < 2024020500) {

        $table = new xmldb_table('coach_event');
        $field = new xmldb_field('external_calendar_id', XMLDB_TYPE_TEXT, null, null, null, null, '', 'message');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2024020500, 'coach');
    }

    if ($oldversion < 2024020503) {

        $table = new xmldb_table('coach_event_type');
        $field = new xmldb_field('disabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0, 'firstsessionfree');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2024020503, 'mod', 'coach');
        // Coach savepoint reached.
    }

    if ($oldversion < 2024020504) {
        global $DB;

        $table = new xmldb_table('coach_event_type');
        $field = new xmldb_field('currency', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'price');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        foreach($DB->get_records_sql('SELECT id FROM {coach_event_type}') as $Type){
            $record = new stdClass();
            $record->id = $Type->id;
            $record->currency = 'CAD';
            $DB->update_record('coach_event_type', $record);
        }

        upgrade_plugin_savepoint(true, 2024020504, 'mod', 'coach');
    }

    if ($oldversion < 2024032000) {

        $table = new xmldb_table('coach_event_type');
        $field = new xmldb_field('disabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0, 'firstsessionfree');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2024032000, 'mod', 'coach');
        // Coach savepoint reached.
    }

    if ($oldversion < 2024041500) {

        // Define table coach_availability to be created.
        $table = new xmldb_table('coach_availability');

        // Adding fields to table coach_availability.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('teacher', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('week_day', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('start_time', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('end_time', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table coach_availability.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for coach_availability.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Coach savepoint reached.
        upgrade_mod_savepoint(true, 2024041500, 'coach');
    }

    if ($oldversion < 2024041501) {

        // Define table coach_specific_date to be created.
        $table = new xmldb_table('coach_specific_date');

        // Adding fields to table coach_specific_date.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('teacher', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('date', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('available', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('starttime', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('endtime', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        // Adding keys to table coach_specific_date.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for coach_specific_date.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Coach savepoint reached.
        upgrade_mod_savepoint(true, 2024041501, 'coach');
    }

    if ($oldversion < 2024042200) {

        $table = new xmldb_table('coach_event_type');
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'customreceipt_format');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2024042200, 'mod', 'coach');
        // Coach savepoint reached.
    }

    if ($oldversion < 2024042201) {

        // Define table coach_consent to be created.
        $table = new xmldb_table('coach_consent');

        // Adding fields to table coach_consent.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('eventid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('answer', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table coach_consent.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for coach_consent.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Coach savepoint reached.
        upgrade_mod_savepoint(true, 2024042201, 'coach');
    }

    if ($oldversion < 2024060709) {

        $table = new xmldb_table('coach_event');
        $field = new xmldb_field('tos_accepted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'coachid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2024060709, 'coach');
    }


    return true;
}
