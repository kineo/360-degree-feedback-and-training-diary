<?php

/**
 * View the reflective log
 *
 * @author  Simon Coggins <simonc@catalyst.net.nz>
 * @package mod/trdiary
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot.'/mod/trdiary/lib.php');
require_once($CFG->dirroot.'/mod/trdiary/locallib.php');
require_once($CFG->dirroot.'/mod/trdiary/reflog_form.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // trdiary instance ID
$u = optional_param('u', null, PARAM_INT); // userid ID if admin editing

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
    if (! $cm = get_coursemodule_from_instance('trdiary', $activity->id, 
                                               $course->id)) {
        error('Course Module ID was incorrect');
    }

} else {
    error('You must specify a course_module ID or an instance ID');
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);
$returnurl = $CFG->wwwroot.'/course/view.php?id='.$course->id;

// are they trying to view reflog for a different user?
if (isset($u)) {
    // check permissions
    if(!has_capability('mod/trdiary:manage', $context)) {
        print_error('error:noperm', 'trdiary', $returnurl);
    }

    // check user exists
    if (! $trdiaryuser = get_record('user', 'id', $u)) {
        print_error('error:nouser','trdiary', $returnurl);
    }
}

if(isset($trdiaryuser)) {
    $name = $trdiaryuser->firstname.' '.$trdiaryuser->lastname;
    $titlestr = get_string('refloguser','trdiary',$name);
    $userid = $trdiaryuser->id;
} else {
    $titlestr = get_string('updatereflog','trdiary');
    $userid = $USER->id;
}


require_login($course, true, $cm);

// determine how to display log entries
if (trim($activity->logfreq) == '') {
    $datebasedlog = false;
} else {
    $datebasedlog = true;
}

// get fields for new entry form and table header
$fields = get_reflog_fields($activity->id);

// don't show add for if viewing another user
if(!isset($trdiaryuser)) {
if($fields !== false) {
    if(has_capability('mod/trdiary:edit', $context)) {
        // set entry to zero for new entry
        $e = 0;
        $mform = new trdiary_reflog_form(null, compact('fields','a','e','trdiaryuser'));
        $returnurl = "reflog.php?a={$activity->id}";
        if ($mform->is_cancelled()) {
            redirect($returnurl);
        } else if ($fromform = $mform->get_data()) {

            if (empty($fromform->submitbutton)) {
                print_error('error:unknownbuttonclicked', 'trdiary', $returnurl);
            }

            $entryid = create_reflog_entry($USER->id,$activity->id, $fields, 
                                           $fromform);
            if($entryid) {
                add_to_log($course->id, 'trdiary', 'insert reflog',
                           "reflog.php?a=$activity->id", 
                           $activity->id, $cm->id);
                // create calendar event reminding user when next entry due
                create_reflog_reminder($USER->id, $activity);
                redirect($returnurl);
            } else {
                print_error('error:cannotinsertreflog','trdiary',$returnurl);
            }
        } else {
            // redisplay with errors
            $toform = new object();
            foreach($fields AS $field) {
                $fieldref = 'field'.$field->fieldid;
            }
        }
    }
} else {
    print_error('error:noreflogfields','trdiary',"view.php?id={$cm->id}");
}
}
add_to_log($course->id, "trdiary", "view reflog", "reflog.php?a={$activity->id}", 
           $activity->id, $cm->id);

/// Print the page header
$strtrdiaries = get_string('modulenameplural', 'trdiary');
$strtrdiary  = get_string('modulename', 'trdiary');

$navlinks = array();
$navlinks[] = array('name' => $strtrdiaries, 'link' => "index.php?id=$course->id", 
                    'type' => 'activity');
$navlinks[] = array('name' => format_string($activity->name), 
                    'link' => "view.php?id=$cm->id", 'type' => 'activityinstance');
$navlinks[] = array('name' => format_string(get_string('reflog','trdiary')), 
                    'link' => '');
$navigation = build_navigation($navlinks);

print_header_simple(format_string($activity->name), '', $navigation, '', '', true,
                    update_module_button($cm->id, $course->id, $strtrdiary), 
                        navmenu($course, $cm));

/// Print the main part of the page
$currenttab = 'reflog';
include 'tabs.php';

print '<h2>'.$titlestr.'</h2>';

if(isset($toform)) {
    $mform->set_data($toform);
    $mform->display();
}

$userlink = isset($trdiaryuser);
$table = build_reflog_table($userid, $activity->id, $fields, $datebasedlog, 
                            $context, $userlink);
if($table !== false) {
    print_table($table);
} else {
    print get_string('noreflogentries', 'trdiary');
}
/// Finish the page
print_footer($course);

