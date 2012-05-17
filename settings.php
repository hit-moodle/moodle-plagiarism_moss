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
require_once($CFG->dirroot.'/plagiarism/moss/locallib.php');
require_once($CFG->dirroot.'/lib/formslib.php');

class moss_global_settings_form extends moodleform {

    function definition() {
        global $CFG;

        $mform =& $this->_form;

        $helplink = get_string('mossexplain', 'plagiarism_moss');
        $mform->addElement('html', $helplink);

        $mform->addElement('checkbox', 'mossenabled', get_string('mossenabled', 'plagiarism_moss'));
        $mform->setDefault('mossenabled', false);

        $mform->addElement('text', 'mossuserid', get_string('mossuserid', 'plagiarism_moss'));
        $mform->addHelpButton('mossuserid', 'mossuserid', 'plagiarism_moss');
        $mform->setDefault('mossuserid', '');
        $mform->disabledIf('mossuserid', 'mossenabled');

        $choices = moss_get_supported_languages();
        $mform->addElement('select', 'defaultlanguage', get_string('defaultlanguage', 'plagiarism_moss'), $choices);
        $mform->setDefault('defaultlanguage', 'ascii');
        $mform->disabledIf('defaultlanguage', 'mossenabled');

        $mform->addElement('checkbox', 'showidnumber', get_string('showidnumber', 'plagiarism_moss'));
        $mform->setDefault('showidnumber', false);
        $mform->disabledIf('showidnumber', 'mossenabled');

        $mform->addElement('text', 'maxfilesize', get_string('maxfilesize', 'plagiarism_moss'));
        $mform->addHelpButton('maxfilesize', 'maxfilesize', 'plagiarism_moss');
        $mform->setDefault('maxfilesize', MOSS_DEFAULT_MAXFILESIZE);
        $mform->setType('maxfilesize', PARAM_INT);
        $mform->disabledIf('maxfilesize', 'mossenabled');

        $mform->addElement('text', 'antiwordpath', get_string('antiwordpath', 'plagiarism_moss'));
        $mform->addHelpButton('antiwordpath', 'antiwordpath', 'plagiarism_moss');
        $mform->setDefault('antiwordpath', '');
        $mform->setType('antiwordpath', PARAM_RAW);
        $mform->disabledIf('antiwordpath', 'mossenabled');

        if ($CFG->ostype == 'WINDOWS') {
            $mform->addElement('text', 'cygwinpath', get_string('cygwinpath', 'plagiarism_moss'));
            $mform->setDefault('cygwinpath', 'C:\cygwin');
            $mform->setType('cygwinpath', PARAM_RAW);
            $mform->disabledIf('cygwinpath', 'mossenabled');
        }

        $this->add_action_buttons(false);
    }

    function validation($data, $files) {
        global $CFG;
        $errors = parent::validation($data, $files);

        if (!empty($data['mossenabled'])) {
            if (!is_numeric($data['mossuserid'])) {
                $errors['mossuserid'] = get_string('err_numeric', 'form');
            }
            if ($CFG->ostype == 'WINDOWS') {
                if (!is_executable($data['cygwinpath'].'\\bin\\perl.exe')) {
                    $errors['cygwinpath'] = get_string('err_cygwinpath', 'plagiarism_moss');
                }
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
        set_config('moss_use', $data->mossenabled, 'plagiarism');
        set_config('mossuserid', $data->mossuserid, 'plagiarism_moss');
        set_config('defaultlanguage', $data->defaultlanguage, 'plagiarism_moss');
        set_config('showidnumber', $data->showidnumber, 'plagiarism_moss');
        set_config('maxfilesize', $data->maxfilesize, 'plagiarism_moss');
        set_config('antiwordpath', $data->antiwordpath, 'plagiarism_moss');
        if ($CFG->ostype == 'WINDOWS') {
            set_config('cygwinpath', $data->cygwinpath, 'plagiarism_moss');
        }
    } else {
        set_config('moss_use', 0, 'plagiarism');
    }

    echo $OUTPUT->notification(get_string('savedconfigsuccess', 'plagiarism_moss'), 'notifysuccess');
}

$settings = array();
if ($moss_use = get_config('plagiarism', 'moss_use')) {
    $settings['mossenabled'] = $moss_use;
}
$moss_configs = (array)get_config('plagiarism_moss');
$settings = array_merge($settings, $moss_configs);
$mform->set_data($settings);

echo $OUTPUT->box_start();
$mform->display();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
