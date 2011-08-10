<?php

require_once '../../config.php';
require_once 'locallib.php';

$a = required_param('a', PARAM_INT); // activity instance ID
$c = required_param('c', PARAM_INT); // competency ID
$confirm = optional_param('confirm', 0, PARAM_INT);  // commit the operation?

if (!$activity = get_record('threesixty', 'id', $a)) {
    error('Activity instance is incorrect: '. $a);
}
if (!$course = get_record('course', 'id', $activity->course)) {
    error('Course is misconfigured');
}
if (!$cm = get_coursemodule_from_instance('threesixty', $activity->id, $course->id)) {
    error('Course Module ID was incorrect');
}
if (!$competency = get_record('threesixty_competency', 'id', $c)) {
    error('Competency ID was incorrect');
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

require_login($course->id, false, $cm);
require_capability('mod/threesixty:manage', $context);

$returnurl = "edit.php?a=$activity->id&amp;section=competencies";

// Header
$strthreesixtys = get_string('modulenameplural', 'threesixty');
$strthreesixty  = get_string('modulename', 'threesixty');

$navlinks = array();
$navlinks[] = array('name' => $strthreesixtys, 'link' => "index.php?id=$course->id", 'type' => 'activity');
$navlinks[] = array('name' => format_string($activity->name), 'link' => $returnurl, 'type' => 'activityinstance');

$title = get_string('addnewcompetency', 'threesixty');
if ($competency != null) {
    $title = $competency->name;
}
$navlinks[] = array('name' => format_string($title), 'link' => '', 'type' => 'activityinstance');
$navigation = build_navigation($navlinks);

if ($confirm) {

    if (threesixty_delete_competency($competency->id)) {
        threesixty_reorder_competencies($activity->id);
        add_to_log($course->id, 'threesixty', 'delete competency', "deletecompentency.php?a=$activity->id&amp;c=$competency->id", $activity->id, $cm->id);
    }
    else {
        print_error('error:cannotdeletecompetency', 'threesixty', $returnurl);
    }

    redirect($returnurl);
}

print_header_simple(format_string($activity->name . " - $title"), '', $navigation, '', '', true,
                    update_module_button($cm->id, $course->id, $strthreesixty), navmenu($course, $cm));

// Ask for confirmation
notice_yesno('<b>'.format_string($competency->name).'</b><blockquote>'.
             format_string($competency->description).'</blockquote><p>'.
             get_string('areyousuredelete', 'threesixty', get_string('competency', 'threesixty')).'</p>',
             "deletecompetency.php?a=$activity->id&amp;c=$competency->id&amp;confirm=1", $returnurl);

print_footer($course);
