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

require_once($CFG->dirroot.'/plagiarism/moss/moss.php');
/**
 *
 * Enter description here ...
 * @author ycc
 *
 */
class file_operator
{
	/**
	 *
	 * Enter description here ...
	 * @param unknown_type $file
	 * @param unknown_type $cmid
	 */
    public function save_base_file($file, $cmid)
    {
        global $DB;
        
        //准备$fs，$context,$tag
        $fs = get_file_storage();
        $context = get_context_instance(CONTEXT_SYSTEM);
       
        //TODO 如果为更新，需要先删除之前的basefile
        
        //保存文件
        $fileinfo = array('contextid' => $context->id, 
                          'component' => 'plagiarism_moss',
                          'filearea'  => 'moss', 
                          'itemid'    => 0,
                          'filepath'  => '/'.$cmid.'/',//直接放在/$cmid/下的原因是为了方便moss cmd的准备 
                          'filename'  => $file->get_filename());   
        $new_file_record = $fs->create_file_from_storedfile($fileinfo, $file);
        if(!isset($new_file_record))
        {
            mtrace('save basefile error');
        	return false;
        }
        return true;
    }
    

    /**
     * 
     * Enter description here ...
     * @param unknown_type $cmid
     */
    public function move_files_to_temp($cmid, $trigger_err = true)
    {
        global $DB;
        global $CFG;
        
        //准备$fs，$context, $cmarray, $temppath
        $fs = get_file_storage();
        $context = get_context_instance(CONTEXT_SYSTEM);
        
        $cnf_xml = new config_xml();
        if($cnf_xml->get_config('enable_cross-course_detection') == 'YES')
        {
            $record = $DB->get_record('moss_tags',array('cmid' => $cmid));
            if($record->tag != NULL)
            {
            	$tag = $record->tag;
                $records = $DB->get_records("moss_tags",array('tag' => $tag));
                foreach($records as $re)
                    $cmarray[$re->cmid] = $re->cmid;
            }
            else 
                $cmarray = array($cmid);
        }
        else 
            $cmarray = array($cmid);
            
        $temppath = $CFG->dataroot.'/moss'.$cmid;
        $this->remove_temp_files($temppath);
        
        //检查当前cm有没有学生上传的文件，如果没有返回false;
        $files = $fs->get_directory_files($context->id, 
                                          'plagiarism_moss', 
                                          'moss', 
                                           0, 
                                          '/'.$cmid.'/', 
                                           true, 
                                           true, 
                                          'filepath');
        $flag = false;
        foreach($files as $file)
        {	//有学生目录就认为存学生有上传文件
            if($file->get_filename() == '.')
            {
                $flag = true;
                break;
            }
        }
        if(!$flag)
        	return false;
        
        //创建目录$CFG->dataroot./moss/ TODO 检查目录是否已经存在 
        if(!mkdir($temppath))
        {
        	if($trigger_err)
        	    $this->trigger_error('Error when creating directory at file path '.$temppath, array('$cmid'=>$cmid), 11);
            return false;
        }
        
        //复制与当前cm有相同tag的所有cm的文件到$temppath(不检查文件是否存在)
        foreach($cmarray as $cm)
        {
        	print_object($cm);
        	$files = $fs->get_directory_files($context->id, 
        	                                  'plagiarism_moss', 
        	                                  'moss', 
        	                                   0, 
                                              '/'.$cm.'/', 
        	                                   true, 
        	                                   true, 
        	                                  'filepath');
            if(empty($files))
                continue;
            
            //创建目录$CFG->dataroot./moss/$cmid/
            if(!mkdir($temppath.'/'.$cm.'/'))
            	return false;
        
            //TODO 确保文件树结构有序，即文件夹在文件前面。
            foreach($files as $file)
            {
            	//不是当前cm则basefiles不复制，只有存放在/$cmid/下的basefile的filepath才有可能是'/$cmid/'
        	    /*if(($file->get_filepath() == '/'.$cm->cmid.'/') && ($cm->cmid != $cmid))
        	          continue; 复制也无所谓，所以就复制吧。*/
        	    if($file->get_filename() == '.')
        	    {	
        		    if(!mkdir($temppath.$file->get_filepath()))
        		    {
        	            if($trigger_err)
        	                $this->trigger_error('Error when creating directory at file path '.$temppath, array('$cmid'=>$cmid), 11);
        	            return false;
        		    }
        	    }
        	    else
        	    {
        		    $contents = $file->get_content();
        		    if($fh = fopen($temppath.$file->get_filepath().$file->get_filename(),'w'))
        		    {
        			    fwrite($fh, $contents);
        		        fclose($fh);
                    }
        	        else 
        	        {
        	            if($trigger_err)
        	                $this->trigger_error('Error when creating file at file path '.$temppath, array('$cmid'=>$cmid), 11);
        	            return false;
        	        }
        	    }
            }
        }
        return true;
    }
    
    /**
     * 
     * error trigger by caller
     * @param unknown_type $directory
     */
    public function remove_temp_files($directory)
    {
    	$re = false;
    	//后序遍历文件树，删除所有文件和文件夹
    	if(file_exists($directory))
    	{//确保目录存在才能rmdir
    	    if($dir = opendir($directory))
    	    {
    	        while($fname = readdir($dir))
    	        {
    	        	if(($fname == '.') || ($fname == '..'))
    	                continue;
    	        	else 
    	        	{//是子目录，递归删除，否则只能是文件，直接删除  
    	                if(is_dir($directory.'/'.$fname))
    	                    $re = $this->remove_temp_files($directory.'/'.$fname);
    	                else 
                            $re = unlink($directory.'/'.$fname);
                        if($re == false)
                            return false;
    	        	}
    	        }
    	        closedir($dir);
    	        return rmdir($directory);
    	    }
    	    return false;
    	}
    	return true;
    }
  
    /**
     * 
     * Enter description here ...
     */
    public function delete_moss_directory()
    {
    	$fs = get_file_storage();
    	$context = get_context_instance(CONTEXT_SYSTEM);
    	$re = $fs -> delete_area_files($context->id, 
    	                               'plagiarism_moss', 
    	                               'moss', 
    	                                0);	
    	return $re;
    } 
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $cmid
     * @param unknown_type $entryid
     */
    public function results_files_exist($cmid, $entryid)
    {
        $fs = get_file_storage();
        $context = get_context_instance(CONTEXT_SYSTEM);

        $files = $fs->get_directory_files($context->id, 
                                          'plagiarism_moss', 
                                          'moss_results', 
                                           0, 
                                          '/'.$cmid.'/'.$entryid.'/', 
                                           true, 
                                           true, 
                                          'filepath'); 
        if(!empty($files))
            return true;
        else
            return false;
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $cmid
     * @param unknown_type $entryid
     */
    public function remove_results_files_by_id($cmid, $entryid)
    {
        $fs = get_file_storage();
        $context = get_context_instance(CONTEXT_SYSTEM);

        $files = $fs->get_directory_files($context->id, 
                                          'plagiarism_moss', 
                                          'moss_results', 
                                           0, 
                                          '/'.$cmid.'/'.$entryid.'/', 
                                           true, 
                                           true, 
                                          'filepath'); 
        if(empty($files))
            return true;
        else 
            foreach($files as $file)
                try{
            	       $file->delete();
                }
                catch(Exception $e)
                {
                	return false;
                }
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $cmid
     */
    public function remove_results_files_by_cm($cmid)
    {
        $fs = get_file_storage();
        $context = get_context_instance(CONTEXT_SYSTEM);

        $files = $fs->get_directory_files($context->id, 
                                          'plagiarism_moss', 
                                          'moss_results', 
                                           0, 
                                          '/'.$cmid.'/', 
                                           true, 
                                           true, 
                                          'filepath'); 
        if(empty($files))
            return true;
        else 
            foreach($files as $file)
                try{
            	       $file->delete();
                }
                catch(Exception $e)
                {
                	return false;
                }
    }

    /**
     * 
     * Enter description here ...
     */
    public function remove_results_files_all()
    {
        $fs = get_file_storage();
    	$context = get_context_instance(CONTEXT_SYSTEM);
    	$re = $fs -> delete_area_files($context->id, 
    	                              'plagiarism_moss', 
    	                              'moss_results', 
    	                               0);
        return $re;	
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $cmid
     * @param unknown_type $entryid
     */
    public function save_results_files($cmid, $entryid, $trigger_err=true)
    {
    	//检查是否已经下载，如果是则删除
    	$re = $this->results_files_exist($cmid, $entryid);
    	if($re == true)
    	    $this->remove_results_files_by_id($cmid, $entryid);
        
    	global $DB;
    	global $CFG;
        $fs = get_file_storage();
        $context = get_context_instance(CONTEXT_SYSTEM);
        
    	$record = $DB->get_record('moss_results', array('id'=>$entryid));
    	//match page url and contents
    	$url = $record->link;
    	//I wanna get the contents of the page above by use file_get_contents($url),but failed, don't know why
        $match_page_contents = '<HTML>
                                    <FRAMESET ROWS="150,*">
                                        <FRAMESET COLS="1000,*">
                                            <FRAME SRC="view_code.php?cmid='.$cmid.'&entryid='.$entryid.'&page=top" NAME="top" FRAMEBORDER=0>
                                        </FRAMESET>
                                        <FRAMESET COLS="50%,50%">
                                            <FRAME SRC="view_code.php?cmid='.$cmid.'&entryid='.$entryid.'&page=0" NAME="0">
                                            <FRAME SRC="view_code.php?cmid='.$cmid.'&entryid='.$entryid.'&page=1" NAME="1">
                                        </FRAMESET>
                                    </FRAMESET>
                                </HTML>';
        //save match page file
        $fileinfo = array('contextid' => $context->id, 
                          'component' => 'plagiarism_moss',
                          'filearea'  => 'moss_results', 
                          'itemid'    => 0,
                          'filepath'  => '/'.$cmid."/".$entryid."/", 
                          'filename'  => 'match.html');   
        $match_page = $fs->create_file_from_string($fileinfo, $match_page_contents);
        if(!isset($match_page))
        {
        	if($trigger_err)
        	    $this->trigger_error('Error when download source code detail from moss server',
        	                          array('cmid'=>$cmid, 'entryid'=>$entryid), 21);
            return false;
        }
        //$url = http://moss.stanford.edu/results/715072715/match0.html
        $top_url = str_replace(".html","-top.html",$url);
        $code0_url = str_replace(".html","-0.html",$url);
        $code1_url = str_replace(".html","-1.html",$url);
        
        $pattern = '/match(\d+)/';
        if(preg_match($pattern, $url, $short_url))
        {
             $code0_url_short = $short_url[0]."-0.html";
             $code1_url_short = $short_url[0]."-1.html";
        }
        else 
        {
        	if($trigger_err)
        	    $this->trigger_error('Error when download source code detail from moss server',
        	                          array('cmid'=>$cmid, 'entryid'=>$entryid), 21);
            return false;
        }
        //get top page's contents modify it and save it
        $code0_local_url = $CFG->wwwroot."/plagiarism/moss/result_pages/view_code.php?cmid=".$cmid."&entryid=".$entryid."&page=0";
        $code1_local_url = $CFG->wwwroot."/plagiarism/moss/result_pages/view_code.php?cmid=".$cmid."&entryid=".$entryid."&page=1";
        
        $top_page_contents = file_get_contents($top_url);
        $top_page_contents = str_replace($code0_url,
                                         $code0_local_url,
                                         $top_page_contents);  
                                         
        $top_page_contents = str_replace($code1_url,
                                         $code1_local_url,
                                         $top_page_contents);

        $fileinfo = array('contextid' => $context->id, 
                          'component' => 'plagiarism_moss',
                          'filearea'  => 'moss_results', 
                          'itemid'    => 0,
                          'filepath'  => '/'.$cmid."/".$entryid."/", 
                          'filename'  => 'top.html');   
        $top_page = $fs->create_file_from_string($fileinfo, $top_page_contents);
        if(!isset($top_page))
        {
        	if($trigger_err)
        	    $this->trigger_error('Error when download source code detail from moss server',
        	                          array('cmid'=>$cmid, 'entryid'=>$entryid), 21);
            return false;
        }
        //get code 0 page's contents modify it and save it
        $code0_page_contents = file_get_contents($code0_url);
        $code0_page_contents = str_replace($code1_url_short,
                                           $code1_local_url,
                                           $code0_page_contents);
        $fileinfo = array('contextid' => $context->id, 
                          'component' => 'plagiarism_moss',
                          'filearea'  => 'moss_results', 
                          'itemid'    => 0,
                          'filepath'  => '/'.$cmid."/".$entryid."/", 
                          'filename'  => '0.html');   
        $code0_page = $fs->create_file_from_string($fileinfo, $code0_page_contents);
        if(!isset($code0_page))
        {
        	if($trigger_err)
        	    $this->trigger_error('Error when download source code detail from moss server',
        	                          array('cmid'=>$cmid, 'entryid'=>$entryid), 21);
            return false;
        }
        //get code 1 page's contents modify it and save it        
        $code1_page_contents = file_get_contents($code1_url);
        $code1_page_contents = str_replace($code0_url_short, 
                                           $code0_local_url, 
                                           $code1_page_contents);
        $fileinfo = array('contextid' => $context->id, 
                          'component' => 'plagiarism_moss',
                          'filearea'  => 'moss_results',  
                          'itemid'    => 0,
                          'filepath'  => '/'.$cmid."/".$entryid."/", 
                          'filename'  => '1.html');   
        $code1_page = $fs->create_file_from_string($fileinfo, $code1_page_contents);
        if(!isset($code1_page))
        {
        	if($trigger_err)
        	    $this->trigger_error('Error when download source code detail from moss server',
        	                          array('cmid'=>$cmid, 'entryid'=>$entryid), 21);
            return false;
        }
        //update database
        $record->link = "downloaded";
        if(!$DB->update_record('moss_results', $record)) 
            return false;
        else 
            return true;
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $cmid
     * @param unknown_type $entryid
     * @param unknown_type $page
     */
    public function get_results_files_contents($cmid, $entryid, $page)
    {
    	$fs = get_file_storage();
        $context = get_context_instance(CONTEXT_SYSTEM);
        $file = $fs->get_file($context->id, 
                              'plagiarism_moss', 
                              'moss_results', 
                               0, 
                              '/'.$cmid.'/'.$entryid.'/', 
                               $page.'.html'); 
        if($file == false)
            return "";   
        
        $contents = $file->get_content();
        return $contents;

    }

    /**
     * 
     * Enter description here ...
     * @param unknown_type $filename
     * @param unknown_type $file_path
     */
    public function unpack_file($filename, $file_path)
    {
    }
  
    /**
     * 
     * Enter description here ...
     * @param unknown_type $description
     * @param unknown_type $type
     */
    private function trigger_error($description, $arguments = NULL, $type)
    {
        global $CFG;
        global $DB;
        $err = new object();
        $err->errdate = time();
        $err->errtype = $type;
        $err->errdescription = $description;
        $err->errstatus = 1;//unsolved
        switch ($type)
        {
        	case 11:
        		$err->errsolution = 'Check directory permission.';
        		$err->testable = 1;
        	    break;//move files to temp error
        	case 12:
        		$err->errsolution = 'Check directory permission.';
        		$err->testable = 1;
        		break;//remove temp files error
        	case 21:
        		$err->errsolution = 'You can press "Test" button to see if it\'s solved, contact a programmer if unsolved.';
        		$err->testable = 1;
        		break;//download code detail error
        	default: 
        		$err->testable = 0;
        		$err->errsolution = 'Unknown.';
        		break;
        }
        if($arguments == NULL)
            $err->errargument = 'no argument';
        else 
        {
        	$str = "";
            foreach($arguments as $name => $value)
            {
            	if($str == "")
            	    $str = $name."%".$value;
            	else 
            	    $str .= "%".$name."%".$value;
            }
            $err->errargument = $str;
        }
        $DB->insert_record('moss_plugin_errors', $err); 
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $type
     * @param unknown_type $arrguments
     */
    public function error_test($type, $argument)
    {
    	global $CFG;
        $arr = split("%",$arrguments);
        $arguments = array();
        if(count($arr)%2 != 0)
            return true;
        for($i =0; $i < count($arr); $i++)
        {
            $arguments[$arr[$i]] = $arr[$i+1];
            $i += 1;
        }
        switch ($type)
        {
        	case 11://move file to temp error
        		if($this->move_files_to_temp($arguments['cmid'], false))
        		{
        			$this->remove_temp_files($CFG->dataroot.'/moss'.$arguments['cmid']);
        			return true;
        		}
        		else 
        		    return false;
        		break;
        	case 12://remove file from temp error
        		if($this->remove_temp_files($argument['path']))
        		    return true;
        		else 
        		    return false;
        		break;
        	case 21://download code detail error
        		if($this->save_results_files($argument['cmid'], $argument['entryid'], false))
        		    return true;
        		else
        		    return false;
        		break;
        	default :
        		return true;
        }
        
    }
}

/**
 * Whether moss is enabled
 *
 * @param int cmid
 * @return bool
 */
function moss_enabled($cmid = 0) {
    global $DB;

    if (!get_config('plagiarism', 'moss_use')) {
        return false;
    } else if ($cmid == 0) {
        return true;
    } else {
        return $DB->get_field('moss', 'enabled', array('cmid' => $cmid));
    }
}

/**
 *
 * Enter description here ...
 * @param unknown_type $eventdata
 */
function moss_save_files($eventdata) {
    global $DB;
    $result = true;

    if (!moss_enabled($eventdata->cmid)) {
        return $result;
    }

    $context = get_context_instance(CONTEXT_SYSTEM);
    $cmid = $eventdata->cmid;
    $userid = $eventdata->userid;

    // check if the module associated with this event still exists
    if (!$DB->record_exists('course_modules', array('id' => $cmid))) {
        return $result;
    }

    if (!empty($eventdata->file) && empty($eventdata->files)) { //single assignment type passes a single file
        $eventdata->files[] = $eventdata->file;
    }

    $fs = get_file_storage();

    // remove all old files
    $old_files = $fs->get_directory_files($context->id, 'plagiarism_moss', 'files', $cmid, "/$userid/", true, true);
    foreach($old_files as $oldfile) {
        $oldfile->delete();
    }

    // store submitted files
    foreach($eventdata->files as $file) {
        if ($file->get_filename() ==='.') {
            continue;
        }
        //hacky way to check file still exists
        $fileid = $fs->get_file_by_id($file->get_id());
        if (empty($fileid)) {
            mtrace("nofilefound!");
            continue;
        }

        $fileinfo = array(
            'contextid' => $context->id,
            'component' => 'plagiarism_moss',
            'filearea'  => 'files',
            'itemid'    => $cmid,
            'filepath'  => '/'.$userid.$file->get_filepath(),
            'filename'  => $file->get_filename());
        $fs->create_file_from_storedfile($fileinfo, $file);

        mtrace('saved file'.$file->get_filepath().$file->get_filename()." from cm $cmid and user $userid");
    }

    return $result;
}

