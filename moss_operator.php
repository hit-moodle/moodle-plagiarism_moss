<?php
//include file operator class
require_once($CFG->dirroot.'/plagiarism/moss/file_operator.php');

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

        //delete previous results
        $this->delete_result($cmid);
    	
        //prepare file directory (move student files to a moss-readable path)
        $file_op = new file_operator();
        if(!$file_op->prepare_files($cmid))
        {
            mtrace('准备temp文件错误，cmid = '.$cmid);
            return false;
        }
        
        //prepare moss's shell command
        $cmdarray = $this->prepare_cmd($cmid);
        if(empty($cmdarray))
        {
            $file_op->delete_temp_directory($CFG->dataroot.'/moss/');
            mtrace('准备cmd错误, cmid = '.$cmid);
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
            $proc = proc_open($command, $descriptorspec, $pipes);
            if (!is_resource($proc)) 
            {
                $file_op->delete_temp_directory($CFG->dataroot.'/moss/');
                mtrace("proc_open 错误"); 
                return false;
            }
            //get standard output and standard error output
            $out = stream_get_contents($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            mtrace($out);
            mtrace($err);
            $count = proc_close($proc);
            if($count)
            {
                $file_op->delete_temp_directory($CFG->dataroot.'/moss/');
                mtrace("proc_close 错误");
                return false;
            } 
            else
            {
        	    $url_p = '/http:\/\/moss\.stanford\.edu\/results\/\d+/';
        	    if(preg_match($url_p, $out, $match))
                {
        	        if(!$this->save_result($match[0], $cmid, $filepattern))
        	        {
                        $file_op->delete_temp_directory($CFG->dataroot.'/moss/');
        	            return false;
        	        }
        	    }
        	    else
        	    {
        	        $file_op->delete_temp_directory($CFG->dataroot.'/moss/');
        	        mtrace("找不到moss结果链接");
        	        return false;
        	    }
           }
        } 
        //TODO 修改moss_settings measuredtime
        return true;
    }

    /**
     * 描述：  准备moss命令。
     * 参数：  $cmid
     * 返回：  $cmdarray 命令字符串组($filepattern => $cmd),错误返回空array
     */
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
            mtrace('找不到moss设置，cmid = '.$cmid);
            return $cmdarray;
        }
           
        //prepare $cmd and save in $cmdarray
        foreach($settings as $setting)
        {
            $cmd = $CFG->dirroot.'/plagiarism/moss/moss/moss_bash';
            $cmd .= ' -l '.$setting->language;
            $cmd .= ' -m '.$setting->sensitivity;
            if(isset($setting->basefilename))
                $cmd .= ' -b '.$CFG->dataroot.'/moss/'.$cmid.'/'.$setting->basefilename;
            //basefile在moodle中存于/moss/$cmid/下的原因，是因为下面可以用/moss/*/*/来表示所有学生的文件夹
            $cmd .= ' -d '.$CFG->dataroot.'/moss/*/*/'.$setting->filepattern;
            $cmdarray[$setting->filepattern] = $cmd;
        }
        return $cmdarray;        
    }

    /**
     * 描述：  保存moss返回结果。
     *        来源： plagiarism plugin 1.0
     * 参数：  $moss_result_url
     * 返回：  bool型
     */
    /**
     * 
     * Enter description here ...
     * @param unknown_type $moss_result_url
     * @param unknown_type $cmid
     * @param unknown_type $filepattern
     */
    private function save_result($moss_result_url, $cmid, $filepattern)
    {
        $fp = fopen($moss_result_url, 'r');
        if(!$fp)
        {
            mtrace('打开url = '.$moss_result_url.' 错误');
            return false;
        }

        //取结果，保存结果
        $rank = 1;
        //TODO /var/moodledata应该改用$CFG.dirroot
        $re_url = '/(http:\/\/moss\.stanford\.edu\/results\/\d+\/match\d+\.html)">\/var\/moodledata\/moss\/(\d+)\/(\d+)\/ \((\d+)%\)/';
        
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
                    	$record -> cmid = $cmid;
                    	$record -> filepattern = $filepattern();
                    	$record -> confirmed = 0;
                        
                    	$record -> rank = $rank++;
                    	$record -> user1id = $matches1[3];
                    	$record -> user2id = $matches2[3];
                    	$record -> user1percent = $matches1[4];
                    	$record -> user2percent = $matches2[4];
                    	$record -> linecount = $matches3[1];
                    	$record -> link = $matches1[1];//===$matches2[1];
                    	
                    	//记录不属于本cm的学生id
                    	if($matches1[2] != $cmid)
                    	    $record -> notuserid = $matches1[3];
                    	else
                    	   if($matches2[2] != $cmid)
                    	       $record -> notuserid = $matches2[3];
                    	
                    	$DB->insert_record('moss_results', $record);
                    }
                }
            }
        }
        
        fclose($fp);
        return true;
    }
	
    /**
     * 描述：  删除moss的运行结果。
     * 参数：  $cmid
     * 返回：  无
     */
    private function delete_result($cmid)
    {
        global $DB;
        $DB->delete_records('moss_results', array('cmid' => $cmid));
    }

}
