<?php  

/**
 * Edit PDP skills for an individual user
 *
 * @author  Simon Coggins <simonc@catalyst.net.nz>
 * @package mod/trdiary
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot.'/mod/trdiary/locallib.php');
require_once($CFG->dirroot.'/mod/trdiary/edit_form.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // trdiary instance ID
$u = required_param('u', PARAM_INT); // user ID

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

$returnurl = "$CFG->wwwroot/mod/trdiary/users.php?a=$activity->id";

if (! $trdiaryuser = get_record('user', 'id', $u)) {
    print_error('error:nouser','trdiary',$returnurl);
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

require_login($course, true, $cm);
require_capability('mod/trdiary:manage',$context);

// check pdp_skill entries for this course and user
// and create them if necessary
if(!has_pdp_skills($trdiaryuser->id,$activity->id)) {
    // create necessary entries in table
    // filling with default priority/isstrength values
    $threesixtyid = $activity->threesixtyid;
    create_pdp_skills($trdiaryuser->id,$activity->id,$threesixtyid);
}

$pdp_skills = get_pdp_skills($trdiaryuser->id, $activity->id);
if($pdp_skills === false) {
    print_error('nopdpskills', 'trdiary', $returnurl);
}

$mform = new trdiary_edit_form(null, compact('a','u','pdp_skills'));
if ($mform->is_cancelled()){
    // redirect to user page
    redirect($returnurl);
} else if ($fromform=$mform->get_data()){

    if (empty($fromform->submitbutton)) {
        print_error('error:unknownbuttonclicked', 'trdiary', $returnurl);
    }

    if(update_user_skills($trdiaryuser->id, $activity->id, $pdp_skills, $fromform)) {
        add_to_log($course->id, 'trdiary', 'update user', 
                   "edit.php?a={$activity->id}&amp;u={$trdiaryuser->id}", 
                   "{$activity->id}", '', $cm->id);
        redirect($returnurl,get_string('pdpupdated','trdiary'));
    } else {
        print_error('error:cannotupdateuserpdp','trdiary', $returnurl);
    }
}

add_to_log($course->id, "trdiary", "view user", "edit.php?a={$activity->id}&amp;u={$trdiaryuser->id}",
    "{$activity->id}", $cm->id);

/// Print the page header
$strtrdiaries = get_string('modulenameplural', 'trdiary');
$strtrdiary  = get_string('modulename', 'trdiary');

$navlinks = array();
$navlinks[] = array('name' => $strtrdiaries, 'link' => "index.php?id=$course->id", 'type' => 
        'activity');
$navlinks[] = array('name' => format_string($activity->name), 'link' => "view.php?id={$cm->id}", 
        'type' => 'activityinstance');
$navlinks[] = array('name' => format_string(get_string('users', 'trdiary')), 'link' => 
        "users.php?id={$cm->id}", 'type' => 'activityinstance');
$title = $trdiaryuser->firstname . ' ' . $trdiaryuser->lastname;
$navlinks[] = array('name' => format_string($title), 'link' => '', 'type' => 'activityinstance');
$navigation = build_navigation($navlinks);

print_header_simple(format_string($activity->name), '', $navigation, '', '', true,
              update_module_button($cm->id, $course->id, $strtrdiary), navmenu($course, $cm));


/// Print the main part of the page
$currenttab = 'users';
include 'tabs.php';
$currentuser = $trdiaryuser->firstname.' '.$trdiaryuser->lastname; 
print '<h2>'.get_string('updatereflogfor','trdiary',$currentuser).'</h2>';

// display actual form if not redirected
$mform->display();

// display read-only version of users additional fields
$extrafields = get_pdp_extra_fields($trdiaryuser->id, $activity->id);
if($extrafields !== false) {
    $noneditable = '<div id="intro" class="generalbox box">';
    $noneditable .= '<h2>'.get_string('additionalfields','trdiary').'</h2>';
    foreach ($extrafields AS $field) {
        $noneditable .= '<h3>'.$field->name.'</h3>';
        $noneditable .= '<p>'.$field->value.'</p>';
    }
    $noneditable .= '</div>';
}
print $noneditable;

// Finish the page
print_footer($course);

