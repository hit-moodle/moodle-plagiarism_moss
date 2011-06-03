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

/**
pl * plagiarism.php - allows the admin to configure plagiarism stuff
 *
 * @package   plagiarism_turnitin
 * @author    Dan Marsden <dan@danmarsden.com>
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

    require_once(dirname(dirname(__FILE__)) . '/../config.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once($CFG->libdir.'/plagiarismlib.php');
    require_once($CFG->dirroot.'/plagiarism/moss/lib.php');
    require_once($CFG->dirroot.'/lib/formslib.php');

    class moss_enable_form extends moodleform 
    {

    	function definition () 
        {
            global $CFG;
            global $DB;

            $mform =& $this->_form;
            $choices = array('YES','NO');
            $yesnooptions = array(0 => "NO", 1 => "YES");
            $mossoptions = array(0 => "NEVER", 1 => "ALWAYS");
            
            $helplink = get_string('mossexplain', 'plagiarism_moss');
            $helplink .= '<a href='.$CFG->wwwroot.'/plagiarism/moss/help.php></a>';
            $mform->addElement('html', $helplink);
            
            $mform->addElement('checkbox', 'moss_use', get_string('usemoss', 'plagiarism_moss'));

            $mform->addElement('textarea', 'moss_student_disclosure', get_string('studentdisclosure','plagiarism_moss'),'wrap="virtual" rows="6" cols="50"');
            $mform->addHelpButton('moss_student_disclosure', 'studentdisclosure', 'plagiarism_moss');
            $mform->setDefault('moss_student_disclosure', get_string('studentdisclosuredefault','plagiarism_moss'));
            $mform->disabledIf('moss_student_disclosure', 'moss_use');

            $mform->addElement('text','default_entry','Default entry number');
            $mform->addRule('default_entry', null, 'numeric', null, 'client');
            $mform->addHelpButton('default_entry','adsf');
            $mform->disabledIf('default_entry', 'moss_use');
            
            $mform->addElement('select','enable_log','Enabel log',$yesnooptions);
            $mform->addHelpButton('enable_log', 'adsf');
            $mform->disabledIf('enable_log', 'moss_use');
            
            $mform->addElement('select','rerun','Rerun after settings changed',$yesnooptions);
            $mform->addHelpButton('rerun', 'adsf');
            $mform->disabledIf('rerun', 'moss_use');
            
            $mform->addElement('select','send_email','Send Email to students',$yesnooptions);
            $mform->addHelpButton('send_email', 'adsf');
            $mform->disabledIf('send_email', 'moss_use');
            
            $mform->addElement('select', 'show_text', 'Show similarity text to student', $mossoptions);
            $mform->addHelpButton('show_text', 'showstudentstext');
            $mform->disabledIf('show_text', 'moss_use');
            
            $mform->addElement('select', 'show_entrys', 'Show result entrys to student', $mossoptions);
            $mform->addHelpButton('show_entrys', 'plagiarism_moss');
            $mform->disabledIf('show_entrys', 'moss_use');
            
            $mform->addElement('select', 'cross_detection','Enable cross course detection',$yesnooptions);
            $mform->addHelpButton('cross_detection', 'plagiarism_moss');
            $mform->disabledIf('cross_detection', 'moss_use');
            
            $this->add_action_buttons(true);
            
            $default_setting = array('entryno'=> 3,'log'=> 1,'rerun'=> 1,'email'=> 0,'text'=> 0,'entrys'=> 1,'cross'=> 1);
            $old_setting = $DB->get_record('moss_plugin_setting', array('id'=>1));
            
            if($old_setting != null)
            {
                $DB->delete_records('moss_plugin_setting', array('id'=>1));
                $mform->setDefault('default_entry',$old_setting->entryno);	
                $mform->setDefault('enable_log',$old_setting->log);
                $mform->setDefault('rerun', $old_setting->rerun);
                $mform->setDefault('send_email', $old_setting->email);
                $mform->setDefault('show_text', $old_setting->text);
                $mform->setDefault('show_entrys', $old_setting->entrys);
                $mform->setDefault('cross_detection', $old_setting->cross);
            }
            else
            {
                $mform->setDefault('default_entry',$default_setting['entryno']);	
                $mform->setDefault('enable_log',$default_setting['log']);
                $mform->setDefault('rerun', $default_setting['rerun']);
                $mform->setDefault('send_email', $default_setting['email']);
                $mform->setDefault('show_text', $default_setting['text']);
                $mform->setDefault('show_entrys', $default_setting['entrys']);
                $mform->setDefault('cross_detection', $default_setting['cross']);
            }
            
        }
    }
    
    require_login();
    admin_externalpage_setup('plagiarismmoss');
    $context = get_context_instance(CONTEXT_SYSTEM);
    require_capability('moodle/site:config', $context, $USER->id, true, "nopermissions");

    $mform = new moss_enable_form();
    $plagiarismplugin = new plagiarism_plugin_moss();
    
    $currenttab='tab1';
    $tabs = array();
    $tabs[] = new tabobject('tab1', 'settings.php', 'Moss general settings', 'General_settings', false);
    $tabs[] = new tabobject('tab2', 'log.php', 'Moss error log', 'Error_log', false);
    $tabs[] = new tabobject('tab3', 'backup.php', 'Plugin backup', 'Plugin_backup', false);

    if ($mform->is_cancelled()) {
        redirect('');
    }

    echo $OUTPUT->header();
    print_tabs(array($tabs), $currenttab);
    
    if (($data = $mform->get_data()) && confirm_sesskey())
    {  	
        $newsetting = new object();
        $newsetting->entryno = (int)($data->default_entry);
        $newsetting->log = (int)($data->enable_log);
        $newsetting->rerun = (int)($data->rerun);
        $newsetting->email = (int)($data->send_email);
        $newsetting->text = (int)($data->show_text);
        $newsetting->entrys = (int)($data->show_entrys);
        $newsetting->cross = (int)($data->cross_detection);
        $DB->insert_record('moss_plugin_setting', $newsetting);
        //print_object($newsetting);
       // die;
        
        if (!isset($data->moss_use)) {
            $data->moss_use = 0;
        }
        foreach ($data as $field=>$value) {
            if (strpos($field, 'moss')===0) {
                if ($tiiconfigfield = $DB->get_record('config_plugins', array('name'=>$field, 'plugin'=>'plagiarism'))) {
                    $tiiconfigfield->value = $value;
                    if (! $DB->update_record('config_plugins', $tiiconfigfield)) {
                        error("errorupdating");
                    }
                } else {
                    $tiiconfigfield = new stdClass();
                    $tiiconfigfield->value = $value;
                    $tiiconfigfield->plugin = 'plagiarism';
                    $tiiconfigfield->name = $field;
                    if (! $DB->insert_record('config_plugins', $tiiconfigfield)) {
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

