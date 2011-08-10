<?php 
    //This php script contains all the stuff to backup/restore
    //training diary mods

    //This is the "graphical" structure of the trdiary mod:


    //            trdiary_pdp_field ----------- trdiary ----------------------- trdiary_reflog_field
    //       (CL, pk->id, fk->trdiaryid)     (CL,pk->id)                    (CL, pk->id, fk->trdiaryid)
    //                   |                        |                                       |
    //                   |                        |                                       |
    //                   |                        |                                       |
    //            trdiary_pdp_value               |---- trdiary_reflog_entry ---- trdiary_reflog_value
    //       (UL, pk->id, fk->fieldid)            |         (UL, pk->id,             (UL, pk->id,
    //                                            |         fk->trdiaryid)      fk->fieldid, fk->entryid)
    //                                            |
    //                                            |
    //                                    trdiary_pdp_skill
    //                                (UL, pk->id, fk->trdiaryid)
    //
    //
    // Meaning: pk->primary key field of the table
    //          fk->foreign key to link with parent
    //          nt->nested field (recursive data)
    //          CL->course level info
    //          UL->user level info
    //          files->table may have files)
    //
    //
    // NOTE: This module also requires tables from the threesixty module
    //       In particular trdiary_pdp_skill is linked to threesixty_skill by fk->skillid
    //
    //----------------------------------------------------------------------------------

    //This function executes all the restore procedure about this mod
    function trdiary_restore_mods($mod,$restore) {

        global $CFG;

        $status = true;

        //Get record from backup_ids
        $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);

        if ($data) {
            //Now get completed xmlized object
            $info = $data->info;
            // if necessary, write to restorelog and adjust date/time fields
            if ($restore->course_startdateoffset) {
                restore_log_date_changes('Training Diary', $restore, $info['MOD']['#'], array('TIMECREATED','TIMEMODIFIED'));
            }

            //traverse_xmlize($info);                                                   //Debug
            //print_object ($GLOBALS['traverse_array']);                                //Debug
            //$GLOBALS['traverse_array']="";                                            //Debug

            //Now, build the training diary record structure
            $trdiary->course = $restore->course_id;
            $trdiary->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
            $trdiary->logfreq = backup_todb($info['MOD']['#']['LOGFREQ']['0']['#']);
            $trdiary->threesixtyid = backup_todb($info['MOD']['#']['THREESIXTYID']['0']['#']);
            $trdiary->timecreated = backup_todb($info['MOD']['#']['TIMECREATED']['0']['#']);
            $trdiary->timemodified = backup_todb($info['MOD']['#']['TIMEMODIFIED']['0']['#']);

            //We have to recode the threesixtyid field
            $threesixty = backup_getid($restore->backup_unique_code,"threesixty",$trdiary->threesixtyid);
            if ($threesixty) {
                $trdiary->threesixtyid = $threesixty->new_id;
            } else {
                error_log('Error restoring training diary. No threesixty module found. This may occur 
                    if someone tries to restore a zip file with a trdiary but no threesixty module in 
                    it, or if for some reason the threesixty module has a higher ID in the mdl_modules
                    table than the training diary.');
                // this branch occurs in the following situations:
                // - No three sixty module is being backed up from the same zip file
                // - The trdiary module has a lower ID than the threesixty module in the 
                //   mdl_modules table. This means that the trdiary is being backed up 
                //   first so can't refer to the threesixtyid or skillids that it needs.
                //   This can happen if the trdiary is installed *before* the threesixty
                //   module. To resolve, delete the trdiary 
                //   module completely, then visit notifications to recreate it. That should 
                //   put trdiary below threesixty in the mdl_modules table.
                $status = false;
                return $status;
            }

            //The structure is equal to the db, so insert the training diary
            $newid = insert_record('trdiary', $trdiary);

            //Do some output
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>" . get_string("modulename","trdiary") . " \""
                     . format_string(stripslashes($trdiary->name),true) . "\"</li>";
            }
            backup_flush(300);

            if ($newid) {
                // We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,$mod->modtype,
                             $mod->id, $newid);

                // Restore trdiary_pdp_field - trdiary_pdp_value restored from here
                $status = trdiary_pdp_field_restore_mods($mod->id, $newid, $info, $restore);
                // Restore trdiary_reflog_field only
                $status = trdiary_reflog_field_restore_mods($mod->id, $newid, $info, $restore);

                //Now check if want to restore user data and do it.
                if (restore_userdata_selected($restore,'trdiary',$mod->id)) {
                    // Restore PDP skills
                    $status = trdiary_pdp_skill_restore_mods($mod->id, $newid, $info, $restore);

                    // Restore Reflective Log entries - trdiary_reflog_value restored from here
                    $status = trdiary_reflog_entry_restore_mods($mod->id, $newid, $info, $restore);
                }

            } else {
                $status = false;
            }
        } else {
            $status = false;
        }

        return $status;
    }



    //This function restores the training diary pdp fields
    function trdiary_pdp_field_restore_mods($old_trdiary_id,$new_trdiary_id,$info,$restore) {

        global $CFG;

        $status = true;

        //Get the pdp_fields array
        if(isset($info['MOD']['#']['PDP_FIELDS']['0']['#']['PDP_FIELD'])) {
            $pdp_fields = $info['MOD']['#']['PDP_FIELDS']['0']['#']['PDP_FIELD'];
        } else {
            $pdp_fields = array();
        }

        //Iterate over pdp_fields
        for($i = 0; $i < sizeof($pdp_fields); $i++) {
            $pdp_field_info = $pdp_fields[$i];
            //traverse_xmlize($pdp_field_info);                                         //Debug
            //print_object ($GLOBALS['traverse_array']);                                //Debug
            //$GLOBALS['traverse_array']="";                                            //Debug

            //We'll need this later!!
            $oldid = backup_todb($pdp_field_info['#']['ID']['0']['#']);

            //Now, build the pdp field record structure
            $pdp_field = new object();
            $pdp_field->trdiaryid = $new_trdiary_id;
            $pdp_field->name = backup_todb($pdp_field_info['#']['NAME']['0']['#']);

            //The structure is equal to the db, so insert the pdp_fields
            $newid = insert_record("trdiary_pdp_field", $pdp_field);

            //Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }
            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"trdiary_pdp_field",$oldid,$newid);

                //If userinfo was selected, restore the values
                if (restore_userdata_selected($restore,'trdiary',$old_trdiary_id)) {
                    //Restore trdiary_pdp_value
                    $status = trdiary_pdp_value_restore_mods($oldid,$newid,$pdp_field_info,$restore);
                }

            } else {
                $status = false;
            }

        }

        return $status;
    }

    //This function restores the trdiary_pdp_value
    function trdiary_pdp_value_restore_mods($old_pdp_field_id, $new_pdp_field_id, $info, $restore) {

        global $CFG;

        $status = true;

        //Get the trdiary_pdp_value array
        if(isset($info['#']['PDP_VALUES']['0']['#']['PDP_VALUE'])) {
            $pdp_values = $info['#']['PDP_VALUES']['0']['#']['PDP_VALUE'];
        } else {
            $pdp_values = array();
        }

        //Iterate over trdiary_pdp_value
        for($i = 0; $i < sizeof($pdp_values); $i++) {
            $pdp_value_info = $pdp_values[$i];
            //traverse_xmlize($pdp_value_info);                                        //Debug
            //print_object ($GLOBALS['traverse_array']);                               //Debug
            //$GLOBALS['traverse_array']="";                                           //Debug

            //We'll need this later!!
            $oldid = backup_todb($pdp_value_info['#']['ID']['0']['#']);

            //Now, build the trdiary_pdp_value record structure
            $pdp_value->fieldid = $new_pdp_field_id;
            $pdp_value->userid = backup_todb($pdp_value_info['#']['USERID']['0']['#']);
            $pdp_value->value = backup_todb($pdp_value_info['#']['VALUE']['0']['#']);

            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code,"user",$pdp_value->userid);
            if ($user) {
                $pdp_value->userid = $user->new_id;
            }

            //The structure is equal to the db, so insert the values
            $newid = insert_record("trdiary_pdp_value",$pdp_value);

            //Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }
            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"trdiary_pdp_value",$oldid,$newid);
            } else {
                $status = false;
            }
        }

        return $status;
    }


    //This function restores the training diary reflog fields
    function trdiary_reflog_field_restore_mods($old_trdiary_id,$new_trdiary_id,$info,$restore) {

        global $CFG;

        $status = true;

        //Get the reflog_fields array
        if(isset($info['MOD']['#']['REFLOG_FIELDS']['0']['#']['REFLOG_FIELD'])) {
            $reflog_fields = $info['MOD']['#']['REFLOG_FIELDS']['0']['#']['REFLOG_FIELD'];
        } else {
            $reflog_fields = array();
        }

        //Iterate over reflog_fields
        for($i = 0; $i < sizeof($reflog_fields); $i++) {
            $reflog_field_info = $reflog_fields[$i];
            //traverse_xmlize($reflog_field_info);                                      //Debug
            //print_object ($GLOBALS['traverse_array']);                                //Debug
            //$GLOBALS['traverse_array']="";                                            //Debug

            //We'll need this later!!
            $oldid = backup_todb($reflog_field_info['#']['ID']['0']['#']);

            //Now, build the reflog field record structure
            $reflog_field = new object();
            $reflog_field->trdiaryid = $new_trdiary_id;
            $reflog_field->name = backup_todb($reflog_field_info['#']['NAME']['0']['#']);

            //The structure is equal to the db, so insert the reflog_fields
            $newid = insert_record("trdiary_reflog_field", $reflog_field);

            //Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }
            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"trdiary_reflog_field",$oldid,$newid);

                //If userinfo was selected, restore the values
                if (restore_userdata_selected($restore,'trdiary',$old_trdiary_id)) {
                    //Restore trdiary_reflog_entry
                    $status = trdiary_reflog_entry_restore_mods($oldid,$newid,$reflog_field_info,$restore);
                }

            } else {
                $status = false;
            }

        }

        return $status;
    }



    //This function restores the training diary pdp skills
    function trdiary_pdp_skill_restore_mods($old_trdiary_id,$new_trdiary_id,$info,$restore) {

        global $CFG;

        $status = true;

        //Get the pdp_skills array
        if(isset($info['MOD']['#']['PDP_SKILLS']['0']['#']['PDP_SKILL'])) {
            $pdp_skills = $info['MOD']['#']['PDP_SKILLS']['0']['#']['PDP_SKILL'];
        } else {
            $pdp_skills = array();
        }

        //Iterate over pdp_skills
        for($i = 0; $i < sizeof($pdp_skills); $i++) {
            $pdp_skill_info = $pdp_skills[$i];
            //traverse_xmlize($pdp_skill_info);                                         //Debug
            //print_object ($GLOBALS['traverse_array']);                                //Debug
            //$GLOBALS['traverse_array']="";                                            //Debug

            //We'll need this later!!
            $oldid = backup_todb($pdp_skill_info['#']['ID']['0']['#']);

            //Now, build the pdp skill record structure
            $pdp_skill = new object();
            $pdp_skill->trdiaryid = $new_trdiary_id;
            $pdp_skill->userid = backup_todb($pdp_skill_info['#']['USERID']['0']['#']);
            $pdp_skill->skillid = backup_todb($pdp_skill_info['#']['SKILLID']['0']['#']);
            $pdp_skill->priority = backup_todb($pdp_skill_info['#']['PRIORITY']['0']['#']);
            $pdp_skill->isstrength = backup_todb($pdp_skill_info['#']['ISSTRENGTH']['0']['#']);

            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code,"user",$pdp_skill->userid);
            if ($user) {
                $pdp_skill->userid = $user->new_id;
            }

            // We have to recode the skillid field
            $skill = backup_getid($restore->backup_unique_code,"threesixty_skill",$pdp_skill->skillid);
            if ($skill) {
                $pdp_skill->skillid = $skill->new_id;
            }

            //The structure is equal to the db, so insert the pdp_skills
            $newid = insert_record("trdiary_pdp_skill", $pdp_skill);

            //Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }
            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"trdiary_pdp_skill",$oldid,$newid);

            } else {
                $status = false;
            }

        }

        return $status;
    }





    //This function restores the training diary reflog entries
    function trdiary_reflog_entry_restore_mods($old_trdiary_id,$new_trdiary_id,$info,$restore) {

        global $CFG;

        $status = true;

        //Get the reflog_entries array
        if(isset($info['MOD']['#']['REFLOG_ENTRIES']['0']['#']['REFLOG_ENTRY'])) {
            $reflog_entries = $info['MOD']['#']['REFLOG_ENTRIES']['0']['#']['REFLOG_ENTRY'];
        } else {
            $reflog_entries = array();
        }

        //Iterate over reflog_entries
        for($i = 0; $i < sizeof($reflog_entries); $i++) {
            $reflog_entry_info = $reflog_entries[$i];
            //traverse_xmlize($reflog_entry_info);                                      //Debug
            //print_object ($GLOBALS['traverse_array']);                                //Debug
            //$GLOBALS['traverse_array']="";                                            //Debug

            //We'll need this later!!
            $oldid = backup_todb($reflog_entry_info['#']['ID']['0']['#']);

            //Now, build the reflog entry record structure
            $reflog_entry = new object();
            $reflog_entry->trdiaryid = $new_trdiary_id;
            $reflog_entry->userid = backup_todb($reflog_entry_info['#']['USERID']['0']['#']);
            $reflog_entry->timecreated = backup_todb($reflog_entry_info['#']['TIMECREATED']['0']['#']);

            //We have to recode the userid field
            $user = backup_getid($restore->backup_unique_code,"user",$reflog_entry->userid);
            if ($user) {
                $reflog_entry->userid = $user->new_id;
            }

            //The structure is equal to the db, so insert the reflog_entries
            $newid = insert_record("trdiary_reflog_entry", $reflog_entry);

            //Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"trdiary_reflog_entry",$oldid,$newid);

                //Restore trdiary_reflog_value
                $status = trdiary_reflog_value_restore_mods($oldid,$newid,$reflog_entry_info,$restore);
            } else {
                $status = false;
            }

        }

        return $status;
    }


    //This function restores the trdiary_reflog_value
    function trdiary_reflog_value_restore_mods($old_reflog_entry_id, $new_reflog_entry_id, $info, $restore) {

        global $CFG;

        $status = true;

        //Get the trdiary_reflog_value array
        if(isset($info['#']['REFLOG_VALUES']['0']['#']['REFLOG_VALUE'])) {
            $reflog_values = $info['#']['REFLOG_VALUES']['0']['#']['REFLOG_VALUE'];
        } else {
            $reflog_values = array();
        }

        //Iterate over trdiary_reflog_value
        for($i = 0; $i < sizeof($reflog_values); $i++) {
            $reflog_value_info = $reflog_values[$i];
            //traverse_xmlize($reflog_value_info);                                     //Debug
            //print_object ($GLOBALS['traverse_array']);                               //Debug
            //$GLOBALS['traverse_array']="";                                           //Debug

            //We'll need this later!!
            $oldid = backup_todb($reflog_value_info['#']['ID']['0']['#']);

            //Now, build the trdiary_reflog_value record structure
            $reflog_value->fieldid = backup_todb($reflog_value_info['#']['FIELDID']['0']['#']);
            $reflog_value->entryid = $new_reflog_entry_id;
            $reflog_value->value = backup_todb($reflog_value_info['#']['VALUE']['0']['#']);

            //We have to recode the fieldid field
            $field = backup_getid($restore->backup_unique_code,"trdiary_reflog_field",$reflog_value->fieldid);
            if ($field) {
                $reflog_value->fieldid = $field->new_id;
            }

            //The structure is equal to the db, so insert the values
            $newid = insert_record("trdiary_reflog_value",$reflog_value);

            //Do some output
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }
            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,"trdiary_reflog_value",$oldid,$newid);
            } else {
                $status = false;
            }
        }

        return $status;
    }

    //This function returns a log record with all the necessay transformations
    //done. It's used by restore_log_module() to restore modules log.
    function trdiary_restore_logs($restore,$log) {

        $status = false;
        //Depending of the action, we recode different things
        switch ($log->action) {
        case "add":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,(int) $log->info);
                if ($mod) {
                    $log->url = "../mod/trdiary/view.php?id={$log->cmid}";
                    $log->info = "trdiary {$mod->new_id}"; 
                    $status = true;
                }
            }
            break;

        case "update":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,(int) $log->info);
                if ($mod) {
                    $log->url = "../mod/trdiary/view.php?id={$log->cmid}";
                    $log->info = "trdiary {$mod->new_id}"; 
                    $status = true;
                }
            }
            break;
        case "delete reflog":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,(int) $log->info);
                if ($mod) {
                    //Extract the entryid from the url field
                    if(preg_match('/e=([^&]*)/', $log->url, $m)) {
                        $entid = $m[1];
                        $ent = backup_getid($restore->backup_unique_code,"trdiary_reflog_entry",$entid);
                        if(isset($ent->new_id)) {
                            $url = '&amp;e='.$ent->new_id;
                        } else {
                            $url = '';
                        }
                    } else {
                        $url = '';
                    }
    
                    $log->url = "deletereflog.php?a={$mod->new_id}$url";
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "insert reflog":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,(int) $log->info);
                if ($mod) {
                    $log->url = "reflog.php?a=".$mod->new_id;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;

        case "update pdp":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,(int) $log->info);
                if ($mod) {
                    $log->url = "view.php?a=".$mod->new_id;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "update reflog":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,(int) $log->info);
                if ($mod) {
                    //Extract the entryid from the url field
                    if(preg_match('/e=([^&]*)/', $log->url, $m)) {
                        $entid = $m[1];
                        $ent = backup_getid($restore->backup_unique_code,"trdiary_reflog_entry",$entid);
                        if (isset($ent->new_id)) {
                            $url = '&amp;e='.$ent->new_id;
                        } else { 
                            $url='';
                        }
                    } else {
                        $url = '';
                    }

                    $log->url = "editreflog.php?a={$mod->new_id}$url";
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "update user":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,(int) $log->info);
                if ($mod) {
                    //Extract the entryid from the url field
                    if(preg_match('/u=([^&]*)/', $log->url, $m)) {
                        $userid = $m[1];
                        $user = backup_getid($restore->backup_unique_code,"user",$userid);
                        if(isset($user->new_id)) {
                            $url = '&amp;u='.$user->new_id;
                        } else {
                            $url = '';
                        }
                    } else {
                        $url = '';
                    }

                    $log->url = "edit.php?a={$mod->new_id}$url";
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view all":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,(int) $log->info);
                if ($mod) {
                    $log->url = "index.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view pdp":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,(int) $log->info);
                if ($mod) {
                    $log->url = "view.php?a={$mod->new_id}";
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view reflog":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,(int) $log->info);
                if ($mod) {
                    $log->url = "reflog.php?a=$mod->new_id";
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view user":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,(int) $log->info);
                if ($mod) {
                    //Extract the entryid from the url field
                    if(preg_match('/u=([^&]*)/', $log->url, $m)) {
                        $userid = $m[1];
                        $user = backup_getid($restore->backup_unique_code,"user",$userid);
                        if(isset($user->new_id)) {
                            $url = '&amp;u='.$user->new_id;
                        } else {
                            $url = '';
                        }
                    } else {
                        $url = '';
                    }
                    $log->url = "edit.php?a={$mod->new_id}$url";
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "view user list":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,(int) $log->info);
                if ($mod) {
                    $log->url = "users.php?a=$mod->new_id";
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        default:
            if (!defined('RESTORE_SILENTLY')) {
                echo "action (".$log->module."-".$log->action.") unknown. Not restored<br />";                 //Debug
            }
            break;
        }

        if ($status) {
            $status = $log;
        }
        return $status;
    }
    
