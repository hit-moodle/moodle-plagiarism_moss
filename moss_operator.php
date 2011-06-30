<?php
//include file operator class
if (!defined('MOODLE_INTERNAL')) 
    die('Direct access to this script is forbidden.');
global $CFG;   
require_once($CFG->dirroot.'/plagiarism/moss/file_operator.php');
require_once($CFG->dirroot.'/plagiarism/moss/lib.php');

/**
 * 
 * Enter description here ...
 * @author ycc
 *
 */
class moss_operator
{ 
	/**
	 * this function will connect moss server and save anti-plagiarism results
	 * 
	 * @param unknown_type $cmid
	 */
    public function connect_moss($cmid)
    {
        global $CFG;
        global $DB;
        //delete previous results
        $this->delete_result($cmid);
    	
        //prepare file directory (move student files to a moss-readable path)
        $file_op = new file_operator();
        if(!$file_op->move_files_to_temp($cmid))
        {
            $this->remove_all($CFG->dataroot.'/moss'.$cmid.'/');
            return false;
        }

        //prepare moss's shell command
        $cmdarray = $this->prepare_cmd($cmid);
        if(empty($cmdarray))
        {
        	//TODO prepare_cmid_error
            $this->remove_all($CFG->dataroot.'/moss'.$cmid.'/');
            return false;
        }

        //connect moss server and save results
        foreach($cmdarray as $filepattern => $cmd)
        {
            mtrace('moss命令： '.$cmd);
            $descriptorspec = array(0 => array('pipe', 'r'),  // stdin 
                                    1 => array('pipe', 'w'),  // stdout
                                    2 => array('pipe', 'w') // stderr
                                   );
            $proc = proc_open($cmd, $descriptorspec, $pipes);
            if (!is_resource($proc)) 
            {
                $this->remove_all($CFG->dataroot.'/moss'.$cmid.'/');
                $this->trigger_error('Function proc_open() return error in file "moss_operator.php"'.
                                     ' call by function "connect_moss() cmid = "'.$cmid, 
                                     'To solve this error you need a programmer',
                                     21);
                return false;
            }
 
            //get standard output and standard error output
            $out = stream_get_contents($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            $count = proc_close($proc);
            if($count)
            {
                $this->remove_all($CFG->dataroot.'/moss'.$cmid.'/');
                $this->trigger_error('Function proc_close() return error in file "moss_operator.php"'.
                                     ' call by function "connect_moss() cmid = "'.$cmid, 
                                     'Check network connection if happen again then you need a programmer',
                                     22,
                                     'Unknown');
                return false;
            } 
            else
            {
        	    $url_p = '/http:\/\/moss\.stanford\.edu\/results\/\d+/';
        	    if(preg_match($url_p, $out, $match))
                {
        	        if(!$this->save_result($match[0], $cmid, $filepattern))
        	        {
                        $this->remove_all($CFG->dataroot.'/moss'.$cmid.'/');
        	            return false;
        	        }
        	    }
        	    else
        	    {
        	        $this->remove_all($CFG->dataroot.'/moss'.$cmid.'/');
        	        $this->trigger_error('Can\'t find moss result link in file "moss_operator.php"'.
                                         ' call by function "connect_moss() cmid = "'.$cmid.
                                         ' shell output = '.$out, 
                                         'To solve this error you need a programmer',
                                         23);
        	        return false;
        	    }
            }

        } 
        $this->remove_all($CFG->dataroot.'/moss'.$cmid.'/');
        $records = $DB->get_records('moss_settings', array('cmid' => $cmid));
        foreach($records as $record)
        {
        	$record->measuredtime = time();
            if (!$DB->update_record('moss_settings', $record)) 
                error('errorupdating in "moss_operator.php" function "connect_moss()"');
        }
        return true;
    }
 
    /**
     * 
     * Enter description here ...
     * @param unknown_type $path
     */
    private function remove_all($path)
    {
        $file_op = new file_operator();
        if(! $file_op->remove_temp_files($path))
            $file_op->trigger_error('Error when removing temp files in '.$path, 
                                     array('path'=>$path),
                                     12);
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $cmid
     */
    private function prepare_cmd($cmid)
    {
        global $DB;
        global $CFG;
        $cmdarray = array();
        
        //get moss settings
        $settings = $DB->get_records('moss_settings', array('cmid'=>$cmid));
        if(!isset($settings))
        {
            return $cmdarray;
        }

        //prepare $cmd and save in $cmdarray
        foreach($settings as $setting)
        {
                $cmd = $CFG->dirroot.'/plagiarism/moss/moss/moss_bash';
                $cmd .= ' -l '.$setting->language;
                $cmd .= ' -m '.$setting->sensitivity;
                if(isset($setting->basefilename))
                    $cmd .= ' -b '.$CFG->dataroot.'/moss'.$cmid.'/'.$cmid.'/'.$setting->basefilename;
                //basefile在moodle中存于/moss/$cmid/下的原因，是因为下面可以用/moss/*/*/来表示所有学生的文件夹
                $cmd .= ' -d '.$CFG->dataroot.'/moss'.$cmid.'/*/*/'.$setting->filepattern;
                $cmdarray[$setting->filepattern] = $cmd;
        }
        return $cmdarray;
    }

    /**
     * 
     * Enter description here ...
     * @param unknown_type $moss_result_url
     * @param unknown_type $cmid
     * @param unknown_type $filepattern
     */
    private function save_result($moss_result_url, $cmid, $filepattern)
    {
    	global $DB;
    	echo $moss_result_url;
        $fp = fopen($moss_result_url, 'r');
        if(!$fp)
        {
            $this->trigger_error('Error when open moss result link, link='.$moss_result_url,
                                 'Make sure server have internet access',25,$moss_result_url);
            return false;
        }
        //取结果，保存结果
        $rank = 1;
        //TODO /var/moodledata应该改用$CFG.dirroot
        $re_url = '/(http:\/\/moss\.stanford\.edu\/results\/\d+\/match\d+\.html)">\/var\/moodledata\/moss\d+\/(\d+)\/(\d+)\/ \((\d+)%\)/';
        $student1;
        $student2;
        while(!feof($fp))
        {
            $line = fgets($fp);
            if(preg_match($re_url, $line, $matches1))//学生一
            {
                $line = fgets($fp);
                if(preg_match($re_url, $line, $matches2))//学生二
                {
                    $line = fgets($fp);
                    if(preg_match('/(\d+)/', $line, $matches3))//行数     
                    { 	
                        //两个学生都不属于本cm 
                    	if(($matches1[2] != $cmid) && ($matches2[2] != $cmid))
                    		continue;
                        $record = new object();
                        if(($matches1[2] != $cmid) || ($matches2[2] != $cmid))
                        {
                        	$record->iscross = 1;
                        	if($matches1[2] != $cmid)
                        	{
                        		$student1 = $matches1;
                        		$student2 = $matches2;
                        	}
                        	else 
                        	{
                        		$student1 = $matches2;
                        		$student2 = $matches1;
                        	}
                        }
                        else 
                        {
                        	$student1 = $matches1;
                            $student2 = $matches2;
                            $record->iscross = 0;
                        }
                    	$record -> cmid = $cmid;
                    	$record -> filepattern = $filepattern;
                    	$record -> confirmed = 0;
                        
                    	$record -> rank = $rank++;
                    	$record -> user1id = $student1[3];
                    	$record -> user2id = $student2[3];
                    	$record -> user1percent = $student1[4];
                    	$record -> user2percent = $student2[4];
                    	$record -> linecount = $matches3[1];
                    	$record -> link = $student1[1];//===$student2[1];

                    	$DB->insert_record('moss_results', $record);
                    }  
                    else
                    { 
                        $this->trigger_error('Parse moss result page error. result link = '.$moss_result_url, 'To solve this you need a programmer',24);
                        return false;
                    } 
                }
                else
                { 
                    $this->trigger_error('Parse moss result page error. result link = '.$moss_result_url, 'To solve this you need a programmer',24);
                    return false;
                }
            }
        }
        
        fclose($fp);
        return true;
    }
	
    /**
     * 
     * Enter description here ...
     * @param unknown_type $cmid
     */
    public function delete_result($cmid)
    {
        global $DB;
        $DB->delete_records('moss_results', array('cmid' => $cmid));
        $file_op = new file_operator();
        return $file_op->remove_results_files_by_cm($cmid);
    }

    /**
     * 
     * Enter description here ...
     * @param unknown_type $description
     * @param unknown_type $type
     */
    private function trigger_error($description, $errsolution = NULL, $type, $argument)
    {
        global $CFG;
        global $DB;
        $err = new object();
        $err->errdate = time();
        $err->errtype = $type;
        $err->errdescription = $description;
        $err->errstatus = 1;//unsolved
        $err->errsolution = $errsolution;
        if($type == 25)
        {
        	$err->testable = 1;
            $err->errargument = $argument;
        }
        else
        {
        	$err->testable = 0; 
            $err->errargument = 'no argument';
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
        if($type == 25)
        {
            $fp = fopen($argument, 'r');
            if(!$fp)
                return false;
            else 
                return true;
        }
        else
            return true;
        
    }
}
