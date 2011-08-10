<?php

define('DEFAULT_NUMBER_SKILLS', 5);
define('EXTRA_SKILLS', 2);

require_once $CFG->dirroot.'/lib/formslib.php';

class mod_threesity_editcompetency_form extends moodleform {

    function definition() {

        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size'=>'50'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('textarea', 'description', get_string('description'), array('cols'=>'56', 'rows'=>'8'));
        $mform->setType('description', PARAM_TEXT);
        $mform->addRule('description', null, 'required', null, 'client');

        $mform->addElement('checkbox', 'showfeedback', get_string('showfeedback', 'threesixty'));
        $mform->setHelpButton('showfeedback', array('showfeedback', get_string('showfeedback', 'threesixty'), 'threesixty'));

        $mform->addElement('header', 'skills', get_string('skills', 'threesixty'));

        $repeatarray = array();
        $repeatarray[] = &$mform->createElement('hidden', 'skillid', 0);
        $repeatarray[] = &$mform->createElement('text', 'skillname', get_string('name'), array('size'=>'50'));
        $repeatarray[] = &$mform->createElement('textarea', 'skilldescription', get_string('description'), array('cols'=>'56', 'rows'=>'6'));
        $checkboxelement = &$mform->createElement('checkbox', 'skilldelete', '', get_string('deleteskill', 'threesixty'));
        unset($checkboxelement->_attributes['id']); // necessary until MDL-20441 is fixed
        $repeatarray[] = $checkboxelement;
        $repeatarray[] = &$mform->createElement('html', '<br/><br/>'); // spacer

        $repeatcount = DEFAULT_NUMBER_SKILLS;
        if ($this->_customdata['skills']) {
            $repeatcount = count($this->_customdata['skills']);
            $repeatcount += EXTRA_SKILLS;
        }

        $repeatoptions = array();
        $repeatoptions['skillname']['disabledif'] = array('skilldelete', 'checked');
        $repeatoptions['skilldescription']['disabledif'] = array('skilldelete', 'checked');
        $mform->setType('skillname', PARAM_TEXT);
        $mform->setType('skilldescription', PARAM_TEXT);

        $this->repeat_elements($repeatarray, $repeatcount, $repeatoptions, 'skill_repeats', 'skill_add_fields',
                               EXTRA_SKILLS, get_string('addnewskills', 'threesixty'), true);

        $mform->addElement('hidden', 'a', $this->_customdata['a']);
        $mform->setType('a', PARAM_INT);
        $mform->addElement('hidden', 'c', $this->_customdata['c']);
        $mform->setType('c', PARAM_INT);

        $this->add_action_buttons();
    }
}
