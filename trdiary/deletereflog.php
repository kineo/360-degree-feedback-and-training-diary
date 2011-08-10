<?php

/**
 * Delete a reflective log entry
 *
 * @author  Simon Coggins <simonc@catalyst.net.nz>
 * @package mod/trdiary
**/

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot.'/mod/trdiary/locallib.php');

$a = required_param('a', PARAM_INT); // activity instance ID
$e = required_param('e', PARAM_INT); // entry ID
$u = optional_param('u', null, PARAM_INT); // user ID if admin is deleting
$confirm = optional_param('confirm', 0, PARAM_INT);  // commit the operation?

if (!$activity = get_record('trdiary', 'id', $a)) {
    error('Activity instance is incorrect: '. $a);
}
if (!$course = get_record('course', 'id', $activity->course)) {
    error('Course is misconfigured');
}
if (!$cm = get_coursemodule_from_instance('trdiary', $activity->id, $course->id)) {
    error('Course Module ID was incorrect');
}
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


// Header
$strtrdiaries = get_string('modulenameplural', 'trdiary');
$strtrdiary  = get_string('modulename', 'trdiary');

$navlinks = array();
$navlinks[] = array('name' => $strtrdiaries, 'link' => 
                    "index.php?id=$course->id", 'type' => 'activity');
$navlinks[] = array('name' => format_string($activity->name), 'link' => 
                    "view.php?id={$cm->id}", 'type' => 'activityinstance');
$navlinks[] = array('name' => format_string(get_string('reflog','trdiary')), 
                    'link' => "reflog.php?id={$cm->id}", 'type' => 'activityinstance');
$title = get_string('deleteentry', 'trdiary');
$navlinks[] = array('name' => format_string($title), 'link' => '', 'type' => 'activityinstance');
$navigation = build_navigation($navlinks);

if ($confirm && confirm_sesskey()) {
   
    if (delete_reflog_entry($entry->id)) {
        add_to_log($course->id, 'trdiary', 'delete reflog', 
                   "deletereflog.php?a=$activity->id&amp;e=$entry->id", 
                   "{$activity->id}", $cm->id);
        redirect($returnurl);
    } else {
        print_error('error:cannotdeletereflogentry', 'trdiary', $returnurl);
    }
}

print_header_simple(format_string($activity->name . " - $title"), '', $navigation, '', '', true,
                    update_module_button($cm->id, $course->id, $strtrdiary), navmenu($course, $cm));

// display entry to be deleted
$thisfields = get_reflog_fields($activity->id);
$thisentry = get_reflog_entries($entry->userid, $activity->id, $entry->id);
// get first/only element
$thisentry = array_shift($thisentry);

$header = array(get_string('datecreated','trdiary'));
$row = array(userdate($thisentry->timecreated, get_string('strftimedatetime')));
foreach($thisfields AS $field) {
    $header[] = $field->name;
    $fieldid = $field->fieldid;
    if(isset($thisentry->$fieldid)) {
        $row[] = $thisentry->$fieldid;
    } else {
        $row[] = '&nbsp;';
    }
}
$table->head = $header;
$table->data[] = $row;

print '<br /><br />';
print_table($table);
print '<br /><br />';

$sesskey = sesskey();
// Ask for confirmation
$yesnourl = "deletereflog.php?a=$activity->id&amp;e=$entry->id&amp;sesskey=$sesskey&amp;confirm=1";
if (isset($u)) {
    $yesnourl .= "&amp;u=$u";
}
notice_yesno('<b>'.get_string('deleteentry','trdiary').'</b><p>'.
             get_string('areyousuredelete', 'trdiary').'</p>',
             $yesnourl, $returnurl);

print_footer($course);

