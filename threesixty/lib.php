<?php

require_once 'locallib.php';

/**
 * Library of functions and constants for module threesixty
 * This file should have two well differenced parts:
 *   - All the core Moodle functions, neeeded to allow
 *     the module to work integrated in Moodle.
 *   - All the threesixty specific functions, needed
 *     to implement all the module logic. Please, note
 *     that, if the module become complex and this lib
 *     grows a lot, it's HIGHLY recommended to move all
 *     these module specific functions to a new php file,
 *     called "locallib.php" (see forum, quiz...). This will
 *     help to save some memory when Moodle is performing
 *     actions across all modules.
 */

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $threesixty An object from the form in mod_form.php
 * @return int The id of the newly inserted threesixty record
 */
function threesixty_add_instance($threesixty) {

    $threesixty->timecreated = time();
    $threesixty->timemodified = $threesixty->timecreated;

    # You may have to add extra stuff in here #

    return insert_record('threesixty', $threesixty);
}


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $threesixty An object from the form in mod_form.php
 * @return boolean Success/Fail
 */
function threesixty_update_instance($threesixty) {

    $threesixty->timemodified = time();
    $threesixty->id = $threesixty->instance;

    # You may have to add extra stuff in here #

    return update_record('threesixty', $threesixty);
}


/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function threesixty_delete_instance($id) {

    if (!$threesixty = get_record('threesixty', 'id', $id)) {
        return false;
    }

    begin_sql();

    // Delete all competencies and skills
    if ($competencies = get_records('threesixty_competency', 'activityid', $id, '', 'id')) {
        foreach ($competencies as $competency) {
            if (!threesixty_delete_competency($competency->id, true)) {
                rollback_sql();
                return false;
            }
        }
    }

    // Delete all analysis records
    if ($analyses = get_records('threesixty_analysis', 'activityid', $id)) {
        foreach ($analyses as $analysis) {
            if (!threesixty_delete_analysis($analysis->id, true)) {
                rollback_sql();
                return false;
            }
        }
    }

    if (!delete_records('threesixty', 'id', $threesixty->id)) {
        rollback_sql();
        return false;
    }

    commit_sql();
    return true;
}


/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @todo Finish documenting this function
 */
function threesixty_user_outline($course, $user, $mod, $threesixty) {
    return $return;
}


/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function threesixty_user_complete($course, $user, $mod, $threesixty) {
    return true;
}


/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in threesixty activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function threesixty_print_recent_activity($course, $isteacher, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}


/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function threesixty_cron () {
    return true;
}


/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of threesixty. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $threesixtyid ID of an instance of this module
 * @return mixed boolean/array of students
 */
function threesixty_get_participants($threesixtyid) {
    return false;
}


/**
 * This function returns if a scale is being used by one threesixty
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $threesixtyid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 */
function threesixty_scale_used($threesixtyid, $scaleid) {
    $return = false;

    //$rec = get_record("threesixty","id","$threesixtyid","scale","-$scaleid");
    //
    //if (!empty($rec) && !empty($scaleid)) {
    //    $return = true;
    //}

    return $return;
}


/**
 * Checks if scale is being used by any instance of threesixty.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any threesixty
 */
function threesixty_scale_used_anywhere($scaleid) {
    if ($scaleid and record_exists('threesixty', 'grade', -$scaleid)) {
        return true;
    } else {
        return false;
    }
}


/**
 * Execute post-install custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function threesixty_install() {
    return true;
}


/**
 * Execute post-uninstall custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function threesixty_uninstall() {
    return true;
}
