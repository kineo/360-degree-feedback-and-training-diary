<?php
/**
 * Edit a reflective log entry 
 *
 * @author  Simon Coggins <simonc@catalyst.net.nz>
 * @package mod/trdiary
**/
require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot.'/mod/trdiary/locallib.php');
require_once($CFG->dirroot.'/mod/trdiary/reflog_form.php');

$a = required_param('a', PARAM_INT); // activity instance ID
$e = required_param('e', PARAM_INT); // entry ID
$u = optional_param('u', null, PARAM_INT); // user ID if admin is editing

if (!$activity = get_record('trdiary', 'id', $a)) {
    error('Activity instance is incorrect: '. $a);
}
if (!$course = get_record('course', 'id', $activity->course)) {
    error('Course is misconfigured');
}
if (!$cm = get_coursemodule_from_instance('trdiary', $activity->id, $course->id)) {
    error('Course Module ID was incorrect');
}
$entry = null;
if (!$entry = get_record('trdiary_reflog_entry', 'id', $e)) {
    error('Entry ID was incorrect');
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);
$returnurl = "reflog.php?a=$activity->id";

if (isset($u)) {
    require_capability('mod/trdiary:manage', $context);
    $returnurl .= '&amp;u='.$u;
}
else if ($entry->userid != $USER->id) {
    error('You do not have permission to access this entry');
}

require_login($course->id, false, $cm);
require_capability('mod/trdiary:edit', $context);


// get the values for the current entry
$values = get_records('trdiary_reflog_value','entryid',$entry->id);

$fields = get_reflog_fields($activity->id);
if ($fields !== false) {
    $mform =& new trdiary_reflog_form(null, compact('a', 'e', 'fields','u'));
    if ($mform->is_cancelled()){
        redirect($returnurl);
    }

    if ($fromform = $mform->get_data()) { // Form submitted

        if (empty($fromform->submitbutton)) {
            print_error('error:unknownbuttonclicked', 'trdiary', $returnurl);
        }

        if(update_reflog_entry($entry->id, $fields, $fromform)) {
            add_to_log($course->id, 'trdiary', 'update reflog', 
                       "editreflog.php?a=$activity->id&amp;e=$entry->id", 
                       "{$activity->id}", $cm->id);
            redirect($returnurl, get_string('reflogupdated','trdiary'), 5);
        } else {
            print_error('error:cannotupdatereflogfields', 'trdiary', $returnurl);
        }

    }
    elseif ($entry != null) { // Edit mode

        // Set values for the form
        $toform = new object();
        if ($values) {
            foreach ($values as $value) {
                $fieldref = 'field'.$value->fieldid;
                $fieldid = 'field'.$value->fieldid.'id';
                $toform->$fieldref = $value->value;
                $toform->$fieldid = $value->id;
            }
        }
        $mform->set_data($toform);
    }
}

// Header
$strtrdiaries = get_string('modulenameplural', 'trdiary');
$strtrdiary  = get_string('modulename', 'trdiary');

$navlinks = array();
$navlinks[] = array('name' => $strtrdiaries, 'link' => "index.php?id=$course->id", 'type' => 
        'activity');
$navlinks[] = array('name' => format_string($activity->name), 'link' => "view.php?id={$cm->id}", 
        'type' => 'activityinstance');

$navlinks[] = array('name' => format_string(get_string('reflog','trdiary')), 'link' => 
        "reflog.php?id={$cm->id}", 'type' => 'activityinstance');
$title = get_string('editentry', 'trdiary');
$navlinks[] = array('name' => format_string($title), 'link' => '', 'type' => 'activityinstance');
$navigation = build_navigation($navlinks);

print_header_simple(format_string($activity->name . " - $title"), '', $navigation, '', '', true,
                    update_module_button($cm->id, $course->id, $strtrdiary), navmenu($course, $cm));

$mform->display();

print_footer($course);

