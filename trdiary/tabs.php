<?php
/**
 * Sets up the tabs used by the training diary pages based on the users capabilites.
 *
 * @author Tim Hunt and others.
 * @author Francois Marier <francois@catalyst.net.nz>
 * @author Simon Coggins <simonc@catalyst.net.nz>
 * @package mod/threesixty
 */

if (empty($activity)) {
    error('You cannot call this script in that way');
}
if (!isset($currenttab)) {
    $currenttab = '';
}
if (!isset($cm)) {
    $cm = get_coursemodule_from_instance('trdiary', $trdiary->id);
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

$tabs = array();
$row = array();

if (has_capability('mod/trdiary:view', $context)) {
    $row[] = new tabobject('pdp', "$CFG->wwwroot/mod/trdiary/view.php?a=$activity->id", 
                           get_string('tab:pdp', 'trdiary'));
}
if (has_capability('mod/trdiary:view', $context)) {
    $row[] = new tabobject('reflog', "$CFG->wwwroot/mod/trdiary/reflog.php?a=$activity->id", 
                           get_string('tab:reflog', 'trdiary'));
}
if (has_capability('mod/trdiary:manage', $context)) {
    $row[] = new tabobject('users', "$CFG->wwwroot/mod/trdiary/users.php?a=$activity->id", 
                           get_string('tab:users', 'trdiary'));
}
$tabs[] = $row;

print_tabs($tabs, $currenttab);

