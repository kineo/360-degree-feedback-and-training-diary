<?php

/**
 * Administration settings for a threesixty activity
 *
 * @author  Francois Marier <francois@catalyst.net.nz>
 * @package mod/threesixty
 */

require_once '../../config.php';
require_once 'locallib.php';

define('MAX_DESCRIPTION', 255); // max number of characters of the description to show in the table
$strmoveup = get_string('moveup');
$strmovedown = get_string('movedown');
$a = required_param('a', PARAM_INT);  // threesixty instance ID
$move = optional_param('move', 0, PARAM_INT); //Reordering competencies.

if (!$activity = get_record('threesixty', 'id', $a)) {
    error('Course module is incorrect');
}
if (!$course = get_record('course', 'id', $activity->course)) {
    error('Course is misconfigured');
}
if (!$cm = get_coursemodule_from_instance('threesixty', $activity->id, $course->id)) {
    error('Course Module ID was incorrect');
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

require_login($course, true, $cm);
require_capability('mod/threesixty:manage', $context);

add_to_log($course->id, 'threesixty', 'admin', "edit.php?a=$activity->id", $activity->id);
if ($move){
  $c = optional_param('c', 0, PARAM_INT); //The competency id that we're needing to move.
  if(!$competency = get_record('threesixty_competency', 'id', $c, 'activityid', $activity->id)){
    error('Competency id incorrect');
  }
  move_competency($competency, $move);
}
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
$currenttab = 'edit';
$section = 'competencies';
include 'tabs.php';

//print '<h2>'.get_string('competenciesheading', 'threesixty').'</h2>';

$competencies = threesixty_get_competency_listing($activity->id);

if (count($competencies) > 0) {

    $table->head = array(get_string('name'), get_string('description'),
                         get_string('skills', 'threesixty'), get_string('feedback', 'threesixty'), '&nbsp;');

    $numCompetencies = count($competencies);
    for ($i=0; $i < $numCompetencies ; $i++) {
        $competency = array_shift($competencies);
        $links = '<a href="editcompetency.php?a='.$activity->id.'&amp;c='.$competency->id.'">'.get_string('edit', 'threesixty').'</a>';
        $links .= ' | <a href="deletecompetency.php?a='.$activity->id.'&amp;c='.$competency->id.'">'.get_string('delete', 'threesixty').'</a>';
        if ($i!=0){
          $links .= ' | <a href="edit.php?a='.$activity->id.'&amp;c='.$competency->id.'&amp;move=-1" title="'.$strmoveup.'">
                  <img src="'.$CFG->pixpath.'/t/up.gif" alt="'.$strmoveup.'" /></a>';
        }
        if ($i<$numCompetencies-1){
          $links .= ' | <a href="edit.php?a='.$activity->id.'&amp;c='.$competency->id.'&amp;move=1" title="'.$strmovedown.'">
                  <img src="'.$CFG->pixpath.'/t/down.gif" alt="'.$strmovedown.'" /></a>';
        }
        // Shorten the description field
        $shortdescription = substr($competency->description, 0, MAX_DESCRIPTION);
        if (strlen($shortdescription) < strlen($competency->description)) {
            $shortdescription .= '...';
        }

        $table->data[] = array(format_string($competency->name), format_text($shortdescription),
                               format_string($competency->skills),
                               $competency->showfeedback ? get_string('yes') : get_string('no') , $links);
    }

    print_table($table);
}
else {
     print_string('nocompetencies', 'threesixty');
}

print '<p><a href="editcompetency.php?a='.$activity->id.'&amp;c=0">'.get_string('addnewcompetency', 'threesixty').'</a></p>';

print_footer($course);

function move_competency($competency, $moveTo){
  $currentlocation = $competency->sortorder;
  $newlocation = $currentlocation + $moveTo;

  $swapcomp = get_record('threesixty_competency', 'activityid', $competency->activityid, 'sortorder', $newlocation);
  if($swapcomp){
    $swapcomp->sortorder = $currentlocation;
    update_record('threesixty_competency', $swapcomp);
  }

  $competency->sortorder = $newlocation;
  update_record('threesixty_competency', $competency);
}