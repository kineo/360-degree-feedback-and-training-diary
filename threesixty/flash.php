<?php

require_once("../../config.php");
require_once("locallib.php");

// the colours the lines will be drawn in
$linecolours = array("0xFFCC00", "0x66CC00", "0xCC66FF", "0x3366FF", "0xFF3399", "0x336600", "0x66FFFF", "0xFF0000", "0x990033", "0x0000FF", "0x999966", "0x99FF00");

// process params
$analysisid = required_param("analysisid", PARAM_INT);
$activityid = required_param("activityid", PARAM_INT);
$filter = required_param("filter", PARAM_ALPHANUM);

// setup respondent types and colours
$respondenttypes = explode("\n", get_config(null, 'threesixty_respondenttypes'));
$selfresponsetypes = explode("\n", get_config(null, 'threesixty_selftypes'));
$linecolour_index = 0;
$linecolour_by_filter = array();
//$linecolour_by_filter["self"] = $linecolours[$linecolour_index++];
if (!empty($selfresponsetypes)) {
	foreach ($selfresponsetypes as $key => $value) {
		$linecolour_by_filter["self$key"] = $linecolours[$linecolour_index++];
	}
}
if (!empty($respondenttypes)) {
	foreach ($respondenttypes as $key => $value) {
		$linecolour_by_filter["type$key"] = $linecolours[$linecolour_index++];
	}
}
$linecolour_by_filter["average"] = $linecolours[$linecolour_index++];

// work out the scores depending on the requested filter
$score = null;
if (strpos($filter, "self") === 0) {
        $typeid = substr($filter, 4);
	$score = threesixty_get_self_scores($analysisid, false, $typeid);
} else if ($filter === "average") {
	$score = threesixty_get_average_skill_scores($analysisid, false, false);
} else if (strpos($filter, "type") === 0) {
	$typeid = substr($filter, 4);
	$score = threesixty_get_average_skill_scores($analysisid, $typeid, false);
}

// write out scores for Flash to render
$s = "";
if ($score !== null) {
	$s .= "result=success";
	$s .= "&name=";
	$s .= preg_replace('/[\r\n]/', '', $score->name);
	$s .= "&colour=";
	$s .= $linecolour_by_filter[$filter];
	
	// ensure the skills are displayed by Flash in the same order as the results in the query
	$skills = threesixty_get_skill_names($activityid);
	$ordinal = 0; 
	foreach ($skills as $skill) {
		$s .= "&skill_";
		$s .= urlencode($skill->competencyname);
		$s .= "_";
		$s .= urlencode($skill->skillname) . "_";
		$s .= $ordinal . "=";
		if (empty($score->records[$skill->id]) || !$score->records[$skill->id]->score) {
			$s .= "0";
		} else {
			$s .= round($score->records[$skill->id]->score);
		}
		++$ordinal;
	}
} else {
	$s .= "result=error";
}
echo $s;

?>
