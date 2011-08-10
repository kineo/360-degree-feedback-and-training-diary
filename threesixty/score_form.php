<?php

require_once $CFG->dirroot.'/lib/formslib.php';

class mod_threesity_score_form extends moodleform {

    function definition() {

        $mform =& $this->_form;
        $competency = $this->_customdata['competency'];
        $page = $this->_customdata['page'];
        $nbpages = $this->_customdata['nbpages'];

        $mform->addElement('hidden', 'a', $this->_customdata['a']);
        $mform->setType('a', PARAM_INT);
        $mform->addElement('hidden', 'code', $this->_customdata['code']);
        $mform->setType('code', PARAM_ALPHANUM);
        $mform->addElement('hidden', 'page', $this->_customdata['page']);
        $mform->setType('page', PARAM_INT);
        $mform->addElement('hidden', 'userid', $this->_customdata['userid']);
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'typeid', $this->_customdata['typeid']);

        $mform->addElement('header', 'competency', format_string($competency->name));
        $mform->addElement('html', '<div class="competencydescription">'.format_text($competency->description).'</div>');
		$mform->addElement('html', '<div class="completionlegend"><p class="legendheading">'.get_string('legend:heading', 'threesixty').'</p>');
		$mform->addElement('html', '<ul><li>Level 1: '.get_string('legend:level1', 'threesixty').'</li>');
		$mform->addElement('html', '<li>Level 2: '.get_string('legend:level2', 'threesixty').'</li>');
		$mform->addElement('html', '<li>Level 3: '.get_string('legend:level3', 'threesixty').'</li>');
		$mform->addElement('html', '<li>Level 4: '.get_string('legend:level4', 'threesixty').'</li></ul></div>');
      

        if ($competency->skills and count($competency->skills) > 0) {
    
            $mform->addElement('html','<br /><br /><div class="clear"><!-- --></div><div class="compheader"><div class="compopt">'.get_string('notapplicable', 'threesixty').'</div><div class="compopt">1</div><div class="compopt">2</div><div class="compopt">3</div><div class="compopt">4</div><div class="clear"><!-- --></div></div>');

            foreach ($competency->skills as $skill) {
            	$mform->addElement('html','<div class="skillset">');
                $elementname = "score_{$skill->id}";
                $radioarray = array();
                $radioarray[] = &$mform->createElement('radio', $elementname, '', '', 0);
                $radioarray[] = &$mform->createElement('radio', $elementname, '', '', 1);
                $radioarray[] = &$mform->createElement('radio', $elementname, '', '', 2);
                $radioarray[] = &$mform->createElement('radio', $elementname, '', '', 3);
                $radioarray[] = &$mform->createElement('radio', $elementname, '', '', 4);

                $skillname = "<div class='skillname'>".format_string($skill->name);
                if(strlen($skill->description)>0) {
                	$skillname .= " - ".format_string($skill->description);
                }
                $skillname .= "</div>";
                $mform->addGroup($radioarray, "radioarray_$skill->id", $skillname);
 				$mform->addElement('html','</div>');
                
                if ($competency->locked) {
                    $mform->hardFreeze("radioarray_{$skill->id}");
                }
            }
        }
        else {
            $mform->addElement('html', get_string('noskills', 'threesixty'));
        }

        //if (1 == $competency->showfeedback and empty($this->_customdata['code'])) {
        // Kat - allowed externals to leave feedback
        if (1 == $competency->showfeedback) {
            $mform->addElement('textarea', 'feedback', get_string('feedback'), array('cols'=>'53', 'rows'=>'8'));
            if ($competency->locked) {
                $mform->hardFreeze('feedback');
            }
        }

        // Paging buttons
        $buttonarray = array();
        if ($page > 1) {
            $buttonarray[] = &$mform->createElement('submit', 'previous', get_string('previous'));
        }
        else {
            $buttonarray[] = &$mform->createElement('submit', 'previous', get_string('previous'),
                                                   array('disabled'=>true));
        }
        if ($page < $nbpages) {
            $buttonarray[] = &$mform->createElement('submit', 'next', get_string('next'));
        }
        else {
            $buttonlabel = get_string('finishbutton', 'threesixty');
            if ($competency->locked) {
                $buttonlabel = get_string('closebutton', 'threesixty');
            }
            $buttonarray[] = &$mform->createElement('submit', 'finish', $buttonlabel);
        }

        $a = new object;
        $a->page = $page;
        $a->nbpages = $nbpages;

        $mform->addGroup($buttonarray, 'buttonarray', '', ' ' . get_string('xofy', 'threesixty', $a) . ' ');
        $mform->closeHeaderBefore('buttonarray');
    }
}
