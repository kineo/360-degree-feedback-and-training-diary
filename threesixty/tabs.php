<?php

/**
 * Sets up the tabs used by the threesixty pages based on the users capabilites.
 *
 * @author Francois Marier <francois@catalyst.net.nz>
 * @package mod/threesixty
 */

if (empty($activity)) {
    error('You cannot call this script in that way');
}
if (!isset($currenttab)) {
    $currenttab = '';
}
if (!isset($cm)) {
    $cm = get_coursemodule_from_instance('threesixty', $threesixty->id);
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

$tabs = array();
$row  = array();
$activated = array();
$inactive = array();

$row[] = new tabobject('activity', "$CFG->wwwroot/mod/threesixty/profiles.php?a=$activity->id", get_string('tab:activity', 'threesixty'));
$row[] = new tabobject('respondents', "$CFG->wwwroot/mod/threesixty/respondents.php?a=$activity->id", get_string('tab:respondents', 'threesixty'));
if (has_capability('mod/threesixty:viewreports', $context) or has_capability('mod/threesixty:viewownreports', $context)) {
    $row[] = new tabobject('reports', "$CFG->wwwroot/mod/threesixty/report.php?a=$activity->id", get_string('tab:reports', 'threesixty'));
}
if (has_capability('mod/threesixty:manage', $context)) {
    $row[] = new tabobject('edit', "$CFG->wwwroot/mod/threesixty/edit.php?a=$activity->id", get_string('tab:edit', 'threesixty'));
}

if (count($row) == 1) {
    // If there's only one tab, don't show the tab bar
} else {
    $tabs[] = $row;
}


$useridparam = '';
if (isset($user)) {
    $useridparam = "&amp;userid=$user->id";
}

if ($currenttab == 'reports' and isset($type)) {
    $activated[] = 'reports';

    $row  = array();
    $currenttab = $type;

    $url = "$CFG->wwwroot/mod/threesixty/report.php?a=$activity->id{$useridparam}";
    $row[] = new tabobject('table', $url . '&amp;type=table', get_string('report:table', 'threesixty'));
    $row[] = new tabobject('spiderweb', $url . '&amp;type=spiderweb', get_string('report:spiderweb', 'threesixty'));

    $tabs[] = $row;
}

if ($currenttab == 'edit' and isset($section)) {
    $activated[] = 'edit';

    $row  = array();
    $currenttab = $section;
    $row[] = new tabobject('competencies', "$CFG->wwwroot/mod/threesixty/edit.php?a=$activity->id",
                           get_string('edit:competencies', 'threesixty'));
    $row[] = new tabobject('carryover', "$CFG->wwwroot/mod/threesixty/carryover.php?a=$activity->id{$useridparam}",
                           get_string('edit:carryover', 'threesixty'));

    $tabs[] = $row;
}

print_tabs($tabs, $currenttab, $inactive, $activated);
