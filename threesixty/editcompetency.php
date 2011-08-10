<?php

require_once '../../config.php';
require_once 'editcompetency_form.php';
require_once 'locallib.php';

$a = required_param('a', PARAM_INT); // threesixty instance id
$c = optional_param('c', 0, PARAM_INT); // competency id

if (!$activity = get_record('threesixty', 'id', $a)) {
    error('Activity instance is incorrect: '. $a);
}
if (!$course = get_record('course', 'id', $activity->course)) {
    error('Course is misconfigured');
}
if (!$cm = get_coursemodule_from_instance('threesixty', $activity->id, $course->id)) {
    error('Course Module ID was incorrect');
}

$competency = null;
$skills = null;
if ($c > 0) {
    if (!$competency = get_record('threesixty_competency', 'id', $c)) {
        error('Competency ID is incorrect');
    }
    $skills = get_records('threesixty_skill', 'competencyid', $competency->id, 'sortorder');
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

require_login($course->id, false, $cm);
require_capability('mod/threesixty:manage', $context);

$returnurl = "edit.php?a=$activity->id&amp;section=competencies";

$mform =& new mod_threesity_editcompetency_form(null, compact('a', 'c', 'skills'));
if ($mform->is_cancelled()){
    redirect($returnurl);
}

if ($fromform = $mform->get_data()) { // Form submitted

    if (empty($fromform->submitbutton)) {
        print_error('error:unknownbuttonclicked', 'threesixty', $returnurl);
    }

    if (!isset($fromform->showfeedback) ) {
        $fromform->showfeedback = 0;
    }
    $todb = new object();
    $todb->activityid = $activity->id;
    $todb->name = trim($fromform->name);
    $todb->description = trim($fromform->description);
    $todb->showfeedback = $fromform->showfeedback;

    $originurl = null;
    $competencyid = null;

    begin_sql();

    // General
    if ($competency != null) {
        $competencyid = $competency->id;
        $originurl = "editcompetency.php?a=$activity->id&amp;c=$competencyid";

        $todb->id = $competencyid;
        if (update_record('threesixty_competency', $todb)) {
            add_to_log($course->id, 'threesixty', 'update competency',
                       $originurl, $activity->id, $cm->id);
        }
        else {
            rollback_sql();
            print_error('error:cannotupdatecompetency', 'threesixty', $returnurl);
        }
    }
    else {
        $originurl = "editcompetency.php?a=$activity->id&amp;c=0";
        //Set the sortorder to the end of the line.
        $todb->sortorder = count_records('threesixty_competency', 'activityid', $activity->id);
        if ($competencyid = insert_record('threesixty_competency', $todb)) {
            add_to_log($course->id, 'threesixty', 'add competency',
                       $originurl, $activity->id, $cm->id);
        }
        else {
            rollback_sql();
            print_error('error:cannotaddcompetency', 'threesixty', $returnurl);
        }
    }

    // Skills
    for ($i = 0; $i < $fromform->skill_repeats; $i++) {

        $skillid = $fromform->skillid[$i];
        $skillname = '';
        if (!empty($fromform->skillname[$i])) {
            $skillname = $fromform->skillname[$i];
        }
        $skilldescription = '';
        if (!empty($fromform->skilldescription[$i])) {
            $skilldescription = $fromform->skilldescription[$i];
        }
        $skilldelete = false;
        if (!empty($fromform->skilldelete[$i])) {
            $skilldelete = (1 == $fromform->skilldelete[$i]);
        }

        if ($skillid > 0) { // Existing skill

            if (!empty($fromform->skilldelete[$i])) { // Delete
                if (threesixty_delete_skill($skillid, true)) {
                    add_to_log($course->id, 'threesixty', 'delete skill',
                               $originurl, $activity->id, $cm->id);
                }
                else {
                    rollback_sql();
                    print_error('error:cannotdeleteskill', 'threesixty', $returnurl);
                }
            }
            elseif (!empty($skillname)) { // Update
                $todb = new object;
                $todb->id = $skillid;
                $todb->name = $skillname;
                $todb->description = $skilldescription;

                if (update_record('threesixty_skill', $todb)) {
                    add_to_log($course->id, 'threesixty', 'update skill',
                               $originurl, $activity->id, $cm->id);
                }
                else {
                    rollback_sql();
                    print_error('error:cannotupdateskill', 'threesixty', $returnurl);
                }
            }
            else {
                // Skip skills without a name
            }
        }
        elseif (!$skilldelete and !empty($skillname)) { // Insert
            $todb = new object;
            $todb->competencyid = $competencyid;
            $todb->name = $skillname;
            $todb->description = $skilldescription;
            $todb->sortorder = $i;

            if ($todb->id = insert_record('threesixty_skill', $todb)) {
                add_to_log($course->id, 'threesixty', 'insert skill',
                           $originurl, $activity->id, $cm->id);
            }
            else {
                rollback_sql();
                print_error('error:cannotaddskill', 'threesixty', $returnurl);
            }
        }
        else {
            // Skip new skills marked as deleted or with an empty name
        }
    }

    commit_sql();
    redirect($returnurl);
}
elseif ($competency != null) { // Edit mode

    // Set values for the form
    $toform = new object();
    $toform->name = $competency->name;
    $toform->description = $competency->description;
    $toform->showfeedback = ($competency->showfeedback == 1);

    if ($skills) {
        $i = 0;
        foreach ($skills as $skill) {
            $idfield = "skillid[$i]";
            $namefield = "skillname[$i]";
            $descriptionfield = "skilldescription[$i]";
            $sortorderfield = "skillsortorder[$i]";
            $toform->$idfield = $skill->id;
            $toform->$namefield = $skill->name;
            $toform->$descriptionfield = $skill->description;
            $toform->$sortorderfield = $skill->sortorder;
            $i++;
        }
    }
    $mform->set_data($toform);
}

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

print_header_simple(format_string($activity->name . " - $title"), '', $navigation, '', '', true,
                    update_module_button($cm->id, $course->id, $strthreesixty), navmenu($course, $cm));
include 'tabs.php';
$mform->display();

print_footer($course);
