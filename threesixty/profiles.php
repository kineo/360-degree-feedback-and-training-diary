<?php
/* 
 * Shows the students' responses to the different profile types required.
 *
 * @author Eleanor Martin <eleanor.martin@kineo.com>
 * @package mod/threesixty
 */

  require_once '../../config.php';
  require_once 'locallib.php';

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

  $context = get_context_instance(CONTEXT_MODULE, $cm->id);

  require_login($course, true, $cm);
  $user = null;
  if ($userid > 0 and !$user = get_record('user', 'id', $userid, '', 'id, firstname, lastname')) {
      error('Invalid User ID');
  }

  $baseurl = "profiles.php?a=$activity->id";
  $strthreesixtys = get_string('modulenameplural', 'threesixty');
  $strthreesixty  = get_string('modulename', 'threesixty');

  $navlinks = array();
  $navlinks[] = array('name' => $strthreesixtys, 'link' => "index.php?id=$course->id", 'type' => 'activity');
  $navlinks[] = array('name' => format_string($activity->name), 'link' => '', 'type' => 'activityinstance');

  $navigation = build_navigation($navlinks);

  print_header_simple(format_string($activity->name), '', $navigation, '', '', true,
                      update_module_button($cm->id, $course->id, $strthreesixty), navmenu($course, $cm));

  // Main content
  $currenttab = 'activity';
  $section = null;
  include 'tabs.php';

  threesixty_self_profile_options($course->id, $baseurl, $activity, $context);

  print_footer($course);

  function threesixty_self_profile_options($courseid, $baseurl, $activity, $context)
  {
    global $CFG, $USER;

    $view_all_users = has_capability('mod/threesixty:viewreports', $context);
    $canedit = has_capability('mod/threesixty:edit', $context);
    if ($view_all_users){
      //$users = threesixty_users($activity);
      $users = threesixty_get_possible_participants($context);
    }
    else {
      $users = array($USER);
    }
    $selfresponses = explode("\n", get_config(null, 'threesixty_selftypes'));
    if (!empty($selfresponses)){
      $table = new object();
      $table->head = array();
      if ($view_all_users){
        $table->head[] = 'User';
      }
      $table->head[] = get_string('self:responsetype', 'threesixty');
      $table->head[] = get_string('self:responsecompleted', 'threesixty');
      $table->head[] = get_string('self:responseoptions', 'threesixty');
      foreach($users as $user){
        $data = array();
        if ($view_all_users){
          $data[] = "<a href=".$CFG->wwwroot."/user/view.php?id={$user->id}&course={$activity->course}>".format_string($user->firstname." ".$user->lastname)."</a>";
        }
        $responsenumber = 0; //This provides the type id of the response. 
        foreach ($selfresponses as $responsetype){
          if($responsenumber>0){
            $data = array();
            if ($view_all_users){
              $data[] = "&nbsp;";
            }
          }
          $data[] = $responsetype;
          $timecompleted = get_completion_time($activity->id, $user->id, $responsenumber, true);
          if ($timecompleted>0){
            $canreallyedit = $canedit;
            $timeoutput = userdate($timecompleted);
          }else{
            $canreallyedit = false;
            $timeoutput = "<span class=\"incomplete\">".get_string('self:incomplete', 'threesixty')."</span>";
          }
          $data[] = $timeoutput;

          $data[] = get_options($activity->id, $user->id, $responsenumber, $view_all_users, $canreallyedit);
          $responsenumber += 1;
          $table->data[] = $data;
        }
      }
      print_table($table);
    }
  }
  function get_completion_time($activityid, $userid, $responsetype, $self=false)
  {
    global $CFG;

    $sql = "SELECT r.timecompleted FROM ".$CFG->prefix."threesixty_analysis a ";
    $sql .= "JOIN ".$CFG->prefix."threesixty_respondent rp ON a.id = rp.analysisid ";
    $sql .= "JOIN ".$CFG->prefix."threesixty_response r ON rp.id = r.respondentid ";
    $sql .= "WHERE a.userid = ".$userid." AND a.activityid = ".$activityid." AND rp.type = ".$responsetype;
    if($self){
      $sql .= " AND rp.uniquehash IS NULL";
    } else {
      $sql .= " AND rp.uniquehash IS NOT NULL";
    }

    $times = get_records_sql($sql);
    if($times){
      if(count($times)>1){
        echo "There has been a problem retrieving the time completed. Please contact your administrator.";
      }else{
        $time = array_pop($times);
        return $time->timecompleted;
      }
    }
  }
  function get_options($activityid, $userid, $typeid, $view_all_users, $canedit)
  {
    global $CFG;

    $scoreurl = $CFG->wwwroot."/mod/threesixty/score.php?a=".$activityid;
    if($view_all_users){
      $scoreurl.="&userid=".$userid;
    }
    $scoreurl.="&typeid=".$typeid;
    $output = "<a href='".$scoreurl."'>View</a>";

    if ($canedit){
      $amendurl =$CFG->wwwroot."/mod/threesixty/amend.php?a=".$activityid."&typeid=".$typeid."&userid=".$userid;
      $output.=" | <a href='".$amendurl."'>Amend</a>";
    }
    return $output;
  }
?>
