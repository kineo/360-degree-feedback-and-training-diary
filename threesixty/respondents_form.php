<?php

require_once $CFG->dirroot.'/lib/formslib.php';

class mod_threesity_respondents_form extends moodleform {

    function definition() {

        $mform =& $this->_form;
        $typelist = $this->_customdata['typelist'];
        $remaininginvitations = $this->_customdata['remaininginvitations'];

        $mform->addElement('hidden', 'a', $this->_customdata['a']);
        $mform->setType('a', PARAM_INT);
        $mform->addElement('hidden', 'userid', $this->_customdata['userid']);
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('header', 'requestrespondent', get_string('requestrespondentheading', 'threesixty'));
        $mform->addElement('html', get_string('requestrespondentexplanation', 'threesixty', $remaininginvitations));

        $mform->addElement('text', 'email', get_string('email'), array('size' => 40));
        $mform->setType('email', PARAM_NOTAGS);
        $mform->addRule('email', get_string('invalidemail'), 'email');

        $mform->addElement('select', 'type', get_string('respondenttype', 'threesixty'), $typelist);
        $mform->setType('type', PARAM_INT);

        $mform->addElement('html', '<br/><br/>');
        $mform->addElement('submit', 'send', get_string('sendemail', 'threesixty'));
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $analysisid = $this->_customdata['analysisid'];

        $email = strtolower($data['email']);
        if (get_field('threesixty_respondent', 'id', 'analysisid', $analysisid, 'email', $email)) {
            $errors['email'] = get_string('validation:emailnotunique', 'threesixty');
        }

        return $errors;
    }
}
