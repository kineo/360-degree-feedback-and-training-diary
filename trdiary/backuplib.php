<?php //$Id$

/**
 * Module backup library functions
 *
 * @author  Simon Coggins <simonc@catalyst.net.nz>
 * @package mod/trdiary

    //This is the "graphical" structure of the trdiary mod:


    //           trdiary_pdp_field ----------- trdiary ----------------------- trdiary_reflog_field
    //      (CL, pk->id, fk->trdiaryid)      (CL,pk->id)                   (CL, pk->id, fk->trdiaryid)
    //                   |                        |                                       |
    //                   |                        |                                       |
    //                   |                        |                                       |
    //           trdiary_pdp_value                |---- trdiary_reflog_entry --- trdiary_reflog_value
    //       (UL, pk->id, fk->fieldid)            |         (UL, pk->id,             (UL, pk->id,
    //                                            |        fk->trdiaryid)      fk->fieldid, fk->entryid)
    //                                            |
    //                                            |
    //                                     trdiary_pdp_skill
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
**/

    function trdiary_backup_mods($bf,$preferences) {
        
        global $CFG;

        $status = true;

        //Iterate over trdiary table
        $trdiaries = get_records ("trdiary","course",$preferences->backup_course,"id");
        if ($trdiaries) {
            foreach ($trdiaries as $trdiary) {
                if (backup_mod_selected($preferences,'trdiary',$trdiary->id)) {
                    $status = trdiary_backup_one_mod($bf,$preferences,$trdiary);
                }
            }
        }
        return $status;
    }

    function trdiary_backup_one_mod($bf,$preferences,$trdiary) {

        global $CFG;
    
        if (is_numeric($trdiary)) {
            $trdiary = get_record('trdiary','id',$trdiary);
        }
    
        $status = true;

        //Start mod
        fwrite($bf,start_tag('MOD', 3, true));
        //Print trdiary data
        fwrite($bf,full_tag('ID', 4, false, $trdiary->id));
        fwrite($bf,full_tag('MODTYPE', 4, false, "trdiary"));
        fwrite($bf,full_tag('NAME', 4, false, $trdiary->name));
        fwrite($bf,full_tag('LOGFREQ', 4, false, $trdiary->logfreq));
        fwrite($bf,full_tag('THREESIXTYID', 4, false, $trdiary->threesixtyid));
        fwrite($bf,full_tag('TIMECREATED', 4, false, $trdiary->timecreated));
        fwrite($bf,full_tag('TIMEMODIFIED', 4, false, $trdiary->timemodified));

        // pdp_field should do user check then call pdp_values
        backup_trdiary_pdp_field($bf, $preferences, $trdiary->id);
        backup_trdiary_reflog_field($bf, $preferences, $trdiary->id);

        // Only if preferences->backup_users != 2 (none users). Else, teachers entries will be included.
        if ($preferences->backup_users != 2) {
            backup_trdiary_pdp_skill($bf, $preferences, $trdiary->id);
            backup_trdiary_reflog_entry($bf, $preferences, $trdiary->id);
        }

        //End mod
        $status = fwrite($bf,end_tag('MOD', 3, true));

        return $status;
    }

    // Backup training diary pdp fields (executed from trdiary_backup_one_mod)
    function backup_trdiary_pdp_field($bf, $preferences, $trdiary) {
        global $CFG;

        $status = true;

        $pdp_fields = get_records('trdiary_pdp_field', 'trdiaryid', $trdiary, 'id');
        // If there are fields
        if ($pdp_fields) {
            $status = fwrite($bf, start_tag('PDP_FIELDS', 4, true));

            // iterate over each PDP field
            foreach ($pdp_fields AS $pdp_field) {
                $status = fwrite($bf, start_tag('PDP_FIELD', 5, true));

                fwrite($bf, full_tag('ID', 6, false, $pdp_field->id));
                fwrite($bf, full_tag('TRDIARYID', 6, false, $pdp_field->trdiaryid));
                fwrite($bf, full_tag('NAME', 6, false, $pdp_field->name));

                // Only if preferences->backup_users != 2 (none users). Else, teachers entries will be included.
                if ($preferences->backup_users != 2) {
                    $status = backup_trdiary_pdp_value($bf, $preferences, $pdp_field->id);
                }

                $status = fwrite($bf, end_tag('PDP_FIELD', 5, true));
            }

            // write end tag
            $status = fwrite($bf, end_tag('PDP_FIELDS', 4, true));

        }
        return $status;      
    }

    // Backup training diary pdp values (executed from backup_trdiary_pdp_field)
    function backup_trdiary_pdp_value($bf, $preferences, $fieldid) {
        global $CFG;

        $status = true;

        $pdp_values = get_records('trdiary_pdp_value', 'fieldid', $fieldid, 'id');
        // If there are values
        if ($pdp_values) {
            $status = fwrite($bf, start_tag('PDP_VALUES', 6, true));

            // iterate over each PDP value
            foreach ($pdp_values AS $pdp_value) {
                $status = fwrite($bf, start_tag('PDP_VALUE', 7, true));

                fwrite($bf, full_tag('ID', 8, false, $pdp_value->id));
                fwrite($bf, full_tag('FIELDID', 8, false, $pdp_value->fieldid));
                fwrite($bf, full_tag('USERID', 8, false, $pdp_value->userid));
                fwrite($bf, full_tag('VALUE', 8, false, $pdp_value->value));

                $status = fwrite($bf, end_tag('PDP_VALUE', 7, true));
            }

            // write end tag
            $status = fwrite($bf, end_tag('PDP_VALUES', 6, true));

        }
        return $status;      
    
    }

    // Backup training diary reflog fields (executed from trdiary_backup_one_mod)
    function backup_trdiary_reflog_field($bf, $preferences, $trdiary) {
        global $CFG;

        $status = true;

        $reflog_fields = get_records('trdiary_reflog_field', 'trdiaryid', $trdiary, 'id');
        // If there are fields
        if ($reflog_fields) {
            $status = fwrite($bf, start_tag('REFLOG_FIELDS', 4, true));

            // iterate over each reflog field
            foreach ($reflog_fields AS $reflog_field) {
                $status = fwrite($bf, start_tag('REFLOG_FIELD', 5, true));

                fwrite($bf, full_tag('ID', 6, false, $reflog_field->id));
                fwrite($bf, full_tag('TRDIARYID', 6, false, $reflog_field->trdiaryid));
                fwrite($bf, full_tag('NAME', 6, false, $reflog_field->name));

                $status = fwrite($bf, end_tag('REFLOG_FIELD', 5, true));
            }

            // write end tag
            $status = fwrite($bf, end_tag('REFLOG_FIELDS', 4, true));

        }
        return $status;
    } 

    // Backup training diary pdp skills (executed from trdiary_backup_one_mod)
    function backup_trdiary_pdp_skill($bf, $preferences, $trdiary) {
        global $CFG;

        $status = true;

        $pdp_skills = get_records('trdiary_pdp_skill', 'trdiaryid', $trdiary, 'id');
        // If there are PDP skills
        if ($pdp_skills) {
            $status = fwrite($bf, start_tag('PDP_SKILLS', 4, true));

            // iterate over each PDP skill
            foreach ($pdp_skills AS $pdp_skill) {
                $status = fwrite($bf, start_tag('PDP_SKILL', 5, true));

                fwrite($bf, full_tag('ID', 6, false, $pdp_skill->id));
                fwrite($bf, full_tag('TRDIARYID', 6, false, $pdp_skill->trdiaryid));
                fwrite($bf, full_tag('USERID', 6, false, $pdp_skill->userid));
                fwrite($bf, full_tag('SKILLID', 6, false, $pdp_skill->skillid));
                fwrite($bf, full_tag('PRIORITY', 6, false, $pdp_skill->priority));
                fwrite($bf, full_tag('ISSTRENGTH', 6, false, $pdp_skill->isstrength));

                $status = fwrite($bf, end_tag('PDP_SKILL', 5, true));
            }

            // write end tag
            $status = fwrite($bf, end_tag('PDP_SKILLS', 4, true));

        }
        return $status;
    }

    // Backup training diary reflog entries (executed from trdiary_backup_one_mod)
    function backup_trdiary_reflog_entry($bf, $preferences, $trdiary) {
        global $CFG;

        $status = true;

        $reflog_entries = get_records('trdiary_reflog_entry', 'trdiaryid', $trdiary, 'id');
        // If there are reflog entries
        if ($reflog_entries) {
            $status = fwrite($bf, start_tag('REFLOG_ENTRIES', 4, true));

            // iterate over each reflog entry
            foreach ($reflog_entries AS $reflog_entry) {
                $status = fwrite($bf, start_tag('REFLOG_ENTRY', 5, true));

                fwrite($bf, full_tag('ID', 6, false, $reflog_entry->id));
                fwrite($bf, full_tag('TRDIARYID', 6, false, $reflog_entry->trdiaryid));
                fwrite($bf, full_tag('USERID', 6, false, $reflog_entry->userid));
                fwrite($bf, full_tag('TIMECREATED', 6, false, $reflog_entry->timecreated));

                // get reflog entry values
                $status = backup_trdiary_reflog_value($bf, $preferences, $reflog_entry->id);

                $status = fwrite($bf, end_tag('REFLOG_ENTRY', 5, true));
            }

            // write end tag
            $status = fwrite($bf, end_tag('REFLOG_ENTRIES', 4, true));

        }
        return $status;
        
    }

    // Backup training diary reflog values (executed from backup_trdiary_reflog_entry)
    function backup_trdiary_reflog_value($bf, $preferences, $entryid) {
        global $CFG;

        $status = true;

        $reflog_values = get_records('trdiary_reflog_value', 'entryid', $entryid, 'id');
        // If there are reflog values
        if ($reflog_values) {
            $status = fwrite($bf, start_tag('REFLOG_VALUES', 6, true));

            // iterate over each reflog value
            foreach ($reflog_values AS $reflog_value) {
                $status = fwrite($bf, start_tag('REFLOG_VALUE', 7, true));

                fwrite($bf, full_tag('ID', 8, false, $reflog_value->id));
                fwrite($bf, full_tag('FIELDID', 8, false, $reflog_value->fieldid));
                fwrite($bf, full_tag('ENTRYID', 8, false, $reflog_value->entryid));
                fwrite($bf, full_tag('VALUE', 8, false, $reflog_value->value));

                $status = fwrite($bf, end_tag('REFLOG_VALUE', 7, true));
            }

            // write end tag
            $status = fwrite($bf, end_tag('REFLOG_VALUES', 6, true));

        }
        return $status;
        
    }
    
   ////Return an array of info (name,value)
   function trdiary_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {
      if (!empty($instances) && is_array($instances) && count($instances)) {
           $info = array();
           foreach ($instances as $id => $instance) {
               $info += trdiary_check_backup_mods_instances($instance,$backup_unique_code);
           }
           return $info;
       }
        //First the course data
        $info[0][0] = get_string('modulenameplural','trdiary');
        if ($ids = trdiary_ids($course)) {
            $info[0][1] = count($ids);
        } else {
            $info[0][1] = 0;
        }

        $info[1][0] = get_string('pdpfields','trdiary');
        if ($ids = trdiary_pdp_field_ids_by_course($course)) {
            $info[1][1] = count($ids);
        } else {
            $info[1][1] = 0;
        }

        $info[2][0] = get_string('reflogfields','trdiary');
        if ($ids = trdiary_reflog_field_ids_by_course($course)) {
            $info[2][1] = count($ids);
        } else {
            $info[2][1] = 0;
        }

        //Now, if requested, the user_data
        if ($user_data) {

            $info[3][0] = get_string('pdpskills','trdiary');
            if ($ids = trdiary_pdp_skill_ids_by_course ($course)) {
                $info[3][1] = count($ids);
            } else {
                $info[3][1] = 0;
            }

            $info[4][0] = get_string('reflogentries','trdiary');
            if ($ids = trdiary_reflog_entry_ids_by_course ($course)) {
                $info[4][1] = count($ids);
            } else {
                $info[4][1] = 0;
            }
        }
        return $info;
    }

   ////Return an array of info (name,value)
   function trdiary_check_backup_mods_instances($instance,$backup_unique_code) {
        //First the course data
        $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
        $info[$instance->id.'0'][1] = '';

        $info[$instance->id.'1'][0] = get_string('pdpfields','trdiary');
        if ($ids = trdiary_pdp_field_ids_by_instance($instance->id)) {
            $info[$instance->id.'1'][1] = count($ids);
        } else {
            $info[$instance->id.'1'][1] = 0;
        }

        $info[$instance->id.'2'][0] = get_string('reflogfields','trdiary');
        if ($ids = trdiary_reflog_field_ids_by_instance($instance->id)) {
            $info[$instance->id.'2'][1] = count($ids);
        } else {
            $info[$instance->id.'2'][1] = 0;
        }

        //Now, if requested, the user_data
        if (!empty($instance->userdata)) {
            $info[$instance->id.'3'][0] = get_string('pdpskills','trdiary');
            if ($ids = trdiary_pdp_skill_ids_by_instance ($instance->id)) {
                $info[$instance->id.'3'][1] = count($ids);
            } else {
                $info[$instance->id.'3'][1] = 0;
            }
 
            $info[$instance->id.'4'][0] = get_string('reflogentries','trdiary');
            if ($ids = trdiary_reflog_entry_ids_by_instance ($instance->id)) {
                $info[$instance->id.'4'][1] = count($ids);
            } else {
                $info[$instance->id.'4'][1] = 0;
            }
        }
        return $info;
    }

    // INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE

    //Returns an array of training diary id
    function trdiary_ids ($course) {

        global $CFG;

        return get_records_sql ("SELECT a.id, a.course
                                 FROM {$CFG->prefix}trdiary a
                                 WHERE a.course = '$course'");
    }
   
    //Returns an array of PDP field ids
    function trdiary_pdp_field_ids_by_course ($course) {

        global $CFG;

        return get_records_sql ("SELECT f.id , f.trdiaryid
                                 FROM {$CFG->prefix}trdiary_pdp_field f,
                                      {$CFG->prefix}trdiary a
                                 WHERE a.course = '$course' AND
                                       f.trdiaryid = a.id");
    }

    //Returns an array of PDP field ids
    function trdiary_pdp_field_ids_by_instance ($instanceid) {

        global $CFG;

        return get_records_sql ("SELECT f.id , f.trdiaryid
                                 FROM {$CFG->prefix}trdiary_pdp_field f
                                 WHERE f.trdiaryid = $instanceid");
    }

    //Returns an array of reflog field ids
    function trdiary_reflog_field_ids_by_course ($course) {

        global $CFG;

        return get_records_sql ("SELECT f.id , f.trdiaryid
                                 FROM {$CFG->prefix}trdiary_reflog_field f,
                                      {$CFG->prefix}trdiary a
                                 WHERE a.course = '$course' AND
                                       f.trdiaryid = a.id");
    }

    //Returns an array of reflog field ids
    function trdiary_reflog_field_ids_by_instance ($instanceid) {

        global $CFG;

        return get_records_sql ("SELECT f.id , f.trdiaryid
                                 FROM {$CFG->prefix}trdiary_reflog_field f
                                 WHERE f.trdiaryid = $instanceid");
    }


    //Returns an array of pdp skill ids
    function trdiary_pdp_skill_ids_by_course ($course) {

        global $CFG;

        return get_records_sql ("SELECT f.id , f.trdiaryid
                                 FROM {$CFG->prefix}trdiary_pdp_skill f,
                                      {$CFG->prefix}trdiary a
                                 WHERE a.course = '$course' AND
                                       f.trdiaryid = a.id");
    }

    //Returns an array of pdp skill ids
    function trdiary_pdp_skill_ids_by_instance ($instanceid) {

        global $CFG;

        return get_records_sql ("SELECT f.id , f.trdiaryid
                                 FROM {$CFG->prefix}trdiary_pdp_skill f
                                 WHERE f.trdiaryid = $instanceid");
    }




    //Returns an array of reflog entry ids
    function trdiary_reflog_entry_ids_by_course ($course) {

        global $CFG;

        return get_records_sql ("SELECT f.id , f.trdiaryid
                                 FROM {$CFG->prefix}trdiary_reflog_entry f,
                                      {$CFG->prefix}trdiary a
                                 WHERE a.course = '$course' AND
                                       f.trdiaryid = a.id");
    }

    //Returns an array of reflog entry ids
    function trdiary_reflog_entry_ids_by_instance ($instanceid) {

        global $CFG;

        return get_records_sql ("SELECT f.id , f.trdiaryid
                                 FROM {$CFG->prefix}trdiary_reflog_entry f
                                 WHERE f.trdiaryid = $instanceid");
    }

