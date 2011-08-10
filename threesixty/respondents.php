<?php

/**
 * Allows a student to assess their skills accross competencies
 *
 * @author  Francois Marier <francois@catalyst.net.nz>
 * @package mod/threesixty
 */

require_once '../../config.php';
require_once 'locallib.php';
require_once 'respondents_form.php';

define('RESPONSE_BASEURL', "$CFG->wwwroot/mod/threesixty/score.php?code=");

$a       = required_param('a', PARAM_INT);  // threesixty instance ID
$userid  = optional_param('userid', 0, PARAM_INT);
$delete  = optional_param('delete', 0, PARAM_INT);
$remind  = optional_param('remind', 0, PARAM_INT);

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

if (!has_capability('mod/threesixty:viewrespondents', $context)) {
    require_capability('mod/threesixty:participate', $context);
    $userid = $USER->id; // force same user
}

$user = null;
if ($userid > 0 and !$user = get_record('user', 'id', $userid, '', 'id, firstname, lastname')) {
    error('Invalid User ID');
}

$baseurl = "respondents.php?a=$activity->id";

$mform = null;
if (isset($user)) {

    // Make sure the form has been submitted by the student
    $returnurl = "view.php?a=$activity->id";
    if (!$analysis = get_record('threesixty_analysis', 'activityid', $activity->id, 'userid', $user->id)) {
        print_error('error:noscoresyet', 'threesixty', $returnurl);
    }

    $currenturl = "$baseurl&amp;userid=$user->id";

    // Handle manual (non-formslib) actions
    if ($remind > 0) {
        if (!confirm_sesskey()) {
            print_error('confirmsesskeybad', 'error', $currenturl);
        }

        if (!send_reminder($remind, fullname($user))) {
            print_error('error:cannotsendreminder', 'threesixty', $currenturl);
        }
        redirect($currenturl);
    }
    if ($delete > 0) {
        if (!confirm_sesskey()) {
            print_error('confirmsesskeybad', 'error', $currenturl);
        }

        if (!threesixty_delete_respondent($delete)) {
            print_error('error:cannotdeleterespondent', 'threesixty', $currenturl);
        }
        redirect($currenturl);
    }

    $typelist = array();
    $i = 0;
    foreach (explode("\n", get_config(null, 'threesixty_respondenttypes')) as $type) {
        $t = trim($type);
        if (!empty($t)) {
            $typelist[$i] = $t;
            $i++;
        }
    }
    if (empty($typelist)) {
        $typelist = array(0 => get_string('none'));
    }
    $currentinvitations = count_records_sql("SELECT COUNT(1) FROM ".$CFG->prefix.
                  "threesixty_respondent WHERE analysisid = ".$analysis->id." AND uniquehash IS NOT NULL");
    $remaininginvitations = $activity->requiredrespondents - $currentinvitations;

    $analysisid = $analysis->id;

    $mform =& new mod_threesity_respondents_form(null, compact('a', 'analysisid', 'userid', 'typelist', 'remaininginvitations'));

    if ($fromform = $mform->get_data()) {

        if (!request_respondent($fromform, $analysis->id, fullname($user))) {
            print_error('error:cannotinviterespondent', 'threesixty', $currenturl);
        }
        redirect($currenturl);
    }

    add_to_log($course->id, 'threesixty', 'respondents', $currenturl, $activity->id);
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
$currenttab = 'respondents';
$section = null;

include 'tabs.php';

if (isset($mform)) {
    if ($USER->id != $userid) {
        print threesixty_selected_user_heading($user, $course->id, $baseurl);
    }

    if ($remaininginvitations > 0) {
        $mform->display();
    }

    $canremind = has_capability('mod/threesixty:remindrespondents', $context);
    $candelete = has_capability('mod/threesixty:deleterespondents', $context);
    print_respondent_table($activity->id, $analysis->id, $user->id, $canremind, $candelete);
}
else {
    //print threesixty_user_listing($activity, $baseurl);
	print print_participants_listing($activity, $baseurl);
}

print_footer($course);

function print_participants_listing($activity, $baseurl)
{
	global $CFG;
	
    if ($users = threesixty_users($activity)) {
        $table = new object();
        $table->head = array(get_string('name'), get_string('numberrespondents', 'threesixty'));
		$table->head[] = get_string('self:responseoptions', 'threesixty');
        $table->data = array();
		foreach ($users as $user) {
			$name = format_string(fullname($user));
			$userurl = "<a href=".$CFG->wwwroot."/user/view.php?id={$user->id}&course={$activity->course}>".$name."</a>";
            $selectlink = "<a href=\"$baseurl&amp;userid=$user->id\">View</a>";

			$numrespondents = count_respondents($user->id, $activity->id);
            $table->data[] = array($userurl, $numrespondents, $selectlink);
		}
		return get_string('selectuser', 'threesixty').print_table($table, true);
	}
	else
	{
		return get_string('nousersfound', 'threesixty');
	}
}
function generate_uniquehash($email)
{
    $timestamp = time();
    $salt = mt_rand();
    return sha1("$salt $email $timestamp");
}

function send_email($recipientemail, $messageid, $extrainfo)
{
    // Fake user object necessary for email_to_user()
    $user = new object();
    $user->id = 0; // required for bounce handling and get_user_preferences()
    $user->email = $recipientemail;

    $a = new object();
    $a->url = $extrainfo['url'];
    $a->userfullname = $extrainfo['userfullname'];

    $from = $extrainfo['userfullname'];
    $subject = get_string("email:{$messageid}subject", 'threesixty', $a);
    $messagetext = get_string("email:{$messageid}body", 'threesixty', $a);

    return email_to_user($user, $from, $subject, $messagetext);
}

function request_respondent($formfields, $analysisid, $senderfullname)
{
    $respondent = new object();
    $respondent->analysisid = $analysisid;
    $respondent->email = strtolower($formfields->email);
    $respondent->type = (int)$formfields->type;
    $respondent->uniquehash = generate_uniquehash($formfields->email);

    $extrainfo = array('url' => RESPONSE_BASEURL . $respondent->uniquehash,
                       'userfullname' => $senderfullname);
    if (!send_email($respondent->email, 'request', $extrainfo)) {
        error_log("threesixty: could not send request email to $respondent->email");
        return false;
    }

    if (!$respondent->id = insert_record('threesixty_respondent', $respondent)) {
        error_log("threesixty: cannot insert respondent email=$respondent->email");
        return false;
    }

    return true;
}

function send_reminder($respondentid, $senderfullname)
{
    if (!$respondent = get_record('threesixty_respondent', 'id', $respondentid)) {
        error_log("threesixty: cannot find respondent id=$respondentid");
        return false;
    }

    $extrainfo = array('url' => RESPONSE_BASEURL. $respondent->uniquehash,
                       'userfullname' => $senderfullname);
    if (!send_email($respondent->email, 'reminder', $extrainfo)) {
        error_log("threesixty: could not send reminder email to $respondent->email");
        return false;
    }

    return true;
}

function print_respondent_table($activityid, $analysisid, $userid, $canremind=false, $candelete=false)
{
  global $CFG, $typelist, $USER;

  $respondents = threesixty_get_external_respondents($analysisid);
  if ($respondents) {
    $table = new object();
    $table->head = array(get_string('email'), get_string('respondenttype', 'threesixty'),
                         get_string('completiondate', 'threesixty'));
    if ($candelete or $canremind) {
        $table->head[] = '&nbsp;';
    }
    $table->data = array();

    foreach ($respondents as $respondent) {
        $data = array();
        $data[] = format_string($respondent->email);

        if (empty($typelist[$respondent->type])) {
            $data[] = get_string('unknown');
        }
        else {
            $data[] = $typelist[$respondent->type];
        }

        if (empty($respondent->timecompleted)) {
            $data[] = get_string('none');
        }
        else {
            $data[] = userdate($respondent->timecompleted, get_string('strftimedate'));
        }

        // Action buttons
        $buttons = '';
        if ($canremind and empty($respondent->timecompleted)) {
            $link = 'respondents.php';
            $options = array('a' => $activityid, 'remind' => $respondent->id,
                             'userid' => $userid, 'sesskey' => $USER->sesskey);
            $buttons .= print_single_button($link, $options, get_string('remindbutton', 'threesixty'), 'post', '_self', true);
        }
        if ($candelete) {
            $link = 'respondents.php';
            $options = array('a' => $activityid, 'delete' => $respondent->id,
                             'userid' => $userid, 'sesskey' => $USER->sesskey);
            $buttons .= print_single_button($link, $options, get_string('delete'), 'post', '_self', true);
        }
        if (!empty($buttons)) {
            $data[] = $buttons;
        }

        $table->data[] = $data;
      }
      print_table($table);
    }
    else {
      print_box_start();
      echo "No respondents have been entered yet.";
      print_box_end();
    }
}
function threesixty_get_external_respondents($analysisid){
  global $CFG;
  
  $sql = "SELECT rt.id, rt.email, rt.type, re.timecompleted
              FROM {$CFG->prefix}threesixty_respondent rt
   LEFT OUTER JOIN {$CFG->prefix}threesixty_response re ON re.respondentid = rt.id
             WHERE rt.analysisid = $analysisid
               AND rt.uniquehash IS NOT NULL
          ORDER BY rt.email";

  $respondents = get_records_sql($sql);

  return $respondents;
}
function count_respondents($userid, $activityid)
{
    global $CFG;
	$sql = "SELECT COUNT(1) FROM ". $CFG->prefix."threesixty_respondent r";
	$sql .= " JOIN ".$CFG->prefix."threesixty_analysis a ON r.analysisid = a.id";
	$sql .= " WHERE a.userid = ".$userid." AND a.activityid = ".$activityid." AND r.uniquehash IS NOT NULL";
	return count_records_sql($sql);
}