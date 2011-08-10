<?php
global $CFG;

require_once($CFG->libdir.'/formslib.php');

/**
 * Reflective log entry form
 *
 * @author  Simon Coggins <simonc@catalyst.net.nz>
 * @package mod/trdiary
**/
class trdiary_reflog_form extends moodleform {

    function definition() {
        $mform =& $this->_form;
        $fields =& $this->_customdata['fields'];
        if (isset($fields) && $fields !== false) {
            $mform->addElement('header', 'reflog', get_string('reflogentry', 
                               'trdiary'));
            foreach ($fields AS $field) {
                $fieldref = 'field'.$field->fieldid;
                $fieldid = 'field'.$field->fieldid.'id';
                $mform->addElement('textarea', $fieldref, $field->name, 
                                   array('cols'=>'56', 'rows'=>'8'));
                $mform->setType($fieldref, PARAM_TEXT);
                $mform->addRule($fieldref, null, 'required', null, 'client');
                $mform->addRule($fieldref, get_string('maximumchars', '', 
                                65535), 'maxlength', 65535, 'client');
                $mform->addElement('hidden', $fieldid, null);
                $mform->setType($fieldid, PARAM_INT);
            }

            $mform->addElement('hidden', 'a', $this->_customdata['a']);
            $mform->setType('a', PARAM_INT);
            $mform->addElement('hidden', 'e', $this->_customdata['e']);
            $mform->setType('e', PARAM_INT);
            if(isset($u) || isset($this->_customdata['u'])) {
                $mform->addElement('hidden', 'u', $this->_customdata['u']);
                $mform->setType('u', PARAM_INT);
            }
            $this->add_action_buttons();
        }
    }
}

