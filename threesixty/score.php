<?php

/**
 * Allows a student (or an external person using a code) to assess
 * skills accross competencies.
 *
 * @author  Francois Marier <francois@catalyst.net.nz>
 * @package mod/threesixty
 */

require_once '../../config.php';
require_once 'locallib.php';
require_once 'score_form.php';

$a    = optional_param('a', 0, PARAM_INT);  // threesixty instance ID
$code = optional_param('code', '', PARAM_ALPHANUM); // unique hash
$page = optional_param('page', 0, PARAM_INT); // page number
$typeid = optional_param('typeid', 0, PARAM_INT); //type of response

$respondent = null;
$analysis = null;
$activity = null;
$user = null;
$userid = 0;

$externalrespondent = !empty($code);

if ($externalrespondent) {
    // External respondent
    if (!$respondent = get_record('threesixty_respondent', 'uniquehash', $code)) {
        error_log("threesixty: Invalid response hash from {$_SERVER['REMOTE_ADDR']}");
        print_error('error:invalidcode', 'threesixty');
    }
    if (!$analysis = get_record('threesixty_analysis', 'id', $respondent->analysisid)) {
        error('Analysis ID is incorrect');
    }
    if (!$activity = get_record('threesixty', 'id', $analysis->activityid)) {
        error('Course module is incorrect');
    }
    if (!$user = get_record('user', 'id', $analysis->userid, '', 'id, firstname, lastname')) {
        error('Invalid User ID');
    }
}
elseif ($a > 0) {
    // Logged-in respondent
    if (!$activity = get_record('threesixty', 'id', $a)) {
        error('Course module is incorrect');
    }

    $userid = optional_param('userid', $USER->id, PARAM_INT);
    if (!$user = get_record('user', 'id', $userid, '', 'id, firstname, lastname')) {
        error('Invalid User ID');
    }
   
    if ($analysis = get_record('threesixty_analysis', 'userid', $userid, 'activityid', $a)) {
      $respondent = get_record('threesixty_respondent', 'analysisid', $analysis->id, 'type', $typeid, 'uniquehash', null);
    }
}
else{
    // We need either $a or $code to be defined
    error('Missing activity ID');
}

if (!$course = get_record('course', 'id', $activity->course)) {
    error('Course is misconfigured');
}
if (!$cm = get_coursemodule_from_instance('threesixty', $activity->id, $course->id)) {
    error('Course Module ID was incorrect');
}

if (!$externalrespondent) {
    // Capability checks only relevant to logged-in users
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    require_login($course, true, $cm);
    require_capability('mod/threesixty:view', $context);

    if ($USER->id == $user->id) {
        require_capability('mod/threesixty:participate', $context);
    }
    else {
        require_capability('mod/threesixty:viewreports', $context);
    }
}

// Set URLs based on logged-in v. loginless mode
$cancelurl = '';
$baseurl = "score.php";
if (!$externalrespondent) {
    $baseurl .= "?a=$activity->id&amp;userid=$user->id&amp;typeid=$typeid";
    $cancelurl = "$CFG->wwwroot/course/view.php?id=$COURSE->id";
}
else {
    $baseurl .= "?code=$code";
}
$currenturl = "$baseurl&amp;page=$page";


if ($page < 1) {
    $page = threesixty_get_first_incomplete_competency($activity->id, $user->id, $respondent);
}

$nbpages = null;
$mform = null;
$fromform = null;

if ($competency = get_competency_details($page, $activity->id, $user->id, $respondent)) {
    $nbpages = count_records('threesixty_competency', 'activityid', $activity->id);
    $mform =& new mod_threesity_score_form(null, compact('a', 'code', 'competency', 'page', 'nbpages', 'userid', 'typeid'));
  
    if ($mform->is_cancelled()){
        redirect($cancelurl);
    }

    $fromform = $mform->get_data();
}
elseif ($page > 1) {
    print_error('error:invalidpagenumber', 'threesixty');
}

if ($fromform) {
    if (!empty($fromform->buttonarray['previous'])) { // Previous button
        $errormsg = save_changes($fromform, $activity->id, $user->id, $competency, false, $respondent);
        if (!empty($errormsg)) {
            print_error('error:cannotsavescores', 'threesixty', $currenturl, $errormsg);
        }

        $newpage = max(1, $page - 1);
        redirect("$baseurl&amp;page=$newpage");
    }
    elseif (!empty($fromform->buttonarray['next'])) { // Next button
        $errormsg = save_changes($fromform, $activity->id, $user->id, $competency, false, $respondent);
        if (!empty($errormsg)) {
            print_error('error:cannotsavescores', 'threesixty', $currenturl, $errormsg);
        }

        $newpage = min($nbpages, $page + 1);
        redirect("$baseurl&amp;page=$newpage");
    }
    elseif (!empty($fromform->buttonarray['finish'])) {
        $errormsg = save_changes($fromform, $activity->id, $user->id, $competency, true, $respondent);
        if (!empty($errormsg)) {
            print_error('error:cannotsavescores', 'threesixty', $currenturl, $errormsg);
        }

        if (!$externalrespondent) {
            redirect("view.php?a=$activity->id");
        }
        else {
            redirect("thankyou.php?a=$activity->id");
        }
    }
    else {
        print_error('error:unknownbuttonclicked', 'threesixty', $cancelurl);
    }
}

add_to_log($course->id, 'threesixty', 'score', $currenturl, $activity->id);

// Header
$strthreesixtys = get_string('modulenameplural', 'threesixty');
$strthreesixty  = get_string('modulename', 'threesixty');

$navlinks = array();
$navlinks[] = array('name' => $strthreesixtys, 'link' => "index.php?id=$course->id", 'type' => 'activity');
$navlinks[] = array('name' => format_string($activity->name), 'link' => '', 'type' => 'activityinstance');

$navigation = build_navigation($navlinks);

print_header_simple(format_string($activity->name), '', $navigation, '', '', true,
                    update_module_button($cm->id, $course->id, $strthreesixty), navmenu($course, $cm));

// Main content
if (!$externalrespondent) {
    $currenttab = 'activity';
    $section = null;
    include 'tabs.php';
}
else {
    $message = get_string('respondentwelcome', 'threesixty', format_string($respondent->email));
    if ($competency->locked) {
        $message .= get_string('thankyoumessage', 'threesixty');
    }
    else {
        $message .= get_string('respondentwarning', 'threesixty', "wrongemail.php?code=$code");
        $message .= get_string('respondentinstructions', 'threesixty');
        $message .= "<p>".get_string('respondentindividual', 'threesixty', $user->firstname." ".$user->lastname)."</p>";
    }
    print_box($message);
}
if ($mform) {
    set_form_data($mform, $competency);
    $mform->display();
}
else {
    print_string('nocompetencies', 'threesixty');
}

print_footer($course);

function get_competency_details($page, $activityid, $userid, $respondent)
{
    global $CFG;

    if ($rs = get_recordset('threesixty_competency', 'activityid', $activityid, 'sortorder', '*', $page - 1, 1)) {
        if (!$record = rs_fetch_record($rs)) {
            return false;
        }

        $respondentclause = 'r.respondentid IS NULL';
        if ($respondent != null) {
            $respondentclause = "r.respondentid = $respondent->id";
        }
        $responsesql = "SELECT r.id AS responseid, c.feedback AS competencyfeedback,
                                           r.timecompleted AS timecompleted
                                      FROM {$CFG->prefix}threesixty_analysis a
                           LEFT OUTER JOIN {$CFG->prefix}threesixty_response r ON a.id = r.analysisid
                           LEFT OUTER JOIN {$CFG->prefix}threesixty_response_comp c ON c.responseid = r.id
                                                                                    AND c.competencyid = $record->id
                                     WHERE a.userid = $userid AND a.activityid = $activityid  AND
                                           $respondentclause";

        $response = get_record_sql($responsesql);

        if ($response and !empty($response->competencyfeedback)) {
            $record->feedback = $response->competencyfeedback;
        }

        $record->locked = false;
        if ($response and !empty($response->timecompleted)) {
            $record->locked = true;
        }

        // Get skill descriptions
        $record->skills = get_records('threesixty_skill', 'competencyid', $record->id, 'sortorder',
                                      'id, name, description, 0 AS score');

        if ($record->skills and $response and $response->responseid != null) {
            // Get scores
            $sql = "SELECT s.id, r.score
                      FROM {$CFG->prefix}threesixty_skill s
                      JOIN {$CFG->prefix}threesixty_response_skill r ON s.id = r.skillid
                     WHERE s.competencyid = $record->id AND r.responseid = $response->responseid";

            if ($scores = get_records_sql($sql)) {
                foreach ($scores as $s) {
                    $record->skills[$s->id]->score = $s->score;
                }
            }
        }

        return $record;
    }
    return false;
}

function set_form_data($mform, $competency)
{
    $toform = array();

    if (!empty($competency->feedback)) {
        $toform['feedback'] = $competency->feedback;
    }

    if (!empty($competency->skills) and count($competency->skills) > 0) {
        foreach ($competency->skills as $skill) {
            $toform["radioarray_{$skill->id}[score_{$skill->id}]"] = $skill->score;
        }
    }

    $mform->set_data($toform);
}

function save_changes($formfields, $activityid, $userid, $competency, $finished, $respondent)
{
    global $CFG;

    if ($competency->locked) {
        // No changes are saved for responses which have been submitted already
        return '';
    }

    if (!$analysis = get_record('threesixty_analysis', 'activityid', $activityid, 'userid', $userid)) {
        $analysis = new object();
        $analysis->activityid = $activityid;
        $analysis->userid = $userid;

        if (!$analysis->id = insert_record('threesixty_analysis', $analysis)) {
            error_log('threesixty: could not insert new analysis record');
            return get_string('error:databaseerror', 'threesixty');
        }
    }

    $respondentid = null;
    if ($respondent == null) {
      $respondent = new object();
      $respondent->analysisid = $analysis->id;
      $respondent->type = $formfields->typeid;
      if(!$respondent->id = insert_record('threesixty_respondent', $respondent)){
        error_log('threesixty: could not insert new respondent record');
        return get_string('error:databaseerror', 'threesixty');
      }
    }
    $respondentid = $respondent->id;
    if (!$response = get_record('threesixty_response', 'analysisid', $analysis->id, 'respondentid', $respondentid)) {
        $response = new object();
        $response->analysisid = $analysis->id;
        $response->respondentid = $respondentid;

        if (!$response->id = insert_record('threesixty_response', $response)) {
            error_log('threesixty: could not insert new response record');
            return get_string('error:databaseerror', 'threesixty');
        }
    }

    if (!empty($competency->skills)) {
        foreach ($competency->skills as $skill) {
            $arrayname = "radioarray_$skill->id";
            if (empty($formfields->$arrayname)) {
                error_log("threesixty: $arrayname is missing from the submitted form fields");
                return get_string('error:formsubmissionerror', 'threesixty');
            }
            $a = $formfields->$arrayname;

            $scorename = "score_$skill->id";
            $scorevalue = 0;
            if (empty($a[$scorename])) {
                // Choosing "Not set" will clear the existing value
            }
            else {
                $scorevalue = $a[$scorename];
            }

            // Save this skill score in the database
            if ($score = get_record('threesixty_response_skill', 'responseid', $response->id, 'skillid', $skill->id)) {
                $newscore = new object();
                $newscore->id = $score->id;
                $newscore->score = $scorevalue;

                if (!update_record('threesixty_response_skill', $newscore)) {
                    error_log("threesixty: could not update score for skill $skill->id");
                    return get_string('error:databaseerror', 'threesixty');
                }
            }
            else {
                $score = new object();
                $score->responseid = $response->id;
                $score->skillid = $skill->id;
                $score->score = $scorevalue;

                if (!$score->id = insert_record('threesixty_response_skill', $score)) {
                    error_log("threesixty: could not insert score for skill $skill->id");
                    return get_string('error:databaseerror', 'threesixty');
                }
            }
        }
    }

    if (isset($formfields->feedback)) {
        // Save this competency score in the database
        if ($comp = get_record('threesixty_response_comp', 'responseid', $response->id, 'competencyid', $competency->id)) {
            $newcomp = new object();
            $newcomp->id = $comp->id;
            $newcomp->feedback = $formfields->feedback;

            if (!update_record('threesixty_response_comp', $newcomp)) {
                error_log("threesixty: could not update score for competency $competency->id");
                return get_string('error:databaseerror', 'threesixty');
            }
        }
        else {
            $comp = new object();
            $comp->responseid = $response->id;
            $comp->competencyid = $competency->id;
            $comp->feedback = $formfields->feedback;

            if (!$comp->id = insert_record('threesixty_response_comp', $comp)) {
                error_log("threesixty: could not insert score for competency $competency->id");
                return get_string('error:databaseerror', 'threesixty');
            }
        }
    }

    if ($finished) {

        $skills = get_records_sql("SELECT s.id FROM {$CFG->prefix}threesixty_competency c
                                              JOIN {$CFG->prefix}threesixty_skill s ON s.competencyid = c.id
                                             WHERE c.activityid = '$activityid';");

        $scores = get_records_sql("SELECT skillid,score FROM {$CFG->prefix}threesixty_response_skill
                                    WHERE responseid = '$response->id';");

        // Check that all of the scores have been set
        foreach ($skills as $skillid => $skill) {
            if (!isset($scores[$skillid])) {
                // Score is not set 
                return get_string('error:allskillneedascore', 'threesixty');
            }
        }

        $newresponse = new object();
        $newresponse->id = $response->id;
        $newresponse->timecompleted = time();
        if (!update_record('threesixty_response', $newresponse)) {
            error_log('threesixty: could not update the timecompleted field of the response');
            return get_string('error:databaseerror', 'threesixty');
        }
    }

    return '';
}

