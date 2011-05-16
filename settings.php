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

    class moss_enable_form extends moodleform {

    	function definition () {
            global $CFG;

            $mform =& $this->_form;
            $choices = array('No','Yes');
            $helplink = get_string('mossexplain', 'plagiarism_moss');
            $helplink .= '<a href='.$CFG->wwwroot.'/plagiarism/moss/help.php></a>';
            $mform->addElement('html', $helplink);
            
            $mform->addElement('checkbox', 'moss_use', get_string('usemoss', 'plagiarism_moss'));

            $mform->addElement('textarea', 'moss_student_disclosure', get_string('studentdisclosure','plagiarism_moss'),'wrap="virtual" rows="6" cols="50"');
            $mform->addHelpButton('moss_student_disclosure', 'studentdisclosure', 'plagiarism_moss');
            $mform->setDefault('moss_student_disclosure', get_string('studentdisclosuredefault','plagiarism_moss'));

            $mform->addElement('text','default_entry','Default entry number');
            $mform->addHelpButton('default_entry','adsf');
            
            $yesnooptions = array(0 => get_string("yes"), 1 => get_string("no"));
            
            $mform->addElement('select','log','Enabel log',$yesnooptions);
            $mform->addHelpButton('log','adsf');
            
            $mform->addElement('select','run','Rerun after settings changed',$yesnooptions);
            $mform->addHelpButton('run','adsf');
            
            $mform->addElement('select','send','Send user Email',$yesnooptions);
            $mform->addHelpButton('send','adsf');
            
            $mossoptions = array(0 => get_string("never"), 1 => get_string("always"));
            $mform->addElement('select', 'plagiarism_show_student_text', 'Show similarity text to student', $mossoptions);
            $mform->addHelpButton('plagiarism_show_student_text', 'showstudentstext');
            $mform->addElement('select', 'plagiarism_show_student_result_detail', 'Show result detail to student', $mossoptions);
            $mform->addHelpButton('plagiarism_show_student_result_detail',  'plagiarism_turnitin');
        
            
            $mform->addElement('select', 'cross_anti_plagiarism','Enable cross course detection',$yesnooptions);
            $mform->addHelpButton('cross_anti_plagiarism',  'plagiarism_turnitin');
            
            $this->add_action_buttons(true);
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
    
    if (($data = $mform->get_data()) && confirm_sesskey()) {
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

