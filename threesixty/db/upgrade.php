<?php

function xmldb_threesixty_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;
    if($result && $oldversion<2009122101){
        //Add a display order column for the competency table.
        $comptable = new XMLDBTable('threesixty_competency');
        $field = new XMLDBField('sortorder');
        $field->setattributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '999', 'showfeedback');
        if(!add_field($comptable, $field)){
            $result = false;
        }
        reorder_competencies();
        $skilltable = new XMLDBTable('threesixty_skill');
        $field->previous = 'description';
        if(!add_field($skilltable, $field)){
            $result = false;
        }
        //Update the existing competency data.
        reorder_skills();
    }

    return $result;
}

function reorder_competencies(){

  global $CFG;
  //$sql = "SELECT * FROM ".$CFG->prefix."threesixty_competency ORDER BY activityid, id";
  if ($competencies = get_records("threesixty_competency", '', "activityid")){
    $activityid = 0;
    $nextposition = 0;
    foreach ($competencies as $competency){
      if($activityid!=$competency->activityid){
        $nextposition = 0;
        $activityid = $competency->activityid;
      }
      $competency->sortorder = $nextposition;
      $nextposition ++;
      update_record("threesixty_competency", $competency);
    }
  }
  
  
}
function reorder_skills(){
 global $CFG;
  //$sql = "SELECT * FROM ".$CFG->prefix."threesixty_skill ORDER BY competencyid, id";
  if ($skills = get_records('threesixty_skill', '', 'competencyid')){
    $competencyid = 0;
    $nextposition = 0;
    foreach ($skills as $skill){

      if($competencyid != $skill->competencyid){
        $nextposition = 0;
        $competencyid=$skill->competencyid;
      }
      $skill->sortorder = $nextposition;
      $nextposition ++;
      update_record("threesixty_skill", $skill);
    }
  }
}