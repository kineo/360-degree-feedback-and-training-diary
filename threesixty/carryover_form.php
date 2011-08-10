<?php

require_once $CFG->dirroot.'/lib/formslib.php';

class mod_threesity_carryover_form extends moodleform {

    function definition() {

        $mform =& $this->_form;
        $complist = $this->_customdata['complist'];
        $nbcarried = $this->_customdata['nbcarried'];

        $mform->addElement('hidden', 'a', $this->_customdata['a']);
        $mform->setType('a', PARAM_INT);
        $mform->addElement('hidden', 'section', 'carryover');
        $mform->setType('section', PARAM_ALPHA);
        $mform->addElement('hidden', 'userid', $this->_customdata['userid']);
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'nbcarried', $nbcarried);
        $mform->setType('nbcarried', PARAM_INT);

        $mform->addElement('header', 'carryover', get_string('carryoverheading', 'threesixty'));

        $mform->addElement('html', get_string('carryoverexplanation', 'threesixty', $nbcarried));

        for ($i=0; $i < $nbcarried; $i++) {
            $mform->addElement('select', "comp$i", ($i + 1) . ':', $complist);
        }

        $mform->addElement('html', get_string('carryovernote', 'threesixty', $nbcarried));

        $this->add_action_buttons();
    }
}
