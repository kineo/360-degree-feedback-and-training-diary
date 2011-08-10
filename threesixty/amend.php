<?php

/**
 * Allows a teacher/admin to edit the scores entered by a student
 *
 * @author  Francois Marier <francois@catalyst.net.nz>
 * @package mod/threesixty
 */

require_once '../../config.php';
require_once 'amend_form.php';
require_once 'locallib.php';

$a      = required_param('a', PARAM_INT);  // threesixty instance ID
$typeid = required_param('typeid', PARAM_INT); // the type of the response
$userid = optional_param('userid', 0, PARAM_INT);

if (!$activity = get_record('threesixty', 'id', $a)) {
    error('Course module is incorrect');
}
if (!$course = get_record('course', 'id', $activity->course)) {
    error('Course is misconfigured');
}
if (!$cm = get_coursemodule_from_instance('threesixty', $activity->id, $course->id)) {
    error('Course Module ID was incorrect');
}
$user = null;
if ($userid > 0 and !$user = get_record('user', 'id', $userid, '', 'id, firstname, lastname')) {
    error('Invalid User ID');
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

require_login($course, true, $cm);
require_capability('mod/threesixty:view', $context);
require_capability('mod/threesixty:edit', $context);

$baseurl = "amend.php?a=$activity->id&typeid=$typeid";

$mform = null;
$usertable = null;
if (isset($user)) {

    $currenturl = "$baseurl&amp;userid=$user->id";
    $returnurl = "view.php?a=$activity->id";

    $skillnames = threesixty_get_skill_names($activity->id);

    if (!$analysis = get_record('threesixty_analysis', 'activityid', $activity->id, 'userid', $user->id)) {
        print_error('error:nodataforuserx', 'threesixty', $returnurl, fullname($user));
    }
    if (!$respondent = get_record('threesixty_respondent', 'analysisid', $analysis->id, 'type', $typeid, 'uniquehash', null)){
        print_error('error:nodataforuserx', 'threesixty', $returnurl, fullname($user));
    }
    if (!$response = get_record('threesixty_response', 'analysisid', $analysis->id, 'respondentid', $respondent->id)) {
        print_error('error:nodataforuserx', 'threesixty', $returnurl, fullname($user));
    }
    if (!$response->timecompleted) {
        print_error('error:userxhasnotsubmitted', 'threesixty', $returnurl, fullname($user));
    }
    if (!$selfscores = threesixty_get_self_scores($analysis->id, false, $typeid)) {
        print_error('error:nodataforuserx', 'threesixty', $returnurl, fullname($user));
    }

    $mform =& new mod_threesity_amend_form(null, compact('a', 'skillnames', 'userid', 'typeid'));

    if ($mform->is_cancelled()){
        redirect($baseurl);
    }

    if ($fromform = $mform->get_data()) {

        $returnurl .= "&amp;userid=$user->id";

        if (!empty($fromform->submitbutton)) {
            $errormsg = save_changes($fromform, $response->id, $skillnames);
            if (!empty($errormsg)) {
                print_error('error:cannotsavescores', 'threesixty', $currenturl, $errormsg);
            }

            redirect($returnurl);
        }
        else {
            print_error('error:unknownbuttonclicked', 'threesixty', $returnurl);
        }
    }

    add_to_log($course->id, 'threesixty', 'amend', $currenturl, $activity->id);
}

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
$currenttab = 'activity';
$section = 'scores';
include 'tabs.php';

if (isset($mform)) {
    print threesixty_selected_user_heading($user, $course->id, 'profiles.php?a='.$activity->id);

    set_form_data($mform, $selfscores);
    $mform->display();
}
else {
    print threesixty_user_listing($activity, $baseurl);
}

print_footer($course);

function set_form_data($mform, $scores)
{
    $toform = array();

    if (!empty($scores->records) and count($scores->records) > 0) {
        foreach ($scores->records as $score) {
            $toform["radioarray_{$score->id}[score_{$score->id}]"] = $score->score;
        }
    }

    $mform->set_data($toform);
}

function save_changes($formfields, $responseid, $skills)
{
    global $CFG;

    foreach ($skills as $skill) {
        $arrayname = "radioarray_$skill->id";
        if (empty($formfields->$arrayname)) {
            error_log("threesixty: $arrayname is missing from the submitted form fields");
            return get_string('error:formsubmissionerror', 'threesixty');
        }
        $a = $formfields->$arrayname;

        $scorename = "score_$skill->id";
        $scorevalue = 0;
        if (!isset($a[$scorename])) {
            error_log("threesixty: $scorename is missing from the submitted form fields");
            return get_string('error:formsubmissionerror', 'threesixty');
        }
        else {
            $scorevalue = $a[$scorename];
        }

        // Save this skill score in the database
        if ($score = get_record('threesixty_response_skill', 'responseid', $responseid, 'skillid', $skill->id)) {
            $newscore = new object();
            $newscore->id = $score->id;
            $newscore->score = $scorevalue;

            if (!update_record('threesixty_response_skill', $newscore)) {
                error_log("threesixty: could not update score for skill $skill->id");
                return get_string('error:databaseerror', 'threesixty');
            }
        }
        else {
            //error_log("threesixty: could not find the response_skill record for skill $skill->id");
            //return get_string('error:databaseerror', 'threesixty');
            $newscore = new object();
            $newscore->skillid = $skill->id;
            $newscore->score = $scorevalue;
            $newscore->responseid = $responseid;
            if(!insert_record('threesixty_response_skill', $newscore)) {
              error_log("threesixty: could not create record for skill $skill->id");
              return get_string('error:databaseerror', 'threesixty');
            }
        }
    }

    return '';
}
