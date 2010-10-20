<?php

require_once($CFG->dirroot.'/lib/formslib.php');

class plagiarism_setup_form extends moodleform {

/// Define the form
    function definition () {
        global $CFG;

        $mform =& $this->_form;
        $choices = array('No','Yes');
        $mform->addElement('html', get_string('newexplain', 'plagiarism_new'));
        $mform->addElement('checkbox', 'new_use', get_string('usenew', 'plagiarism_new'));

        $mform->addElement('textarea', 'new_student_disclosure', get_string('studentdisclosure','plagiarism_new'),'wrap="virtual" rows="6" cols="50"');
        $mform->addHelpButton('new_student_disclosure', 'studentdisclosure', 'plagiarism_new');
        $mform->setDefault('new_student_disclosure', get_string('studentdisclosuredefault','plagiarism_new'));

        $this->add_action_buttons(true);
    }
}

