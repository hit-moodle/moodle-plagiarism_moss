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

//防止直接访问
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    
}

global $CFG;//获得全局配置类
require_once($CFG->dirroot.'/plagiarism/lib.php');
require_once($CFG->dirroot.'/plagiarism/moss/moss_settings.php');//对单个反抄袭任务的moss配置函数类
require_once($CFG->dirroot.'/plagiarism/moss/moss_operator.php');//启动moss函数类
require_once($CFG->dirroot.'/plagiarism/moss/file_operator.php');//文件操作处理函数类

/**
 * 类描述：  plagiarism_plugin_moss继承了plagiarism_plugin这个Moodle所提供的反抄袭插件父类。
 * 类使用：  为Moodle所调用，是插件最重要的一个类。
 * 
 */
class plagiarism_plugin_moss extends plagiarism_plugin {

    /**
     * 描述：  钩子函数，显示给用户的信息，告诉用户当它上交文件后Moodle会对文件做什么操作。
     * 参数：  $cmid - 课程模块ID
     * 返回：  无
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
     * 描述：  钩子函数，保存对单个反抄袭任务的moss配置。
     * 参数：  $data - 配置界面返回的配置数据
     * 返回：  无
     */
    public function save_form_elements($data) {
    	$setting = new moss_settings();
        $setting->save_settings($data);  
        //print_object($data);
        //die;
    }
    
    /**
     * 描述：  钩子函数，显示对单个反抄袭任务的moss配置。
     * 参数：  $mform - Moodle quickform 对象
     *        $context - 当前内容
     * 返回：  无
     */
    public function get_form_elements_module($mform, $context) {
    	$setting = new moss_settings();
        $setting->show_settings_form($mform, $context);  
         
    }
    
    /**
     * 描述：  钩子函数，在上交的作业后面显示链接，这条链接指向反抄袭结果页面。
     * 参数：  $linkarray - 包含了插件生成链接的所有相关数据
     * 返回：  $link - 链接
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
     * 描述：  钩子函数，在grading/report页面显示，显示所有反抄袭结果。
     * 参数：  $course - 课程对象
     *        $cm - 课程模块对象
     * 返回：  无
     */
    public function update_status($course, $cm) { 
    	//called at top of submissions/grading pages - allows printing of admin style links or updating status
        //echo '<div class="allcoursegrades"><a href="' . $CFG->wwwroot . '/grade/report/grader/index.php?id=' . $course->id . '">'
        //        . get_string('seeallcoursegrades', 'grades') . '</a></div>';
        
        echo '<a href="http://localhost/moodle/plagiarism/moss/test/test.php">anti-plagiarism verify page</a>';
       
    }
    
    /**
     * 描述：  钩子函数，由admin/cron.php定时调用，用以实现moss定时启动。 
     * 参数：  无
     * 返回：  无
     */
    public function cron() {
        mtrace("\n***********************>moss定时器启动\n");
        //global $DB;
        $moss_op = new moss_operator();
        $moss_op -> connect_moss(4);
        
        
        //读出所有measuredTime == 0 的cm
        //读出这些cm的duetime...
        //$file_handler = new file_operator();
        //$file_handler->delete_moss_files();
        //$file_handler->prepare_files(7);
    }
}

/**
 * 描述：  事件处理函数，处理学生上传单个文件，转存文件。
 * 参数：  $eventdata
 * 返回：  bool
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
function moss_event_files_done($eventdata) {
    $result = true;
    return $result;
}

/**
 * 描述：  事件处理函数，处理反抄袭任务被创建事件。
 * 参数：  $eventdata
 * 返回：  bool
 */
function moss_event_mod_created($eventdata) {
    mtrace("\n***********************>模块创建\n");
    $result = true;
    return $result;
}

/**
 * 描述：  事件处理函数，处理反抄袭任务被更新事件。
 * 参数：  $eventdata
 * 返回：  bool
 */
function moss_event_mod_updated($eventdata) {
    mtrace("\n***********************>模块更新\n");
    $result = true;
    return $result;    
}

/**
 * 描述：  事件处理函数，处理反抄袭任务被删除事件。
 * 参数：  $eventdata
 * 返回：  bool
 */
function moss_event_mod_deleted($eventdata) {
    mtrace("\n***********************>模块删除\n");  
    $result = true;
    return $result;
}
