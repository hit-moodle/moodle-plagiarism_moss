<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
//                   Moss Anti-Plagiarism for Moodle                     //
//         https://github.com/hit-moodle/moodle-plagiarism_moss          //
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
 * Anti-Plagiarism by Moss
 *
 * @package   plagiarism_moss
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot.'/plagiarism/lib.php');
require_once($CFG->dirroot.'/plagiarism/moss/locallib.php');

define('MOSS_MAX_PATTERNS', 3);

/**
 * plagiarism_plugin_moss inherit from plagiarism_plugin class, this is the most important class in plagiarism plugin,
 * Moodle platform will automatically call the function of this class.
 * @author
 *
 */
class plagiarism_plugin_moss extends plagiarism_plugin {
	/**
	 * (non-PHPdoc)
	 * @see plagiarism_plugin::print_disclosure()
	 */
    public function print_disclosure($cmid) {
    	global $OUTPUT, $DB;
        if (moss_enabled($cmid)) {
            echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
            $formatoptions = new stdClass;
            $formatoptions->noclean = true;
            echo format_text(get_config('plagiarism_moss', 'moss_student_disclosure'), FORMAT_MOODLE, $formatoptions);
            echo $OUTPUT->box_end();
    	}
    }

    /**
     * (non-PHPdoc)
     * @see plagiarism_plugin::save_form_elements()
     */
    public function save_form_elements($data) {
        global $DB;

        if (!moss_enabled()) {
            return;
        }

        $moss = new stdClass();
        $moss->enabled = empty($data->enabled) ? 0 : 1;
        $moss->timetomeasure = $data->timetomeasure; //TODO: activity due date
        $moss->cmid = $data->coursemodule;
        $moss->modulename = $data->name;
        $moss->coursename = $DB->get_field('course', 'shortname', array('id' => $data->course));

        // process tag
        if (empty($data->tag)) {
            $moss->tag = 0;
        } else {
            if ($tagid = $DB->get_field('moss_tags', 'id', array('name' => $data->tag))) {
                $moss->tag = $tagid;
            } else {
                $tag = new stdClass();
                $tag->name = $data->tag;
                $moss->tag = $DB->insert_record('moss_tags', $tag);
            }
        }

        if (isset($data->mossid)) {
            $moss->id = $data->mossid;
            $DB->update_record('moss', $moss);
        } else {
            $data->mossid = $DB->insert_record('moss', $moss);
        }

        if (!$moss->enabled) {
            // disabled moss keep old configs
            return;
        }

        // sub configs
        for($index = 0; $index < MOSS_MAX_PATTERNS; $index++) {
            $config = new stdClass();
            $config->moss = $data->mossid;
            $member = 'language'.$index;
            $config->language = isset($data->$member) ? $data->$member : 'c';
            $member = 'sensitivity'.$index;
            $config->sensitivity = $data->$member;

            $member = 'filepatterns'.$index;
            $config->filepatterns = str_replace('..', '', $data->$member); // filter out .. to for safety
            if ($index == 0 and empty($config->filepatterns)) {
                $config->filepatterns = '*';
            }

            $member= 'configid'.$index;
            if (isset($data->$member)) {
                $config->id = $data->$member;
                $DB->update_record('moss_configs', $config);
            } else {
                $config->id = $DB->insert_record('moss_configs', $config);
            }

            $context = get_system_context();
            $member= 'basefile'.$index;
            file_save_draft_area_files($data->$member, $context->id, 'plagiarism_moss', 'basefiles', $config->id);
        }
    }

    /**
     * (non-PHPdoc)
     * @see plagiarism_plugin::get_form_elements_module()
     */
    public function get_form_elements_module($mform, $context) {
        global $DB;

        if (!moss_enabled()) {
            return;
        }

        // Construct the form
        $mform->addElement('header', 'mossdesc', get_string('moss', 'plagiarism_moss'));
        $mform->addHelpButton('mossdesc', 'moss', 'plagiarism_moss');

        $mform->addElement('checkbox', 'enabled', get_string('mossenabled', 'plagiarism_moss'));

        $mform->addElement('date_time_selector', 'timetomeasure', get_string('timetomeasure', 'plagiarism_moss'));
        $mform->addHelpButton('timetomeasure', 'timetomeasure', 'plagiarism_moss');
        $mform->disabledIf('timetomeasure', 'enabled');

        $mform->addElement('text', 'tag', get_string('tag', 'plagiarism_moss'));
        $mform->addHelpButton('tag', 'tag', 'plagiarism_moss');
        $mform->setType('tag', PARAM_TEXT);
        $mform->disabledIf('tag', 'enabled');

        // multi configs
        for($index = 0; $index < MOSS_MAX_PATTERNS; $index++) {
            if ($index == 0) {
                $subheader = get_string('configrequired', 'plagiarism_moss', $index+1);
            } else {
                $subheader = get_string('configoptional', 'plagiarism_moss', $index+1);
            }
            $subheader = html_writer::tag('strong', $subheader);
            $mform->addElement('static', 'subheader', $subheader);

            $mform->addElement('text', 'filepatterns'.$index, get_string('filepatterns', 'plagiarism_moss'));
            $mform->addHelpButton('filepatterns'.$index, 'filepatterns', 'plagiarism_moss');
            $mform->setType('filepatterns'.$index, PARAM_TEXT);
            $mform->disabledIf('filepatterns'.$index, 'enabled');

            $choices = array('ada'     => 'Ada',              'ascii'      => 'ASCII',
                             'a8086'   => 'a8086 assembly',   'c'          => 'C',
                             'cc'      => 'C++',              'csharp'     => 'C#',
                             'fortran' => 'FORTRAN',          'haskell'    => 'Haskell',
                             'java'    => 'Java',             'javascript' => 'Javascript',
                             'lisp'    => 'Lisp',             'matlab'     => 'Matlab',
                             'mips'    => 'MIPS assembly',    'ml'         => 'ML',
                             'modula2' => 'Modula2',          'pascal'     => 'Pascal',
                             'perl'    => 'Perl',             'plsql'      => 'PLSQL',
                             'prolog'  => 'Prolog',           'python'     => 'Python',
                             'scheme'  => 'Scheme',           'spice'      => 'Spice',
                             'vhdl'    => 'VHDL',             'vb'         => 'Visual Basic');
            $mform->addElement('select', 'language'.$index, get_string('language', 'plagiarism_moss'), $choices);
            $mform->disabledIf('language'.$index, 'enabled');

            $mform->addElement('text', 'sensitivity'.$index, get_string('sensitivity', 'plagiarism_moss'), 'size = "10"');
            $mform->addHelpButton('sensitivity'.$index, 'sensitivity', 'plagiarism_moss');
            $mform->setType('sensitivity'.$index, PARAM_NUMBER);
            $mform->addRule('sensitivity'.$index, null, 'numeric', null, 'client');
            $mform->disabledIf('sensitivity'.$index, 'enabled');

            $mform->addElement('filemanager', 'basefile'.$index, get_string('basefile', 'plagiarism_moss'), null, array('subdirs' => 0));
            $mform->addHelpButton('basefile'.$index, 'basefile', 'plagiarism_moss');
            $mform->disabledIf('basefile'.$index, 'enabled');
        }

        // set config values
        $cmid = optional_param('update', 0, PARAM_INT); //there doesn't seem to be a way to obtain the current cm a better way - $this->_cm is not available here.
        if ($cmid != 0 and $moss = $DB->get_record('moss', array('cmid'=>$cmid))) { // configed
            $mform->setDefault('enabled', $moss->enabled);
            $mform->setDefault('timetomeasure', $moss->timetomeasure);
            $mform->setDefault('tag', $DB->get_field('moss_tags', 'name', array('id' => $moss->tag)));
            $mform->addElement('hidden', 'mossid', $moss->id);

            $subconfigs = $DB->get_records('moss_configs', array('moss'=>$moss->id));
            $index = 0;
            foreach ($subconfigs as $subconfig) {
                $mform->setDefault('filepatterns'.$index, $subconfig->filepatterns);
                $mform->setDefault('language'.$index, $subconfig->language);
                $mform->setDefault('sensitivity'.$index, $subconfig->sensitivity);
                $mform->addElement('hidden', 'configid'.$index, $subconfig->id);

                $context = get_system_context();
                $draftitemid = 0;
                file_prepare_draft_area($draftitemid, $context->id, 'plagiarism_moss', 'basefiles', $subconfig->id);
                $mform->setDefault('basefile'.$index, $draftitemid);

                $index++;
            }
        } else { // new config
            $mform->setDefault('enabled', 0);
            $mform->setDefault('tag', '');
            $mform->setDefault('filepatterns0', '*.c');
            $mform->setDefault('language0', 'c');
            $mform->setDefault('sensitivity0', 20);
            // leave other subconfig empty
        }
    }

    /**
     * (non-PHPdoc)
     * @see plagiarism_plugin::get_links()
     */
    public function get_links($linkarray) {
        //$userid, $file, $cmid, $course, $module
        global $CFG;
        $cmid = $linkarray['cmid'];
        $userid = $linkarray['userid'];
        $file = $linkarray['file'];
        $link = '<span class="plagiarismreport">
                     <a href= "'.$CFG->wwwroot.'/plagiarism/moss/result_pages/student_page.php?cmid='.$cmid.'&id='.$userid.'" >
                     anti-plagiarism result link
                     <a/>';//$cmid.$userid.$file;
        //add link/information about this file to $link
        return $link;
    }

    /**
     * (non-PHPdoc)
     * @see plagiarism_plugin::update_status()
     */
    public function update_status($course, $cm) {
        global $CFG;
        echo '<a href="'.$CFG->wwwroot.'/plagiarism/moss/result_pages/view_all.php?cmid='.$cm->id.'">
              anti-plagiarism verify page
              </a>';
    }

    /**
     * Hook for cron
     */
    public function cron() {
        global $DB;

        $select  = 'timetomeasure > timemeasured AND enabled = 1';
        $mosses = $DB->get_records_select('moss', $select);
        foreach ($mosses as $moss) {
            mtrace("Moss measure $moss->modulename ($moss->cmid) in $moss->coursename");
            $moss_obj = new moss($moss->cmid);
            $moss_obj->measure();
        }
    }
}


/**
 *
 * Enter description here ...
 * @author ycc
 *
 */
class plugin_error_test
{
    /**
     *
     * Enter description here ...
     */
    function response($id, $echo = true)
    {
    	global $DB;
    	$record = $DB->get_record('moss_plugin_errors',array('id' => $id));

    	if((! isset($record))||($record->errstatus == 0))
    	{
    	    echo $this->generatexml($id, true, $echo);
    	    return;
    	}
    	//configuration file
    	if(($record->errtype >= 1) && ($record->errtype <= 5))
    	{
    		$cfg = new config_xml();
    		$re = $cfg->error_test($record->errtype, $record->errargument);
    	}
    	//move file to temp error; remove file from temp error; download code detail error
    	if(($record->errtype == 11) || ($record->errtype == 12) || ($record->errtype == 21))
    	{
    		$file_op = new file_operator();
    		$re = $file_op->error_test($record->errtype, $record->errargument);
    	}
    	//
    	if(($record->errtype == 25))
    	{
    	    $moss_op = new moss_operator();
    	    $re = $moss_op->error_test($record->errtype, $record->errargument);
    	}
    	
        if(($re != true))
        {
            echo $this->generatexml($id, false, $echo);
            return;
        }
        else
    	{    
    		if(($this->update_solved($id) != true)) 
    		    echo $this->generatexml($id, false, $echo);
    		else 
    		    echo $this->generatexml($id, true, $echo);
    		return;
    	}
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $re
     * @param unknown_type $echo
     */
    private function generatexml($id, $re, $echo)
    {
    	if($echo == false)
    	    return '';
    	    
    	$status = ($re == true)? 0 : 1;
    	$content = '<?xml version="1.0" encoding="ISO-8859-1"?>';
        $content.= '<ROOT><RESPONSE>';
        $content.= '<STATUS>'.$status.'</STATUS>';
        $content.= '<ID>'.$id.'</ID>';
        $content.= '</RESPONSE></ROOT>'; 
        
        return $content;
    }
    
    /**
     * 
     * Enter description here ...
     */
    private function update_solved($id)
    {
    	global $DB;
    	$record = $DB->get_record('moss_plugin_errors',array('id' => $id));
    	if(! isset($record))
    	    return true;
    	$record->errstatus = 0;//solved
        if (! $DB->update_record('moss_plugin_errors', $record)) 
        {    
        	error("errorupdating");
        	return false;
        }
        return true;
    }
    
    /**
     * 
     * Enter description here ...
     */
    public function test_all()
    {
    	global $DB;
    	$records = $DB->get_records('moss_plugin_errors', array('errstatus' => 1, 'testable' => 1));//all unsolved entrys
    	foreach($records as $record)
    		$this->response($record->id, false);
    }
}

/**
 * 
 * Enter description here ...
 * @param unknown_type $eventdata
 */
function moss_event_file_uploaded($eventdata) {
    return moss_save_files($eventdata);
}

/**
 * 
 * Enter description here ...
 * @param unknown_type $eventdata
 */
function moss_event_files_done($eventdata) {
    $result = true;
    // nothing to do
    return $result;
}

/**
 * 
 * Enter description here ...
 * @param unknown_type $eventdata
 */
function moss_event_mod_created($eventdata) {
    $result = true;
    return $result;
}

/**
 * 
 * Enter description here ...
 * @param unknown_type $eventdata
 */
function moss_event_mod_updated($eventdata) {
    //check if duetime modified, if duetime was postponed and moss already run before
    //set rerun = 1, 
    // TODO: update coursename and modulename
    $result = true;
    return $result;    
}

/**
 * 
 * Enter description here ...
 * @param unknown_type $eventdata
 */
function moss_event_mod_deleted($eventdata) {
    //delete entrys that relevant to this cm in table 'moss_settings', 'moss_results'
    //do not delete entry in table 'moss_tag'
    //delete all result pages that downloaded from moss server
	global $DB;
	$re;
	if($DB -> record_exists_select('moss_settings', 'cmid='.$cmid))
    {
        $re = $DB->delete_records('moss_settings', array('cmid'=>$eventdata->cmid));
        if($re == false)
            return false;
        $DB->delete_records('moss_results', array('cmid'=>$eventdata->cmid));
        $file_handler = new file_operator(); 
        $re = $file_handler->remove_results_files_by_cm($eventdata->cmid);
        return $re;
    }
}

