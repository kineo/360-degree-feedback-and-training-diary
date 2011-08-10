<?php

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_threesixty_mod_form extends moodleform_mod {

    function definition() {
        global $COURSE;
        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $competenciescarried = array();
        for ($i=1; $i<=10; $i += 1) {
            $competenciescarried[$i] = $i;
        }
        $mform->addElement('select', 'competenciescarried', get_string('competenciescarried', 'threesixty'), $competenciescarried);
        $mform->setDefault('competenciescarried', 3);
        $mform->setHelpButton('competenciescarried', array('competenciescarried', get_string('competenciescarried', 'threesixty'), 'threesixty'));

        $requiredrespondents = array();
        for ($i=0; $i<=20; $i += 1) {
            $requiredrespondents[$i] = $i;
        }
        $mform->addElement('select', 'requiredrespondents', get_string('requiredrespondents', 'threesixty'), $requiredrespondents);
        $mform->setDefault('requiredrespondents', 10);
        $mform->setHelpButton('requiredrespondents', array('requiredrespondents', get_string('requiredrespondents', 'threesixty'), 'threesixty'));

        $features = new stdClass;
        $features->groups = false;
        $features->groupings = false;
        $features->groupmembersonly = false;
        $features->outcomes = false;
        $features->gradecat = false;
        $features->idnumber = false;
        $this->standard_coursemodule_elements($features);
        $this->add_action_buttons();

    }
}
