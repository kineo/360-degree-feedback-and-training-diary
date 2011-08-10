<?php

require_once("$CFG->libdir/formslib.php");

/**
 * Form for editing user Personal Development Plan
 *
 * @author  Simon Coggins <simonc@catalyst.net.nz>
 * @package mod/trdiary
**/
class trdiary_edit_form extends moodleform {
 
    function definition() {
 
        $mform =& $this->_form;
        $pdp_skills = $this->_customdata['pdp_skills'];

        $mform->addElement('header','pdp_skill',get_string('setskills', 'trdiary'));
        $tabledef = '<table width="80%" cellspacing="1" cellpadding="5" '
            .'class="generaltable boxaligncenter"><thead><tr><th class="header">'
            .get_string('strengthdevelop','trdiary').'</th><th class="header">'
            .get_string('priority','trdiary').'</th></tr></thead><tbody>';
        $mform->addElement('html',$tabledef);

        $compid='';
        foreach($pdp_skills AS $pdp_skill) {
            // row containing competency
            if($pdp_skill->compid != $compid) {
                $tablehead = '<tr><th colspan="2" align="left" class="cell">' .
                    $pdp_skill->compname.'</th></tr>';
                $mform->addElement('html',$tablehead);
            }
            // column with skill name
            $skillname = $pdp_skill->skillname;
            $mform->addElement('html','<tr>');

            // Radio buttons for strength/area for development
            $mform->addElement('html','<td class="cell">');
            $elementname = "isstrength_{$pdp_skill->skillid}";
            $groupname = "radioarray_{$pdp_skill->skillid}";
            $radioarray = array();
            $radioarray[] = &$mform->createElement('radio', $elementname, '', 
                get_string('strength','trdiary'),1);
            $radioarray[] = &$mform->createElement('radio', $elementname, '', 
                get_string('area','trdiary'), 2);

            $mform->addGroup($radioarray, $groupname, $skillname, ' ', false);
            if($pdp_skill->isstrength > 0) {
                $mform->setDefault($elementname, $pdp_skill->isstrength);
            }
            $mform->setType($groupname, PARAM_INT);
            $mform->addGroupRule($groupname, array($elementname => 
                array(array(get_string('invalidselection','trdiary'), 'numeric', 
                null, 'client'))));
            $mform->addElement('html','</td>');

            // Pull down select for priority
            $mform->addElement('html','<td class="cell">');
            $selectarray = array();
            $selectarray[0] = get_string('notset','trdiary');
            $selectarray[1] = get_string('none','trdiary');
            $selectarray[2] = get_string('low','trdiary');
            $selectarray[3] = get_string('medium','trdiary');
            $selectarray[4] = get_string('high', 'trdiary');
            $selectname = "select{$pdp_skill->skillid}";
            $mform->addElement('select', $selectname, '', $selectarray);
            if($pdp_skill->priority > 0) {
                $mform->setDefault($selectname, $pdp_skill->priority);
            }
            $mform->setType($selectname, PARAM_INT);
            $mform->addRule($selectname, get_string('invalidselection', 'trdiary'), 
                'numeric', null, 'client');
            $mform->addElement('html','</td>');

            // end of row
            $mform->addElement('html','</tr>');
            $compid = $pdp_skill->compid;
        }

        $mform->addElement('html','</tbody></table>');

        $mform->addElement('hidden', 'u', $this->_customdata['u']);
        $mform->setType('u', PARAM_INT);
        $mform->addElement('hidden', 'a', $this->_customdata['a']);
        $mform->setType('a', PARAM_INT);

        $this->add_action_buttons(); 
   }

} // end class

