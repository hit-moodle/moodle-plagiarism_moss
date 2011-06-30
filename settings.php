<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


require_once(dirname(dirname(__FILE__)) . '/../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/plagiarismlib.php');
require_once($CFG->dirroot.'/plagiarism/moss/lib.php');
require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * 
 * Enter description here ...
 * @author ycc
 *
 */
class moss_enable_form extends moodleform 
{
    /**
     * (non-PHPdoc)
     * @see moodleform::definition()
     */
    function definition () 
    {
        global $CFG;
        global $DB;

        $mform =& $this->_form;
        $choices = array('YES',
                         'NO');
        $yesnooptions = array('NO'  => "NO", 
                              'YES' => "YES");
        $mossoptions = array('NEVER'  => "NEVER", 
                             'ALWAYS' => "ALWAYS");
            
        $helplink = get_string('mossexplain', 'plagiarism_moss');
        $helplink .= '<a href='.$CFG->wwwroot.'/plagiarism/moss/help.php></a>';
        $mform->addElement('html', $helplink);
            
        $mform->addElement('checkbox', 'moss_use', get_string('usemoss', 'plagiarism_moss'));

        $mform->addElement('textarea', 'moss_student_disclosure', get_string('studentdisclosure','plagiarism_moss'),'wrap="virtual" rows="6" cols="50"');
        $mform->addHelpButton('moss_student_disclosure', 'studentdisclosure', 'plagiarism_moss');
        $mform->setDefault('moss_student_disclosure', get_string('studentdisclosuredefault','plagiarism_moss'));
        $mform->disabledIf('moss_student_disclosure', 'moss_use');

        $mform->addElement('text','default_entry',get_string('default_entry_number', 'plagiarism_moss'));
        $mform->addRule('default_entry', null, 'numeric', null, 'client');
        $mform->addHelpButton('default_entry', 'default_entry_number', 'plagiarism_moss');
        $mform->disabledIf('default_entry', 'moss_use');
        
        $mform->addElement('select','enable_log',get_string('enable_log', 'plagiarism_moss'),$yesnooptions);
        $mform->addHelpButton('enable_log', 'enable_log', 'plagiarism_moss');
        $mform->disabledIf('enable_log', 'moss_use');
        
        $mform->addElement('select','rerun',get_string('rerun', 'plagiarism_moss'),$yesnooptions);
        $mform->addHelpButton('rerun', 'rerun', 'plagiarism_moss');
        $mform->disabledIf('rerun', 'moss_use');
          
        $mform->addElement('select','send_email',get_string('send_email', 'plagiarism_moss'),$yesnooptions);
        $mform->addHelpButton('send_email', 'send_email', 'plagiarism_moss');
        $mform->disabledIf('send_email', 'moss_use');
           
        $mform->addElement('select', 'show_code', get_string('similar_code', 'plagiarism_moss'), $mossoptions);
        $mform->addHelpButton('show_code', 'similar_code', 'plagiarism_moss');
        $mform->disabledIf('show_code', 'moss_use');
           
        $mform->addElement('select', 'show_entrys_detail', get_string('result_entrys_detail', 'plagiarism_moss'), $mossoptions);
        $mform->addHelpButton('show_entrys_detail', 'result_entrys_detail', 'plagiarism_moss');
        $mform->disabledIf('show_entrys_detail', 'moss_use');

        $mform->addElement('select', 'appeal', get_string('student_appeal', 'plagiarism_moss'), $mossoptions);
        $mform->addHelpButton('appeal', 'student_appeal', 'plagiarism_moss');
        $mform->disabledIf('appeal', 'moss_use');
                     
        $mform->addElement('select', 'cross_detection',get_string('cross_detection', 'plagiarism_moss'),$yesnooptions);
        $mform->addHelpButton('cross_detection', 'cross_detection', 'plagiarism_moss');
        $mform->disabledIf('cross_detection', 'moss_use');
            
        $mform->addElement('text', 'default_students',get_string('default_students', 'plagiarism_moss'));
        $mform->addRule('default_students', null, 'numeric', null, 'client');
        $mform->addHelpButton('default_students', 'default_students', 'plagiarism_moss');
        $mform->disabledIf('default_students', 'moss_use');
        
        $this->add_action_buttons(true);
            
        $cnf_xml = new config_xml();
        $config = $cnf_xml->get_config_all();

        $mform->setDefault('default_entry',$config['entry_number']);	
        $mform->setDefault('enable_log',$config['enable_log']);
        $mform->setDefault('rerun', $config['rerun_after_change']);
        $mform->setDefault('send_email', $config['send_email']);
        $mform->setDefault('show_text', $config['show_code']);
        $mform->setDefault('show_entrys_detail', $config['show_entrys_detail']);
        $mform->setDefault('cross_detection', $config['enable_cross-course_detection']);
        $mform->setDefault('appeal', $config['enable_student_appeal']);
        $mform->setDefault('default_students', $config['default_students_in_statistics_page']);
    }
}
    
require_login();
admin_externalpage_setup('plagiarismmoss');
$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('moodle/site:config', $context, $USER->id, true, "nopermissions");

$plagiarismplugin = new plagiarism_plugin_moss();
$mform = new moss_enable_form();
    
$currenttab='tab1';
$tabs = array();
$tabs[] = new tabobject('tab1', 
                        'settings.php', 
                         get_string('general_settings', 'plagiarism_moss'), 
                        'General_settings', 
                         false);
                            
$tabs[] = new tabobject('tab2', 
                        'log.php', 
                         get_string('error_log', 'plagiarism_moss'), 
                        'Error_log', 
                         false);
   
if($mform->is_cancelled()) 
    redirect('');
    
echo $OUTPUT->header();
print_tabs(array($tabs), $currenttab);
    
if (($data = $mform->get_data()) && confirm_sesskey())
{ 
    $cnf_xml = new config_xml();
    $cnf_xml->save_config(array('entry_number'                        => $data->default_entry,
                                'enable_log'                          => $data->enable_log,
                                'rerun_after_change'                  => $data->rerun,
                                'send_email'                          => $data->send_email,
                                'show_code'                           => $data->show_code,
                                'show_entrys_detail'                  => $data->show_entrys_detail,
                                'enable_cross-course_detection'       => $data->cross_detection,
                                'enable_student_appeal'               => $data->appeal,
                                'default_students_in_statistics_page' => $data->default_students));
      
    if (!isset($data->moss_use)) 
        $data->moss_use = 0;
    foreach ($data as $field=>$value) 
    {
        if (strpos($field, 'moss')===0) 
        {
            if ($tiiconfigfield = $DB->get_record('config_plugins', array('name'=>$field, 'plugin'=>'plagiarism')))
            {
                $tiiconfigfield->value = $value;
                if (! $DB->update_record('config_plugins', $tiiconfigfield)) 
                {
                    error("errorupdating");
                }
            } 
            else 
            {
                $tiiconfigfield = new stdClass();
                $tiiconfigfield->value = $value;
                $tiiconfigfield->plugin = 'plagiarism';
                $tiiconfigfield->name = $field;
                if (! $DB->insert_record('config_plugins', $tiiconfigfield)) 
                {
                    error("errorinserting");
                }
            }
        }
    }
    notify(get_string('savedconfigsuccess', 'plagiarism_moss'), 'notifysuccess');
}

$plagiarismsettings = (array)get_config('plagiarism');
$mform->set_data($plagiarismsettings);
    
echo $OUTPUT->box_start();
$mform->display();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
