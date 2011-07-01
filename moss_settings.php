<?php
if (!defined('MOODLE_INTERNAL')) 
    die('Direct access to this script is forbidden.');

global $CFG;      
require_once($CFG->dirroot.'/plagiarism/moss/file_operator.php');//文件操作处理函数类
require_once($CFG->dirroot.'/plagiarism/moss/lib.php');
require_once($CFG->dirroot.'/plagiarism/moss/moss_operator.php');

/**
 * 
 * Enter description here ...
 * @author ycc
 *
 */
class moss_settings
{
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $mform
	 * @param unknown_type $context
	 */
    public function show_settings_form($mform, $context)
    { 	
        global $DB;
        
        $mform->addElement('header', 'mossdesc', get_string('moss', 'plagiarism_moss'));
        
        $cnf_xml = new config_xml();
        $entry_num = $cnf_xml->get_config('entry_number');
        
        $mform->addElement('text', 'tag', get_string('tag', 'plagiarism_moss'));
        
        //初始化 
        for($index = 0; $index < $entry_num; $index++)
        {
            //添加单选框
            $mform->addElement('checkbox', 'activate'.$index, get_string('activateentry', 'plagiarism_moss'));
     
            //添加作业名输入框
            $mform->addElement('text', 'filepattern'.$index, get_string('filepattern', 'plagiarism_moss'));
            $mform->disabledIf('filepattern'.$index, 'activate'.$index);
            
            //添加编程语言输入框
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
            
            $mform->addElement('select', 'language'.$index, get_string('language','plagiarism_moss'),$choices);
            $mform->disabledIf('language'.$index, 'activate'.$index);
            
            //添加灵敏度输入框
            $mform->addElement('text', 'sensitivity'.$index, get_string('sensitivity','plagiarism_moss'),'size = "10"');
            $mform->addHelpButton('sensitivity'.$index, 'sensitivity', 'plagiarism_moss');
            $mform->disabledIf('sensitivity'.$index, 'activate'.$index);
            
            //添加base文件选择框
            $mform->addElement('filepicker', 'basefile'.$index, get_string('basefile','plagiarism_moss'), null, array('maxbytes' => 1024,'accepted_types' =>'*'));
            $mform->addHelpButton('basefile'.$index, 'basefile', 'plagiarism_moss');
            $mform->disabledIf('basefile'.$index, 'activate'.$index);
           
        }
        
        //设置默认值
        $index = 0;
        $default_settings = array('filepattern'=>'*.c','language'=>'c','sensitivity'=>'10');

        //检查是否为更新，如果是则使用原有数据设置默认值
        $cmid = optional_param('update', 0, PARAM_INT); //obtain the current cm a better way - $this->_cm is not available here.
        if ($cmid <> '0') 
        {//在update界面时，读取数据库数据，注：有可能读出空，原因是新建作业时没有设置反抄袭	
            $old_settings = $DB->get_records('moss_settings', array('cmid'=>$cmid));
            
            if(count($old_settings) != 0)
            {//有旧设置
                foreach ($old_settings as $record)
                {
                    $mform->setDefault('activate'.$index,'1');//把checkbox设置成启用
                    $mform->setDefault('filepattern'.$index, $record->filepattern);
                    $mform->setDefault('language'.$index, $record->language);
                    $mform->setDefault('sensitivity'.$index++, $record->sensitivity);
                }
            }
            
            $old_tag = $DB->get_record('moss_tags', array('cmid'=>$cmid));
            if($old_tag->tag != NULL)
                $mform->setDefault('tag', $old_tag->tag);
        }
        
        //使用默认数据设置默认值 
        for(; $index < $entry_num; $index++)
        {
                $mform->setDefault('filepattern'.$index, $default_settings['filepattern']);
                $mform->setDefault('language'.$index, $default_settings['language']);
                $mform->setDefault('sensitivity'.$index, $default_settings['sensitivity']);	
        } 
    }

    /**
     * 
     * Enter description here ...
     * @param unknown_type $data
     */
    public function save_settings($data)
    {
    	global $USER;
    	global $DB;
    	
    	$usercontext = get_context_instance(CONTEXT_USER, $USER->id);
    	$fs = get_file_storage();
        $flag = false;
        $requirererun = 0;
        $run = 0;
        
        if($data->update <> '0')
        {//更新作业，删除原有设置（如果有的话）
            $cmid = $data->coursemodule;
            $old_settings= $DB->get_records_menu('moss_settings', array('cmid'=>$cmid),'','id, measuredtime');
            foreach ($old_settings as $record)
            {
            	if($record->measuredtime <> 0)
            	{
            		 $cnf_xml = new config_xml();
                     $rerun_after_change = $cnf_xml->get_config('rerun_after_change');
                     if($rerun_after_change == 'YES')
                     	 $run = 1;
                     else
                         $requirererun = 1; 
            	}
            }
            $DB->delete_records('moss_settings', array('cmid'=>$cmid));//删除原记录
            $DB->delete_records('moss_tags', array('cmid'=>$cmid));//删除tag
        }
        
        $cnf_xml = new config_xml();
        $entry_num = $cnf_xml->get_config('entry_number');
        
        //根据checkbox状态来写数据库
        for($index = 0; $index < $entry_num; $index++)
        {	
            $element_name = array('checkbox'    => 'activate'.$index,
                                  'filepattern' => 'filepattern'.$index,
                                  'language'    => 'language'.$index,
                                  'sensitivity' => 'sensitivity'.$index,
                                  'basefile'    => 'basefile'.$index);
            if(isset($data->$element_name['checkbox']))
            {//设置启用，写数据库
            	$flag = true;
                $newelement = new object();
                $newelement->cmid = $data->coursemodule;
                $newelement->filepattern = $data->$element_name['filepattern'];//TODO输入必须为*.c或filename.c 不允许输入多个
                $newelement->language = $data->$element_name['language'];
                $newelement->sensitivity = $data->$element_name['sensitivity'];
                $newelement->measuredtime = 0;
                $newelement->requirererun = $requirererun;
                
                //保存basefiles
                if(isset($data->$element_name['basefile']))
                {
                    $basefile = $fs->get_directory_files($usercontext->id, 
                                                         'user', 
                                                         'draft', 
                                                         $data->$element_name['basefile'],
                                                         '/');
                    if(!empty($basefile)) 
                    {
                        foreach($basefile as $file)
                            if($file -> get_filename() != '.')
                            {
                    	        $file_op = new file_operator();
                                $file_op -> save_base_file($file, $data -> coursemodule);
                                $newelement -> basefilename = $file -> get_filename();
                            }
                    }
                }
                $DB->insert_record('moss_settings', $newelement);
            }
        }
        
        //有设置则保存tag
        if($flag)
        {
            $newelement = new object();
            $newelement->cmid = $data->coursemodule;
            if(isset($data->tag))
                $newelement->tag = $data->tag;
            else
                $newelement->tag = NULL;
            $DB->insert_record('moss_tags', $newelement);
        }
        
        if($run == 1)
        {
        	$moss_op = new moss_operator();
        	$moss_op -> connect_moss($data->coursemodule);
        }
    }
    
}
