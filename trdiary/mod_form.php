<?php
/**
 *
 * Training Diary Module instance form
 *
 * @author  Simon Coggins <simonc@catalyst.net.nz>
 * @package mod/trdiary
**/

define('DEFAULT_NUMBER_ADD_FIELDS', 2);
define('EXTRA_ADD_FIELDS', 2);
define('DEFAULT_NUMBER_REFLOG_FIELDS', 2);
define('EXTRA_REFLOG_FIELDS', 2);

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/trdiary/locallib.php');

class mod_trdiary_mod_form extends moodleform_mod {

    function definition() {
        global $COURSE;
        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('trdiaryname', 'trdiary'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // show parent 360 course
        if(isset($this->_instance) && $this->_instance != '') {
            $threesixty = trdiary_get_linked_threesixty($this->_instance);
            if ($threesixty) {
                $parent = $threesixty->name;
                if (debugging()) {
                    $parent .= ' (ID='.$threesixty->id.')';
                }
            } else {
                $parent = get_string('parentunknown','trdiary');
            }
        } else {
            $threesixtyid = trdiary_get_threesixty_instance($COURSE->id);
            $result = get_record('threesixty','id',$threesixtyid,null,null,null,null,'id,name');
            if($result) {
                $parent = $result->name;
                if(debugging()) {
                    $parent .= ' (ID='.$result->id.')';
                }
            } else {
                $parent = get_string('parentunknown','trdiary');
            }
        }
        $mform->addElement('static', 'parent', get_string('parent360module','trdiary'), $parent);
        $mform->setHelpButton('parent', array('parent', get_string('parent360module', 'trdiary'), 'trdiary'));

        
        $mform->addElement('header', 'pdp', get_string('pdp', 'trdiary'));

        $repeatarray = array();
        $repeatarray[] = &$mform->createElement('hidden','addfieldid', 0);
        $repeatarray[] = &$mform->createElement('text','addfieldname', get_string('fieldname',
                                                'trdiary'), array('size'=>'50'));
        $checkboxelement = &$mform->createElement('checkbox', 'addfielddelete', '', 
                                                  get_string('deleteaddfield', 'trdiary'));
        unset($checkboxelement->_attributes['id']); // necessary until MDL-20441 is fixed
        $repeatarray[] = $checkboxelement;
        $repeatarray[] = &$mform->createElement('html', '<br /><br />'); // spacer
        
        if(!empty($this->_instance)) {
            $numaddfields = count_records('trdiary_pdp_field', 'trdiaryid', $this->_instance);
        }    
        $repeatcount = DEFAULT_NUMBER_ADD_FIELDS;
        if (isset($numaddfields)) {
            $repeatcount = $numaddfields;
            $repeatcount += EXTRA_ADD_FIELDS;
        }

        $repeatoptions = array();
        $repeatoptions['addfieldname']['disabledif'] = array('addfielddelete', 'checked');
        $repeatoptions['addfieldname']['rule'] = array(get_string('maximumchars', '', 255), 
                'maxlength', 255, 'client');

        $repeatoptions['addfieldname']['helpbutton'] = array('addfields', 
                get_string('pdpaddfieldname','trdiary'),'trdiary');
        $mform->setType('addfieldname', PARAM_TEXT);
        $this->repeat_elements($repeatarray, $repeatcount, $repeatoptions, 'addfields_repeats', 
                               'addfields_more_fields', EXTRA_ADD_FIELDS, 
                               get_string('addnewfields', 'trdiary'), true);

        $mform->addElement('header', 'reflog', get_string('reflog', 'trdiary'));

        $reflogrepeat = array();
        $reflogrepeat[] = &$mform->createElement('hidden','reflogfieldid', 0);
        $reflogrepeat[] = &$mform->createElement('text','reflogfieldname', 
                                                 get_string('fieldname','trdiary'), 
                                                 array('size'=>'50'));
        $reflogcheckboxelement = &$mform->createElement('checkbox', 'reflogfielddelete', '', 
                                                        get_string('deletereflogfield', 'trdiary'));
        unset($reflogcheckboxelement->_attributes['id']); // necessary until MDL-20441 is fixed
        $reflogrepeat[] = $reflogcheckboxelement;
        $reflogrepeat[] = &$mform->createElement('html', '<br /><br />'); // spacer
        
        if(!empty($this->_instance)) {
            $numreflogfields = count_records('trdiary_reflog_field', 'trdiaryid', $this->_instance);
        }    
        $reflogrepeatcount = DEFAULT_NUMBER_REFLOG_FIELDS;
        if (isset($numreflogfields)) {
            $reflogrepeatcount = $numreflogfields;
            $reflogrepeatcount += EXTRA_REFLOG_FIELDS;
        }

        $reflogrepeatoptions = array();
        $reflogrepeatoptions['reflogfieldname']['disabledif'] = array('reflogfielddelete', 
                'checked');
        $reflogrepeatoptions['reflogfieldname']['rule'] = array(get_string('maximumchars', '', 
                255), 'maxlength', 255, 'client');
        $reflogrepeatoptions['reflogfieldname']['helpbutton'] = array('reflogfields', 
                get_string('reflogfieldname','trdiary'),'trdiary');
        $mform->setType('reflogfieldname', PARAM_TEXT);
        $this->repeat_elements($reflogrepeat, $reflogrepeatcount, $reflogrepeatoptions, 
                               'reflogfields_repeats', 'reflogfields_more_fields', 
                               EXTRA_REFLOG_FIELDS, get_string('addnewreflogfields', 'trdiary'), 
                               true);

        $mform->addElement('html', '<br /><br />'); // spacer
        $mform->addElement('text', 'logfreq', get_string('logfreq', 'trdiary'));
        $mform->addRule('logfreq', get_string('logfreqnumeric','trdiary'), 'numeric', null, 
                                              'client');
        $mform->setHelpButton('logfreq', array('logfreq', get_string('logfreq', 'trdiary'), 
                              'trdiary'));

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

    /**
     * Data preprocessing used to fill additional fields with values from database
    **/
    function data_preprocessing(&$default_values){
        if(isset($this->_instance) && $this->_instance != '') {
            // get default values for PDP additional fields
            $currentaddfields = get_records('trdiary_pdp_field', 'trdiaryid', $this->_instance, 'id');
            if(!empty($this->_instance) && $currentaddfields) {
                $i =0;
                foreach($currentaddfields AS $addfield) {
                    $idfield = "addfieldid[$i]";
                    $namefield = "addfieldname[$i]";
                    $default_values[$idfield] = $addfield->id;
                    $default_values[$namefield] = $addfield->name;
                    $i++;
                }
            }

            // get default values for Reflective Log fields
            $currentreflogfields = get_records('trdiary_reflog_field', 'trdiaryid', $this->_instance, 
                                           'id');  
            if(!empty($this->_instance) && $currentreflogfields) {
                $i =0;
                foreach($currentreflogfields AS $reflogfield) {
                    $idfield = "reflogfieldid[$i]";
                    $namefield = "reflogfieldname[$i]";
                    $default_values[$idfield] = $reflogfield->id;
                    $default_values[$namefield] = $reflogfield->name;
                    $i++;
                }
            }
        }
    }

} // end class

