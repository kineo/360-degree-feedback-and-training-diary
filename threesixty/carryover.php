<?php

/**
 * Administration settings for selecting which competencies to carry
 * over to the Training Diary.
 *
 * @author  Francois Marier <francois@catalyst.net.nz>
 * @package mod/threesixty
 */

require_once '../../config.php';
require_once 'locallib.php';
require_once 'carryover_form.php';

define('MAX_DESCRIPTION', 255); // max number of characters of the description to show in the table

$a       = required_param('a', PARAM_INT);  // threesixty instance ID
$userid  = optional_param('userid', 0, PARAM_INT);

if (!$activity = get_record('threesixty', 'id', $a)) {
    error('Course module is incorrect');
}
if (!$course = get_record('course', 'id', $activity->course)) {
    error('Course is misconfigured');
}
if (!$cm = get_coursemodule_from_instance('threesixty', $activity->id, $course->id)) {
    error('Course Module ID was incorrect');
}
$user = null;
if ($userid > 0 and !$user = get_record('user', 'id', $userid, '', 'id, firstname, lastname')) {
    error('Invalid User ID');
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

require_login($course, true, $cm);
require_capability('mod/threesixty:manage', $context);

$baseurl = "carryover.php?a=$activity->id";

$mform = null;
if (isset($user)) {
    $returnurl = "view.php?a=$activity->id";
    $currenturl = "$baseurl&amp;userid=$user->id";

    if (!$analysis = get_record('threesixty_analysis', 'activityid', $activity->id, 'userid', $user->id)) {
        print_error('error:nodataforuserx', 'threesixty', $returnurl, fullname($user));
    }

    $complist = get_full_competency_list($activity->id);
    $nbcarried = $activity->competenciescarried;

    $mform =& new mod_threesity_carryover_form(null, compact('a', 'userid', 'complist', 'nbcarried'));

    if ($fromform = $mform->get_data()) {
        if ($mform->is_cancelled()) {
            redirect($baseurl);
        }

        if (save_changes($fromform, $analysis->id)) {
            redirect($currenturl);
        }
        else {
            redirect($currenturl, get_string('error:cannotsavechanges', 'threesixty', get_string('error:databaseerror', 'threesixty')));
        }
    }

    add_to_log($course->id, 'threesixty', 'carryover', $currenturl, $activity->id);
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
$section = 'carryover';
include 'tabs.php';

if (isset($mform)) {
    print threesixty_selected_user_heading($user, $course->id, $baseurl);

    set_form_data($mform, $analysis->id);
    $mform->display();
}
else {
    display_current_user_data($activity, $baseurl);
}

print_footer($course);

function get_full_competency_list($activityid)
{
  global $CFG;
    $ret = array(0 => get_string('none'));

    $sql = "SELECT s.*, c.name as competency FROM {$CFG->prefix}threesixty_skill s
            JOIN {$CFG->prefix}threesixty_competency c ON s.competencyid = c.id
            WHERE c.activityid = ".$activityid;
    if ($records = get_records_sql($sql)) {
        foreach ($records as $record) {
            $ret[$record->id] = $record->competency.": ".$record->name;
        }
    }

    return $ret;
}

function set_form_data($mform, $analysisid)
{
    if (!$carriedcomps = get_records('threesixty_carried_comp', 'analysisid', $analysisid, '', 'id, competencyid')) {
        return; // no existing data
    }

    $toform = array();

    $previousvalues = array();
    $i = 0;
    foreach ($carriedcomps as $carried) {
        if ($i >= $mform->_customdata['nbcarried']) {
            error_log('threesixty: more records in carried_comp than allowed in activity settings');
            break;
        }

        $compid = $carried->competencyid;
        if (!empty($previousvalues[$compid])) {
            $i++;
            continue; // only add competencies once
        }
        $previousvalues[$compid] = true;

        $toform["comp$i"] = $compid;
        $i++;
    }

    $mform->set_data($toform);
}

function save_changes($formfields, $analysisid)
{
    if (!empty($formfields->nbcarried)) {
        begin_sql();

        // Remove existing ones
        if (!delete_records('threesixty_carried_comp', 'analysisid', $analysisid)) {
            error_log("threesixty: could not delete records from carried_comp");
            rollback_sql();
            return false;
        }

        // Add all new selected competencies
        $previousvalues = array();
        for ($i=0; $i < $formfields->nbcarried; $i++) {
            $fieldname = "comp$i";
            if (empty($formfields->$fieldname)) {
                continue; // missing from the form data (or set to 'None')
            }

            $compid = (int)$formfields->$fieldname;
            if (!empty($previousvalues[$compid])) {
                continue; // only add competencies once
            }
            $previousvalues[$compid] = true;

            $record = new object();
            $record->analysisid = $analysisid;
            $record->competencyid = $compid;

            if (!insert_record('threesixty_carried_comp', $record)) {
                error_log("threesixty: could not insert new record in carried_comp");
                rollback_sql();
                return false;
            }
        }

        commit_sql();
    }

    return true;
}
function display_current_user_data($activity, $url){
  global $CFG;

  $table = new object();
  $table->head = array('User');
  $nbcarried = $activity->competenciescarried;
  for ($i=1; $i<=$nbcarried; $i++){
    $table->head[] = 'Skill '.$i;
  }
  $table->head[] = 'Options';

  $users = threesixty_users($activity);
  if($users){
    foreach($users as $user){
      $data = array("<a href=".$CFG->wwwroot."/user/view.php?id={$user->id}&course={$activity->course}>".format_string($user->firstname." ".$user->lastname)."</a>");
      $sql = "SELECT c.name AS competency
              FROM {$CFG->prefix}threesixty_analysis a
              JOIN {$CFG->prefix}threesixty_carried_comp cc ON a.id = cc.analysisid
              JOIN {$CFG->prefix}threesixty_skill c ON cc.competencyid = c.id
              WHERE a.userid = {$user->id} and a.activityid = {$activity->id}";
      $carriedcomps = get_records_sql($sql);
      $missingcells = $nbcarried;
      if($carriedcomps){
        foreach ($carriedcomps as $comp)
        {
          $data[] = $comp->competency;
          $missingcells--;
        }
      }
      if($missingcells){
        for($i=0;$i<$missingcells;$i++)
        {
          $data[] = "&nbsp;";
        }
      }
      $data[] = "<a href=\"$url&amp;userid=$user->id\">Edit</a>";
      $table->data[] = $data;
    }
    print_table($table);
  }else{
    print "No users to display";
  }
}