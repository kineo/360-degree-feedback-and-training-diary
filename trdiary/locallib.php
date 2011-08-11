<?php

/**
 * Training Diary Local Library Functions
 *
 * @author  Simon Coggins <simonc@catalyst.net.nz>
 * @package mod/trdiary
**/

/**
 * Get instanceid of first threesixty instance in current course
 * Used to determine which 360 activity should be linked to a new 
 * training diary instance
 * 
 * @param int $courseid Course ID of the training diary being created
 * @return mixed Activity ID of the 360 activity to be linked or false if none found 
 */
function trdiary_get_threesixty_instance($courseid) {
    global $CFG;

    if(!isset($courseid)) {
        return false;
    }

    // get ids of all 360 instances for this course
    $instanceids = get_records('threesixty', 'course', $courseid, 'id', 'id');

    // no instances of 360 activity in this course
    if ($instanceids === false) {
        return false;
    }

    // if more than one instance is found, record a warning in log
    if (count($instanceids) > 1) {
        error_log('Warning: Multiple 360 diagnotics instances for this course. '
                 .'Training Diary using one with lowest id.');
    }

    // return the first instanceid found
    $firstinstance = array_shift($instanceids);
    return $firstinstance->id;
}

/**
 * Get information about the 360 activity that is linked to a particular trdiary
 *
 * @param int $trdiaryid ID of the training diary which we want to find the linked
 *                       360 activity for
 * @return mixed An object containing the ID and name of the linked 360 activity, 
 *               or false if none could be found
**/
function trdiary_get_linked_threesixty($trdiaryid) {
    global $CFG;
    if (!isset($trdiaryid)) {
        return false;
    }
    $sql = "SELECT t.id, t.name FROM 
                    {$CFG->prefix}threesixty t
                    JOIN {$CFG->prefix}trdiary d ON d.threesixtyid = t.id
                    WHERE t.id = '{$trdiaryid}';";
    $result = get_records_sql($sql);
    if ($result and count($result) > 0) {
        $threesixty = array_shift($result);
        return $threesixty;
    } else {
        return false;
    }

}

/**
 * Check if specified user has skills defined in the pdp_skill table
 * for the current activity
 *
 * Could use get_pdp_skills() and look at count but checking first is more 
 * efficient
 *
 * @param int $userid ID of user to find in pdp_skills table
 * @param int $activityid ID of activity to find in pdp_skills table
 * @return boolean Returns true if $userid has skills defined in $activityid
**/
function has_pdp_skills($userid,$activityid) {
    $num = count_records('trdiary_pdp_skill','trdiaryid',$activityid,'userid',$userid);
    if ($num == 0) {
        return false;
    } else {
        return true;
    }
}

/**
 * Create default entries in the trdiary_pdp_skills table based on the 
 * competencies carried over from 360 for this user
 *
 * @param int $userid ID of user to create skills for
 * @param int $trdiaryid ID of trdiary activity instance
 * @param int $threesixtyid Activity ID of linked 360 activity, used to get carried 
 *                          competencies
 * @return boolean True if carried competencies found and all skills could be created
 **/ 
function create_pdp_skills($userid,$trdiaryid,$threesixtyid) {
    global $CFG;

    // get skillids of skills required for each of the carried competencies
    $sql = "SELECT cc.competencyid as id FROM {$CFG->prefix}threesixty_carried_comp cc 
        INNER JOIN {$CFG->prefix}threesixty_analysis a ON cc.analysisid = a.id 
        WHERE a.activityid = '$threesixtyid' AND userid = '$userid'
        ORDER BY cc.id;";
    $skills = get_records_sql($sql);

    if($skills !== false) {
        // for each skillid add a new pdp_skill
        // Use default value of zero for priority and isstrength
        $ret = true;
        foreach ($skills AS $skill) {
            $newrecord = new stdClass();
            $newrecord->trdiaryid = $trdiaryid;
            $newrecord->userid = $userid;
            $newrecord->skillid = $skill->id;
            $newrecord->priority = 0;
            $newrecord->isstrength = 0;
            if(!insert_record('trdiary_pdp_skill',$newrecord,false)) {
                error_log("Error writing PDP skill with skillid={$newrecord->skillid}
                           to database.");
                $ret = false;
            }
        }
        return $ret;
    } else {
        // no carried competencies found
        error(get_string('nocarriedcomp','trdiary'));
        return false;
    }    
}

/**
 * Obtain a list of pdp skills for a particular user and training diary activity
 * Used to build personal development table
 *
 * @param int $userid ID of user to find skills for
 * @param int $activityid Activity ID of current trdiary instance
 * @return array Array of objects containing competency and skill information
**/
function get_pdp_skills($userid, $activityid) {
    global $CFG;
    // since we need to join to skill table to get competency name, may as well 
    // collect skills and competencies as one object and loop through to generate
    // the table
    $sql = "SELECT t.id, c.id AS compid, c.name AS compname, s.name AS skillname,
        s.id AS skillid, priority, isstrength 
        FROM {$CFG->prefix}trdiary_pdp_skill t
        INNER JOIN {$CFG->prefix}threesixty_skill s ON t.skillid = s.id
        INNER JOIN {$CFG->prefix}threesixty_competency c ON s.competencyid = c.id
        WHERE t.trdiaryid = '$activityid' AND t.userid = '$userid'
        ORDER BY compname, skillname;";
    return get_records_sql($sql);
}

/**
 * get additional fields for PDP page. Fill fields with values for each user
 *
 * @param int $userid ID of user to find extra field values for
 * @param int $activityid Activity ID of current trdiary instance
 * @return array Array of objects containing field names and values
**/
function get_pdp_extra_fields($userid, $activityid) {
    global $CFG;

    // first query gets additional fields
    $fields = get_records('trdiary_pdp_field','trdiaryid',$activityid,'','id,name');
    if($fields === false) {
        return false;
    }
    // build output (array of objects)
    $ret = Array();
    foreach ($fields AS $field) {
        $row = new object();
        $row->id = $field->id;
        $row->name = $field->name;
        // second query gets values if they exist
        $vals = get_record('trdiary_pdp_value','fieldid',$field->id,'userid',$userid);
        if ($vals !== false) {
            $row->valueid = $vals->id;
            $row->value = $vals->value;
        } else {
            $row->valueid = null;
            $row->value = null;
        }
        $ret[] = $row;
    }
    return $ret;
}

/**
 * Update the database with user additional fields based on form data
 *
 * @param int $userid ID of the user to update
 * @param int $activityid ID of the current trdiary instance
 * @param array $extrafields Array of additional fields, as returned 
 *                           by {@link get_pdp_extra_fields()}
 * @param object $fromform Submitted data in object form
 * @return boolean True if data could be updated, else false 
**/
function update_pdp_entry($userid, $activityid, $extrafields, $fromform) {
    // check for valid inputs
    if(!isset($userid) || !isset($activityid) || !isset($extrafields)
            || count($extrafields) < 1) {
        return false;
    }

    begin_sql();
    $todb = new object();
    $todb->userid = $userid;
    foreach ($extrafields AS $field) {
        $todb->fieldid = $field->id;
        $todb->id = $field->valueid; 
        $fieldref = 'extrafield'.$field->id;
        $todb->value = $fromform->$fieldref;
        // if value id is null, create record for the first time
        if ($todb->id === null) {
            if(!insert_record('trdiary_pdp_value', $todb, false)) {
                rollback_sql();
                return false;
            }
        } else {
            // otherwise update an existing record
            if(!update_record('trdiary_pdp_value', $todb)) {
                rollback_sql();
                return false;
            }
        }
    }
    commit_sql();
    return true;
}

/**
 * Get an array of reflective log fields for a given activity
 *
 * @param int $activity ID of the trdiary instance that the fields are in
 * @return mixed Array of objects containing reflective log fields or
 *               false if none found
**/
function get_reflog_fields($activityid) {
    global $CFG;
    $fields = get_records('trdiary_reflog_field','trdiaryid',$activityid,'id',
                          'id as fieldid,name');
    // no ref log fields stored in database for this activity
    if ($fields === false) {
        return false;
    }
    return $fields;
}

/**
 * Get an array of reflective log entries for a given user and activity
 *
 * @param int $userid ID of the owner of the reflective log entries
 * @param int $activity ID of the trdiary instance that the entries are in
 * @param int $entryid ID of a specific entry. If null returns all entries by 
 *                     the specified user
 * @return mixed Array of objects containing reflective log entries or
 *               false if none found
**/
function get_reflog_entries($userid, $activityid, $entryid=null) {
    global $CFG;
    $fields = get_reflog_fields($activityid);
    // no ref log fields stored in database for this activity
    if ($fields === false) {
        return false;
    } 

    if($entryid !== null) {
        $limitentry = "AND e.id='$entryid'";
    } else {
        $limitentry = '';
    }
    $sql = "SELECT v.id AS valueid, e.id, 
                timecreated, fieldid, value
            FROM {$CFG->prefix}trdiary_reflog_entry e
            JOIN {$CFG->prefix}trdiary_reflog_value v ON e.id = v.entryid
            WHERE trdiaryid='$activityid' AND userid='$userid'
            $limitentry
            ORDER BY timecreated DESC, entryid, valueid;"; 
    $entries = get_records_sql($sql);
    // no entries for this activity and user
    if ($entries === false) {
        return false;
    }

    $ret = Array();
    $entryid='';
    foreach ($entries AS $entry) {
        // when entryid changes start a new array element
        if($entryid != $entry->id) {
            $ret[$entry->id] = new object();
            $ret[$entry->id]->id = $entry->id;
            $ret[$entry->id]->timecreated = $entry->timecreated;
        }
        // look for each field and add to output array if found
        foreach ($fields AS $field) {
            if($field->fieldid == $entry->fieldid) {
                $entryfieldid = $entry->fieldid;
                $ret[$entry->id]->$entryfieldid = $entry->value;
            }
        }
        $entryid=$entry->id;    
    }
    return $ret;
}

/**
 * Creates reflective log entry given data from the new entry form
 * This involves adding entries to reflog_entry and reflog_values
 *
 * @param int $userid ID of the user who submitted an entry
 * @param int $activity ID of the activity instance where entry is created
 * @param array $fields Array of objects containing the fields use
 * @param object $fromform Object containing submitted form data
 * @return mixed Returns ID of new entry made, or false if insert fails
**/
function create_reflog_entry($userid, $activityid, $fields, $fromform) {
    // insert entry record
    $entrytodb = new object();
    $entrytodb->userid = $userid;
    $entrytodb->trdiaryid = $activityid;
    $entrytodb->timecreated = time();

    begin_sql();
    $entryid = insert_record('trdiary_reflog_entry', $entrytodb);

    if($entryid) {
        foreach ($fields AS $field) {
            $valtodb = new object();
            $valtodb->entryid = $entryid;
            $valtodb->fieldid = $field->fieldid;
            $fieldref = 'field'.$field->fieldid;
            $valtodb->value = $fromform->$fieldref;
            if(! insert_record('trdiary_reflog_value', $valtodb, false)) {
                rollback_sql();
                return false;
            }
        }
    } else {
        rollback_sql();
        return false;
    }
    commit_sql();
    return $entryid;
}

/**
 * Delete a reflective log entry and all associated values
 * 
 * @param int $entryid ID of the entry to delete
 * @return bool True if required records could be deleted, else false
 *
 **/
function delete_reflog_entry($entryid) {
    if(!isset($entryid)) {
        return false;
    }
    begin_sql();
    if (delete_records('trdiary_reflog_entry','id',$entryid)) {
        if(!delete_records('trdiary_reflog_value','entryid',$entryid)) {
            rollback_sql();
            return false;
        }
    }
    else {
        rollback_sql();
        return false;
    }
    commit_sql();
    return true;

}

/**
 * Update an existing reflective log entry
 *
 * @param int $entryid ID of the existing entry
 * @param object $fields Object returned by {@link get_reflog_fields()}
 *                       which consists of array of field objects
 * @param object $fromform Data from the submitted form to be used for update
 * @return boolean True if update succeeds, else false
**/
function update_reflog_entry($entryid, $fields, $fromform) {
    if(!$fields || count($fields) < 1) {
        return false;
    }

    begin_sql();
    foreach($fields AS $field) {
        $todb = new object();
        $fieldref = 'field'.$field->fieldid;
        $fieldid = 'field'.$field->fieldid.'id';
        $todb->id = $fromform->$fieldid;
        $todb->fieldid = $field->fieldid;
        $todb->entryid = $entryid;
        $todb->value = $fromform->$fieldref;
        if(!update_record('trdiary_reflog_value', $todb, false)) {
            rollback_sql();
            return false;
        }
    }
    commit_sql(); 
    return true;
}

/**
 * Creates contents of table listing users PDP skills
 *
 * @param int $userid ID of the user to display the skills for
 * @param int $activityid ID of the current Training diary instance
 * @return mixed Array used to display the table or false
**/
function build_pdp_table($userid, $activityid) {
    if(!isset($userid) || !isset($activityid)) {
        return false;
    }
    
    $pdp_skills = get_pdp_skills($userid, $activityid);
    if ($pdp_skills === false) {
        // this should not happen as new pdp skills should be generated by 
        // {@link create_pdp_skills()} before this is called
        error_log('Warning: create_pdp_skills() failed to auto-generate '
                 .'trdiary_pdp_skills entries for user '.$userid);
        return false;
    }

    $table->head = array('&nbsp;',get_string('strengthdevelop','trdiary'), 
                         get_string('priority','trdiary'));

    $compid = '';
    foreach ($pdp_skills AS $pdp_skill) {
        // since results are sorted by competency name, id will change when 
        // we switch to a new competency
        if($pdp_skill->compid != $compid) {
            $table->data[] = array("<strong>".$pdp_skill->compname."</strong>",
                                   '&nbsp;','&nbsp;');
        }
        $skillname = $pdp_skill->skillname;
        switch ($pdp_skill->isstrength) {
            case 1:
                $isstrength = get_string('strength', 'trdiary');
                break;
            case 2:
                $isstrength = get_string('areadevelop', 'trdiary');
                break;
            default:
                $isstrength = get_string('notset', 'trdiary');
        }
        switch ($pdp_skill->priority) {
            case 1:
                $priority = get_string('none', 'trdiary');
                break;
            case 2:
                $priority = get_string('low', 'trdiary');
                break;
            case 3:
                $priority = get_string('medium', 'trdiary');
                break;
            case 4:
                $priority = get_string('high', 'trdiary');
                break;
            default:
                $priority = get_string('notset', 'trdiary');
        }
        $table->data[] = array($skillname, $isstrength, $priority);

        $compid = $pdp_skill->compid;
    }
    return $table;
    
}

/**
 * Create contents of table listing users reflective log entries
 *
 * @param int $userid ID of user to display data for
 * @param int $activityid ID of current training diary instance
 * @param array $fields Table fields as an array of objects
 * @param boolean $datebasedlog True if table should include dates with entries
 * @param object $context Current context, for determining capabilities
 * @param boolean $userlink If true, include userid in add and edit links
 *                          Used to determine if user is editing their own entry
 *                          or if an admin is editing a student entry
 * @return mixed Contents of table or false if could not be generated
**/
function build_reflog_table($userid, $activityid, $fields, $datebasedlog, $context,
                            $userlink=false) {
    // get entries for table body
    $reflogs = get_reflog_entries($userid,$activityid);
    if($reflogs === false || count($fields) < 1) {
        // only print the table if there are existing entries and fields
        return false;
    }

    if ($datebasedlog) {
        $header = Array(get_string('datecreated','trdiary'));
    } else {
        $header = Array(get_string('entrynumber','trdiary'));
    }
    foreach($fields AS $field) {
        $header[] = $field->name;
    }
    if (has_capability('mod/trdiary:edit', $context)) {
        $header[] = get_string('edit','trdiary');
        $header[] = get_string('delete','trdiary');
    }
    $table->head = $header;


    if ($userlink) {
        $url = '&amp;u='.$userid;
    } else {
        $url = '';
    }

    $count = count($reflogs);
    foreach ($reflogs AS $reflog) {
        if ($datebasedlog) {
            $entryref = userdate($reflog->timecreated, get_string('strftimedatetime'));
        } else {
            $entryref = $count;
            $count -= 1;
        }
        $row = Array($entryref);
        // loop round fields, checking for log entries
        foreach ($fields AS $field) {
            $fieldid = $field->fieldid;
            if(isset($reflog->$fieldid)) {
                $row[] = $reflog->$fieldid;
            } else {
                $row[] = '&nbsp;';
            }
        }
        if (has_capability('mod/trdiary:edit', $context)) {
            $row[] = '<a href="editreflog.php?a='.$activityid.'&amp;e='
                     .$reflog->id.$url.'">'.get_string('edit', 'trdiary').'</a>';
            $row[] = '<a href="deletereflog.php?a='.$activityid.'&amp;e='
                     .$reflog->id.$url.'">'.get_string('delete', 'trdiary').'</a>';
        }
        $table->data[] = $row;
    }

    return $table;
       
}

/**
 * Create contents of table listing all users for tutor
 *
 * @param int $activityid ID of the current activity
 * @return mixed Table object or false if could not be generated
**/
function build_users_table($activityid, $courseid) {
    global $CFG;

    if(!isset($activityid)) {
        return false;
    }
    $header = array();
    $header[] = get_string('user','trdiary');
    $header[] = get_string('pdpskillsset','trdiary');
    $header[] = get_string('pdpprioritiesset','trdiary');
    $header[] = get_string('reflogs', 'trdiary');
    $header[] = '&nbsp;';
    $header[] = '&nbsp;';
    $table->head = $header;

    $context = get_context_instance(CONTEXT_MODULE, $courseid);
    $users = get_users_by_capability($context, 'mod/trdiary:edit', 'u.id, u.firstname, u.lastname, u.username');
    // no users - can't display a table
    if ($users === false || count($users) < 1) {
        return false;
    }

    // get user, pdp and reflog information
    $user_sql = "";
    $notfirst = false;
    foreach ($users as $user) {
        if ($notfirst) {
            $user_sql.=" ,";
        }else {
            $notfirst = true;
        }
        $user_sql .= "'".$user->id."'"; 
    }

    $tocomplete_sql = "SELECT userid, COUNT(DISTINCT s.id) AS skillcount FROM {$CFG->prefix}threesixty_carried_comp cc
                          JOIN {$CFG->prefix}threesixty_skill s ON s.id=cc.competencyid
                          JOIN {$CFG->prefix}threesixty_analysis a ON cc.analysisid=a.id
                          RIGHT OUTER JOIN {$CFG->prefix}user u ON a.userid = u.id
                          JOIN {$CFG->prefix}trdiary t ON t.threesixtyid = a.activityid
                          WHERE (t.id='$activityid' OR t.id IS NULL) 
                          AND userid IN ($user_sql) GROUP BY userid;";

    $completed_sql = "SELECT userid, SUM(CASE WHEN priority != 0 THEN 1 ELSE 0 END) AS prioritycount,
                          SUM(CASE WHEN isstrength != 0 THEN 1 ELSE 0 END) AS strengthcount
                          FROM {$CFG->prefix}trdiary_pdp_skill WHERE userid IN ($user_sql)
                          AND trdiaryid='$activityid'
                          GROUP BY userid;";

    $reflog_sql = "select userid, COUNT(id) AS entrycount from {$CFG->prefix}trdiary_reflog_entry WHERE userid IN ($user_sql) AND trdiaryid = '$activityid' GROUP BY userid;";

    //$user_info = get_records_sql($user_info_sql);
    $tocomplete = get_records_sql($tocomplete_sql);
    $completed = get_records_sql($completed_sql);
    $reflog = get_records_sql($reflog_sql);

    $data = array();
    foreach ($users AS $user) {
        $userinfo = $user->lastname.', '.$user->firstname.' ('.$user->username.')';
        $numskills = (isset($tocomplete[$user->id])) ? $tocomplete[$user->id]->skillcount : 0;
        $priorities = (isset($completed[$user->id])) ? $completed[$user->id]->prioritycount : 0;
        $strengths = (isset($completed[$user->id])) ? $completed[$user->id]->strengthcount : 0;
        $numentries = (isset($reflog[$user->id])) ? $reflog[$user->id]->entrycount : 0;
        $row = array();
        $row[] = $userinfo;
        $row[] = $strengths . ' / ' . $numskills;
        $row[] = $priorities . ' / ' . $numskills;
        $row[] = $numentries;
        // only show edit links if editing is possible for that user
        if($numskills != 0) {
            $row[] = '<a href="edit.php?a='.$activityid.'&amp;u='.$user->id.'">'
                .get_string('edit','trdiary').' '.get_string('pdpskills','trdiary').'</a>';
        } else {
            $row[] = '&nbsp;';
        }
        if($numentries != 0) {
            $row[] = '<a href="reflog.php?a='.$activityid.'&amp;u='.$user->id.'">'
                .get_string('edit','trdiary').' '.get_string('reflogs','trdiary').'</a>';
        } else {
            $row[] = '&nbsp;';
        }
        $data[] = $row;

    }
 
    $table->data = $data;
    return $table;
}

/**
 * Updates the values in the trdiary_pdp_skills table based on form submission
 *
 * @param int $userid ID of user to update
 * @param int $activityid ID of current trdiary instance
 * @param array $pdp_skills Array of skills for this user 
 *                          as returned by {@link get_pdp_skills()}
 * @param object $fromform Form submission data
 * @return boolean True if data could be updated, else false
**/
function update_user_skills($userid, $activityid, $pdp_skills, $fromform) {
    begin_sql();
    foreach($pdp_skills AS $pdp_skill) {
        $priority = $pdp_skill->priority;
        $isstrength = $pdp_skill->isstrength;
        $skillid = $pdp_skill->skillid;
        $selectref = 'select'.$skillid;
        if(isset($fromform->$selectref)) {
            $priority = $fromform->$selectref;
        }
        $radioref = 'isstrength_'.$skillid;
        if(isset($fromform->$radioref)) {
            $isstrength = $fromform->$radioref;
        }
        // not very efficient we are updating every skill
        // whether it is changed or not
        $todb = new object();
        $todb->id = $pdp_skill->id;
        $todb->trdiaryid = $activityid;
        $todb->userid = $userid;
        $todb->skillid = $skillid;
        $todb->priority = $priority;
        $todb->isstrength = $isstrength;
        if (!update_record('trdiary_pdp_skill', $todb)) {
            rollback_sql();
            return false;
        }
    }
    commit_sql();

    return true;
}

function create_reflog_reminder($userid, $activity) {
    if (!isset($userid) || !isset($activity)) {
        return false;
    }
    $activityid = $activity->id;
    $courseid = $activity->course;
    $logfreq = $activity->logfreq;
    $modname = 'trdiary';

    // don't create calendar events if logfreq not set
    if ($logfreq == 0 || trim($logfreq)=='' || !isset($logfreq)) {
        return false;
    }

    // calculate reminder date
    $eventdate = time() + $logfreq * 24 * 60 * 60; // $logfreq days from now

    $event->name = get_string('eventtitle', 'trdiary');
    $event->description = get_string('eventdesc', 'trdiary');
    $event->format = 'FORMAT_PLAIN';
    $event->courseid = $courseid;
    $event->groupid = 0;
    $event->userid = $userid;
    $event->modulename = $modname;
    $event->instance = $activityid;
    $event->eventtype = 'open';
    $event->timestart = $eventdate;
    $event->timeduration = 0;
    $event->visible = 1;

    if(add_event($event)) {
        // delete any events between now and new reminder
        $select = "userid='$userid' AND modulename='$modname' AND instance='$activityid'
            AND courseid='$courseid' AND TO_TIMESTAMP(timestart) > now() 
            AND timestart < $eventdate";
        delete_records_select('event', $select);
    }

    return true;
}
