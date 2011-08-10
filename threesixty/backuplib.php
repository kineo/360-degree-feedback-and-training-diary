<?php
    // This php script contains all the stuff to backup/restore
    // threesixty mods
    //
    // This is the "graphical" structure of the threesixty mod:
    //
    //       threesixty ------------------- threesixty_competency ------------ threesixty_skill
    //      (CL, pk->id)                     |  (CL, pk->id,   |                (CL, pk->id, 
    //            |                          | fk->activityid) |             fk->competencyid)
    //            |                          |                 |                       |
    //            |                          |                 |                       |
    //  threesixty_analysis ----- threesixty_carried_comp      |                       |
    //      (UL, pk->id,              (UL, pk->id,             |                       |
    //     fk->activityid)           fk->competencyid,         |                       |
    //            |                  fk->analysisid)           |                       |
    //            |                                            |                       |
    //            |                                            |                       |
    //            |                                            |                       |
    //  threesixty_respondent -- threesixty_response ---- threesixty_response_comp     |
    //    (UL, pk->id,              (UL, pk->id,                (UL, pk->id,           |
    //     fk->analysisid)         fk->respondentid)         fk->responseid,           |
    //                                     |                fk->competencyid)          |
    //                                     |                                           |
    //                                     |                                           |
    //                                     |----------------------------- threesixty_response_skill
    //                                                                  (UL, pk->id, fk->responseid,
    //                                                                           fk->skillid)
    //
    // Meaning: pk->primary key field of the table
    //          fk->foreign key to link with parent
    //          nt->nested field (recursive data)
    //          CL->course level info
    //          UL->user level info
    //          files->table may have files)
    //
    //
    //----------------------------------------------------------------------------------

    function threesixty_backup_mods($bf,$preferences) {
        
        global $CFG;

        $status = true;

        //Iterate over threesixty table
        $threesixties = get_records ("threesixty","course",$preferences->backup_course,"id");
        if ($threesixties) {
            foreach ($threesixties as $threesixty) {
                if (backup_mod_selected($preferences,'threesixty',$threesixty->id)) {
                    $status = threesixty_backup_one_mod($bf,$preferences,$threesixty);
                }
            }
        }
        return $status;
    }

    function threesixty_backup_one_mod($bf,$preferences,$threesixty) {

        global $CFG;
    
        if (is_numeric($threesixty)) {
            $threesixty = get_record('threesixty','id',$threesixty);
        }
    
        $status = true;

        //Start mod
        fwrite($bf,start_tag('MOD', 3, true));
        //Print threesixty data
        fwrite($bf,full_tag('ID', 4, false, $threesixty->id));
        fwrite($bf,full_tag('MODTYPE', 4, false, "threesixty"));
        fwrite($bf,full_tag('NAME', 4, false, $threesixty->name));
        fwrite($bf,full_tag('COMPETENCIESCARRIED', 4, false, $threesixty->competenciescarried));
        fwrite($bf,full_tag('REQUIREDRESPONDENTS', 4, false, $threesixty->requiredrespondents));
        fwrite($bf,full_tag('TIMECREATED', 4, false, $threesixty->timecreated));
        fwrite($bf,full_tag('TIMEMODIFIED', 4, false, $threesixty->timemodified));

        // threesixty_competency should do call threesixty_skill
        backup_threesixty_competency($bf, $preferences, $threesixty->id);

        // Only if preferences->backup_users != 2 (none users). Else, teachers entries will be included.
        if ($preferences->backup_users != 2) {
            // threesixty_analysis also backs up rest of user level data
            backup_threesixty_analysis($bf, $preferences, $threesixty->id);
        }

        //End mod
        $status = fwrite($bf,end_tag('MOD', 3, true));

        return $status;
    }

    // Backup threesixty competencies (executed from threesixty_backup_one_mod)
    function backup_threesixty_competency($bf, $preferences, $threesixty) {
        global $CFG;

        $status = true;

        $competencies = get_records('threesixty_competency', 'activityid', $threesixty, 'id');
        // If there are competencies
        if ($competencies) {
            $status = fwrite($bf, start_tag('COMPETENCIES', 4, true));

            // iterate over each competency
            foreach ($competencies AS $competency) {
                $status = fwrite($bf, start_tag('COMPETENCY', 5, true));

                fwrite($bf, full_tag('ID', 6, false, $competency->id));
                fwrite($bf, full_tag('ACTIVITYID', 6, false, $competency->activityid));
                fwrite($bf, full_tag('NAME', 6, false, $competency->name));
                fwrite($bf, full_tag('DESCRIPTION', 6, false, $competency->description));
                fwrite($bf, full_tag('SHOWFEEDBACK', 6, false, $competency->showfeedback));

                $status = backup_threesixty_skill($bf, $preferences, $competency->id);

                $status = fwrite($bf, end_tag('COMPETENCY', 5, true));
            }

            // write end tag
            $status = fwrite($bf, end_tag('COMPETENCIES', 4, true));

        }
        return $status;      
    }

    // Backup threesixty skills (executed from backup_threesixty_competency)
    function backup_threesixty_skill($bf, $preferences, $competencyid) {
        global $CFG;

        $status = true;

        $skills = get_records('threesixty_skill', 'competencyid', $competencyid, 'id');
        // If there are skills
        if ($skills) {
            $status = fwrite($bf, start_tag('SKILLS', 6, true));

            // iterate over each skill
            foreach ($skills AS $skill) {
                $status = fwrite($bf, start_tag('SKILL', 7, true));

                fwrite($bf, full_tag('ID', 8, false, $skill->id));
                fwrite($bf, full_tag('COMPETENCYID', 8, false, $skill->competencyid));
                fwrite($bf, full_tag('NAME', 8, false, $skill->name));
                fwrite($bf, full_tag('DESCRIPTION', 8, false, $skill->description));

                $status = fwrite($bf, end_tag('SKILL', 7, true));
            }

            // write end tag
            $status = fwrite($bf, end_tag('SKILLS', 6, true));

        }
        return $status;      
    
    }

    // Backup threesixty analyses (executed from threesixty_backup_one_mod)
    function backup_threesixty_analysis($bf, $preferences, $threesixty) {
        global $CFG;

        $status = true;

        $analyses = get_records('threesixty_analysis', 'activityid', $threesixty, 'id');
        // If there are analyses
        if ($analyses) {
            $status = fwrite($bf, start_tag('ANALYSES', 4, true));

            // iterate over each analysis
            foreach ($analyses AS $analysis) {
                $status = fwrite($bf, start_tag('ANALYSIS', 5, true));

                fwrite($bf, full_tag('ID', 6, false, $analysis->id));
                fwrite($bf, full_tag('ACTIVITYID', 6, false, $analysis->activityid));
                fwrite($bf, full_tag('USERID', 6, false, $analysis->userid));

                $status = backup_threesixty_carried_comp($bf, $preferences, $analysis->id);
                $status = backup_threesixty_respondent($bf, $preferences, $analysis->id);

                $status = fwrite($bf, end_tag('ANALYSIS', 5, true));
            }

            // write end tag
            $status = fwrite($bf, end_tag('ANALYSES', 4, true));

        }
        return $status;      
    }

    // Backup threesixty carried comps (executed from backup_threesixty_analysis)
    function backup_threesixty_carried_comp($bf, $preferences, $analysisid) {
        global $CFG;

        $status = true;

        $carried_comps = get_records('threesixty_carried_comp', 'analysisid', $analysisid, 'id');
        // If there are carried_comps
        if ($carried_comps) {
            $status = fwrite($bf, start_tag('CARRIED_COMPS', 6, true));

            // iterate over each carried_comp
            foreach ($carried_comps AS $carried_comp) {
                $status = fwrite($bf, start_tag('CARRIED_COMP', 7, true));

                fwrite($bf, full_tag('ID', 8, false, $carried_comp->id));
                fwrite($bf, full_tag('ANALYSISID', 8, false, $carried_comp->analysisid));
                fwrite($bf, full_tag('COMPETENCYID', 8, false, $carried_comp->competencyid));

                $status = fwrite($bf, end_tag('CARRIED_COMP', 7, true));
            }

            // write end tag
            $status = fwrite($bf, end_tag('CARRIED_COMPS', 6, true));

        }
        return $status;      
    
    }
 
    // Backup threesixty respondents (executed from backup_threesixty_analysis)
    function backup_threesixty_respondent($bf, $preferences, $analysisid) {
        global $CFG;

        $status = true;

        $respondents = get_records('threesixty_respondent', 'analysisid', $analysisid, 'id');
        // responses from self don't appear in the respondent table
        // check the response table to see if any self responses are made
        $selfresponse = get_records_select('threesixty_response', "respondentid IS NULL AND analysisid=$analysisid", 'id');

        // If there are respondents or a self response
        if ($respondents || $selfresponse) {
            $status = fwrite($bf, start_tag('RESPONDENTS', 6, true));

            if ($selfresponse) {
                // if their are self responses, create a SELF tag to contain them
                $status = fwrite($bf, start_tag('SELF', 7, true));
                $status = backup_threesixty_response($bf, $preferences, null, $analysisid);
                $status = fwrite($bf, end_tag('SELF', 7, true));
            }

            if ($respondents) {
                // iterate over each respondent
                foreach ($respondents AS $respondent) {
                    $status = fwrite($bf, start_tag('RESPONDENT', 7, true));

                    fwrite($bf, full_tag('ID', 8, false, $respondent->id));
                    fwrite($bf, full_tag('EMAIL', 8, false, $respondent->email));
                    fwrite($bf, full_tag('TYPE', 8, false, $respondent->type));
                    fwrite($bf, full_tag('ANALYSISID', 8, false, $respondent->analysisid));
                    fwrite($bf, full_tag('UNIQUEHASH', 8, false, $respondent->uniquehash));

                    $status = backup_threesixty_response($bf, $preferences, $respondent->id);

                    $status = fwrite($bf, end_tag('RESPONDENT', 7, true));
                }   
            }
            // write end tag
            $status = fwrite($bf, end_tag('RESPONDENTS', 6, true));

        }

        return $status;      
    
    }
 
     // Backup threesixty responses (executed from backup_threesixty_respondent)
    function backup_threesixty_response($bf, $preferences, $respondentid, $analysisid=null) {
        global $CFG;

        $status = true;
        if ($respondentid !== null) {
            $responses = get_records('threesixty_response', 'respondentid', $respondentid, 'id');
        } else {
            $responses = get_records_select('threesixty_response', "respondentid IS NULL AND analysisid=$analysisid", 'id');
        }    
        // If there are responses
        if ($responses) {
            $status = fwrite($bf, start_tag('RESPONSES', 8, true));

            // iterate over each response
            foreach ($responses AS $response) {
                $status = fwrite($bf, start_tag('RESPONSE', 9, true));

                fwrite($bf, full_tag('ID', 10, false, $response->id));
                fwrite($bf, full_tag('ANALYSISID', 10, false, $response->analysisid));
                fwrite($bf, full_tag('RESPONDENTID', 10, false, $response->respondentid));
                fwrite($bf, full_tag('TIMECOMPLETED', 10, false, $response->timecompleted));

                $status = backup_threesixty_response_comp($bf, $preferences, $response->id);
                $status = backup_threesixty_response_skill($bf, $preferences, $response->id);

                $status = fwrite($bf, end_tag('RESPONSE', 9, true));
            }

            // write end tag
            $status = fwrite($bf, end_tag('RESPONSES', 8, true));

        }
        return $status;      
    
    }

    // Backup threesixty response competency (executed from backup_threesixty_response)
    function backup_threesixty_response_comp($bf, $preferences, $responseid) {
        global $CFG;

        $status = true;

        $response_comps = get_records('threesixty_response_comp', 'responseid', $responseid, 'id');
        // If there are response_comps
        if ($response_comps) {
            $status = fwrite($bf, start_tag('RESPONSE_COMPS', 10, true));

            // iterate over each response_comp
            foreach ($response_comps AS $response_comp) {
                $status = fwrite($bf, start_tag('RESPONSE_COMP', 11, true));

                fwrite($bf, full_tag('ID', 12, false, $response_comp->id));
                fwrite($bf, full_tag('RESPONSEID', 12, false, $response_comp->responseid));
                fwrite($bf, full_tag('COMPETENCYID', 12, false, $response_comp->competencyid));
                fwrite($bf, full_tag('FEEDBACK', 12, false, $response_comp->feedback));

                $status = fwrite($bf, end_tag('RESPONSE_COMP', 11, true));
            }

            // write end tag
            $status = fwrite($bf, end_tag('RESPONSE_COMPS', 10, true));

        }
        return $status;      
    
    }
 
  
     // Backup threesixty response skill (executed from backup_threesixty_response)
    function backup_threesixty_response_skill($bf, $preferences, $responseid) {
        global $CFG;

        $status = true;

        $response_skills = get_records('threesixty_response_skill', 'responseid', $responseid, 'id');
        // If there are response_skills
        if ($response_skills) {
            $status = fwrite($bf, start_tag('RESPONSE_SKILLS', 10, true));

            // iterate over each response_skill
            foreach ($response_skills AS $response_skill) {
                $status = fwrite($bf, start_tag('RESPONSE_SKILL', 11, true));

                fwrite($bf, full_tag('ID', 12, false, $response_skill->id));
                fwrite($bf, full_tag('RESPONSEID', 12, false, $response_skill->responseid));
                fwrite($bf, full_tag('SKILLID', 12, false, $response_skill->skillid));
                fwrite($bf, full_tag('SCORE', 12, false, $response_skill->score));

                $status = fwrite($bf, end_tag('RESPONSE_SKILL', 11, true));
            }

            // write end tag
            $status = fwrite($bf, end_tag('RESPONSE_SKILLS', 10, true));

        }
        return $status;      
    
    }
 



   ////Return an array of info (name,value)
   function threesixty_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {
      if (!empty($instances) && is_array($instances) && count($instances)) {
           $info = array();
           foreach ($instances as $id => $instance) {
               $info += threesixty_check_backup_mods_instances($instance,$backup_unique_code);
           }
           return $info;
       }
        //First the course data
        $info[0][0] = get_string('modulenameplural','threesixty');
        if ($ids = threesixty_ids($course)) {
            $info[0][1] = count($ids);
        } else {
            $info[0][1] = 0;
        }

        $info[1][0] = get_string('competenciesheading','threesixty');
        if ($ids = threesixty_competency_ids_by_course($course)) {
            $info[1][1] = count($ids);
        } else {
            $info[1][1] = 0;
        }

        $info[2][0] = get_string('skills','threesixty');
        if ($ids = threesixty_skill_ids_by_course($course)) {
            $info[2][1] = count($ids);
        } else {
            $info[2][1] = 0;
        }

        //Now, if requested, the user_data
        if ($user_data) {
            $info[3][0] = get_string('analyses','threesixty');
            if ($ids = threesixty_analysis_ids_by_course ($course)) {
                $info[3][1] = count($ids);
            } else {
                $info[3][1] = 0;
            }
        }


        return $info;
    }

   ////Return an array of info (name,value)
   function threesixty_check_backup_mods_instances($instance,$backup_unique_code) {
        //First the course data
        $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
        $info[$instance->id.'0'][1] = '';

        $info[$instance->id.'1'][0] = get_string('competenciesheading','threesixty');
        if ($ids = threesixty_competency_ids_by_instance($instance->id)) {
            $info[$instance->id.'1'][1] = count($ids);
        } else {
            $info[$instance->id.'1'][1] = 0;
        }

        $info[$instance->id.'2'][0] = get_string('skills','threesixty');
        if ($ids = threesixty_skill_ids_by_instance($instance->id)) {
            $info[$instance->id.'2'][1] = count($ids);
        } else {
            $info[$instance->id.'2'][1] = 0;
        }

        //Now, if requested, the user_data
        if (!empty($instance->userdata)) {
            $info[$instance->id.'3'][0] = get_string('analyses','threesixty');
            if ($ids = threesixty_analysis_ids_by_instance ($instance->id)) {
                $info[$instance->id.'3'][1] = count($ids);
            } else {
                $info[$instance->id.'3'][1] = 0;
            }
        }
        return $info;
    }

    // INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE

    //Returns an array of threesixty ids
    function threesixty_ids ($course) {

        global $CFG;

        return get_records_sql ("SELECT a.id, a.course
                                 FROM {$CFG->prefix}threesixty a
                                 WHERE a.course = '$course'");
    }
   
    //Returns an array of competency ids
    function threesixty_competency_ids_by_course ($course) {

        global $CFG;

        return get_records_sql ("SELECT c.id , c.activityid
                                 FROM {$CFG->prefix}threesixty_competency c,
                                      {$CFG->prefix}threesixty a
                                 WHERE a.course = '$course' AND
                                       c.activityid = a.id");
    }

    //Returns an array of competency ids
    function threesixty_competency_ids_by_instance ($instanceid) {

        global $CFG;

        return get_records_sql ("SELECT c.id , c.activityid
                                FROM {$CFG->prefix}threesixty_competency c
                                WHERE c.activityid = $instanceid");
    }

    //Returns an array of skill ids
    function threesixty_skill_ids_by_course ($course) {

        global $CFG;

        return get_records_sql ("SELECT s.id , c.activityid
                                 FROM {$CFG->prefix}threesixty_skill s,
                                      {$CFG->prefix}threesixty_competency c,
                                      {$CFG->prefix}threesixty a
                                 WHERE a.course = '$course' AND
                                       c.activityid = a.id AND
                                       s.competencyid = c.id");
    }

    //Returns an array of skill ids
    function threesixty_skill_ids_by_instance ($instanceid) {

        global $CFG;

        return get_records_sql ("SELECT s.id , c.activityid
                                 FROM {$CFG->prefix}threesixty_skill s,
                                      {$CFG->prefix}threesixty_competency c
                                 WHERE s.competencyid = c.id AND
                                       c.activityid = $instanceid");
    }

    //Returns an array of analsysis ids
    function threesixty_analysis_ids_by_course ($course) {

        global $CFG;

        return get_records_sql ("SELECT a.id , a.activityid
                                 FROM {$CFG->prefix}threesixty_analysis a,
                                      {$CFG->prefix}threesixty t
                                 WHERE t.course = '$course' AND
                                       a.activityid = t.id");
    }

    //Returns an array of analysis ids
    function threesixty_analysis_ids_by_instance ($instanceid) {

        global $CFG;

        return get_records_sql ("SELECT a.id , a.activityid
                                FROM {$CFG->prefix}threesixty_analysis a
                                WHERE a.activityid = $instanceid");
    }


    
?>
