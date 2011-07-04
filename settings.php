<?php
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
//                      Online Judge for Moodle                          //
//        https://github.com/hit-moodle/moodle-local_onlinejudge         //
//                                                                       //
// Copyright (C) 2009 onwards  Sun Zhigang  http://sunner.cn             //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 3 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Global settings
 *
 * @package   plagiarism_moss
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/plagiarismlib.php');
require_once($CFG->dirroot.'/lib/formslib.php');

class moss_global_settings_form extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $helplink = get_string('mossexplain', 'plagiarism_moss');
        $mform->addElement('html', $helplink);

        $mform->addElement('checkbox', 'mossenabled', get_string('mossenabled', 'plagiarism_moss'));
        $mform->setDefault('mossenabled', false);

        $mform->addElement('textarea', 'moss_student_disclosure', get_string('studentdisclosure','plagiarism_moss'),'wrap="virtual" rows="6" cols="50"');
        $mform->addHelpButton('moss_student_disclosure', 'studentdisclosure', 'plagiarism_moss');
        $mform->setDefault('moss_student_disclosure', get_string('studentdisclosuredefault','plagiarism_moss'));
        $mform->setType('moss_student_disclosure', PARAM_TEXT);
        $mform->disabledIf('moss_student_disclosure', 'mossenabled');

        $mform->addElement('text', 'mossuserid', get_string('mossuserid', 'plagiarism_moss'));
        $mform->addHelpButton('mossuserid', 'mossuserid', 'plagiarism_moss');
        $mform->setDefault('mossuserid', '');
        $mform->disabledIf('mossuserid', 'mossenabled');

        $this->add_action_buttons(false);
    }

    function validation($data, $files) { 
        $errors = parent::validation($data, $files);

        if (!empty($data['mossenabled'])) {
            if (!is_numeric($data['mossuserid'])) {
                $errors['mossuserid'] = get_string('err_numeric', 'form');
            }
        }

        return $errors;
    }
}

require_login();
admin_externalpage_setup('plagiarismmoss');
$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('moodle/site:config', $context, $USER->id);

$mform = new moss_global_settings_form();
    
echo $OUTPUT->header();

if (($data = $mform->get_data()) && confirm_sesskey()) { 

    if (!empty($data->mossenabled)) {
        set_config('mossenabled', $data->mossenabled, 'plagiarism_moss');
        set_config('moss_student_disclosure', $data->moss_student_disclosure, 'plagiarism_moss');
        set_config('mossuserid', $data->mossuserid, 'plagiarism_moss');
    } else {
        set_config('mossenabled', 0, 'plagiarism_moss');
    }

    notify(get_string('savedconfigsuccess', 'plagiarism_moss'), 'notifysuccess');
}

$settings = array();
if (get_config('plagiarism_moss', 'mossenabled')) {
    $settings['mossenabled'] = get_config('plagiarism_moss', 'mossenabled');
}
if (get_config('plagiarism_moss', 'moss_student_disclosure')) {
    $settings['moss_student_disclosure'] = get_config('plagiarism_moss', 'moss_student_disclosure');
}
if (get_config('plagiarism_moss', 'mossuserid')) {
    $settings['mossuserid'] = get_config('plagiarism_moss', 'mossuserid');
}
$mform->set_data($settings);
    
echo $OUTPUT->box_start();
$mform->display();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
