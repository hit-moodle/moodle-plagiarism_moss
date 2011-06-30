<?php
require_once(dirname(dirname(__FILE__)) . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/plagiarism/moss/lib.php');

/**
 * class top_x_node and class top_x implement a list, which specifically designed to get 'top X node' from input,
 * we can use other approach to do 'get top X', but this is a graduation project, i need 10000 lines to graduate...
 * @author ycc
 *
 */
class top_x_node
{
    private $next_node;
    private $compare_key;
    private $value;
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $compare_key
     * @param unknown_type $value
     */
    function __construct($compare_key=null, $value=null)
    {
        $this->compare_key = $compare_key;
        $this->value = $value;
        $this->next_node = null;
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $node
     */
    function set_next($node)
    {
        $this->next_node = $node;
    }
    
    /**
     * 
     * Enter description here ...
     */
    function get_next()
    {
        return $this->next_node;
    }
    
    /**
     * 
     * Enter description here ...
     */
    function get_compare_key()
    {
        return $this->compare_key;
    }
    
    /**
     * 
     * Enter description here ...
     */
    function get_value()
    {
        return $this->value;
    }
}

/**
 * 
 * Enter description here ...
 * @author ycc
 *
 */
class top_x
{
	private $available_size;
	private $max_size;
    private $empty_head;
    private $arr;
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $max_size
     */
	function __construct($max_size = 8)
    {
        $this->max_size = $max_size;
        $this->available_size = $max_size;
        $this->empty_head = new top_x_node();
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $compare_key
     * @param unknown_type $value
     */
    function insert($compare_key, $value)
    {
    	
        $temp = $this->empty_head;
    	while(($temp->get_next() != null) && ($temp->get_next()->get_compare_key() < $compare_key))
    	{
    	    $temp = $temp->get_next();
    	}
    	$tail = $temp->get_next();
    	$new = new top_x_node($compare_key, $value);
    	$temp->set_next($new);
    	$new->set_next($tail);
    	
    	if($this->available_size == 0)//delete one
    	{
    		$temp = $this->empty_head->get_next()->get_next();//the second node;
    		$this->empty_head->set_next($temp);//delete first node;
    	}
    	else
    	{
    	    $this->available_size -= 1;
    	}
    }
    
    /**
     * return $value array 
     * order by it's corresponding  $compare_key,
     */
    function to_array()
    {
        $this->arr = array();
        $this->load($this->empty_head->get_next());
        return $this->arr;	
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $node
     */
    private function load($node)
    {
        if($node == null)
        {
            return;
        }
        else 
        {
            $this->load($node->get_next());
            $this->arr[] = $node->get_value();
        }
    }
    
    /**
     * 
     * Enter description here ...
     */
    function print_value()
    {
        $temp = $this->empty_head->get_next();
        while($temp != null)
        {
            print_object($temp->get_value());
            $temp = $temp->get_next();
        }
    }
}

/**
 * 
 * Enter description here ...
 * @param unknown_type $cmid
 * @param unknown_type $table_array
 * @param unknown_type $cmid_array
 * @param unknown_type $summary_array
 */
function initial_array($cmid, &$table_array, &$cmid_array, &$summary_array)
{
    global $DB;
    $sql = "SELECT DISTINCT cmid 
            FROM {moss_settings}
            WHERE cmid IN
                         (SELECT b.id 
                          FROM {course_modules} AS a, {course_modules} AS b 
                          WHERE a.id = ? AND a.course = b.course)";
    $params = array($cmid);
    $results = $DB->get_records_sql($sql,$params);
    foreach($results as $result)
        $cmid_array[$result->cmid] = $result->cmid;
    
	$sql = "SELECT id,cmid,iscross,user1id,user2id 
          FROM {moss_results}  
          WHERE confirmed=1 AND cmid IN
                                       (SELECT DISTINCT cmid 
                                        FROM {moss_settings}
                                        WHERE cmid IN
                                                     (SELECT b.id 
                                                      FROM {course_modules} AS a, {course_modules} AS b 
                                                      WHERE a.id = ? AND a.course = b.course))";
    $params = array($cmid);
    $results = $DB->get_records_sql($sql,$params);
    foreach($results as $result)
    {
        if($result->iscross == 0)
        {
            if(!isset($table_array[$result->user1id]))
                $table_array[$result->user1id] = array();
            if(!isset($table_array[$result->user1id][$result->cmid]))
            {
            	if(!isset($summary_array[$result->cmid]))
            	    $summary_array[$result->cmid] = 1;
            	else
            	    $summary_array[$result->cmid] += 1;
            }
            $table_array[$result->user1id][$result->cmid] = 1;
        }
        
        if(!isset($table_array[$result->user2id]))
            $table_array[$result->user2id] = array();
        if(!isset($table_array[$result->user2id][$result->cmid]))
        {
         	if(!isset($summary_array[$result->cmid]))
                $summary_array[$result->cmid] = 1;
          	else
                $summary_array[$result->cmid] += 1;
        }
        $table_array[$result->user2id][$result->cmid] = 1;
                  
    }
}

/**
 * 返回ture 代表top_x数组为所有学生（这时不可扩张），  false 为 $x < count($table_array) 可扩张
 * Enter description here ...
 * @param unknown_type $table_array
 */
function get_top_x_list(&$table_array)
{
	//sort top x students
	$cnf_xml = new config_xml();
    $x = $cnf_xml->get_config('default_students_in_statistics_page');
    $flag;
    if($x == 0)//show all students
        $x = count($table_array);

    if($x >= count($table_array))
        $flag = true;
    else 
        $flag = false;

    $top_x_list = new top_x($x);//sort top x 
    foreach($table_array as $student_id => $cm_array)
    {
        $top_x_list->insert(count($cm_array), array($student_id, $cm_array));
    }
    
    $table_array = $top_x_list->to_array();
    return $flag;
}

/**
 * 
 * Enter description here ...
 * @param unknown_type $flag
 * @param unknown_type $table_array
 * @param unknown_type $cmid_array
 * @param unknown_type $summary_array
 */
function initial_table($flag, $table_array, $cmid_array, $summary_array)
{
    global $CFG;
    global $DB;
    $table = new html_table();  
    $table->width = "100%";
    $table->id = 'statistics_table';
    
    //inital head row
    $head_row = array(get_string('student_name', 'plagiarism_moss'));
    $align = array('center');
    //assignment head cell
    foreach($cmid_array as $cmid)
    {
        $cell = new html_table_cell('<font color="#3333FF">'.get_string('assignment', 'plagiarism_moss').$cmid.'</font>');
        $cell -> attributes['onclick'] = 'show_assignment_profile("'.$CFG->wwwroot.'/mod/assignment/view.php?id='.$cmid.'")';
        $cell -> style = 'cursor:move';
        $head_row[] = $cell;
        $align[] = 'center';
    }
    //summary head cell
    $cell = new html_table_cell(get_string('summary', 'plagiarism_moss'));
    $align[] = 'center';
    $head_row[] = $cell;
    
    $table->head = $head_row;
    $table->align = $align;
    
    //inital student rows
    foreach($table_array as $student)
    {	
	    $user = $DB->get_record('user', array('id'=>$student[0]));
        $link = $CFG->wwwroot."/user/profile.php?id=".$student[0];
        $user_name = new html_table_cell('<font color="#3333FF">'.fullname($user).'</font>');
        $user_name -> attributes['onclick'] = 'show_user_profile("'.$link.'")';
        $user_name -> style = 'cursor:move';
	    $student_row = array($user_name);//name cell
	    $count = 0;
	    //record cell
	    foreach($cmid_array as $cmid)
	    {
		    if(isset($student[1][$cmid]))
		    {
		        $student_row[] = '<input type="checkbox" onchange = reverse(this) checked="checked" />';
		        $count += 1;
		    }
		    else 
		    {
		        $student_row[] = '<input type="checkbox" onchange = reverse(this) />';
		    }
	    }
	    //summary cell
	    $student_row[] = $count;
	    
	    $row = new html_table_row($student_row);
	    $row -> id = $student[0];//student's id
	    $table->data[] = $row;
    }
				  
    //initial summary row 
    $expand_row = array('...');
    $summary_row = array('<b>'.get_string('summary', 'plagiarism_moss').'</b>');
    foreach($cmid_array as $cmid => $id)
    {
        if(isset($summary_array[$cmid]))
            $summary_row[] = '<b>'.$summary_array[$cmid].'</b>';
        else 
            $summary_row[] = '<b> 0 </b>';
        $expand_row[] = '...';
    }
    $summary_row[] = ' ';
    $expand_row[] = '...';
    
    if($flag == false)//expandable
    {
    	$row = new html_table_row($expand_row);
    	$row -> id = 'expand_row';
        $table->data[] = $row;
        $row = new html_table_row($summary_row);
    	$row -> id = 'summary_row';
        $table->data[] = $row;
        return $table;
    }
    else 
    {
        $row = new html_table_row($summary_row);
    	$row -> id = 'summary_row';
        $table->data[] = $row;	
        return $table;
    }
}

require_login();

$PAGE->set_url('/plagiarism/moss/result_pages/statistics.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('statistics_title', 'plagiarism_moss'));
$PAGE->set_heading(get_string('statistics_heading', 'plagiarism_moss'));
$PAGE->navbar->add(get_string('plugin_name', 'plagiarism_moss'));
$PAGE->navbar->add(get_string('results', 'plagiarism_moss'));
$PAGE->navbar->add(get_string('statistics', 'plagiarism_moss'));

$cmid = optional_param('cmid', 0, PARAM_INT);  

$currenttab='tab3';
$tabs = array();
$tabs[] = new tabobject('tab1', 
                        "view_all.php?cmid=".$cmid, 
                        get_string('view_all', 'plagiarism_moss'), 
                        'View all', 
                        false);

$tabs[] = new tabobject('tab3', 
                        "statistics.php?id=".$cmid, 
                        get_string('statistics', 'plagiarism_moss'), 
                        'Statistics', 
                        false);

$cmid = optional_param('cmid', 0, PARAM_INT);


$table_array = array();
$cmid_array = array();
$summary_array = array();

initial_array($cmid, $table_array, $cmid_array, $summary_array);
$flag = get_top_x_list($table_array);
$table = initial_table($flag, $table_array, $cmid_array, $summary_array);
    
echo $OUTPUT->header();
echo $OUTPUT->box_start();
echo '<b>'.get_string('expand_contract_describtion', 'plagiarism_moss').'<b> <br/>';
if($flag == false)
{
    echo  '<button id="expand_button" type="button" onclick = expand("'.$cmid.'")>'.get_string('expand', 'plagiarism_moss').'</button>';
    echo  '<button id="contract_button" disabled="true" type="button" onclick = contract()>'.get_string('contract', 'plagiarism_moss').'</button>';
}
else
{
    echo  '<button id="expand_button" disabled="true" type="button" onclick = expand("'.$cmid.'")>'.get_string('expand', 'plagiarism_moss').'</button>';
    echo  '<button id="contract_button" disabled="true"type="button" onclick = contract()>'.get_string('contract', 'plagiarism_moss').'</button>';
}
echo $OUTPUT->box_end();
print_tabs(array($tabs), $currenttab);
echo html_writer::table($table);
echo $OUTPUT->footer();

echo  '<div style="visibility:hidden">';
echo  '<div id="parse_xml_exception">'.get_string('parse_xml_exception','plagiarism_moss').'</div>';
echo  '<div id="request_rejected">'.get_string('request_rejected','plagiarism_moss').'</div>';
echo  '</div>';
?>



<head>
<script type="text/javascript">

function reverse(element)
{
    if(element.checked == true)
        element.checked = false;
    else
        element.checked = true;
}

function show_assignment_profile(url)
{

    var height = window.screen.availHeight;
    var width = window.screen.availWidth;
    var w_height = parseInt(height * 2 / 3);
    var w_width = parseInt(width * 4 / 5);
    var w_top = parseInt((height - w_height) / 2);
    var w_left = parseInt((width - w_width) / 2);
	window.open(url,
			    "view assignment",
			    "height="+w_height+",width="+w_width+",top="+w_top+",left="+w_left); 
}

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

function get_label(id, default_txt)
{
	var element = document.getElementById(id);
	if(element != null)
		return element.innerHTML;
	else
		return default_txt;	
}

storage = new Array();
expand_row;
function expand($cmid)
{
    var table=document.getElementById("statistics_table");
    if(storage.length != 0)//expand before
    {
    	var summary_row_index = table.rows.length - 1;
        var summary_row = table.rows[summary_row_index];
        table.deleteRow(summary_row_index);

        var expand_row_index = table.rows.length - 1;
        expand_row = table.rows[expand_row_index];
        table.deleteRow(expand_row_index);

        for(var i = 0; i < storage.length; i++)
        {
        	var row = table.insertRow(-1);
        	row.innerHTML = storage[i].innerHTML;
        	row.className = 'expand';
        }
        var row = table.insertRow(-1);
        row.innerHTML = summary_row.innerHTML;
        
    	var expand_button = document.getElementById("expand_button");
        expand_button.disabled = true;
        var contract_button = document.getElementById("contract_button");
        contract_button.disabled = false;
    }
    else
    {
        var expand_button = document.getElementById("expand_button");
        var contract_button = document.getElementById("contract_button");
    
        if (window.XMLHttpRequest)// code for IE7+, Firefox, Chrome, Opera, Safari
            xmlhttp=new XMLHttpRequest();
        else// code for IE6, IE5
            xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
        xmlhttp.onreadystatechange=function()
        {
            if(xmlhttp.readyState==4 && xmlhttp.status==200)
            {
                var students = xmlhttp.responseXML.documentElement.getElementsByTagName("STUDENT");
                var status = xmlhttp.responseXML.documentElement.getElementsByTagName("STATUS");
                if(status == 1)
                {
                    alert(get_label('request_rejected', 'Request rejected by server.'));
                    return;
                }
                var summary_row_index = table.rows.length - 1;
                var summary_row = table.rows[summary_row_index];
                table.deleteRow(summary_row_index);

                var expand_row_index = table.rows.length - 1;
                expand_row = table.rows[expand_row_index];
                table.deleteRow(expand_row_index);
                
                try{
                    for (var i = 0; i < students.length; i++)
                    {
                        var id = students[i].getElementsByTagName("ID")[0].firstChild.nodeValue;
                        var name = students[i].getElementsByTagName("NAME")[0].firstChild.nodeValue;
                        var link = students[i].getElementsByTagName("LINK")[0].firstChild.nodeValue;
                        var summary = students[i].getElementsByTagName("SUMMARY")[0].firstChild.nodeValue;

                        var row = table.insertRow(-1);
                        row.id = id;
                        row.className = 'expand';
                         
                        var cell_name = row.insertCell(-1);
                        cell_name.innerHTML = '<font color="#3333FF" onclick = show_user_profile("'+link+'")>'+name+'</font>';
                        cell_name.align = 'center';
                        cell_name.style['cursor'] = 'move'; 

                        var cm_value = students[i].getElementsByTagName("CM");
                        for (var j = 0; j < cm_value.length; j++)
                        {
                            var cell_cm = row.insertCell(-1);
                            if(cm_value[j].firstChild.nodeValue == 1)
                                cell_cm.innerHTML = '<input type="checkbox" onchange = reverse(this) checked="checked" />'
                    	    else
                        	    cell_cm.innerHTML = '<input type="checkbox" onchange = reverse(this) />';
                            cell_cm.align = 'center'; 
                        }
                        var cell_sum = row.insertCell(-1);
                        cell_sum.innerHTML = summary;
                        cell_sum.align = 'center'; 
                        storage[i] = row; 
                    }
                }
                catch(er)
                {
                    alert(get_label('parse_xml_exception', 'Parse XML exception.'));
                    //clear storage array
                    storage = new Array();
                    //restore table
                    contract(); 
                    return;
                }
                var row = table.insertRow(-1);
                row.innerHTML = summary_row.innerHTML;
                var expand_button = document.getElementById("expand_button");
                expand_button.disabled = true;
                var contract_button = document.getElementById("contract_button");
                contract_button.disabled = false;
            }
        }
        xmlhttp.open("GET","ajax_response.php?page=statistics_page&request=expand&id="+$cmid,true);
        xmlhttp.send();
    }    
}

function contract()
{
    var table = document.getElementById('statistics_table'); 
    var length = table.rows.length;
    
    var summary_row_index = length - 1;

    var row = table.insertRow(summary_row_index);
    row.innerHTML = expand_row.innerHTML;

    var expand_button = document.getElementById("expand_button");
    expand_button.disabled = false;
    var contract_button = document.getElementById("contract_button");
    contract_button.disabled = true;

    length = table.rows.length;

    for(var i = length-1; i >= 0; i--)
    {
       if(table.rows[i].className == 'expand')
       {
           row = table.rows[i];
           row.parentNode.removeChild(row);
       }
    }
}

</script>
</head>
