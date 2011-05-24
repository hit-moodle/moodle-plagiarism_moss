<?php
require_once(dirname(dirname(__FILE__)) . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/lib/formslib.php');

class moss_tab2_form extends moodleform {

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

$PAGE->set_url('/plagiarism/moss/test/tab2.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
$PAGE->set_title('anti-plagiarism confirm page');
$PAGE->set_heading('Confirm page');
$PAGE->navbar->add('anti-plagiarism');
$PAGE->navbar->add('result');

$form = new moss_tab2_form();

$currenttab='tab2';
$strplagiarism = '浏览';
$strplagiarismdefaults = '已确认';
$strplagiarismerrors = '评判';
$tabs = array();
$tabs[] = new tabobject('tab1', 'test.php', 'View all', 'View all', false);
$tabs[] = new tabobject('tab2', 'tab2.php', 'Confirmed', 'Confirmed', false);
$tabs[] = new tabobject('tab3', 'tab3.php', 'Statistic', 'Statistic', false);

    
if(($data = $form->get_data()) && confirm_sesskey()) 
    echo 'save tab2';
    
    
echo $OUTPUT->header();

print_tabs(array($tabs), $currenttab);
    
echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
$form->display();
echo $OUTPUT->box_end();

echo $OUTPUT->footer();