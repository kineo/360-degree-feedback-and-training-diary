<?php

require_once $CFG->dirroot.'/lib/formslib.php';

class mod_threesity_amend_form extends moodleform {

    function definition() {

        $mform =& $this->_form;
        $skills = $this->_customdata['skillnames'];

        $mform->addElement('hidden', 'a', $this->_customdata['a']);
        $mform->setType('a', PARAM_INT);
        $mform->addElement('hidden', 'userid', $this->_customdata['userid']);
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'typeid', $this->_customdata['typeid']);
        $mform->setType('typeid', PARAM_INT);
        $radioarray = array();
        $radioarray[] = &$mform->createElement('radio', 'score_dummy', '', '', 0, 'class="radioarray_dummy"');
        $mform->addGroup($radioarray, "radioarray_dummy", '');

        $competency = new object();
        $competency->skills = false;

        if ($skills and count($skills) > 0) {
            $curcompetency = 0;
            foreach ($skills as $skill) {
            	
                if ($curcompetency != $skill->competencyid) {
                    $mform->addElement('html','<br /><br /><div class="compheader"><div class="complabel">'.format_string($skill->competencyname).'</div><div class="compopt">'.get_string('notapplicable', 'threesixty').'</div><div class="compopt">1</div><div class="compopt">2</div><div class="compopt">3</div><div class="compopt">4</div><div class="clear"><!-- --></div></div>');
                    $curcompetency = $skill->competencyid;
                }

                $mform->addElement('html','<div class="skillset">');
                $elementname = "score_{$skill->id}";
                $radioarray = array();
                $radioarray[] = &$mform->createElement('radio', $elementname, '', '', 0);
                $radioarray[] = &$mform->createElement('radio', $elementname, '', '', 1);
                $radioarray[] = &$mform->createElement('radio', $elementname, '', '', 2);
                $radioarray[] = &$mform->createElement('radio', $elementname, '', '', 3);
                $radioarray[] = &$mform->createElement('radio', $elementname, '', '', 4);
                $skillname = "<div class='skillname'>".format_string($skill->skillname)."</div>";
                $mform->addGroup($radioarray, "radioarray_$skill->id", $skillname);
 				$mform->addElement('html','</div>');
            }
        }
        $this->add_action_buttons();
    }
}
