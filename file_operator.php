<?php

class file_operator{
	
    /**
     * 描述：  保存$cmid所指定的反抄袭任务的base_file。
     * 参数：  $fileid
     *        $cmid
     * 返回：  bool型
     */
    public function save_base_file($file, $cmid){
        global $DB;
        
        //准备$fs，$context,$tag
        $fs = get_file_storage();
        $context = get_context_instance(CONTEXT_SYSTEM);
       
        //TODO 如果为更新，需要先删除之前的basefile
        
        //保存文件
        $fileinfo = array('contextid' => $context->id, 'component' => 'plagiarism_moss',
                          'filearea' => 'moss', 'itemid' => 0,
                          'filepath' => '/'.$cmid.'/',//直接放在/$cmid/下的原因是为了方便moss cmd的准备 
                          'filename' => $file->get_filename());   
        $new_file_record = $fs->create_file_from_storedfile($fileinfo, $file);
        return true;
    }
    
    /**
     * 描述：  保存学生上传的作业，可以保存单文件作业也可以保存多文件作业，
     *        如果学生为更新作业，则先删除原有的文件记录，然后转存文件。
     * 参数：  $eventdata
     * 返回：  $result bool型
     */
    public function save_upload_file_files($eventdata){
        global $DB;
        $result = true; 
        
        //单文件作业使用多文件作业方法转存
        if(!empty($eventdata->file) && empty($eventdata->files)){
                $eventdata->files[] = $eventdata->file;
        }
        
        //序列化对象,准备$fs，$context,$cmid,$userid
        $temp = serialize($eventdata->files);
        $eventdata->files = unserialize($temp);
        $fs = get_file_storage();
        $context = get_context_instance(CONTEXT_SYSTEM);
        $cmid = $eventdata->cmid;
        $userid = $eventdata->userid;
        
        //错误检查
        if(!$DB->record_exists('course_modules', array('id' => $cmid))){
            mtrace("cm不存在\n");
        	return $result;
        }
        foreach($eventdata->files as $file){
            $fileid = $fs->get_file_by_id($file->get_id());
            if(empty($fileid)){
                mtrace("找不到文件\n");
                return $result;
            }
        }
        
        //获取当前用户之前上传的文件（如果有的话则删除）
        $old_file_records = $fs->get_directory_files($context->id, 'plagiarism_moss', 'moss', 0, 
                                                    '/'.$cmid.'/'.$userid.'/');
        if(empty($old_file_records))
            mtrace("上传文件\n");
        else{
            mtrace("更新文件\n");
            foreach($old_file_records as $record)   
                $record->delete();
        } 
        
        //转存文件
        foreach($eventdata->files as $file){
            if($file->get_filename() === '.')
                continue;
            $fileinfo = array('contextid' => $context->id, 'component' => 'plagiarism_moss',
                              'filearea' => 'moss', 'itemid' => 0,
                              'filepath' => '/'.$cmid."/".$userid."/", 
                              'filename' => $file->get_filename());   
            $new_file_record = $fs->create_file_from_storedfile($fileinfo, $file);
            if(!isset($new_file_record))
                mtrace("转存错误\n");
            else 
                mtrace("转存成功\n");
        }
        return $result;
    }
	/**
     * 描述：  保存学生上传的作业，可以保存单文件作业
     *        如果学生为更新作业，则先删除原有的文件记录，然后转存文件。
     * 参数：  $eventdata
     * 返回：  $result bool型
     */
    public function save_upload_file($eventdata){
        global $DB;
        $result = true; 
        
        //序列化对象,准备$fs，$context,$cmid,$userid
        $temp = serialize($eventdata->files);
        $eventdata->files = unserialize($temp);
        $fs = get_file_storage();
        $context = get_context_instance(CONTEXT_SYSTEM);
        $cmid = $eventdata->cmid;
        $userid = $eventdata->userid;
        
        //错误检查
        if(!$DB->record_exists('course_modules', array('id' => $cmid))){
            mtrace("cm不存在\n");
        	return $result;
        }
        $fileid = $fs->get_file_by_id($eventdata->file->get_id());
        if(empty($fileid)){
            mtrace("找不到文件\n");
            return $result;
        }
        
        //获取当前用户之前上传的文件（如果有的话则删除）
        $old_file_records = $fs->get_directory_files($context->id, 'plagiarism_moss', 'moss', 0, 
                                                    '/'.$cmid.'/'.$userid.'/');
        if(empty($old_file_records))
            mtrace("上传文件\n");
        else{
            mtrace("更新文件\n");
            foreach($old_file_records as $record)   
                $record->delete();
        } 
        
        //转存文件
        $fileinfo = array('contextid' => $context->id, 'component' => 'plagiarism_moss',
                          'filearea' => 'moss', 'itemid' => 0,
                          'filepath' => '/'.$cmid."/".$userid."/", 
                          'filename' => $eventdata->file->get_filename());   
        $new_file_record = $fs->create_file_from_storedfile($fileinfo, $eventdata->file);
        if(!isset($new_file_record))
            mtrace("转存错误\n");
        else 
            mtrace("转存成功\n");
        return $result;
    }
    
    /**
     * 描述：  复制当前cmid的所有学生文件到/temp；
     *        复制与cmid有同一个tag的所有cm的所有学生作业到/temp；
     *        复制当前cmid的所有basefile。
     *        结构：/temp/moss/$cmid/$userid/$filename
     * 参数：  $cmid
     * 返回：  bool型
     */
    public function prepare_files($cmid){
        global $DB;
        global $CFG;
        
        //准备$fs，$context, $cmarray, $temppath
        $fs = get_file_storage();
        $context = get_context_instance(CONTEXT_SYSTEM);
        $record = $DB->get_record('moss_tags',array('cmid' => $cmid));
        $tag = $record->tag;
        $cmarray = $DB->get_records("moss_tags",array('tag' => $tag));
        $temppath = $CFG->dataroot.'/moss';
        
        //检查当前cm有没有学生上传的文件，如果没有返回false;
        $files = $fs->get_directory_files($context->id, 'plagiarism_moss', 'moss', 0, 
                                     '/'.$cmid.'/', true, true, 'filepath');
        $flag = false;
        foreach($files as $file){
        	//有学生目录就认为存在学生上传文件
            if($file->get_filename() == '.'){
                $flag = true;
                break;
            }
        }
        if(!$flag){
            mtrace('cm('.$cmid.')没有学生上传文件记录');
        	return false;
        }
            
        //创建目录$CFG->dataroot./moss/ TODO 检查目录是否已经存在 
        if(!mkdir($temppath)){
            mtrace('创建目录'.$temppath.'失败');
            return false;
        }
        
        //复制与当前cm有相同tag的所有cm的文件到$temppath(不检查文件是否存在)
        foreach($cmarray as $cm){
        	$files = $fs->get_directory_files($context->id, 'plagiarism_moss', 'moss', 0, 
                                     '/'.$cm->cmid.'/', true, true, 'filepath');
            if(empty($files))
                continue;
            //创建目录$CFG->dataroot./moss/$cmid/
            if(!mkdir($temppath.'/'.$cm->cmid.'/'))
                mtrace('创建目录'.$temppath.'/'.$cm->cmid.'/'.'失败');
            //TODO 确保文件树结构有序，即文件夹在文件前面。
            foreach($files as $file){
            	//不是当前cm则basefiles不复制，只有存放在/$cmid/下的basefile的filepath才有可能是'/$cmid/'
        	    /*if(($file->get_filepath() == '/'.$cm->cmid.'/') && ($cm->cmid != $cmid))
        	          continue; 复制也无所谓，所以就复制吧。*/
        	    if($file->get_filename() == '.'){	
        		    if(!mkdir($temppath.$file->get_filepath()))
        	            mtrace('创建目录'.$temppath.$file->get_filepath().'失败');
        	    }
        	    else{
        		    $contents = $file->get_content();
        		    if($fh = fopen($temppath.$file->get_filepath().$file->get_filename(),'w')){
        			    fwrite($fh, $contents);
        		        fclose($fh);
                    }
        	        else 
        	            mtrace('创建文件'.$temppath.$file->get_filepath().$file->get_filename().'失败');
        	    }
            }
        }
        return true;
    }
    
    /**
     * 描述：  删除/temp下的临时文件，在moss返回结果后调用
     * 参数：  $directory /temp目录
     * 返回：  无
     */
    public function delete_temp_directory($directory){
    	//后序遍历文件树，删除所有文件和文件夹
    	if(file_exists($directory)){//确保目录存在才能rmdir
    	    if($dir = opendir($directory)){
    	        while($fname = readdir($dir)){
    	        	if(($fname == '.') || ($fname == '..'))
    	                continue;
    	            //是子目录，递归删除，否则只能是文件，直接删除  
    	            if(is_dir($directory.'/'.$fname))
    	                $this->delete_temp_directory($directory.'/'.$fname);
    	            else 
    	                unlink($directory.'/'.fname);
    	            
    	        }
    	        closedir($dir);
    	        rmdir($directory);
    	    }
    	}
    }
    
    /**
     * 描述：  删除反抄袭插件的所有文件。
     * 参数：  无
     * 返回：  无
     */
    public function delete_moss_directory(){
    	$fs = get_file_storage();
    	$context = get_context_instance(CONTEXT_SYSTEM);
    	$fs -> delete_area_files($context->id, 'plagiarism_moss', 'moss', 0);	
    } 
    
    /**
     * 描述：  在Moodle中创建文件。
     * 参数：  $content
     *        $filename
     *        $path
     * 返回：  $result bool型
     */
    public function create_file($content, $filename, $filepath){
        $result = false;
        return $result;
    }
    
    /**
     * 描述：  解压操作系统文件目录下的zip文件。
     * 参数：  $filename
     *        $file_path
     * 返回：  $result bool型
     */
    public function unpack_zip($filename, $file_path){
        $result = false;
        return $result;
    }

    /**
     * 描述：  解压操作系统文件目录下的rar文件。
     * 参数：  $filename
     *        $file_path
     * 返回：  $result bool型
     */
    public function unpack_rar($file_path){
        $result = false;
        return $result;
    }
  
}
