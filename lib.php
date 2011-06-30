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


//prevent direct access
if (!defined('MOODLE_INTERNAL')) 
    die('Direct access to this script is forbidden.');    

//global class
global $CFG;
require_once($CFG->dirroot.'/plagiarism/lib.php');
//include moss setting class
require_once($CFG->dirroot.'/plagiarism/moss/moss_settings.php');
//include moss operator class
require_once($CFG->dirroot.'/plagiarism/moss/moss_operator.php');
//include file operator class
require_once($CFG->dirroot.'/plagiarism/moss/file_operator.php');


/**
 * plagiarism_plugin_moss inherit from plagiarism_plugin class, this is the most important class in plagiarism plugin,
 * Moodle platform will automatically call the function of this class.
 * @author 
 *
 */
class plagiarism_plugin_moss extends plagiarism_plugin 
{
	/**
	 * (non-PHPdoc)
	 * @see plagiarism_plugin::print_disclosure()
	 */
    public function print_disclosure($cmid)
    {
    	global $OUTPUT;
    	global $DB;
    	if($DB -> record_exists_select('moss_settings', 'cmid='.$cmid))
    	{
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
    public function save_form_elements($data) 
    {
    	$setting = new moss_settings();
        $setting->save_settings($data);  
    }
    
    /**
     * (non-PHPdoc)
     * @see plagiarism_plugin::get_form_elements_module()
     */
    public function get_form_elements_module($mform, $context) 
    {
    	$setting = new moss_settings();
        $setting->show_settings_form($mform, $context);      
    }
    
    /**
     * (non-PHPdoc)
     * @see plagiarism_plugin::get_links()
     */
    public function get_links($linkarray) 
    {
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
    public function update_status($course, $cm) 
    { 
        global $CFG;
        echo '<a href="'.$CFG->wwwroot.'/plagiarism/moss/result_pages/view_all.php?cmid='.$cm->id.'">
              anti-plagiarism verify page
              </a>'; 
    }
    
    /**
     * 
     * Enter description here ...
     */
    public function cron() 
    {
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
        foreach($results as $result)
        {
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
class config_xml
{
    /**
     * 
     * Enter description here ...
     */
    public function get_config_all()
    {
    	global $CFG;
        $array = array();
        //if xml file not exist return default data 
        if(file_exists($CFG->dirroot.'/plagiarism/moss/config.xml'))
        {
        	if(!is_readable($CFG->dirroot.'/plagiarism/moss/config.xml'))
        	{
        	    $this->trigger_error('Configuration file "config.xml" unreadable.', 1);
                return $this->default;        
        	}
        }    
        else//is not an error 
            return $this->default;
        
        $doc = new DOMDocument();
        if($doc->load($CFG->dirroot.'/plagiarism/moss/config.xml'))
        {
        	$array = $this->parse_xml($doc);
        	if($array == NULL)
            {
            	$this->trigger_error('Error when parse configuration file "config.xml", some configuration tag missing.', 3);
            	return $this->default;
            }
            else 
    	        return $array;
        }
        else
        {
        	$this->trigger_error('Error when loading Configuration file "config.xml".', 2);
            return $this->default;
        }
    }

    /**
     * 
     * Enter description here ...
     * @param unknown_type $doc
     */
    private function parse_xml($doc)
    {
    	$array = array();
        $entry_number                           = $doc->getElementsByTagName('entry_number');
        $enable_log                             = $doc->getElementsByTagName('enable_log');
        $rerun                                  = $doc->getElementsByTagName('rerun_after_change');
        $send_email                             = $doc->getElementsByTagName('send_email');
        $show_code                              = $doc->getElementsByTagName('show_code');
        $show_entrys_detail                     = $doc->getElementsByTagName('show_entrys_detail');   
        $cross                                  = $doc->getElementsByTagName('enable_cross-course_detection'); 
        $appeal                                 = $doc->getElementsByTagName('enable_student_appeal');
        $default_students                       = $doc->getElementsByTagName('default_students_in_statistics_page');
       
        if(($entry_number->length == 0)||
          ($enable_log->length == 0)||
          ($rerun->length == 0)||
          ($send_email->length == 0)||
          ($show_code->length == 0)||
          ($show_entrys_detail->length == 0)||
          ($cross->length == 0)||
          ($appeal->length == 0)||
          ($default_students ->length == 0))
         return NULL;
                   
        $array['entry_number']                        = $entry_number->item(0)->nodeValue;   
        $array['enable_log']                          = $enable_log->item(0)->nodeValue;                      
        $array['rerun_after_change']                  = $rerun->item(0)->nodeValue;           
        $array['send_email']                          = $send_email->item(0)->nodeValue;                       
        $array['show_code']                           = $show_code->item(0)->nodeValue;                       
        $array['show_entrys_detail']                  = $show_entrys_detail->item(0)->nodeValue;                      
        $array['enable_cross-course_detection']       = $cross->item(0)->nodeValue;            
        $array['enable_student_appeal']               = $appeal->item(0)->nodeValue;
        $array['default_students_in_statistics_page'] = $default_students->item(0)->nodeValue;

    	    return $array;
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $array
     */
    public function save_config($array)
    {
        global $CFG;
    	$doc = new DOMDocument('1.0');
        $doc->formatOutput = true;
    	$root = $doc->createElement("config");
    	$doc->appendChild($root);
    	//expandable code
    	foreach ($array as $name => $value)
        {
            $$name = $doc->createElement($name);
            $$name->appendChild($doc->createTextNode($value));
            $root->appendChild($$name);
        }
        //$config_xml = $doc->saveXML();
        //delete config.xml if existed.
        if(file_exists($CFG->dirroot.'/plagiarism/moss/config.xml'))
        {
        	$re = unlink($CFG->dirroot.'/plagiarism/moss/config.xml');
            if($re == false)
            {
                $this->trigger_error('Unable to delete configuration file "config.xml".', 4);
                return false;
            }
        }
        
        if(!$doc->save($CFG->dirroot.'/plagiarism/moss/config.xml'))
        {
        	$this->trigger_error('Unable to save configuration file "config.xml".', 5);
        	return false;
        }   
        return true;
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $tagname
     */
    public function get_config($tagname)
    {
    	global $CFG;
    	//read xml file if not exist, return default 
    	if(! file_exists($CFG->dirroot.'/plagiarism/moss/config.xml'))
            return $this->default[$tagname];
    	else 
    	{
    	    $doc = new DOMDocument();
            if($doc->load($CFG->dirroot.'/plagiarism/moss/config.xml'))
            {
                $tag = $doc->getElementsByTagName($tagname);
                if(isset($tag))
                    return $tag->item(0)->nodeValue;
                else
                    return $this->default[$tagname];
            }
            else 
            {
                return $this->default[$tagname];
            }
        //TODO error handle
    	}
    }
   
    /**
     * 
     * Enter description here ...
     */
    public function error_test($type, $arrguments)
    {
        global $CFG;
        switch($type)
        {
        	case 1://existed but unreadable
        		if(is_readable($CFG->dirroot.'/plagiarism/moss/config.xml'))
        		    return true;
        		return false;
        		break;
        	case 4://unable to delete
        	case 5://unable to create
        		//test if directory access = "read and write";
                $fp = fopen($CFG->dirroot.'/plagiarism/moss/config_error_test.xml','w+');
                if($fp == false)
                    return false;
                fwrite($fp, 'test');
                fclose($fp); 
                return unlink($CFG->dirroot.'/plagiarism/moss/config_error_test.xml');
        		break;
        	case 2://load error
        	case 3://parse error
        		$doc = new DOMDocument();
        	    if(! $doc->load($CFG->dirroot.'/plagiarism/moss/config.xml'))
        	        return false;
        	    if($type == 2)
        	        return true;
        	    else 
        	    {	
        	    	$array = $this->parse_xml($doc);
        	    	if($array == NULL)
        	    	    return false;
        	    	else 
        	    	    return true;
        	    }
        		break;
        	default:break;
        }
    }

    /**
     * 
     * Enter description here ...
     */
    private function trigger_error($description, $type)
    {
        global $CFG;
        global $DB;
        $records = $DB->get_records('moss_plugin_errors', array('errtype'=>$type, 'errstatus'=>1));
        if(count($records) != 0)
        {
            return;
        }
        $err = new object();
        $err->errdate = time();
        $err->errtype = $type;
        $err->errdescription = $description;
        $err->errstatus = 1;

        if(($type == 1) || ($type == 4) || ($type == 5))//unable to read; unable to delete; unable to create;
        {
            $err->errsolution = 'Check permission on directory :"'.$CFG->dirroot.'/plagiarism/moss/"'. 
                                'or configuration file :"./config.xml" make sure Access = "Read and Write".';
            $err->testable = 1;
        }
        else 
        {
            if(($type == 2) || ($type == 3))//unable to load; unable to parse
            {    
            	$err->errsolution = 'Check file format, file path=:"'.$CFG->dirroot.'/plagiarism/moss/config.xml"'. 
                                    'make sure it\'s a XML file. visit "Plugin general settings" page and press "save change" button, the plugin will generate'.
                                    'a default "config.xml" file';
            	$err->testable = 1;
            }
            else
            { 
                $err->errsolution = 'Unknown.';
                $err->testable = 0;
            }
        }
        $err->errargument = 'no arguments';
        $DB->insert_record('moss_plugin_errors', $err);
    }
    
    private $default = array('entry_number' => 2,
                             'enable_log' => 'YES',
                             'rerun_after_change' => 'YES',
                             'send_email' => 'NO',
                             'show_code' => 'NEVER',
                             'show_entrys_detail' => 'ALWAYS',
                             'enable_cross-course_detection' => 'NO',
                             'enable_student_appeal' => 'ALWAYS',
                             'default_students_in_statistics_page' => 8);
   
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


