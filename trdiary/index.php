<?php

/**
 * This page lists all the instances of trdiary in a particular course
 *
 * @author  Simon Coggins <simonc@catalyst.net.nz>
 * @package mod/trdiary
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot.'/mod/trdiary/lib.php');

$id = required_param('id', PARAM_INT);   // course

if (! $course = get_record('course', 'id', $id)) {
    error('Course ID is incorrect');
}

require_course_login($course);

add_to_log($course->id, 'trdiary', 'view all', "index.php?id=$course->id", '');


/// Get all required stringstrdiary

$strtrdiaries = get_string('modulenameplural', 'trdiary');
$strtrdiary  = get_string('modulename', 'trdiary');


/// Print the header

$navlinks = array();
$navlinks[] = array('name' => $strtrdiaries, 'link' => '', 'type' => 'activity');
$navigation = build_navigation($navlinks);

print_header_simple($strtrdiaries, '', $navigation, '', '', true, '', navmenu($course));

/// Get all the appropriate data

if (! $trdiaries = get_all_instances_in_course('trdiary', $course)) {
    notice('There are no instances of trdiary', "../../course/view.php?id=$course->id");
    die;
}

/// Print the list of instances

$timenow  = time();
$strname  = get_string('name');
$strweek  = get_string('week');
$strtopic = get_string('topic');

if ($course->format == 'weeks') {
    $table->head  = array ($strweek, $strname);
    $table->align = array ('center', 'left');
} else if ($course->format == 'topics') {
    $table->head  = array ($strtopic, $strname);
    $table->align = array ('center', 'left', 'left', 'left');
} else {
    $table->head  = array ($strname);
    $table->align = array ('left', 'left', 'left');
}

foreach ($trdiaries as $trdiary) {
    if (!$trdiary->visible) {
        //Show dimmed if the mod is hidden
        $link = '<a class="dimmed" href="view.php?id='.$trdiary->coursemodule.'">'
            .format_string($trdiary->name).'</a>';
    } else {
        //Show normal if the mod is visible
        $link = '<a href="view.php?id='.$trdiary->coursemodule.'">'.format_string($trdiary->name)
            .'</a>';
    }

    if ($course->format == 'weeks' or $course->format == 'topics') {
        $table->data[] = array ($trdiary->section, $link);
    } else {
        $table->data[] = array ($link);
    }
}

print_heading($strtrdiaries);
print_table($table);

/// Finish the page

print_footer($course);


