<?php

require_once($CFG->dirroot.'/lib/formslib.php');

class plagiarism_setup_form extends moodleform {

/// Define the form
    function definition () {
        global $CFG;

        $mform =& $this->_form;
        $choices = array('No','Yes');
        $mform->addElement('html', get_string('mossexplain', 'plagiarism_moss'));
        $mform->addElement('checkbox', 'moss_use', get_string('usemoss', 'plagiarism_moss'));

        $mform->addElement('textarea', 'moss_student_disclosure', get_string('studentdisclosure','plagiarism_moss'),'wrap="virtual" rows="6" cols="50"');
        $mform->addHelpButton('moss_student_disclosure', 'studentdisclosure', 'plagiarism_moss');
        $mform->setDefault('moss_student_disclosure', get_string('studentdisclosuredefault','plagiarism_moss'));

        $this->add_action_buttons(true);
    }
}

