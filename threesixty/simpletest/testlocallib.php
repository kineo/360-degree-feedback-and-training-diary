<?php // $Id$
/*
**
 * Unit tests for mod/threesixty/locallib.php
 *
 * @author Simon Coggins <simonc@catalyst.net.nz>
 * @package mod/threesixty
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot . '/mod/threesixty/locallib.php');
require_once($CFG->libdir . '/simpletestlib.php');

class locallib_test extends prefix_changing_test_case {
    // test data for database

    var $user_data = array(
            array('id', 'firstname', 'lastname', 'username'),
            array(1, 'admin', 'user', 'adminuser'),
            array(2, 'test', 'user', 'testuser')
        );

    var $threesixty_data = array(
            array('id', 'course', 'name', 'competenciescarried', 'requiredrespondents', 'timecreated', 'timemodified'),
            array(1, 1, 'Test 360', 3, 10, 1255991305, 0),
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
            array(1, 1, 1),
            array(2, 1, 2)
        );

    var $threesixty_carried_comp_data = array(
            array('id', 'analysisid', 'competencyid'),
            array(1, 1, 1),
            array(2, 1, 2),
            array(3, 1, 3)
        );

    var $threesixty_respondent_data = array(
            array('id', 'email', 'type', 'analysisid', 'uniquehash'),
            array(1, 'test@example.com', 0, 1, '001f78072cd900336a3be617f2546ae03f277125'),
            array(2, 'test2@example.com', 0, 2, 'aaabbb')
        );

    // order of first two entries swapped so respondentid type is set to int
    // (column type is determined by first row when creating tables)
    var $threesixty_response_data = array(
        array('id', 'analysisid', 'respondentid', 'timecompleted'),
            array(1, 1, 1, 1256268568),
            array(2, 1, null, 1256268568),
            array(3, 2, null, 0),
            array(4, 2, 2, 0)
        );

    var $threesixty_response_skill_data = array(
            array('id', 'responseid', 'skillid', 'score'),
            array(1, 1, 1, 5),
            array(2, 1, 2, 4),
            array(3, 1, 3, 3),
            array(4, 1, 4, 2),
            array(5, 1, 5, 1),
            array(6, 1, 6, 0),
            array(7, 2, 1, 3),
            array(8, 2, 2, 3),
            array(9, 2, 3, 3),
            array(10, 2, 4, 3),
            array(11, 2, 5, 3),
            array(12, 2, 6, 3),
            array(13, 3, 1, 1),
            array(14, 3, 2, 1),
            array(15, 3, 3, 2),
            array(16, 3, 4, 2),
            array(17, 3, 5, 3),
            array(18, 3, 6, 3),
            array(19, 4, 1, 5),
            array(20, 4, 2, 5),
            array(21, 4, 3, 5),
            array(22, 4, 4, 0),
            array(23, 4, 5, 0),
            array(24, 4, 6, 0)
        );

    var $threesixty_response_comp_data = array(
            array('id', 'responseid', 'competencyid', 'feedback'),
            array(1, 1, 1, 'C1 R1 feedback'),
            array(2, 1, 2, 'C2 R1 feedback'),
            array(3, 2, 1, 'C1 R2 feedback'),
            array(4, 2, 2, 'C2 R2 feedback')
        );

    function setUp() {
        global $db,$CFG;
        parent::setup();
        load_test_table($CFG->prefix . 'user', $this->user_data, $db);
        load_test_table($CFG->prefix . 'threesixty', $this->threesixty_data, $db);
        load_test_table($CFG->prefix . 'threesixty_competency', $this->threesixty_competency_data, $db);
        load_test_table($CFG->prefix . 'threesixty_skill', $this->threesixty_skill_data, $db);
        load_test_table($CFG->prefix . 'threesixty_analysis', $this->threesixty_analysis_data, $db);
        load_test_table($CFG->prefix . 'threesixty_carried_comp', $this->threesixty_carried_comp_data, $db);
        load_test_table($CFG->prefix . 'threesixty_respondent', $this->threesixty_respondent_data, $db);
        load_test_table($CFG->prefix . 'threesixty_response', $this->threesixty_response_data, $db);
        load_test_table($CFG->prefix . 'threesixty_response_skill', $this->threesixty_response_skill_data, $db);
        load_test_table($CFG->prefix . 'threesixty_response_comp', $this->threesixty_response_comp_data, $db);
    }

    function tearDown() {
        global $db,$CFG;
        
        remove_test_table($CFG->prefix.'unittest_threesixty_response_comp', $db);
        remove_test_table($CFG->prefix.'unittest_threesixty_response_skill', $db);
        remove_test_table($CFG->prefix.'unittest_threesixty_response', $db);
        remove_test_table($CFG->prefix.'unittest_threesixty_respondent', $db);
        remove_test_table($CFG->prefix.'unittest_threesixty_carried_comp', $db);
        remove_test_table($CFG->prefix.'unittest_threesixty_analysis', $db);
        remove_test_table($CFG->prefix.'unittest_threesixty_skill', $db);
        remove_test_table($CFG->prefix.'unittest_threesixty_competency', $db);
        remove_test_table($CFG->prefix.'unittest_threesixty', $db);
        remove_test_table($CFG->prefix.'unittest_user', $db);
        
        parent::tearDown();
    }

    function test_mod_trdiary_get_competency_listing() {
        $activityid = 1;
        $activityid_2 = 999;

        $obj = new object();
        $obj->id = '1';
        $obj->name = 'C1';
        $obj->description = 'C1D';
        $obj->showfeedback = true;
        $obj->skills = 'S1, S2';

        $this->assertEqual(count(threesixty_get_competency_listing($activityid)),3);
        $this->assertEqual(array_shift(threesixty_get_competency_listing($activityid)),$obj);
        $this->assertFalse(threesixty_get_competency_listing($activityid_2));
    }

    function test_mod_threesixty_delete_competency() {
        $competencyid = 1;
        $competencyid_2 = 999;

        $comp_before = count_records('threesixty_competency');
        $skill_before = count_records('threesixty_skill');
        $carried_before = count_records('threesixty_carried_comp');
        $resp_before = count_records('threesixty_response_comp');

        // this should fail and records remain unchanged
        threesixty_delete_competency($competencyid_2);

        $comp_after = count_records('threesixty_competency');
        $skill_after = count_records('threesixty_skill');
        $carried_after = count_records('threesixty_carried_comp');
        $resp_after = count_records('threesixty_response_comp');
        $this->assertEqual($comp_before-$comp_after, 0);
        $this->assertEqual($carried_before - $carried_after, 0);
        $this->assertEqual($skill_before-$skill_after, 0);
        $this->assertEqual($resp_before-$resp_after, 0);

        $comp_before2 = count_records('threesixty_competency');
        $skill_before2 = count_records('threesixty_skill');
        $carried_before2 = count_records('threesixty_carried_comp');
        $resp_before2 = count_records('threesixty_response_comp');

        // now do a real delete
        $this->assertTrue(threesixty_delete_competency($competencyid));

        $comp_after2 = count_records('threesixty_competency');
        $skill_after2 = count_records('threesixty_skill');
        $carried_after2 = count_records('threesixty_carried_comp');
        $resp_after2 = count_records('threesixty_response_comp');
        // deleting this competency should delete 1 competency, 1 carried comp,
        // 2 response competencies and 2 skills
        $this->assertEqual($comp_before2 - $comp_after2, 1);
        $this->assertEqual($carried_before2 - $carried_after2, 1);
        $this->assertEqual($skill_before2 - $skill_after2, 2);
        $this->assertEqual($resp_before2 - $resp_after2, 2);

    }

    // this also tests threesixty_delete_response() as it is called
    // from threesixty_delete_analysis
    function test_mod_threesixty_delete_analysis() {
        $analysisid = 1;
        $analysisid_2 = 999;

        $analysis_before = count_records('threesixty_analysis');
        $carried_before = count_records('threesixty_carried_comp');
        $resp_before = count_records('threesixty_response_comp');
        $respondent_before = count_records('threesixty_respondent');

        // this should fail and records remain unchanged
        threesixty_delete_analysis($analysisid_2);

        $analysis_after = count_records('threesixty_analysis');
        $carried_after = count_records('threesixty_carried_comp');
        $resp_after = count_records('threesixty_response_comp');
        $respondent_after = count_records('threesixty_respondent');

        $this->assertEqual($analysis_before-$analysis_after, 0);
        $this->assertEqual($carried_before - $carried_after, 0);
        $this->assertEqual($resp_before-$resp_after, 0);
        $this->assertEqual($respondent_before-$respondent_after, 0);

        $analysis_before2 = count_records('threesixty_analysis');
        $carried_before2 = count_records('threesixty_carried_comp');
        $resp_before2 = count_records('threesixty_response_comp');
        $respondent_before2 = count_records('threesixty_respondent');

        // now do a real delete       
        $this->assertTrue(threesixty_delete_analysis($analysisid));

        $analysis_after2 = count_records('threesixty_analysis');
        $carried_after2 = count_records('threesixty_carried_comp');
        $resp_after2 = count_records('threesixty_response_comp');
        $respondent_after2 = count_records('threesixty_respondent');

        $this->assertEqual($analysis_before2-$analysis_after2, 1);
        $this->assertEqual($carried_before2 - $carried_after2, 3);
        $this->assertEqual($resp_before2 - $resp_after2, 4);
        $this->assertEqual($respondent_before2 - $respondent_after2, 1);

    }

    function test_mod_threesixty_delete_respondent() {
        $respondentid = 1;
        $respondentid_2 = 999;

        $resp_before = count_records('threesixty_response_comp');
        $respondent_before = count_records('threesixty_respondent');

        // this should fail and records remain unchanged
        threesixty_delete_respondent($respondentid_2);

        $resp_after = count_records('threesixty_response_comp');
        $respondent_after = count_records('threesixty_respondent');

        $this->assertEqual($resp_before - $resp_after, 0);
        $this->assertEqual($respondent_before - $respondent_after, 0);

        $resp_before2 = count_records('threesixty_response_comp');
        $respondent_before2 = count_records('threesixty_respondent');

        // now do a real delete       
        $this->assertTrue(threesixty_delete_respondent($respondentid));

        $resp_after2 = count_records('threesixty_response_comp');
        $respondent_after2 = count_records('threesixty_respondent');

        $this->assertEqual($resp_before2 - $resp_after2, 2);
        $this->assertEqual($respondent_before2 - $respondent_after2, 1);

    }

    function test_mod_threesixty_get_skill_names() {
        $activityid = 1;
        $activityid_2 = 999;
        $obj = new stdClass();
        $obj->competencyid = '1';
        $obj->competencyname = 'C1';
        $obj->skillname = 'S1';
        $obj->id = 1;
        $this->assertEqual(array_shift(threesixty_get_skill_names($activityid)), $obj);
        $this->assertEqual(count(threesixty_get_skill_names($activityid)),6);
        $this->assertFalse(threesixty_get_skill_names($activityid_2));

    }

    function test_mod_threesixty_get_self_scores() {
        $analysisid = 1;
        $analysisid_2 = 999;
        $this->assertEqual(count(threesixty_get_self_scores($analysisid, false)->records), 6);
        $this->assertEqual(count(threesixty_get_self_scores($analysisid, true)->records), 3);
        $this->assertEqual(count(threesixty_get_self_scores($analysisid_2, false)->records), 0);

        $res = threesixty_get_self_scores($analysisid, false)->records;
        $this->assertEqual($res[1]->score, 3);
        $res = threesixty_get_self_scores($analysisid, true)->records;
        $this->assertEqual($res[1]->score, 3);

    }

    function test_mod_threesixty_get_feedback() {
        $analysisid = 1;
        $analysisid_2 = 999;
        $test = threesixty_get_feedback($analysisid);
        $this->assertEqual(count(threesixty_get_feedback($analysisid)), 2);
        $test = threesixty_get_feedback($analysisid_2);
        $this->assertEqual(count(threesixty_get_feedback($analysisid_2)), 0);

    }

    function test_mod_threesixty_is_completed() {
        $activityid = 1;
        $activityid_2 = 999;
        $userid = 1;
        $userid_2 = 999;
        
        $this->assertTrue(threesixty_is_completed($activityid, $userid));
        $this->assertFalse(threesixty_is_completed($activityid_2, $userid));
        $this->assertFalse(threesixty_is_completed($activityid, $userid_2));
        $this->assertFalse(threesixty_is_completed($activityid_2, $userid_2));

    }

    function test_mod_threesixty_user_listing() {
        $activity = new object;
        $activity->id = 1;
        $activity_2 = new object;
        $activity_2->id = 999;
        $url = "test.html";

        $this->assertEqual(strlen(threesixty_user_listing($activity, $url)),356);
        $this->assertEqual(threesixty_user_listing($activity_2, $url), get_string('nousersfound', 'threesixty'));
    }

    function test_mod_threesixty_selected_user_heading() {
        $user = new object();
        $user->id = 1;
        $courseid = 1;
        $url = "test.html";
        $this->assertEqual(strlen(threesixty_selected_user_heading($user, $courseid, $url)), 163);
        $this->assertEqual(strlen(threesixty_selected_user_heading($user, $courseid, $url, false)), 124);
    }

    function test_mod_threesixty_get_first_incomplete_competency() {
        $activityid = 1;
        $activityid_2 = 999;
        $userid = 1;
        $userid_2 = 2;
        $respondent = new object();
        $respondent->id = 1;
        $respondent_2 = new object();
        $respondent_2->id = 2;

        // activity complete show first page
        $this->assertEqual(threesixty_get_first_incomplete_competency($activityid, $userid, null), 1);
        $this->assertEqual(threesixty_get_first_incomplete_competency($activityid, $userid, $respondent), 1);
        // all skills have been scored, go to last page
        $this->assertEqual(threesixty_get_first_incomplete_competency($activityid, $userid_2, null),3);
        // partially complete show which page to display
        $this->assertEqual(threesixty_get_first_incomplete_competency($activityid, $userid_2, $respondent_2), 2);

        // no responses exist show first page
        $this->assertEqual(threesixty_get_first_incomplete_competency($activityid_2, $userid_2, $respondent_2), 1);

    }

    function test_mod_threesixty_get_average_skill_scores() {
        $analysisid = 1;
        $analysisid_2 = 999;
        $respondenttype = 0;
        $respondenttype_2 = 999;

        $obj = new stdClass();
        $obj->score = '0.00000000000000000000';
        $obj->id = 6;
        // check format of a single result
        $this->assertEqual(array_shift(threesixty_get_average_skill_scores($analysisid, 0, false)->records), $obj);

        // check the number of results 
        $this->assertEqual(count(threesixty_get_average_skill_scores($analysisid, 0, true)->records), 3); 
        $this->assertEqual(count(threesixty_get_average_skill_scores($analysisid, 0, false)->records), 6);

        // zero records if bad analysisid or respondenttype
        $this->assertEqual(count(threesixty_get_average_skill_scores($analysisid_2, 0, false)->records), 0);
        $this->assertEqual(count(threesixty_get_average_skill_scores($analysisid, $respondenttype_2, false)->records), 0);

        // check some numbers for different situations
        $res = threesixty_get_average_skill_scores(1, false, false)->records;
        $this->assertEqual($res[1]->score, 4);
        $res = threesixty_get_average_skill_scores(1, 0, false)->records;
        $this->assertEqual($res[1]->score, 5);
        $res = threesixty_get_average_skill_scores(1, false, true)->records;
        $this->assertEqual($res[1]->score, 3.75);
        $res = threesixty_get_average_skill_scores(1, 0, true)->records;
        $this->assertEqual($res[1]->score, 4.5);
        


    }

}
