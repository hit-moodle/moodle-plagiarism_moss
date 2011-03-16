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

/**
 * lib.php - Contains Plagiarism plugin specific functions called by Modules.
 *
 * @since 2.0
 * @package    plagiarism_moss
 * @subpackage plagiarism
 * @copyright  2010 Dan Marsden http://danmarsden.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

//get global class
global $CFG;
require_once($CFG->dirroot.'/plagiarism/lib.php');


class plagiarism_plugin_moss extends plagiarism_plugin {
    /**
     * hook to allow plagiarism specific information to be displayed beside a submission 
     * @param array  $linkarraycontains all relevant information for the plugin to generate a link
     * @return string
     */
    public function get_links($linkarray) {
        //$userid, $file, $cmid, $course, $module
        $cmid = $linkarray['cmid'];
        $userid = $linkarray['userid'];
        $file = $linkarray['file'];
        $output = '';
        //add link/information about this file to $output
        return $output;
    }
    /** hook to save plagiarism specific settings on a module settings page
     * @param object $data - data from an mform submission.
     */
    public function save_form_elements($data) {
        global $DB;
        $finished = false;
        if($data->update <> '0'){//更新作业，删除原有设置（如果有的话）
            $cmid = $data->coursemodule;
            $old_settings= $DB->get_records_menu('moss_assignment_settings', array('cmid'=>$cmid),'','id,finished');
            foreach ($old_settings as $record){
            	$finished = ($record == '1') ? true : false;//记录是否运行过
            }
            $DB->delete_records('moss_assignment_settings', array('cmid'=>$cmid));//删除原记录
        }
        //根据checkbox状态来写数据库
        for($index = 0; $index <= 2; $index++){	
            $element_name = array('checkbox'=>'active'.$index,'filename'=>'filename'.$index,'language'=>'language'.$index,
                                  'sensitivity'=>'sensitivity'.$index,'basefile'=>'basefile'.$index);
            if(isset($data->$element_name['checkbox'])){//设置启用，写数据库
                $newelement = new object();
                $newelement->cmid = $data->coursemodule;
                $newelement->filename = $data->$element_name['filename'];
                $newelement->judger = 'moss';//judger留着
                $newelement->language = $data->$element_name['language'];
                $newelement->sensitivity = $data->$element_name['sensitivity'];
                $newelement->basefile = $data->$element_name['basefile'];
                $newelement->autoruntime = $data->timedue;
                $newelement->finished = '0';
                //判断moss是否之前有运行过，如果有则标记，当教师进入‘moss结果界面’时提醒是否从新运行moss
                $newelement->rerunrequired = ($finished)? '1' : '0';    
                $DB->insert_record('moss_assignment_settings', $newelement);
            }
        }
    }
    /**
     * hook to add plagiarism specific settings to a module settings page
     * @param object $mform  - Moodle form
     * @param object $context - current context
     */
    public function get_form_elements_module($mform, $context) {
        global $DB;
        $mform->addElement('header', 'mossdesc', get_string('moss', 'plagiarism_moss'));
        //初始化 
        for($index = 0; $index <= 2; $index++){
            //添加单选框
            $mform->addElement('checkbox', 'active'.$index, get_string('activateentry', 'plagiarism_moss'));
            //添加作业名输入框
            $mform->addElement('text', 'filename'.$index, get_string('filename', 'plagiarism_moss'));
            $mform->disabledIf('filename'.$index, 'active'.$index);
            //添加编程语言输入框
            $choices = array('ada' => 'Ada', 'ascii' => 'ASCII', 'a8086' => 'a8086 assembly', 'c' => 'C', 
                             'cc' => 'C++', 'csharp' => 'C#', 'fortran' => 'FORTRAN', 'haskell' => 'Haskell', 
                             'java' => 'Java', 'javascript' => 'Javascript', 'lisp' => 'Lisp', 'matlab' => 'Matlab', 
                             'mips' => 'MIPS assembly', 'ml' => 'ML', 'modula2' => 'Modula2', 'pascal' => 'Pascal', 
                             'perl' => 'Perl', 'plsql' => 'PLSQL', 'prolog' => 'Prolog', 'python' => 'Python', 
                             'scheme' => 'Scheme', 'spice' => 'Spice', 'vhdl' => 'VHDL', 'vb' => 'Visual Basic');
            $mform->addElement('select', 'language'.$index, get_string('language','plagiarism_moss'),$choices);
            $mform->disabledIf('language'.$index, 'active'.$index);
            //添加灵敏度输入框
            $mform->addElement('text', 'sensitivity'.$index, get_string('sensitivity','plagiarism_moss'),'size = "10"');
            $mform->addHelpButton('sensitivity'.$index, 'sensitivity', 'plagiarism_moss');
            $mform->disabledIf('sensitivity'.$index, 'active'.$index);
            //添加base文件选择框
            $mform->addElement('filepicker', 'basefile'.$index, get_string('basefile','plagiarism_moss'), null, array('maxbytes' => 1024,'accepted_types' =>'*'));
            $mform->addHelpButton('basefile'.$index, 'basefile', 'plagiarism_moss');
            $mform->disabledIf('basefile'.$index, 'active'.$index);
        }
        //设置默认值
        $index = 0;
        $default_settings = array('filename'=>'*.c','language'=>'c','sensitivity'=>'50');

        //检查是否为更新，如果是则使用原有数据设置默认值
        $cmid = optional_param('update', 0, PARAM_INT); //obtain the current cm a better way - $this->_cm is not available here.
        if ($cmid <> '0') {//在update界面时，读取数据库数据，注：有可能读出空，原因是新建作业时没有设置反抄袭	
            $old_settings = $DB->get_records('moss_assignment_settings', array('cmid'=>$cmid));
            if(isset($old_settings))//有旧设置
            foreach ($old_settings as $record){
                $mform->setDefault('active'.$index,'1');//把checkbox设置成启用
                $mform->setDefault('filename'.$index, $record->filename);
                $mform->setDefault('language'.$index, $record->language);
                $mform->setDefault('sensitivity'.$index++, $record->sensitivity);
            }
        }
        //使用默认数据设置默认值 
        for(; $index <= 2; $index++){
                $mform->setDefault('filename'.$index, $default_settings['filename']);
                $mform->setDefault('language'.$index, $default_settings['language']);
                $mform->setDefault('sensitivity'.$index, $default_settings['sensitivity']);	
        }
    }
    /**
     * hook to allow a disclosure to be printed notifying users what will happen with their submission
     * @param int $cmid - course module id
     * @return string
     */
    public function print_disclosure($cmid) {
        global $OUTPUT;
        $plagiarismsettings = (array)get_config('plagiarism');
        //TODO: check if this cmid has plagiarism enabled.
        echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        echo format_text($plagiarismsettings['moss_student_disclosure'], FORMAT_MOODLE, $formatoptions);
        echo $OUTPUT->box_end();
    }
    /**
     * hook to allow status of submitted files to be updated - called on grading/report pages.
     * @param object $course - full Course object
     * @param object $cm - full cm object
     */
    public function update_status($course, $cm) {
        //called at top of submissions/grading pages - allows printing of admin style links or updating status
    }
    /**
     * called by admin/cron.php 
     *
     */
    public function cron() {
        //do any scheduled task stuff
    }
}



function event_file_uploaded($eventdata) {
    print_object($eventdata);
    die;
    $result = true;
    //a file has been uploaded - submit this to the plagiarism prevention service.
    return $result;
}
function event_files_done($eventdata) {
    print_object($eventdata);
    die;
    $result = true;
    //mainly used by assignment finalize - used if you want to handle "submit for marking" events
    //a file has been uploaded/finalised - submit this to the plagiarism prevention service.
    return $result;
}

function event_mod_created($eventdata) {
    print_object($eventdata);
    die;
    $result = true;
    //a new module has been created - this is a generic event that is called for all module types
    //make sure you check the type of module before handling if needed.
    return $result;
}

function event_mod_updated($eventdata) {
    print_object($eventdata);
    die;
    $result = true;
    //a module has been updated - this is a generic event that is called for all module types
    //make sure you check the type of module before handling if needed.
    return $result;
}

function event_mod_deleted($eventdata) {
    print_object($eventdata);
    die;
    $result = true;
    //a module has been deleted - this is a generic event that is called for all module types
    //make sure you check the type of module before handling if needed.
    return $result;
}


