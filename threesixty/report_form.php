<?php

require_once $CFG->dirroot.'/lib/formslib.php';

class mod_threesity_report_form extends moodleform {

    function definition() {
    	global $type;

        $mform =& $this->_form;
        $filters = $this->_customdata['filters'];

        $mform->addElement('hidden', 'a', $this->_customdata['a']);
        $mform->setType('a', PARAM_INT);
        $mform->addElement('hidden', 'type', $this->_customdata['type']);
        $mform->setType('type', PARAM_ALPHA);
        $mform->addElement('hidden', 'userid', $this->_customdata['userid']);
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('header', 'filters', get_string('filters', 'threesixty'));

        $checkarray = array();

        $col = 0;
        $nbcols = 4;
        foreach ($filters as $code => $name) {
            if (0 == $col) {
                $checkarray[] = &$mform->createElement('static', '', '', '<table class="respondent_filters"><tr><td>');
            }

            $checkarray[] = &$mform->createElement('checkbox', $code, '', $name);
            $mform->setDefault("checkarray[$code]", 1);

            $col++;
            if ($col == $nbcols) {
                $checkarray[] = &$mform->createElement('static', '', '', '</td></tr></table>');
                $col = 0;
            }
            else {
                $checkarray[] = &$mform->createElement('static', '', '', '</td><td>');
            }
        }

        if ($col > 0) {
            $checkarray[] = &$mform->createElement('static', '', '', '</td></tr></table>');
        }
        $mform->addGroup($checkarray, 'checkarray', '');

        $mform->addElement('submit', 'submitbutton', get_string('applybutton', 'threesixty'));
        
        $mform->addElement('header', 'key', get_string('key', 'threesixty'));
        
        if($type == 'spiderweb') {
        	// key for spiderweb
        	$mform->addElement('html', 'Level 1: '.get_string('legend:level1', 'threesixty').'&nbsp; &nbsp; Level 2: '.get_string('legend:level2', 'threesixty').'&nbsp; &nbsp; Level 3: '.get_string('legend:level3', 'threesixty').'&nbsp; &nbsp; Level 4: '.get_string('legend:level4', 'threesixty'));
    	} else {
        	// key for tables
        	$mform->addElement('html', "<span class='scoresmaller'>&nbsp; &nbsp;</span> Below average&nbsp; &nbsp;<span class='scorebigger'>&nbsp; &nbsp;</span> Above average");
    	}
    }
}
