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
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    
}

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
class plagiarism_plugin_moss extends plagiarism_plugin {

	/**
	 * (non-PHPdoc)
	 * @see plagiarism_plugin::print_disclosure()
	 */
    public function print_disclosure($cmid) {
    	global $OUTPUT;
    	global $DB;
    	if($DB -> record_exists('moss_tags', array('cmid' => $cmid))){
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
        //print_object($data);
        //die;
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
        $cmid = $linkarray['cmid'];
        $userid = $linkarray['userid'];
        $file = $linkarray['file'];
        $link = '<span class="plagiarismreport"><a href= www.google.com > anti-plagiarism result link <a/>';//$cmid.$userid.$file;
        //add link/information about this file to $link
        return $link;      
    }

    /**
     * (non-PHPdoc)
     * @see plagiarism_plugin::update_status()
     */
    public function update_status($course, $cm) { 
    	//called at top of submissions/grading pages - allows printing of admin style links or updating status
        //echo '<div class="allcoursegrades"><a href="' . $CFG->wwwroot . '/grade/report/grader/index.php?id=' . $course->id . '">'
        //        . get_string('seeallcoursegrades', 'grades') . '</a></div>';
      
        echo '<a href="http://localhost/moodle/plagiarism/moss/result_pages/view_all.php?id='.$cm->id.'">anti-plagiarism verify page</a>';
       
    }
    
    /**
     * 
     * Enter description here ...
     */
    public function cron() {
        mtrace("\n***********************>moss定时器启动\n");
        //global $DB;
        $moss_op = new moss_operator();
        //$moss_op -> connect_moss(4);
    }
}

class config_xml
{

    public function get_config_all()
    {
    	global $CFG;
        $array = array();
        //if xml file not exist return default data 
        if(file_exists($CFG->dirroot.'/plagiarism/moss/config.xml'))
        {
        	if(!is_readable($CFG->dirroot.'/plagiarism/moss/config.xml'))
        	{
        	    echo 'xml file exist, but unreadable, check permission';
                return $this->default;        
        	}
        }    
        else 
        {
            return $this->default;
        }
        $doc = new DOMDocument();
        if($doc->load($CFG->dirroot.'/plagiarism/moss/config.xml'))
        {
            try{
                $entry_number = $doc->getElementsByTagName('entry_number');
                $array['entry_number'] = $entry_number->item(0)->nodeValue;
                $enable_log = $doc->getElementsByTagName('enable_log');
                $array['enable_log'] = $enable_log->item(0)->nodeValue;        
                $rerun = $doc->getElementsByTagName('rerun_after_change');
                $array['rerun_after_change'] = $rerun->item(0)->nodeValue;           
                $send_email = $doc->getElementsByTagName('send_email');
                $array['send_email'] = $send_email->item(0)->nodeValue;          
                $show_code = $doc->getElementsByTagName('show_code');
                $array['show_code'] = $show_code->item(0)->nodeValue;         
                $show_entrys = $doc->getElementsByTagName('show_entrys');
                $array['show_entrys'] = $show_entrys->item(0)->nodeValue;         
                $cross = $doc->getElementsByTagName('enable_cross-course_detection');
                $array['enable_cross-course_detection'] = $cross->item(0)->nodeValue;
            }
            catch(Exception $e)
            {//error unable to read xml file
            	return $this->default;
            }
            
    	    return $array;
        }
        else
        {
            echo 'load xml file error';
            return $this->default;
        }
    }
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
           if(!unlink($CFG->dirroot.'/plagiarism/moss/config.xml'))
               echo 'unlink config.xml error';//delete error, throw exception
        if(!$doc->save($CFG->dirroot.'/plagiarism/moss/config.xml'))
            echo 'save xml file error';//save error, throw exception
    }
    public function get_config($tagname)
    {
    	global $CFG;
    	//read xml file if not exist, return default 
    	if(! file_exists($CFG->dirroot.'/plagiarism/moss/config.xml'))
            return $this->default[$tagname];
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
            echo 'xml file exist, but load xml error';
            return $this->default[$tagname];
        }
        //TODO error handle
    }
   

    private $default = array('entry_number' => 2,
                             'enable_log' => 'YES',
                             'rerun_after_change' => 'YES',
                             'send_email' => 'NO',
                             'show_code' => 'NEVER',
                             'show_entrys' => 'ALWAYS',
                             'enable_cross-course_detection' => 'NO');
   
}

class error_log
{
}
/**
 * 
 * Enter description here ...
 * @param unknown_type $eventdata
 */
function moss_event_file_uploaded($eventdata) {
    mtrace("\n***********************>文件上传\n"); 
    $file_handler = new file_operator(); 
    if(!empty($eventdata->files))
        mtrace("高级作业上传\n");
    else
    	mtrace("单文件作业上传\n");
    return $file_handler->save_upload_file_files($eventdata);
}

/**
 * 描述：  事件处理函数，多文件任务上传确认。
 * 参数：  $eventdata
 * 返回：  bool
 */
/**
 * 
 * Enter description here ...
 * @param unknown_type $eventdata
 */
function moss_event_files_done($eventdata) {
    $result = true;
    return $result;
}

/**
 * 
 * Enter description here ...
 * @param unknown_type $eventdata
 */
function moss_event_mod_created($eventdata) {
    mtrace("\n***********************>模块创建\n");
    $result = true;
    return $result;
}

/**
 * 
 * Enter description here ...
 * @param unknown_type $eventdata
 */
function moss_event_mod_updated($eventdata) {
    mtrace("\n***********************>模块更新\n");
    $result = true;
    return $result;    
}

/**
 * 
 * Enter description here ...
 * @param unknown_type $eventdata
 */
function moss_event_mod_deleted($eventdata) {
    mtrace("\n***********************>模块删除\n");  
    $result = true;
    return $result;
}
