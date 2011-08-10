<?php

/**
 * Training Diary Module Functions
 *
 * @author  Simon Coggins <simonc@catalyst.net.nz>
 * @package mod/trdiary
 **/

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $trdiary An object from the form in mod_form.php
 * @return int The id of the newly inserted trdiary record
 */
function trdiary_add_instance($trdiary) {

    global $COURSE,$CFG;

    require_once($CFG->dirroot.'/mod/trdiary/locallib.php');
    require_once($CFG->dirroot.'/mod/trdiary/mod_form.php');

    $returnurl = $CFG->wwwroot.'/course/view.php?id='.$COURSE->id;
    
    $trdiary->timecreated = time();

    // deal with empty or zero log frequency
    if(trim($trdiary->logfreq)=='' || $trdiary->logfreq == 0) {
        $trdiary->logfreq = null;
    }

    // link training diary to the appropriate threesixty instance
    $threesixtyid = trdiary_get_threesixty_instance($COURSE->id);
    if ($threesixtyid === false) {
        print_error('no360','trdiary',$returnurl);
    }
    $trdiary->threesixtyid = $threesixtyid;

    begin_sql();

    $activityid = insert_record('trdiary', $trdiary);
    if($activityid === false) {
        rollback_sql();
        print_error('error:cannotcreateinstance', 'trdiary', $returnurl);
        error_log('id'.$COURSE->id);
    }

    // create new fields for PDP
    for ($i = 0; $i < $trdiary->addfields_repeats; $i++) {
        $addfieldname = '';
        if (!empty($trdiary->addfieldname[$i])) {
            $addfieldname = $trdiary->addfieldname[$i];
        }
        $addfielddelete = false;
        if (!empty($trdiary->addfielddelete[$i])) {
            $addfielddelete = (1 == $trdiary->addfielddelete[$i]);
        }
        if (!$addfielddelete AND !empty($addfieldname)) {
            // insert each field 
            $todb = new object();
            $todb->trdiaryid = $activityid;
            $todb->name = $addfieldname;
            if(!insert_record('trdiary_pdp_field',$todb, false)) {
                rollback_sql();
                print_error('error:cannotcreateaddfields', 'trdiary', $returnurl);
            }
        }
        else {
            // skip new additional fields marked as delete or with an empty name
        } 
    }

    // Create new fields for reflective log
    for ($i = 0; $i < $trdiary->reflogfields_repeats; $i++) {
        $reflogfieldname = '';
        if (!empty($trdiary->reflogfieldname[$i])) {
            $reflogfieldname = $trdiary->reflogfieldname[$i];
        }
        $reflogfielddelete = false;
        if (!empty($trdiary->reflogfielddelete[$i])) {
            $reflogfielddelete = (1 == $trdiary->reflogfielddelete[$i]);
        }
        if (!$reflogfielddelete AND !empty($reflogfieldname)) {
            // insert each field 
            $todb = new object();
            $todb->trdiaryid = $activityid;
            $todb->name = $reflogfieldname;
            if(!insert_record('trdiary_reflog_field',$todb, false)) {
                rollback_sql();
                print_error('error:cannotcreatereflogfields', 'trdiary', $returnurl);
            }
        }
        else {
            // skip new additional fields marked as delete or with an empty name
        } 
    }

    commit_sql();

    return $activityid;
}


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $trdiary An object from the form in mod_form.php
 * @return boolean Success/Fail
 */
function trdiary_update_instance($trdiary) {
    global $CFG,$COURSE;
    $returnurl = $CFG->wwwroot.'/course/view.php?id='.$COURSE->id;
 
    $trdiary->timemodified = time();
    $trdiary->id = $trdiary->instance;

    // deal with empty or zero log frequency
    if(trim($trdiary->logfreq)=='' || $trdiary->logfreq == 0) {
        $trdiary->logfreq = null;
    }

    begin_sql();

    $update = update_record('trdiary', $trdiary);

    if(!$update) {
        rollback_sql();
        print_error('error:cannotupdateinstance', 'trdiary', $returnurl);
        error_log('id'.$COURSE->id);
    }

    // insert/update/delete PDP fields
    for ($i = 0; $i < $trdiary->addfields_repeats; $i++) {
        $addfieldname = '';
        if (!empty($trdiary->addfieldname[$i])) {
            $addfieldname = $trdiary->addfieldname[$i];
        }
        $addfieldid = '';
        if (!empty($trdiary->addfieldid[$i])) {
            $addfieldid = $trdiary->addfieldid[$i];
        }
        $addfielddelete = false;
        if (!empty($trdiary->addfielddelete[$i])) {
            $addfielddelete = (1 == $trdiary->addfielddelete[$i]);
        }
        if (!$addfielddelete AND !empty($addfieldname)) {
            // update each field 
            $todb = new object();
            $todb->id = $addfieldid;
            $todb->trdiaryid = $trdiary->id;
            $todb->name = $addfieldname;
            if($addfieldid == 0) {
                // add
                if(!insert_record('trdiary_pdp_field',$todb, false)) {
                    rollback_sql();
                    print_error('error:cannotinsertaddfields', 'trdiary', $returnurl);
                }
            } else {
                if(!update_record('trdiary_pdp_field',$todb, false)) {
                    rollback_sql();
                    print_error('error:cannotupdateaddfields', 'trdiary', $returnurl);
                }
            }
        }
        elseif ($addfielddelete AND !empty($addfieldid)) {
            // delete this record
            if(!delete_records('trdiary_pdp_field','trdiaryid', $trdiary->id, 'id', $addfieldid)) {
                rollback_sql();
                print_error('error:cannotdeleteaddfields', 'trdiary', $returnurl);
            }
            else {
                // also delete user values for this field
                if(!delete_records('trdiary_pdp_value','fieldid', $addfieldid)) {
                    rollback_sql();
                    print_error('error:cannotdeletefieldvalues', 'trdiary', $returnurl);
                }
            }
        } else {
            // skip new additional fields marked as delete or with an empty name
        } 
    }

    // insert/update/delete reflective log fields
    for ($i = 0; $i < $trdiary->reflogfields_repeats; $i++) {
        $reflogfieldname = '';
        if (!empty($trdiary->reflogfieldname[$i])) {
            $reflogfieldname = $trdiary->reflogfieldname[$i];
        }
        $reflogfieldid = '';
        if (!empty($trdiary->reflogfieldid[$i])) {
            $reflogfieldid = $trdiary->reflogfieldid[$i];
        }
        $reflogfielddelete = false;
        if (!empty($trdiary->reflogfielddelete[$i])) {
            $reflogfielddelete = (1 == $trdiary->reflogfielddelete[$i]);
        }
        if (!$reflogfielddelete AND !empty($reflogfieldname)) {
            // update each field 
            $todb = new object();
            $todb->id = $reflogfieldid;
            $todb->trdiaryid = $trdiary->id;
            $todb->name = $reflogfieldname;
            if($reflogfieldid == 0) {
                // add
                if(!insert_record('trdiary_reflog_field',$todb, false)) {
                    rollback_sql();
                    print_error('error:cannotinsertreflogfields', 'trdiary', $returnurl);
                }
            } else {
                if(!update_record('trdiary_reflog_field',$todb, false)) {
                    rollback_sql();
                    print_error('error:cannotupdatereflogfields', 'trdiary', $returnurl);
                }
            }
        }
        elseif ($reflogfielddelete AND !empty($reflogfieldid)) {
            // delete this record
            if(!delete_records('trdiary_reflog_field','trdiaryid', $trdiary->id, 'id', 
                               $reflogfieldid)) {
                rollback_sql();
                print_error('error:cannotdeletereflogfields', 'trdiary', $returnurl);
            }
            else {
                // also delete user values for this field
                if(!delete_records('trdiary_reflog_value','fieldid', $reflogfieldid)) {
                    rollback_sql();
                    print_error('error:cannotdeletereflogfieldvalues', 'trdiary', $returnurl);
                }
            }
        } else {
            // skip new additional fields marked as delete or with an empty name
        } 
    }

    commit_sql();

    return $update;
}


/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function trdiary_delete_instance($id) {

    if (! $trdiary = get_record('trdiary', 'id', $id)) {
        return false;
    }

    $result = true;

    // delete any user PDP skills 
    if (! delete_records('trdiary_pdp_skill', 'trdiaryid', $trdiary->id)) {
        error_log('Could not delete pdp skills with instance ID of '.$trdiary->id);
    }

    // delete additional fields and values
    if (! cleanup_fields('pdp', $trdiary->id)) {
        error_log('Could not delete all additional PDP fields.');
    }

    // delete reflective log entries
    if (! delete_records('trdiary_reflog_entry', 'trdiaryid', $trdiary->id)) {
        error_log('Could not delete reflog entries with instance ID of '.$trdiary->id);
    }

    // delete reflective log fields and values
    if (! cleanup_fields('reflog', $trdiary->id)) {
        error_log('Could not delete all reflog entries.');
    }

    // delete instance record
    if (! delete_records('trdiary', 'id', $trdiary->id)) {
        $result = false;
    }

    return $result;
}

/**
 * Removes field and value entries from the database when an activity is
 * deleted.
 *
 * @param string $type Set to 'pdp' or 'reflog' to specify which records 
 *                     to delete
 * @param int $activityid ID of activity being deleted
 * @return boolean True if no errors occurred during the delete
**/
function cleanup_fields($type, $activityid) {
    global $CFG;

    // confirm we are deleting from a valid table
    if($type == 'pdp' || $type == 'reflog') {
        $fieldtable = 'trdiary_'.$type.'_field';
        $valuetable = 'trdiary_'.$type.'_value';

        // get field entries for current activity instance
        $fields = get_records($fieldtable,'trdiaryid',$activityid,'','id');
        if ($fields !== false) {
            // delete all fields belonging to this instance
            if (! delete_records($fieldtable, 'trdiaryid', $activityid)) {
                return false;
            }
            // build where clause to delete all values for fields 
            // that have just been deleted
            $fieldids = Array();
            foreach ($fields AS $field) {
                $fieldids[] = "fieldid='{$field->id}'";
            }
            $select = implode (' or ', $fieldids);
            // don't check for errors as fields do not necessarily have 
            // any values
            delete_records_select($valuetable, $select);
        }
        return true;

    } else {
        return false;
    }
}


