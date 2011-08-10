<?php

/**
 * Process a click from a user claiming that the user code did not
 * pick up the right email address. This will delete that respondent
 * and the associated response record if any.
 *
 * @author  Francois Marier <francois@catalyst.net.nz>
 * @package mod/threesixty
 */

require_once '../../config.php';
require_once 'locallib.php';

$code = required_param('code', PARAM_ALPHANUM); // unique hash

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
if (!$course = get_record('course', 'id', $activity->course)) {
    error('Course is misconfigured');
}
if (!$cm = get_coursemodule_from_instance('threesixty', $activity->id, $course->id)) {
    error('Course Module ID was incorrect');
}

add_to_log($course->id, 'threesixty', 'wrongemail', "wrongemail.php?code=$code", $activity->id);

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
if (threesixty_delete_respondent($respondent->id)) {
    error_log("threesixty: user claims the response code doesn't match their email address -- deleted $respondent->email from (analysisid=$analysis->id)");
}
else {
    error_log("threesixty: user claims the response code doesn't match their email address -- could not delete $respondent->email (analysisid=$analysis->id)");
}

print_box(get_string('adminnotified', 'threesixty'));

print_footer($course);
