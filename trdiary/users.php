<?php  

/**
 * View list of users
 *
 * @author  Simon Coggins <simonc@catalyst.net.nz>
 * @package mod/trdiary
 */

require_once(dirname(__FILE).'/../../config.php');
require_once($CFG->dirroot.'/mod/trdiary/lib.php');
require_once($CFG->dirroot.'/mod/trdiary/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // trdiary instance ID

if ($id) {
    if (! $cm = get_coursemodule_from_id('trdiary', $id)) {
        error('Course Module ID was incorrect');
    }

    if (! $course = get_record('course', 'id', $cm->course)) {
        error('Course is misconfigured');
    }

    if (! $activity = get_record('trdiary', 'id', $cm->instance)) {
        error('Course module is incorrect');
    }

} else if ($a) {
    if (! $activity = get_record('trdiary', 'id', $a)) {
        error('Course module is incorrect');
    }
    if (! $course = get_record('course', 'id', $activity->course)) {
        error('Course is misconfigured');
    }
    if (! $cm = get_coursemodule_from_instance('trdiary', $activity->id, $course->id)) {
        error('Course Module ID was incorrect');
    }

} else {
    error('You must specify a course_module ID or an instance ID');
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

require_login($course, true, $cm);
require_capability('mod/trdiary:manage',$context);

add_to_log($course->id, "trdiary", "view user list", "users.php?a={$activity->id}", $activity->id, $cm->id);

/// Print the page header
$strtrdiaries = get_string('modulenameplural', 'trdiary');
$strtrdiary  = get_string('modulename', 'trdiary');

$navlinks = array();
$navlinks[] = array('name' => $strtrdiaries, 'link' => "index.php?id=$course->id", 
        'type' => 'activity');
$navlinks[] = array('name' => format_string($activity->name), 'link' => "view.php?id={$cm->id}", 
        'type' => 'activityinstance');
$navlinks[] = array('name' => format_string(get_string('users', 'trdiary')), 'link' => '', 
        'type' => 'activityinstance');
$navigation = build_navigation($navlinks);

print_header_simple(format_string($activity->name), '', $navigation, '', '', true,
              update_module_button($cm->id, $course->id, $strtrdiary), navmenu($course, $cm));


/// Print the main part of the page
$currenttab = 'users';
include 'tabs.php';

print '<h2>'.get_string('users','trdiary').'</h2>';

$table = build_users_table($activity->id, $course->id);

if($table !== false) {
    print_table($table);
} else {
    print get_string('cannotdisplayusertable', 'trdiary');
}

// Finish the page
print_footer($course);


