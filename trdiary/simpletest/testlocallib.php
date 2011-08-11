<?php // $Id$
/*
**
 * Unit tests for mod/trdiary/locallib.php
 *
 * @author Simon Coggins <simonc@catalyst.net.nz>
 * @package mod/trdiary
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot . '/mod/trdiary/locallib.php');
require_once($CFG->libdir . '/simpletestlib.php');

class locallib_test extends prefix_changing_test_case {
    // test data for database

    var $user_data = array(
            array('id', 'firstname', 'lastname', 'username'),
            array(2, 'admin', 'user', 'adminuser')
        );

    var $event_data = array(
            array('id', 'name', 'description', 'format', 'courseid', 'groupid',
                'userid', 'modulename', 'instance', 'eventtype', 'timestart', 
                'timeduration', 'visible'),
            array(1, 'test event', 'event desc', 'FORMAT_PLAIN', 1, 0, 2, 
                'trdiary', 1, 'open', 0, 0, 1)
            );

    var $threesixty_data = array(
            array('id', 'course', 'name', 'competenciescarried', 'requiredrespondents', 'timecreated', 'timemodified'),
            array(1, 1, 'Test 360', 3, 10, '1255991305', 0),
            array(2, 1, 'Test 360 2', 3, 10, '1255991306', 0)
        );

    var $threesixty_competency_data = array(
            array('id', 'activityid', 'name', 'description', 'showfeedback'),
            array(1, 1, 'C1', 'C1D', 1),
            array(2, 1, 'C2', 'C2D', 1),
            array(3, 1, 'C3', 'C3D', 0)
        );

    var $threesixty_skill_data = array(
            array('id', 'competencyid', 'name', 'description'),
            array(1, 1, 'S1', 'S1D'),
            array(2, 1, 'S2', 'S2D'),
            array(3, 2, 'S3', 'S3D'),
            array(4, 2, 'S4', 'S4D'),
            array(5, 3, 'S5', 'S5D'),
            array(6, 3, 'S6', 'S6D')
        );

    var $threesixty_analysis_data = array(
            array('id', 'activityid', 'userid'),
            array(1, 1, 2)
        );

    var $threesixty_carried_comp_data = array(
            array('id', 'analysisid', 'competencyid'),
            array(1, 1, 1),
            array(2, 1, 2),
            array(3, 1, 3)
        );

    var $trdiary_data = array(
            array('id', 'course', 'name', 'logfreq', 'threesixtyid', 'timecreated', 'timemodified'),
            array(1, 1, 'Test Training Diary', 7, 1, 1255991305, 0)
        );

    var $trdiary_pdp_skill_data = array(
            array('id', 'trdiaryid', 'userid', 'skillid', 'priority', 'isstrength'),
            array(1, 1, 2, 1, 0, 0),
            array(2, 1, 2, 2, 0, 0),
            array(3, 1, 2, 3, 0, 0),
            array(4, 1, 2, 4, 0, 0),
            array(5, 1, 2, 5, 0, 0),
            array(6, 1, 2, 6, 0, 0)
        );

    var $trdiary_pdp_field_data = array(
            array('id', 'trdiaryid', 'name'),
            array(1, 1, 'A1'),
            array(2, 1, 'A2')
        );

    var $trdiary_pdp_value_data = array(
            array('id', 'fieldid', 'userid', 'value'),
            array(1, 1, 2, 'A1V'),
            array(1, 2, 2, 'A2V')
        );

    var $trdiary_reflog_field_data = array(
            array('id', 'trdiaryid', 'name'),
            array(1, 1, 'RF1'),
            array(2, 1, 'RF2')
        );

    var $trdiary_reflog_entry_data = array(
            array('id', 'trdiaryid', 'userid', 'timecreated'),
            array(1, 1, 2, 1255991307),
            array(2, 1, 2, 1255991308)
        );

    var $trdiary_reflog_value_data = array(
            array('id', 'fieldid', 'entryid', 'value'),
            array(1, 1, 1, 'RF1V1'),
            array(2, 2, 1, 'RF1V2'),
            array(1, 1, 2, 'RF2V1'),
            array(2, 2, 2, 'RF2V2')
        );

    function setUp() {
        global $db,$CFG;
        parent::setup();
        load_test_table($CFG->prefix . 'user', $this->user_data, $db);
        load_test_table($CFG->prefix . 'event', $this->event_data, $db);
        load_test_table($CFG->prefix . 'threesixty', $this->threesixty_data, $db);
        load_test_table($CFG->prefix . 'threesixty_competency', $this->threesixty_competency_data, $db);
        load_test_table($CFG->prefix . 'threesixty_skill', $this->threesixty_skill_data, $db);
        load_test_table($CFG->prefix . 'threesixty_analysis', $this->threesixty_analysis_data, $db);
        load_test_table($CFG->prefix . 'threesixty_carried_comp', $this->threesixty_carried_comp_data, $db);
        load_test_table($CFG->prefix . 'trdiary', $this->trdiary_data, $db);
        load_test_table($CFG->prefix . 'trdiary_pdp_skill', $this->trdiary_pdp_skill_data, $db);
        load_test_table($CFG->prefix . 'trdiary_pdp_field', $this->trdiary_pdp_field_data, $db);
        load_test_table($CFG->prefix . 'trdiary_pdp_value', $this->trdiary_pdp_value_data, $db);
        load_test_table($CFG->prefix . 'trdiary_reflog_field', $this->trdiary_reflog_field_data, $db);
        load_test_table($CFG->prefix . 'trdiary_reflog_entry', $this->trdiary_reflog_entry_data, $db);
        load_test_table($CFG->prefix . 'trdiary_reflog_value', $this->trdiary_reflog_value_data, $db);
    }

    function tearDown() {
        global $db,$CFG;
        
        remove_test_table($CFG->prefix.'unittest_trdiary_reflog_value', $db);
        remove_test_table($CFG->prefix.'unittest_trdiary_reflog_entry', $db);
        remove_test_table($CFG->prefix.'unittest_trdiary_reflog_field', $db);
        remove_test_table($CFG->prefix.'unittest_trdiary_pdp_value', $db);
        remove_test_table($CFG->prefix.'unittest_trdiary_pdp_field', $db);
        remove_test_table($CFG->prefix.'unittest_trdiary_pdp_skill', $db);
        remove_test_table($CFG->prefix.'unittest_trdiary', $db);
        remove_test_table($CFG->prefix.'unittest_threesixty_carried_comp', $db);
        remove_test_table($CFG->prefix.'unittest_threesixty_analysis', $db);
        remove_test_table($CFG->prefix.'unittest_threesixty_skill', $db);
        remove_test_table($CFG->prefix.'unittest_threesixty_competency', $db);
        remove_test_table($CFG->prefix.'unittest_threesixty', $db);
        remove_test_table($CFG->prefix.'unittest_event', $db);
        remove_test_table($CFG->prefix.'unittest_user', $db);
        
        parent::tearDown();
    }

    function test_mod_trdiary_get_threesixty_instance() {
        $courseid = 1;
        $courseid_2 = 999;

        $this->assertEqual(trdiary_get_threesixty_instance($courseid),1);
        $this->assertFalse(trdiary_get_threesixty_instance($courseid_2));
    }

    function test_mod_trdiary_get_linked_threesixty() {
        $trdiaryid = 1;
        $trdiaryid_2 = 999;

        $obj = new stdClass();
        $obj->name = 'Test 360';
        $obj->id = 1;
        $this->assertEqual(trdiary_get_linked_threesixty($trdiaryid), $obj);
        $this->assertFalse(trdiary_get_linked_threesixty($trdiaryid_2));
    } 

    function test_mod_trdiary_has_pdp_skills() {
        $userid = 2;
        $userid_2 = 999;
        $activityid = 1;

        $this->assertTrue(has_pdp_skills($userid, $activityid));
        $this->assertFalse(has_pdp_skills($userid_2, $activityid));
    }

    function test_mod_create_pdp_skills() {
        $userid = 2;
        $userid_2 = 999;
        $trdiaryid = 1;
        $threesixtyid = 1;
        $this->assertTrue(create_pdp_skills($userid, $threesixtyid, $trdiaryid));
        //$this->assertFalse(create_pdp_skills($userid_2, $threesixtyid, $trdiaryid)); 
    }

    function test_mod_get_pdp_skills() {
        $userid = 2;
        $userid_2 = 999;
        $activityid = 1;
        $activityid_2 = 999;

        $this->assertEqual(count(get_pdp_skills($userid, $activityid)),6);
        $this->assertFalse(get_pdp_skills($userid_2, $activityid));
        $this->assertFalse(get_pdp_skills($userid, $activityid_2));
    }

    function test_mod_get_pdp_extra_fields() {
        $userid = 2;
        $userid_2 = 999;
        $activityid = 1;
        $activityid_2 = 999;

        $obj = new object();
        $obj->id = 1;
        $obj->name = 'A1';
        $obj->valueid = 1;
        $obj->value = 'A1V';
        $obj2 = new object();
        $obj2->id = 2;
        $obj2->name = 'A2';
        $obj2->valueid = 2;
        $obj2->value = 'A2V';
        $arr = array();
        $arr[0] = $obj;
        $arr[1] = $obj2;
        $this->assertEqual(get_pdp_extra_fields($userid, $activityid), $arr);
        $obj->valueid = null;
        $obj->value = null;
        $obj2->valueid = null;
        $obj2->value = null;
        $arr2 = array();
        $arr2[0] = $obj;
        $arr2[1] = $obj2;
        // if activity is good but user bad, should still return results
        $this->assertEqual(get_pdp_extra_fields($userid_2, $activityid), $arr2);
        $this->assertFalse(get_pdp_extra_fields($userid, $activityid_2));
    }

    function test_mod_update_pdp_entry() {
        $userid = 2;
        $activityid = 1;
        $extrafields = get_pdp_extra_fields($userid, $activityid);
        $fromform = new object();
        $fromform->extrafield1 = 'RF1';
        $fromform->extrafield2 = 'RF2';
        $this->assertTrue(update_pdp_entry($userid, $activityid, $extrafields, $fromform));
        $ret = get_records('trdiary_pdp_value');
        $this->assertEqual($ret[1]->value, 'RF1');
        $this->assertEqual($ret[2]->value, 'RF2');
    }

    function test_mod_get_reflog_fields() {
        $activityid = 1;
        $activityid_2 = 999;
        
        $obj = new stdClass();
        $obj->name = 'RF1';
        $obj->fieldid = 1;
        $obj2 = new stdClass();
        $obj2->name = 'RF2';
        $obj2->fieldid = 2;
        $arr = array();
        $arr[1] = $obj;
        $arr[2] = $obj2;

        $this->assertEqual(get_reflog_fields($activityid), $arr);
        $this->assertFalse(get_reflog_fields($activityid_2));    
    }

    function test_mod_get_reflog_entries() {
        $userid = 2;
        $userid_2 = 999;
        $activityid = 1;
        $activityid_2 = 999;
        $entryid = 1;
        $entryid_null = null;
        $entryid_2 = 999;

        $one = 1;
        $two = 2;
        $obj = new object();
        $obj->id = '1';
        $obj->timecreated = '1255991307';
        $obj->$one = 'RF1V1';
        $obj->$two = 'RF1V2';
        $obj2 = clone $obj;
        $obj2->id = '2';
        $obj2->timecreated = '1255991308';
        $obj2->$one = 'RF2V1';
        $obj2->$two = 'RF2V2';
        $arr = array();
        $arr[1] = $obj;
        $arr2 = array();
        $arr2[2] = $obj2;
        $arr2[1] = $obj;
        $this->assertEqual(get_reflog_entries($userid, $activityid, $entryid),$arr);
        $this->assertFalse(get_reflog_entries($userid, $activityid_2, $entryid));
        $this->assertFalse(get_reflog_entries($userid, $activityid, $entryid_2));
        $this->assertEqual(get_reflog_entries($userid, $activityid, $entryid_null), $arr2);
        $this->assertFalse(get_reflog_entries($userid_2, $activityid, $entryid));
    }

    function test_mod_create_reflog_entry() {
        $userid = 2;
        $activityid = 1;
        $fields = get_reflog_fields($activityid);
        $fromform = new object();
        $fromform->field1 = 'F1';
        $fromform->field2 = 'F2';
        $this->assertEqual(create_reflog_entry($userid, $activityid, $fields, $fromform), 3);
        $this->assertEqual(count_records('trdiary_reflog_entry'), 3);
    }

    function test_mod_delete_reflog_entry() {
        $entryid = 1;
        $this->assertTrue(delete_reflog_entry($entryid));
        $this->assertEqual(count_records('trdiary_reflog_entry'),1);
    }

    function test_mod_update_reflog_entry() {
        $activityid = 1;
        $entryid = 1;
        $fields = get_reflog_fields($activityid); 
        $fromform = new object();
        $fromform->field1id = 1;
        $fromform->field1 = 'RF1V1U';
        $fromform->field2id = 2;
        $fromform->field2 = 'RF1V2U';
        $this->assertTrue(update_reflog_entry($entryid, $fields, $fromform));
        $ret = get_records('trdiary_reflog_value');
        $this->assertEqual($ret[1]->value,'RF1V1U');
        $this->assertEqual($ret[2]->value,'RF1V2U');
    }
    
    function test_mod_build_pdp_table() {
        $userid = 2;
        $userid_2 = 999;
        $activityid = 1;
        $activityid_2 = 999;
        $this->assertFalse(build_pdp_table($userid_2, $activityid));
        $this->assertFalse(build_pdp_table($userid, $activityid_2));
        $this->assertEqual(count(build_pdp_table($userid, $activityid)->data), 9);
    }

    function test_mod_build_reflog_table() {
        $userid = 2;
        $userid_2 = 999;
        $activityid = 1;
        $activityid_2 = 999;
        $datebasedlog = true;
        $context = new object();
        $context->id = 1;
        $context->contextlevel = 10;
        $context->instanceid = 0;
        $context->path = '/1';
        $context->depth = 4;
        $fields = get_reflog_fields($activityid);
        $this->assertEqual(count(build_reflog_table($userid, $activityid, $fields, $datebasedlog, $context)->data), 2);
    }
 
    function test_mod_update_user_skills() {
        $userid = 2;
        $activityid = 1;
        $pdp_skills = get_pdp_skills($userid, $activityid);
        $fromform = new object();
        $fromform->select1 = 3;
        $fromform->isstrength_1 = 3;
        $this->assertTrue(update_user_skills($userid, $activityid, $pdp_skills, $fromform));
        $ret = get_records('trdiary_pdp_skill');
        $this->assertEqual($ret[1]->priority, 3);
        $this->assertEqual($ret[1]->isstrength ,3);
    }

    function test_mod_create_reflog_reminder() {
        $userid = 2;
        $activity = new object;
        $activity->id = 1;
        $activity->course = 1;
        $activity->logfreq = 7;
        create_reflog_reminder($userid, $activity);
        $this->assertEqual(count_records('event'),2);
    }  

}
