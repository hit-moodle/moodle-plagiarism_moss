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
 * Anti-Plagiarism by Moss
 *
 * @package   plagiarism_moss
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');    

require_once($CFG->dirroot.'/plagiarism/lib.php');
require_once($CFG->dirroot.'/plagiarism/moss/moss_settings.php');
require_once($CFG->dirroot.'/plagiarism/moss/moss_operator.php');
require_once($CFG->dirroot.'/plagiarism/moss/file_operator.php');


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
    	if ($DB->record_exists('moss', array('cmid' => $cmid))) {
            $plagiarismsettings = (array)get_config('plagiarism');
            echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
            $formatoptions = new stdClass;
            $formatoptions->noclean = true;
            echo format_text($plagiarismsettings['moss_student_disclosure'], FORMAT_MOODLE, $formatoptions);
            echo $OUTPUT->box_end();
    	}
    }

    /**
     * (non-PHPdoc)
     * @see plagiarism_plugin::save_form_elements()
     */
    public function save_form_elements($data) {
    	$setting = new moss_settings();
        $setting->save_settings($data);
    }

    /**
     * (non-PHPdoc)
     * @see plagiarism_plugin::get_form_elements_module()
     */
    public function get_form_elements_module($mform, $context) {
    	$setting = new moss_settings();
        $setting->show_settings_form($mform, $context);
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
     * 
     * Enter description here ...
     */
    public function cron() {
        global $DB;
        $err_test = new plugin_error_test();
        echo 'moss plugin check error...';
        $err_test -> test_all();
        echo 'moss plugin check error finished.';
        $moss_op = new moss_operator();
        //mtrace('当前时间：'.userdate(time()));
        $current_time = time();

        $sql = "SELECT cm.id AS cmid, am.name AS name, am.timeavailable AS timeavilable, am.timedue AS timedue
                FROM {course_modules} AS cm, {assignment} AS am
                WHERE cm.module=? AND
                      cm.instance=am.id AND
                      cm.id IN
                            (SELECT DISTINCT cmid
                             FROM {moss_settings} 
                             WHERE measuredtime=0)"; 
        //currently only assignment can activate anti-plagiarism 
        $params = array(1);
        //get all
        $results = $DB->get_records_sql($sql,$params);
        foreach($results as $result) {
            //if($result->timedue > $current_time)//time to run
            //{
            	$moss_op -> connect_moss($result->cmid);
            //}   
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
function moss_event_file_uploaded($eventdata) 
{
    $file_handler = new file_operator(); 
    return $file_handler->save_upload_file_files($eventdata);
}

/**
 * 
 * Enter description here ...
 * @param unknown_type $eventdata
 */
function moss_event_files_done($eventdata) 
{
    $result = true;
    return $result;
}

/**
 * 
 * Enter description here ...
 * @param unknown_type $eventdata
 */
function moss_event_mod_created($eventdata) 
{
    $result = true;
    return $result;
}

/**
 * 
 * Enter description here ...
 * @param unknown_type $eventdata
 */
function moss_event_mod_updated($eventdata) 
{
    //check if duetime modified, if duetime was postponed and moss already run before
    //set rerun = 1, 
    $result = true;
    return $result;    
}

/**
 * 
 * Enter description here ...
 * @param unknown_type $eventdata
 */
function moss_event_mod_deleted($eventdata) 
{
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

