<?php

/**
 * This page prints a particular instance of threesixty by redirecting
 * to the most appropriate page based on the user's capabilities.
 *
 * @author  Francois Marier <francois@catalyst.net.nz>
 * @package mod/threesixty
 */

require_once '../../config.php';

$id     = optional_param('id', 0, PARAM_INT); // course_module ID, or
$a      = optional_param('a', 0, PARAM_INT);  // threesixty instance ID
$userid = optional_param('userid', 0, PARAM_INT);

if ($id) {
    if (!$cm = get_coursemodule_from_id('threesixty', $id)) {
        error('Course Module ID was incorrect');
    }
    if (!$course = get_record('course', 'id', $cm->course)) {
        error('Course is misconfigured');
    }
    if (!$activity = get_record('threesixty', 'id', $cm->instance)) {
        error('Course module is incorrect');
    }
}
else if ($a) {
    if (!$activity = get_record('threesixty', 'id', $a)) {
        error('Course module is incorrect');
    }
    if (!$course = get_record('course', 'id', $activity->course)) {
        error('Course is misconfigured');
    }
    if (!$cm = get_coursemodule_from_instance('threesixty', $activity->id, $course->id)) {
        error('Course Module ID was incorrect');
    }
}
else {
    error('You must specify a course_module ID or an instance ID');
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

require_login($course, true, $cm);
require_capability('mod/threesixty:view', $context);

if (has_capability('mod/threesixty:viewreports', $context)) {
    redirect("$CFG->wwwroot/mod/threesixty/report.php?a=$activity->id&amp;userid=$userid");
}

/*if ($analysis = get_record('threesixty_analysis', 'activityid', $activity->id, 'userid', $USER->id)) {
    if ($response = get_record('threesixty_response', 'analysisid', $analysis->id, 'uniquehash', null, 'typeid', 0)) {
        if ($response->timecompleted > 0) {
            // Activity is finished/completed
            redirect("$CFG->wwwroot/mod/threesixty/respondents.php?a=$activity->id");
        }
    }
}*/

//redirect("$CFG->wwwroot/mod/threesixty/score.php?a=".$activity->id."&typeid=0");
redirect("$CFG->wwwroot/mod/threesixty/profiles.php?a=$activity->id");