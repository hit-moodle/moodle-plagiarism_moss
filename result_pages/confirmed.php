<?php
require_once(dirname(dirname(__FILE__)) . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/lib/formslib.php');

class confirmed_filter_form extends moodleform {

    	function definition () {
            global $CFG;

            $mform =& $this->_form;
            $choices = array('No','Yes');
            $mform->addElement('html', get_string('mossexplain', 'plagiarism_moss'));
            $mform->addElement('checkbox', 'moss_use', get_string('usemoss', 'plagiarism_moss'));

            $mform->addElement('textarea', 'moss_student_disclosure', get_string('studentdisclosure','plagiarism_moss'),'wrap="virtual" rows="6" cols="50"');
            $mform->addHelpButton('moss_student_disclosure', 'studentdisclosure', 'plagiarism_moss');
            $mform->setDefault('moss_student_disclosure', 'tab2');

            $this->add_action_buttons(true);
        }
}

require_login();
$PAGE->set_url('/plagiarism/moss/result_pages/confirmed.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
$PAGE->set_title('anti-plagiarism confirm page');
$PAGE->set_heading('Confirm page');
$PAGE->navbar->add('anti-plagiarism');
$PAGE->navbar->add('results');

global $DB;
$form = new confirmed_filter_form();
$cmid = optional_param('id', 0, PARAM_INT);  
$table;

$currenttab='tab2';
$tabs = array();
$tabs[] = new tabobject('tab1', "view_all.php?id=".$cmid, 'View all', 'View all', false);
$tabs[] = new tabobject('tab2', "confirmed.php?id=".$cmid, 'Confirmed', 'Confirmed', false);
$tabs[] = new tabobject('tab3', "statistics.php?id=".$cmid, 'Statistics', 'Statistics', false);

if(($data = $form->get_data()) && confirm_sesskey()) 
    echo 'save tab2';
    
    
echo $OUTPUT->header();

print_tabs(array($tabs), $currenttab);
    
echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
$form->display();
echo $OUTPUT->box_end();

echo $OUTPUT->footer();