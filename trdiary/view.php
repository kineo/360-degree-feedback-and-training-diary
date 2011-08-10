<?php  

/**
 * This page prints a particular instance of trdiary
 *
 * @author  Simon Coggins <simonc@catalyst.net.nz>
 * @package mod/trdiary
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot.'/mod/trdiary/lib.php');
require_once($CFG->dirroot.'/mod/trdiary/view_form.php');
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

// check additional fields form
// need to do this before anything is printed as it may result in a redirect
$extrafields = get_pdp_extra_fields($USER->id, $activity->id);
if($extrafields !== false) {
    if(has_capability('mod/trdiary:edit', $context)) {
        $mform = new trdiary_view_form(null, compact('extrafields','a','id'));
        $returnurl = "view.php?a={$activity->id}";
        if ($mform->is_cancelled()) {
            // redirect back to page without updating database
            redirect($returnurl);
        } else if ($fromform = $mform->get_data()) {

            if (empty($fromform->submitbutton)) {
                print_error('error:unknownbuttonclicked','trdiary', $returnurl);
            }
            // process validated data
            if(update_pdp_entry($USER->id, $activity->id, $extrafields, $fromform)) {
                add_to_log($course->id, 'trdiary', 'update pdp', 
                    "view.php?a=$activity->id", $activity->id, $cm->id);
                redirect($returnurl, get_string('addfieldsupdated','trdiary'),5);
            } else {
               print_error('error:cannotupdatefields','trdiary',$returnurl);
            }
        } else {
            // redisplay with errors
            // set field info here, form is actually displayed lower down page
            $toform = new object();
            foreach($extrafields AS $field) {
                $fieldref = 'extrafield'.$field->id;
                $toform->$fieldref = $field->value;
            }
        }

    } else {
        // print as non-editable text
        $noneditable = '';
        foreach ($extrafields AS $field) {
            $noneditable .= '<h3>'.$field->name.'</h3>';
            $noneditable .= '<p>'.$field->value.'</p>';
        }
    }
}

add_to_log($course->id, "trdiary", "view pdp", "view.php?a=$activity->id", $activity->id, $cm->id);

/// Print the page header
$strtrdiaries = get_string('modulenameplural', 'trdiary');
$strtrdiary  = get_string('modulename', 'trdiary');

$navlinks = array();
$navlinks[] = array('name' => $strtrdiaries, 'link' => "index.php?id=$course->id", 
        'type' => 'activity');
$navlinks[] = array('name' => format_string($activity->name), 'link' => '', 
        'type' => 'activityinstance');

$navigation = build_navigation($navlinks);

print_header_simple(format_string($activity->name), '', $navigation, '', '', true,
              update_module_button($cm->id, $course->id, $strtrdiary), navmenu($course, $cm));

/// Print the main part of the page
$currenttab = 'pdp';
include 'tabs.php';

// check pdp_skill entries for this course and user
// and create them if necessary
if(!has_pdp_skills($USER->id,$activity->id)) {
    // create necessary entries in table
    // filling with default priority/isstrength values
    $threesixtyid = $activity->threesixtyid;
    create_pdp_skills($USER->id,$activity->id,$threesixtyid);
}

print '<h2>'.get_string('areas','trdiary').'</h2>';

// display table

$table = build_pdp_table($USER->id, $activity->id);
if ($table) {
    print_table($table);
} else {
    print_error('nopdpskills','trdiary',"{$CFG->wwwroot}/course/view.php?id={$course->id}");
}


// form should be displayed or redisplayed
// see top of page for most of form logic
if (isset($toform)) {
    $mform->set_data($toform);
    $mform->display();
}
// display additional fields but not as a form
// (no edit capability)
if (isset($noneditable)) {
    print $noneditable;
}


/// Finish the page
print_footer($course);


