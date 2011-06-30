<?php
require_once(dirname(dirname(__FILE__)) . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/lib/form/button.php');
require_once($CFG->libdir.'/tablelib.php');

global $CFG;
require_once($CFG->dirroot.'/plagiarism/moss/lib.php');
global $DB;

require_login();

$PAGE->set_url('/plagiarism/moss/result_pages/statistics.php');
$PAGE->set_context(null);
$PAGE->set_title(get_string('student_page_title', 'plagiarism_moss'));
$PAGE->set_heading(get_string('student_page_heading', 'plagiarism_moss'));
$PAGE->navbar->add(get_string('plugin_name', 'plagiarism_moss'));
$PAGE->navbar->add(get_string('results', 'plagiarism_moss'));


function inital_basic_table($results, $enable_appeal, $cmid, $id)
{
	    global $DB;
	    global $CFG;
	    
        $table = new html_table();
        $table->id = 'result_table';

        //initialize sortable columns of the table
        $rank_head_cell = new html_table_cell(get_string('rank','plagiarism_moss'));
        $student1_head_cell = new html_table_cell(get_string('student_name', 'plagiarism_moss').' 1');
        $student2_head_cell = new html_table_cell(get_string('student_name', 'plagiarism_moss').' 2');
        $appeal_head_cell = new html_table_cell(get_string('appeal', 'plagiarism_moss'));
        
        $table->head = array ($rank_head_cell,
                              $student1_head_cell,
                              $student2_head_cell,
                              $appeal_head_cell);
        $table->align = array ("center", "left", "left", "center");
        $table->width = "100%";   
        
        foreach($results as $re)               
            foreach($re as $result)
            {
            	$student1 = $DB->get_record('user', array('id'=>$result->user1id));
                $student2 = $DB->get_record('user', array('id'=>$result->user2id));
             
                //student 1
                $student1_cell = new html_table_cell('<font color="#3333FF">'.fullname($student1).'</font>');
                $student1_cell -> attributes['onclick'] = 'show_user_profile("'.$CFG->wwwroot."/user/profile.php?id=".$result->user1id.'")';
                $student1_cell -> style = 'cursor:move';
            
                //student 2
                $student2_cell = new html_table_cell('<font color="#3333FF">'.fullname($student2).'</font>');
                $student2_cell -> attributes['onclick'] = 'show_user_profile("'.$CFG->wwwroot."/user/profile.php?id=".$result->user2id.'")';
                $stduent2_cell -> style = 'cursor:move';
            
                $rank_cell = new html_table_cell($result->id);
                if($enable_appeal == 'NEVER')
                	$appeal_cell = '';
                else 
                {
                	$link = $CFG->wwwroot.'/message/index.php?id='.$result->teacherid;
                	$appeal_cell = '<button type="button" onclick = appeal_message("'.$link.'")>'.get_string('appeal', 'plagiarism_moss').'</button>';;
                }
            	if($result->user1id == $id)
            	{
        	        $row = new html_table_row(array(
                                                     $rank_cell,
                                                     $student1_cell,
                                                     $student2_cell,
                                                     $appeal_cell                                      
    	                                            ));
                    $row->id = $result->id;
                    $table->data[] = $row;
        	    }
        	    else 
            	{
            		$row = new html_table_row(array(
                                                     $rank_cell,
                                                     $student2_cell,
                                                     $student1_cell,
                                                     $appeal_cell                                      
    	                                            ));
                    $row->id = $result->id;
                    $table->data[] = $row;
            	}
            }
        return $table;
}

function inital_detail_table($results, $show_code, $enable_appeal, $cmid, $id)
{
	global $DB;
	global $CFG;
	
    $table = new html_table();
    $table->id = 'result_table';

    //initialize column
    $rank_head_cell = new html_table_cell(get_string('rank','plagiarism_moss'));
    $student1_head_cell = new html_table_cell(get_string('student_name', 'plagiarism_moss').' 1');
    $match1_head_cell = new html_table_cell(get_string('match_percent', 'plagiarism_moss'));  
    $student2_head_cell = new html_table_cell(get_string('student_name', 'plagiarism_moss').' 2');
    $match2_head_cell = new html_table_cell(get_string('match_percent', 'plagiarism_moss'));
    $line_count_head_cell = new html_table_cell(get_string('lines_match', 'plagiarism_moss'));
    $detail_head_cell = new html_table_cell(get_string('view_code', 'plagiarism_moss'));
    $appeal_head_cell = new html_table_cell(get_string('appeal', 'plagiarism_moss'));
    
    $table->head = array ($rank_head_cell,
                          $student1_head_cell,
                          $match1_head_cell,
                          $student2_head_cell,
                          $match2_head_cell,
                          $line_count_head_cell,
                          $detail_head_cell,
                          $appeal_head_cell);
    $table->align = array ("center","left", "center", "left","center", "center", "center", "center");
    $table->width = "100%";
    
    foreach($results as $re)
        foreach($re as $result)
        {      
            $student1 = $DB->get_record('user', array('id'=>$result->user1id));
            $student2 = $DB->get_record('user', array('id'=>$result->user2id));
             
            //student 1
            $student1_cell = new html_table_cell('<font color="#3333FF">'.fullname($student1).'</font>');
            $student1_cell -> attributes['onclick'] = 'show_user_profile("'.$CFG->wwwroot."/user/profile.php?id=".$result->user1id.'")';
            $student1_cell -> style = 'cursor:move';
        
            //student 2
            $student2_cell = new html_table_cell('<font color="#3333FF">'.fullname($student2).'</font>');
            $student2_cell -> attributes['onclick'] = 'show_user_profile("'.$CFG->wwwroot."/user/profile.php?id=".$result->user2id.'")';
            $stduent2_cell -> style = 'cursor:move';
                
            $rank_cell = new html_table_cell($result->id);
            $match1_cell = new html_table_cell($result->user1percent.' %');
            $match2_cell = new html_table_cell($result->user2percent.' %');
            $line_count_cell = new html_table_cell($result->linecount);
            
            if(($show_code == 'ALWAYS') && ($result->link == "downloaded"))
                $view_code_cell = '<button type="button" onclick = view_code("'.$cmid.'",this)>'.get_string('view_code', 'plagiarism_moss').'</button>';
            else 
                $view_code_cell = '';
            
            if($enable_appeal == 'NEVER')
             	$appeal_cell = '';
            else 
            {
             	$link = $CFG->wwwroot.'/message/index.php?id='.$result->teacherid;
             	$appeal_cell = '<button type="button" onclick = appeal_message("'.$link.'")>'.get_string('appeal', 'plagiarism_moss').'</button>';;
            }
            
            if($result->user1id == $id)
            {
        	    $row = new html_table_row(array(
                                                $rank_cell,
                                                $student1_cell,
                                                $match1_cell,
                                                $student2_cell,
                                                $match2_cell,
                                                $line_count_cell,
                                                $view_code_cell,
                                                $appeal_cell));
                $row->id = $result->id;
                $table->data[] = $row;
        	}
            else 
            {
            	$row = new html_table_row(array(
                                                $rank_cell,
                                                $student2_cell,
                                                $match2_cell,
                                                $student1_cell,
                                                $match1_cell,
                                                $line_count_cell,
                                                $view_code_cell,
                                                $appeal_cell));
                $row->id = $result->id;
                $table->data[] = $row;
            }
        }
    return $table;
}

$cmid = optional_param('cmid', -1, PARAM_INT);  
$id = optional_param('id', -1, PARAM_INT); //student id
//TODO check validation of $cmid and $id
//TODO check current login user id, see if equal to $id

if(($cmid == -1) || ($id == -1))
{
    echo $OUTPUT->header();
    echo 'Invalid course module id or student id';
    echo $OUTPUT->footer();
    die;
}

//not allow to view other student's record
if($id != $USER->id)
{
    echo $OUTPUT->header();
    echo "Not allow to view other student's record";
    echo $OUTPUT->footer();
    die;
}

//check if cm activate anti-plagiarism, and check measuretime
$moss_settings = $DB->get_records('moss_settings', array('cmid'=>$cmid));
if(!isset($moss_settings))
{
    echo $OUTPUT->header();
	echo 'Anti-plagiarism plugin not activated in course module id = '.$cmid;
    echo $OUTPUT->footer();
    die;
}
else 
{
	foreach($moss_settings as $settings)
	{
	    if($settings->measuredtime == 0)//
	    {
		    echo $OUTPUT->header();
	    	echo 'Results not available yet, try later';
	    	echo $OUTPUT->footer();
            die;
	    }
	}
}

$results = array();
$results[0] = $DB->get_records('moss_results', array('cmid'=>$cmid, 'user1id'=>$id, 'confirmed'=>1));

$results[1] = $DB->get_records('moss_results', array('cmid'=>$cmid, 'user2id'=>$id, 'confirmed'=>1));

$table;

if(!isset($results[0]) && !isset($results[1]))
{
	echo $OUTPUT->header();
	echo 'Plagiarism record not found, Congratulations !';
	echo $OUTPUT->footer();
	die;
}
else 
{   
    $cnf_xml = new config_xml();
    $show_code = $cnf_xml->get_config('show_code');
    $show_entrys_detail = $cnf_xml->get_config('show_entrys_detail');
    $enable_appeal = $cnf_xml->get_config('enable_student_appeal');
    //initial records table
    if($show_entrys_detail == 'ALWAYS')
    	$table = inital_detail_table($results, $show_code, $enable_appeal, $cmid, $id);  
    else 
    	$table = inital_basic_table($results, $enable_appeal, $cmid, $id);
}

echo $OUTPUT->header();
echo html_writer::table($table);
echo $OUTPUT->footer();

?>

<head>
<script type="text/javascript">

function show_user_profile(url)
{

    var height = window.screen.availHeight;
    var width = window.screen.availWidth;
    var w_height = parseInt(height * 2 / 3);
    var w_width = parseInt(width * 4 / 5);
    var w_top = parseInt((height - w_height) / 2);
    var w_left = parseInt((width - w_width) / 2);
    window.open(url,
    	        "view user",
                "height="+w_height+",width="+w_width+",top="+w_top+",left="+w_left); 
}

function view_code(cmid, element)
{
    var entryid = element.parentNode.parentNode.id;
    var height = window.screen.availHeight;
    var width = window.screen.availWidth;
    var w_height = parseInt(height * 2 / 3);
    var w_width = parseInt(width * 4 / 5);
    var w_top = parseInt((height - w_height) / 2);
    var w_left = parseInt((width - w_width) / 2);
	window.open("view_code.php?cmid="+cmid+"&entryid="+entryid+"&role=student",
			    "view code",
			    "height="+w_height+",width="+w_width+",top="+w_top+",left="+w_left);
}

function appeal_message(link)
{
    var height = window.screen.availHeight;
    var width = window.screen.availWidth;
    var w_height = parseInt(height * 2 / 3);
    var w_width = parseInt(width * 4 / 5);
    var w_top = parseInt((height - w_height) / 2);
    var w_left = parseInt((width - w_width) / 2);
	window.open(link,
			    "Send appeal message",
			    "height="+w_height+",width="+w_width+",top="+w_top+",left="+w_left);

}
</script>
</head>
