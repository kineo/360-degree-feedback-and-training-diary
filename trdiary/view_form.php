<?php

require_once($CFG->libdir.'/formslib.php');

/**
 * Form to add new PDP additional fields
 *
 * @author  Simon Coggins <simonc@catalyst.net.nz>
 * @package mod/trdiary
**/
class trdiary_view_form extends moodleform {

    function definition() {
        $mform =& $this->_form;
        $extrafields =& $this->_customdata['extrafields'];

        if (isset($extrafields) && $extrafields !== false) {
            $mform->addElement('header', 'additionalfields', get_string('additionalfields', 
                               'trdiary'));
            foreach ($extrafields AS $field) {
                $fieldref = 'extrafield'.$field->id;
                $mform->addElement('textarea', $fieldref, $field->name, array('cols'=>'56', 
                                   'rows'=>'8'));
                $mform->setType($fieldref, PARAM_TEXT);
                $mform->addRule($fieldref, get_string('maximumchars', '', 65535), 'maxlength', 
                                                      65535, 'client');
            }

            $mform->addElement('hidden', 'a', $this->_customdata['a']);
            $mform->setType('a', PARAM_INT);
            $mform->addElement('hidden', 'id', $this->_customdata['id']);
            $mform->setType('id', PARAM_INT);

            $this->add_action_buttons();
        }
    }
}

