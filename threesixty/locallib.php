<?php

require_once "$CFG->libdir/ddllib.php";

/**
 * List of competencies along with their skills.
 */
function threesixty_get_competency_listing($activityid)
{
    global $CFG;
    $ret = array();

    $sql = "SELECT s.id AS skillid, c.id AS competencyid, c.name, c.description, s.name AS skillname, c.showfeedback
              ,c.sortorder AS competencyorder, s.sortorder AS skillorder 
              FROM {$CFG->prefix}threesixty_skill s
  RIGHT OUTER JOIN {$CFG->prefix}threesixty_competency c ON s.competencyid = c.id
             WHERE c.activityid = $activityid
          ORDER BY c.sortorder, s.sortorder";

    if ($rs = get_recordset_sql($sql)) {
        while ($record = rs_fetch_next_record($rs)) {
            if (empty($ret[$record->competencyid])) {
                $competency = new object;
                $competency->id = $record->competencyid;
                $competency->name = $record->name;
                $competency->description = $record->description;
                $competency->showfeedback = ($record->showfeedback == 1);
                $competency->skills = $record->skillname;
                $ret[$competency->id] = $competency;
            }
            else {
                $ret[$record->competencyid]->skills .= ', ' . $record->skillname;
            }
        }
    }

    return $ret;
}

/**
 * Delete the given competency from the database.
 *
 * @param integer $competencyid  The ID of the competency record
 * @param boolean $intransaction True if there is already an active transation
 * @returns boolean              True if the operation has succeeded, false otherwise
 */
function threesixty_delete_competency($competencyid, $intransaction=false)
{
    if (!$intransaction) {
        begin_sql();
    }

    // Delete all dependent skills
    $skills = get_records('threesixty_skill', 'competencyid', $competencyid, '', 'id');
    if ($skills and count($skills) > 0) {
        foreach ($skills as $skill) {
            if (!threesixty_delete_skill($skill->id, true)) {
                if (!$intransaction) {
                    rollback_sql();
                }
                return false;
            }
        }
    }

    // Delete all dependent response competencies
    if (!delete_records('threesixty_response_comp', 'competencyid', $competencyid)) {
        if (!$intransaction) {
            rollback_sql();
        }
        return false;
    }

    // Delete competencies to be carried to the training diary
    if (!delete_records('threesixty_carried_comp', 'competencyid', $competencyid)) {
        if (!$intransaction) {
            rollback_sql();
        }
        return false;
    }

    // Perform the deletion
    if (!delete_records('threesixty_competency', 'id', $competencyid)) {
        if (!$intransaction) {
            rollback_sql();
        }
        return false;
    }

    if (!$intransaction) {
        commit_sql();
    }
    return true;
}

/**
 * Delete the given skill from the database.
 *
 * @param integer $skillid       The ID of the skill record
 * @param boolean $intransaction True if there is already an active transation
 * @returns boolean              True if the operation has succeeded, false otherwise
 */
function threesixty_delete_skill($skillid, $intransaction=false)
{
    if (!$intransaction) {
        begin_sql();
    }

    // Delete all dependent response skills
    if (!delete_records('threesixty_response_skill', 'skillid', $skillid)) {
        if (!$intransaction) {
            rollback_sql();
        }
        return false;
    }

    // Delete matching records in trdiary_pdp_skill
    $trdiarytable = new XMLDBTable('trdiary_pdp_skill');
    if (table_exists($trdiarytable)) {
        if (!delete_records('trdiary_pdp_skill', 'skillid', $skillid)) {
            if (!$intransaction) {
                rollback_sql();
            }
            return false;
        }
    }

    // Perform the deletion
    if (!delete_records('threesixty_skill', 'id', $skillid)) {
        if (!$intransaction) {
            rollback_sql();
        }
        return false;
    }

    if (!$intransaction) {
        commit_sql();
    }
    return true;
}

/**
 * Delete the given analysis from the database.
 *
 * @param integer $analysisid    The ID of the analysis record
 * @param boolean $intransaction True if there is already an active transation
 * @returns boolean              True if the operation has succeeded, false otherwise
 */
function threesixty_delete_analysis($analysisid, $intransaction=false)
{
    if (!$intransaction) {
        begin_sql();
    }

    // Delete all dependent responses
    $responses = get_records('threesixty_response', 'analysisid', $analysisid, '', 'id');
    if ($responses and count($responses) > 0) {
        foreach ($responses as $response) {
            if (!threesixty_delete_response($response->id, true)) {
                if (!$intransaction) {
                    rollback_sql();
                }
                return false;
            }
        }
    }

    // Delete all dependent carried_competencies
    if (!delete_records('threesixty_carried_comp', 'analysisid', $analysisid)) {
        if (!$intransaction) {
            rollback_sql();
        }
        return false;
    }

    // Delete all dependent respondent
    if (!delete_records('threesixty_respondent', 'analysisid', $analysisid)) {
        if (!$intransaction) {
            rollback_sql();
        }
        return false;
    }

    // Perform the deletion
    if (!delete_records('threesixty_analysis', 'id', $analysisid)) {
        if (!$intransaction) {
            rollback_sql();
        }
        return false;
    }

    if (!$intransaction) {
        commit_sql();
    }
    return true;
}

/**
 * Delete the given response from the database.
 *
 * @param integer $responseid    The ID of the response record
 * @param boolean $intransaction True if there is already an active transation
 * @returns boolean              True if the operation has succeeded, false otherwise
 */
function threesixty_delete_response($responseid, $intransaction=false)
{
    if (!$intransaction) {
        begin_sql();
    }

    // Delete all dependent response competencies
    if (!delete_records('threesixty_response_comp', 'responseid', $responseid)) {
        if (!$intransaction) {
            rollback_sql();
        }
        return false;
    }

    // Delete all dependent response skills
    if (!delete_records('threesixty_response_skill', 'responseid', $responseid)) {
        if (!$intransaction) {
            rollback_sql();
        }
        return false;
    }

    // Perform the deletion
    if (!delete_records('threesixty_response', 'id', $responseid)) {
        if (!$intransaction) {
            rollback_sql();
        }
        return false;
    }

    if (!$intransaction) {
        commit_sql();
    }
    return true;
}

/**
 * Delete the given respondent from the database.
 *
 * @param integer $respondentid  The ID of the respondent record
 * @param boolean $intransaction True if there is already an active transation
 * @returns boolean              True if the operation has succeeded, false otherwise
 */
function threesixty_delete_respondent($respondentid, $intransaction=false)
{
    if (!$intransaction) {
        begin_sql();
    }

    // Delete the dependent response if necessary
    if ($responseid = get_field('threesixty_response', 'id', 'respondentid', $respondentid)) {
        if (!threesixty_delete_response($responseid, true)) {
            if (!$intransaction) {
                rollback_sql();
            }
            return false;
        }
    }

    // Perform the deletion
    if (!delete_records('threesixty_respondent', 'id', $respondentid)) {
        if (!$intransaction) {
            rollback_sql();
        }
        return false;
    }

    if (!$intransaction) {
        commit_sql();
    }
    return true;
}

/**
 * List of skills and their competency.
 */
function threesixty_get_skill_names($activityid)
{
    global $CFG;

    $sql = "SELECT s.id, c.id AS competencyid, c.name AS competencyname, s.name AS skillname
              FROM {$CFG->prefix}threesixty_competency c
  RIGHT OUTER JOIN {$CFG->prefix}threesixty_skill s ON c.id = s.competencyid
             WHERE c.activityid = $activityid
          ORDER BY c.sortorder, s.sortorder";

    return get_records_sql($sql);
}

/**
 * List of competencyid and feedback.
 */
function threesixty_get_feedback($analysisid) {
    global $CFG;

    $sql = "SELECT trc.id, trc.competencyid, trc.feedback
            FROM {$CFG->prefix}threesixty_response tr
            JOIN {$CFG->prefix}threesixty_response_comp trc
                ON trc.responseid=tr.id
            WHERE tr.analysisid={$analysisid}";
            
    $ret = get_records_sql($sql);
    if ($ret) {
        return $ret;
    } else {
        return array();
    }
}

/**
 * List of scores set by the user as well as the name of the score.
 */
function threesixty_get_self_scores($analysisid, $competencyaverage, $typeid)
{
    global $CFG, $selfresponsetypes;

    $ret = new object();
    $ret->name = $selfresponsetypes[$typeid];
	$ret->type = 'self'.$typeid;
    $idcolumn = 's.id';
    $scorecolumn = 'rs.score';
    $competencyjoin = '';
    $groupbyclause = '';
    $orderbyclause = 'order by s.competencyid, s.sortorder';
    if ($competencyaverage) {
        $idcolumn = 'c.id';
        $scorecolumn = 'AVG(rs.score) AS score';
        $competencyjoin = "JOIN {$CFG->prefix}threesixty_competency c ON c.id = s.competencyid";
        $groupbyclause = 'GROUP BY c.id';
        $orderbyclause = '';
    }

    $sql = "SELECT $idcolumn, $scorecolumn
              FROM {$CFG->prefix}threesixty_respondent rp
  RIGHT OUTER JOIN {$CFG->prefix}threesixty_response r ON r.respondentid = rp.id
              JOIN {$CFG->prefix}threesixty_response_skill rs ON r.id = rs.responseid
  RIGHT OUTER JOIN {$CFG->prefix}threesixty_skill s ON s.id = rs.skillid
   $competencyjoin
             WHERE (r.analysisid IS NULL OR r.analysisid = $analysisid) AND
                   (r.timecompleted IS NULL or r.timecompleted > 0) AND
                   rp.uniquehash IS NULL AND
                   rp.type = $typeid
    $groupbyclause $orderbyclause";

    if (!$ret->records = get_records_sql($sql)) {
        $ret->records = array();
    }

    return $ret;
}

/**
 * Returns true if the given activity has been completed by the given user.
 */
function threesixty_is_completed($activityid, $userid)
{
    global $CFG;
/*
    $sql = "SELECT r.id
              FROM {$CFG->prefix}threesixty_analysis a
              JOIN {$CFG->prefix}threesixty_response r ON r.analysisid = a.id
             WHERE a.activityid = $activityid AND a.userid = $userid AND
                   r.timecompleted > 0 AND r.d IS NULL";
    return get_records_sql($sql) ? true : false;*/
  return true;
}

/**
 * Return a list of users having submitted a response in this activity.
 *
 * @param object $activity Record from the threesixty table
 * @returns an array of user records.
 */
 function threesixty_users($activity){
   global $CFG;

    $sql = "SELECT u.id, u.firstname, u.lastname
              FROM {$CFG->prefix}threesixty_analysis a
              JOIN {$CFG->prefix}threesixty_response r ON r.analysisid = a.id
              JOIN {$CFG->prefix}user u ON a.userid = u.id
             WHERE a.activityid = $activity->id AND
                   r.timecompleted > 0";
  $records = get_records_sql($sql);
  return $records;
}
/**
 * Returns a list of all of the users who are eligible to participate in the
 * 360 activity.
 * @author eleanor.martin
 * @param <type> $context
 * @return an array of user records with id, firstname, lastname.
 */
 function threesixty_get_possible_participants($context, $sort="u.lastname"){
   $fields = 'u.id, u.firstname, u.lastname';
   // params are $context, $capability, $fields, $sort, $limitfrom, $limitnum, $groups, $exceptions, $doanything
   // doanything set to false so admins are not brought back by default
   $users = get_users_by_capability($context, 'mod/threesixty:participate', $fields, $sort, '', '', '', '', false);
   return $users;
 }
 /**
 * Return an html table listing the users.
 *
 * @param object $activity Record from the threesixty table
 * @param string $url      URL of the page to open once the 'userid' param has been added
 * @returns string The HTML to print out on the page (either a table or error message)
 */
function threesixty_user_listing($activity, $url)
{
    global $CFG;
    
    if ($records = threesixty_users($activity)) {
        $table = new object();
        $table->head = array(get_string('name'));
        $table->data = array();

        foreach ($records as $r) {
            $name = format_string(fullname($r));
            $selectlink = "<a href=\"$url&amp;userid=$r->id\">$name</a>";

            $table->data[] = array($selectlink);
        }

        return get_string('selectuser', 'threesixty').print_table($table, true);
    }

    return get_string('nousersfound', 'threesixty');
}

/**
 * Return the heading to print out to show the currently selected user.
 *
 * @param object $user     Record from the user table
 * @param int    $courseid ID of the current course
 * @param string $url      URL of the current page without the userid parameter
 */
function threesixty_selected_user_heading($user, $courseid, $url, $selectanother=true)
{
    global $CFG;

    $name = format_string(fullname($user));

    $data = new object();
    $data->fullname = "<a href=\"$CFG->wwwroot/user/view.php?id=$user->id&amp;course=$courseid\">$name</a>";
    $data->url = $url;

    if ($selectanother) {
        $text = get_string('selecteduser', 'threesixty', $data);
    } else {
        $text = get_string('reportforuser', 'threesixty', $data);
    }

    return print_heading($text, '', 2, 'main', true);
}


/**
 * Return the page where the first incomplete competency is or 1 if it's complete.
 */
function threesixty_get_first_incomplete_competency($activityid, $userid, $respondent)
{
    global $CFG;

    $respondentclause = 'r.respondentid IS NULL';
    if ($respondent != null) {
        $respondentclause = "r.respondentid = $respondent->id";
    }

    if (!$response = get_record_sql("SELECT r.id
                                       FROM {$CFG->prefix}threesixty_analysis a
                                       JOIN {$CFG->prefix}threesixty_response r ON r.analysisid = a.id
                                       WHERE a.activityid = $activityid AND a.userid = $userid AND
                                             $respondentclause AND r.timecompleted = 0")) {
        return 1; // activity is either not started or completed already
    }

    $sql = "SELECT c.id, c.sortorder
              FROM {$CFG->prefix}threesixty_response r
              JOIN {$CFG->prefix}threesixty_response_skill rs ON rs.responseid = r.id
  RIGHT OUTER JOIN {$CFG->prefix}threesixty_skill s ON rs.skillid = s.id
              JOIN {$CFG->prefix}threesixty_competency c ON c.id = s.competencyid
             WHERE (r.id IS NULL or r.id = $response->id) AND (score IS NULL OR score = 0)
          ORDER BY c.sortorder";

    if ($rs = get_recordset_sql($sql, 0, 1) and $record = rs_fetch_record($rs)) {
        $competencyid = $record->id;

        // Figure out which page this competency is in
        return $record->sortorder+1;
    }

    // All skills have been scored, form has not been submitted, go to last page
    return count_records('threesixty_competency', 'activityid', $activityid);
}

function threesixty_get_average_skill_scores($analysisid, $respondenttype, $competencyaverage)
{
    global $CFG, $respondenttypes;

    $ret = new object();

    $fromclause = "FROM {$CFG->prefix}threesixty_response r";
    $wherefragment = '';

    if ($respondenttype !== false) {
        $fromclause = "FROM {$CFG->prefix}threesixty_respondent rp
           RIGHT OUTER JOIN {$CFG->prefix}threesixty_response r ON r.respondentid = rp.id";
        $wherefragment = "AND rp.uniquehash IS NOT NULL AND rp.type = $respondenttype";

        $ret->name = $respondenttypes[$respondenttype] . ' ' . get_string('filter:average', 'threesixty');
		$ret->type = 'type'.$respondenttype;
    }
    else {
        $ret->name = get_string('filter:average', 'threesixty');
        $ret->type = get_string('filter:average', 'threesixty');
    }

    $idcolumn = 's.id';
    $competencyjoin = '';
    if ($competencyaverage) {
        $idcolumn = 'c.id';
        $competencyjoin = "JOIN {$CFG->prefix}threesixty_competency c ON s.competencyid = c.id";
    }

    $sql = "SELECT $idcolumn, AVG(rs.score) AS score
              $fromclause
              JOIN {$CFG->prefix}threesixty_response_skill rs ON r.id = rs.responseid
  RIGHT OUTER JOIN {$CFG->prefix}threesixty_skill s ON s.id = rs.skillid
   $competencyjoin
             WHERE (r.analysisid IS NULL OR r.analysisid = $analysisid) AND
                   (r.timecompleted IS NULL or r.timecompleted > 0)
                   $wherefragment
          GROUP BY $idcolumn";

    if (!$ret->records = get_records_sql($sql)) {
        $ret->records = array();
    }

    return $ret;
}
/*
 * Redo the sort orders of the competencies in a given activity.
 *
 * @param $activityid - the id of the activity to reorder the competencies for.
 */
function threesixty_reorder_competencies($activityid){
  //Get the remaining competencies, ordered correctly, and reset the sortorder from 0.
  $competencies = get_records('threesixty_competency', 'activityid', $activityid, 'sortorder');
  if($competencies){
    $neworder = 0;
    foreach($competencies as $competency){
      if($competency->sortorder != $neworder){
        $competency->sortorder = $neworder;
        update_record('threesixty_competency', $competency);
      }
      $neworder++;
    }
  }
}