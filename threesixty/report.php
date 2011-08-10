<?php

/**
 * Table and Spiderweb reports
 *
 * @author  Francois Marier <francois@catalyst.net.nz>
 * @package mod/threesixty
 */

require_once '../../config.php';
require_once 'locallib.php';
require_once 'report_form.php';

define('AVERAGE_PRECISION', 1); // number of decimal places when displaying averages
define("SPIDERWEB_IMPL_KINEO", true); // whether to implement the spiderweb using Kineo's method (as opposed to the company that was originally outsourced to)

$a      = required_param('a', PARAM_INT);  // threesixty instance ID
$type   = optional_param('type', 'table', PARAM_ALPHA); // report type
$userid = optional_param('userid', 0, PARAM_INT); // user's data to examine
$basetype = optional_param('base', 'self0', PARAM_ALPHANUM); // Score to do gap analysis from.

if (!$activity = get_record('threesixty', 'id', $a)) {
    error('Course module is incorrect');
}
if (!$course = get_record('course', 'id', $activity->course)) {
    error('Course is misconfigured');
}
if (!$cm= get_coursemodule_from_instance('threesixty', $activity->id, $course->id)) {
    error('Course Module ID was incorrect');
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

require_login($course, true, $cm);

if (!has_capability('mod/threesixty:viewreports', $context)) {
    require_capability('mod/threesixty:viewownreports', $context);
    $userid = $USER->id; // force same user
}

$user = null;
if ($userid > 0 and !$user = get_record('user', 'id', $userid, '', 'id, firstname, lastname')) {
    error('Invalid User ID');
}

$baseurl = "report.php?a=$activity->id&amp;type=$type";

$mform = null;
$filters = array();
if (isset($user)) {
    $currenturl = "$baseurl&amp;userid=$user->id";

    $selfresponsetypes = explode("\n", get_config(null, 'threesixty_selftypes'));
    $respondenttypes = explode("\n", get_config(null, 'threesixty_respondenttypes'));

    if (!empty($selfresponsetypes)) {
      foreach ($selfresponsetypes as $key => $value) {
        $v = trim($value);
        if (!empty($v)){
          $filters["self$key"] = $v;
        }
      }
    }
    if (!empty($respondenttypes)) {
        foreach ($respondenttypes as $key => $value) {
            $v = trim($value);
            if (!empty($v)) {
                $filters["type$key"] = $v;
            }
        }
    }
    $filters['average'] = get_string('filter:average', 'threesixty');

    $mform =& new mod_threesity_report_form(null, compact('a', 'type', 'userid', 'filters'));

    // Apply the filters
    if ($fromform = $mform->get_data()) {
        foreach ($filters as $code => $name) {
            if (empty($fromform->checkarray[$code])) {
                unset($filters[$code]); // 'code' is not checked, remove it
            }
        }
    }

    add_to_log($course->id, 'threesixty', 'report', $currenturl, $activity->id);
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
$currenttab = 'reports';
include 'tabs.php';

if (isset($mform)) {

    if (!$analysis = get_record('threesixty_analysis', 'activityid', $activity->id, 'userid', $user->id)) {
        print_error('error:nodataforuserx', 'threesixty', $baseurl, fullname($user));
    }
    
    $currentinvitations = count_records_sql("SELECT COUNT(1) FROM ".$CFG->prefix.
                  "threesixty_respondent WHERE analysisid = ".$analysis->id." AND uniquehash IS NOT NULL");
	$remaininginvitations = $activity->requiredrespondents - $currentinvitations;
	
	if($remaininginvitations>0) {
		echo "<br />";
		notice(get_string("respondentsremaining", "threesixty"), "$CFG->wwwroot/mod/threesixty/respondents.php?a=$activity->id");
	}

    print threesixty_selected_user_heading($user, $course->id, $baseurl, has_capability('mod/threesixty:viewreports', $context));
    $mform->display();

    // Display filters and scores
    if ('table' == $type) {
        $scores = get_scores($analysis->id, $filters, false);
        $skillnames = threesixty_get_skill_names($activity->id);
        $feedback = threesixty_get_feedback($analysis->id);
        
        echo "<div class='scoretables'>";
        print_score_table($skillnames, $scores, $feedback, $baseurl.'&userid='.$user->id, $basetype);
        echo "</div>";
    }
    elseif ('spiderweb' == $type) {
    	if (SPIDERWEB_IMPL_KINEO) {
	    	print_spiderweb_kineo($analysis->id, $activity->id, $filters);
    	} else {
	        $scores = get_scores($analysis->id, $filters, true);
	        $competencynames = get_records('threesixty_competency', 'activityid', $activity->id, 'name', 'id, name');
	        print_spiderweb($competencynames, $scores);
    	}
    }
    else {
        print_error('error:invalidreporttype', 'threesixty', "view.php?a=$activity->id", $type);
    }
}
else {
    print threesixty_user_listing($activity, $baseurl);
}

print_footer($course);


function print_score_table($skills, $scores, $feedback, $url, $basetype)
{
	$base_score = $scores[$basetype];
	//Set up the column names.
    $header = array('&nbsp;');
    foreach($scores as $score) {
		if($score->type != $basetype){
        	$header[] = '<a href="'.$url.'&base='.$score->type.'">'.$score->name.'</a>';
		}
		else {
			$header[] = format_string($score->name);
		}
    }

    $curcompetency = 0;
    $table = null;
    foreach($skills as $skill) {
        if ($curcompetency != $skill->competencyid) {
            
            if($table){
                print_table($table);
                
                print_feedback_table($feedback, $curcompetency);
            }
            echo '<div class="competencyname">'.format_string($skill->competencyname).'</div>';
            $table = new object();
            $table->head = $header;
            $table->width = '100%';

            $curcompetency = $skill->competencyid;
        }

        $data = array("<span class='skillname'>". format_string($skill->skillname)."</span>");
        foreach($scores as $scorecolumn) {
            if (empty($scorecolumn->records[$skill->id]) or !$scorecolumn->records[$skill->id]->score) {
                if(!empty($scorecolumn->records[$skill->id]) && $scorecolumn->records[$skill->id]->score==0){
					$data[] = get_string('notapplicable', 'threesixty');
				}
				else {
					$data[] = get_string('noscore', 'threesixty');					
				}
            }
            else {
              if($base_score && $base_score->records){
                  $roundedscore = round($scorecolumn->records[$skill->id]->score, AVERAGE_PRECISION);
                    if($roundedscore > $base_score->records[$skill->id]->score){
                      $data[]="<span class='scorebigger'>".format_string($roundedscore)."</span>";
                    }
                    elseif($roundedscore < $base_score->records[$skill->id]->score){
                            $data[]="<span class='scoresmaller'>".format_string($roundedscore)."</span>";
                    }
                    else{
                            $data[] = "<span class='scoreeven'>".format_string($roundedscore)."</span>";
                    }
                }
                else{
                    $roundedscore = round($scorecolumn->records[$skill->id]->score, AVERAGE_PRECISION);
                    $data[] = format_string($roundedscore);
               }
            }
        }
        $table->data[] = $data;
    }
    print_table($table);
    
    print_feedback_table($feedback, $curcompetency);
    
}

function print_feedback_table($feedback,$curcompetency) {
	global $context;
    
	if (has_capability('mod/threesixty:feedbackview', $context)) {
		//Set up the column names.
	    $header = array(get_string("feedback", "threesixty"));
	    $table = new object();
	    $table->head = $header;
	    $table->width = '100%';
	    foreach ($feedback as $f) {
	        if ($f->competencyid == $curcompetency) {
	            $table->data[] = array("<span class='feedback'>".$f->feedback."</span>");
	        }
	    }
	    
	    print_table($table);
	}
}

function get_scores($analysisid, $filters, $competencyaverage)
{
    $scores = array();

    foreach ($filters as $code => $name) {
        if (strpos($code, 'self') === 0) {
            $typeid = substr($code, 4);
            $scores[$code] = threesixty_get_self_scores($analysisid, $competencyaverage, $typeid);
        }
        elseif ('average' == $code) {
            $scores[$code] = threesixty_get_average_skill_scores($analysisid, false, $competencyaverage);
        }
        elseif (strpos($code, 'type') === 0) {
            // Normal respondent types
            $typeid = substr($code, 4);
            $scores[$code] = threesixty_get_average_skill_scores($analysisid, $typeid, $competencyaverage);
        }
        else {
            error_log("Invalid respondent type filter: $code");
        }
    }

    return $scores;
}

function print_spiderweb($competencies, $scores)
{
    require_once('php-ofc-library/open-flash-chart.php');
    require_once('php-ofc-library/ofc_sugar.php');

    $chart = new open_flash_chart();

    // All of these colours are on the Web safe colour palette
    $linecolours = array('#FFCC00', '#66CC00', '#CC66FF', '#3366FF', '#FF3399', '#336600',
                         '#66FFFF', '#FF0000', '#990033', '#0000FF', '#999966', '#99FF00');

    foreach($scores as $scoreline) {
        $line = new line();

        $points = array();
        foreach($competencies as $comp) {
            if (empty($scoreline->records[$comp->id]) or !$scoreline->records[$comp->id]->score) {
                $points[] = null;
            }
            else {
                $roundedscore = round($scoreline->records[$comp->id]->score, AVERAGE_PRECISION);
                $points[] = $roundedscore;
            }
        }

        $linecolour = array_shift($linecolours);

        $line->set_values($points);
        $line->set_default_dot_style(new s_box($linecolour, 4));
        $line->set_width(1);
        $line->set_colour($linecolour);
        $line->set_tooltip("#val#");
        $line->set_key($scoreline->name, 10);
        $line->loop();

        $chart->add_element($line);
    }

    $r = new radar_axis(5);

    $LIGHTGRAY = '#CCCCCC';
    $r->set_colour($LIGHTGRAY);
    $r->set_grid_colour($LIGHTGRAY);

    $DARKGRAY = '#666666';
    $labels = new radar_axis_labels(array('', '1','2','3','4','5'));
    $labels->set_colour($DARKGRAY);
    $r->set_labels($labels);

    $competencynames = array();
    foreach ($competencies as $comp) {
        $competencynames[] = $comp->name;
    }

    $spoke_labels = new radar_spoke_labels($competencynames);
    $spoke_labels->set_colour($DARKGRAY);
    $r->set_spoke_labels($spoke_labels);

    $chart->set_radar_axis($r);

    $tooltip = new tooltip();
    $tooltip->set_proximity();
    $chart->set_tooltip($tooltip);

    $WHITE = '#FFFFFF';
    $chart->set_bg_colour($WHITE);

    include 'spiderwebchart.html';
}

function print_spiderweb_kineo($analysisid, $activityid, $filters) {
	global $CFG;
	
	// determine the PHP script that Flash will invoke to get the data it needs
	$scriptURL = $CFG->wwwroot . "/mod/threesixty/flash.php";
	
	// bring in the HTML page which embeds the SWF
	include("spiderwebchart_kineo.html");
}
