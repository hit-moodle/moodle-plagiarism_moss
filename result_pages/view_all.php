<?php 
require_once(dirname(dirname(__FILE__)) . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/lib/form/button.php');
require_once($CFG->libdir.'/tablelib.php');
global $CFG;

/**
 * 
 * Enter description here ...
 * @author ycc
 *
 */
class view_all_filter_form extends moodleform 
{
    /**
     * (non-PHPdoc)
     * @see moodleform::definition()
     */
    function definition () 
    {
        global $CFG;
        $cmid  = $this->_customdata['cmid'];
         
        $mform =& $this->_form;
        $mform->addElement('header', 'head', get_string('view_all_filter', 'plagiarism_moss'));
        $table_choices = array('view_all_table'    => get_string('entry_type_all', 'plagiarism_moss'),
                               'confirmed_table'   => get_string('entry_type_confirmed', 'plagiarism_moss'),
                               'unconfirmed_table' => get_string('entry_type_unconfirmed', 'plagiarism_moss'),
                               'cross_table'       => get_string('entry_type_cross', 'plagiarism_moss'));
        $name_choices = array('western' => get_string('student_name_western', 'plagiarism_moss'),
                              'Eastern' => get_string('student_name_eastern', 'plagiarism_moss'));
        $rank_choices = $this->rank_array($cmid);
        $percent_choices = $this->percent_array($cmid);
        $line_choices = $this->lines_array($cmid);



        $rank[] = $mform->createElement('select', 'rank_from', null, $rank_choices);
        $rank[] = $mform->createElement('select', 'rank_to', null, array_reverse($rank_choices, true));
        //try to set element value by call function setDefault, but it doesn't work
        $rank[] = $mform->createElement('checkbox', 'rank_not_include', null, get_string('not_include', 'plagiarism_moss'));
        $mform->addElement('group', 'rank', get_string('rank_range', 'plagiarism_moss'), $rank);
        $mform->addHelpButton('rank', 'rank_range', 'plagiarism_moss');
          
        $percent[] = $mform->createElement('select', 'percent_from', null, $percent_choices);
        $percent[] = $mform->createElement('select', 'percent_to', null, array_reverse($percent_choices, true));
        $percent[] = $mform->createElement('checkbox', 'percent_not_include', null, get_string('not_include', 'plagiarism_moss'));
        $mform->addElement('group', 'percent', get_string('percentage_range', 'plagiarism_moss'), $percent);
        $mform->addHelpButton('percent', 'percentage_range', 'plagiarism_moss');
        
        $lines[] = $mform->createElement('select', 'lines_from', null, $line_choices);
        $lines[] = $mform->createElement('select', 'lines_to', null, array_reverse($line_choices, true));
        $lines[] = $mform->createElement('checkbox', 'lines_not_include', null, get_string('not_include', 'plagiarism_moss'));
        $mform->addElement('group', 'lines', get_string('lines_range', 'plagiarism_moss'), $lines);
        $mform->addHelpButton('lines', 'lines_range', 'plagiarism_moss');
        
                $general[] = $mform->createElement('select','tabletype', get_string('entry_type','plagiarism_moss'), $table_choices);
        $general[] = $mform->createElement('submit', 'submit1', 'Submit');
        $mform->addElement('group', 'general', get_string('entry_type','plagiarism_moss'), $general);
        $mform->addHelpButton('general', 'entry_type', 'plagiarism_moss');
        
        $mform->addElement('submit', 'submit2', 'Submit');
        
        //$mform->setAdvanced('name');
        $mform->setAdvanced('rank');
        $mform->setAdvanced('percent');
        $mform->setAdvanced('lines');
        $mform->setAdvanced('submit2');
        
        //url param
        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);
        $this->set_data(array('cmid'=>$cmid));
                 
    }

    /**
     * 
     * Enter description here ...
     * @param unknown_type $cmid
     */
    function rank_array($cmid)
    {
        global $DB;
        $rank_choices = array();
        $max_rank = $DB->get_record_sql("SELECT MAX(rank) as maxrank FROM {moss_results}
                                         WHERE cmid = ?",
                                        array($cmid));
        $max = $max_rank->maxrank;  
        for($i = 1; $i <= $max; $i++)      
            $rank_choices[''.$i] = 'rank '.$i;  
        return $rank_choices;
    }

    /**
     * 
     * Enter description here ...
     * @param unknown_type $cmid
     */
    function percent_array($cmid)
    {
        $percent_choices = array();
        for($i = 1; $i <=100; $i++)
            $percent_choices[''.$i] = $i.' %';
        return $percent_choices;
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $cmid
     */
    function lines_array($cmid)
    {
        global $DB;
        $lines_choices = array();
        $max_line = $DB->get_record_sql("SELECT MAX(linecount) as maxline FROM {moss_results}
                                         WHERE cmid = ?",
                                        array($cmid));
        $max = $max_line->maxline;  
        for($i = 1; $i <= $max; $i++)      
            $lines_choices[''.$i] = $i.' lines';
        return $lines_choices;
    }
}

/**
 * 
 * Enter description here ...
 * @param unknown_type $cmid
 * @param unknown_type $DB_results
 * @param unknown_type $tablesummary
 */
function initial_table($cmid, $DB_results, $tablesummary)
{
    global $DB;
    global $CFG;
    $table = new html_table();
    $table->id = 'result_table';
    $table->summary = $tablesummary;

    //initialize sortable columns of the table
    $rank_head_cell = new html_table_cell('<font color="#3333FF">'.get_string('rank','plagiarism_moss').'<span> </span><img id ="arror_img" src="pix/DESC.gif"></font>');
    $rank_head_cell -> attributes['onclick'] = 'sort_table(0)';
    $rank_head_cell -> style = 'cursor:move';
    
    $match1_head_cell = new html_table_cell('<font color="#3333FF">'.get_string('match_percent', 'plagiarism_moss').' 1<span> </span></font>');
    $match1_head_cell -> attributes['onclick'] = 'sort_table(2)';
    $match1_head_cell -> style = 'cursor:move';
    
    $match2_head_cell = new html_table_cell('<font color="#3333FF">'.get_string('match_percent', 'plagiarism_moss').' 2<span> </span></font>');
    $match2_head_cell -> attributes['onclick'] = 'sort_table(4)';
    $match2_head_cell -> style = 'cursor:move';
    
    $line_count_head_cell = new html_table_cell('<font color="#3333FF">'.get_string('lines_match', 'plagiarism_moss').'<span> </span></font>');
    $line_count_head_cell -> attributes['onclick'] = 'sort_table(5)';
    $line_count_head_cell -> style = 'cursor:move';
    
    //initialize unsortable columns
    if($tablesummary == 'cross_table')
    {
        $name1_head_cell    = new html_table_cell(get_string('student_from_other_course', 'plagiarism_moss'));
        $name2_head_cell    = new html_table_cell(get_string('student_name', 'plagiarism_moss'));
    }
    else
    {    
        $name1_head_cell    = new html_table_cell(get_string('student_name', 'plagiarism_moss').' 1');
        $name2_head_cell    = new html_table_cell(get_string('student_name', 'plagiarism_moss').' 2');
    }
    $detail_head_cell   = new html_table_cell(get_string('view_code', 'plagiarism_moss'));
    $action_head_cell   = new html_table_cell(get_string('action', 'plagiarism_moss'));
    $relevant_head_cell = new html_table_cell(get_string('relevant_entry', 'plagiarism_moss'));
    $status_head_cell   = new html_table_cell(get_string('entry_status', 'plagiarism_moss'));
    
    //add head cells to $table, notice that the order of head cells isn't random,
    //for example $rank_cell must at the head of the array, 
    //because if the 'onclick' event is triggered JS function 'sort(0)' will be called.
    if(($tablesummary == 'view_all_table') || ($tablesummary == 'cross_table'))
    {
        $table->head = array ($rank_head_cell,
                              $name1_head_cell,
                              $match1_head_cell,
                              $name2_head_cell,
                              $match2_head_cell,
                              $line_count_head_cell,
                              $detail_head_cell,
                              $action_head_cell,
                              $relevant_head_cell,
                              $status_head_cell);
						  
        $table->align = array ("center","left", "center", "left","center", "center", "center","center" ,"center","center");
    }
    else 
    {
    	$table->head = array ($rank_head_cell,
                              $name1_head_cell,
                              $match1_head_cell,
                              $name2_head_cell,
                              $match2_head_cell,
                              $line_count_head_cell,
                              $detail_head_cell,
                              $action_head_cell,
                              $status_head_cell);
						  
        $table->align = array ("center","left", "center", "left","center", "center", "center","center" ,"center");
    }
    $table->width = "100%";
	
    foreach($DB_results as $entry)
    {
        if($entry->confirmed == 1)
        {
            $status_txt = '<font color="#FF0000"><b>'.get_string('confirmed', 'plagiarism_moss').'</b></font>';	
            $confirm_button = '<button type="button" onclick = unconfirm_entry(this)>'.get_string('unconfirm', 'plagiarism_moss').'</button>';
        }
        else 
        {
            $status_txt = get_string('unconfirmed', 'plagiarism_moss');
            $confirm_button = '<button type="button" onclick = confirm_entry(this)>'.get_string('confirm', 'plagiarism_moss').'</button>';
        }
        
        $user1 = $DB->get_record('user', array('id'=>$entry->user1id));
        $user2 = $DB->get_record('user', array('id'=>$entry->user2id));
        
        $user1_cell = new html_table_cell('<font color="#3333FF">'.fullname($user1).'</font>');
        $link1 = $CFG->wwwroot."/user/profile.php?id=".$entry->user1id;
        $user1_cell -> attributes['onclick'] = 'show_user_profile("'.$link1.'")';
        $user1_cell -> style = 'cursor:move';
        
        $user2_cell = new html_table_cell('<font color="#3333FF">'.fullname($user2).'</font>');
        $link2 = $CFG->wwwroot."/user/profile.php?id=".$entry->user2id;
        $user2_cell -> attributes['onclick'] = 'show_user_profile("'.$link2.'")';
        $user2_cell -> style = 'cursor:move';
        
        if(($tablesummary == 'view_all_table') || ($tablesummary == 'cross_table'))
        {
            $row1 = new html_table_row(array(
                                            $entry->rank,
                                            $user1_cell,
                                            $entry->user1percent.' %',
                                            $user2_cell,
                                            $entry->user2percent.' %',
                                            $entry->linecount,
                                            '<button type="button" onclick = view_code("'.$cmid.'","'.$entry->id.'")>'.get_string('view_code', 'plagiarism_moss').'</button>',
                                            $confirm_button,
                                            '<button type="button" onclick = relevant_entry("'.$cmid.'","'.$entry->user1id.'","'.$entry->user2id.'")>'.
                                            ''.get_string('relevant_entry', 'plagiarism_moss').'</button>',
                                            $status_txt));
        }
        else
        {
        	$row1 = new html_table_row(array(
                                            $entry->rank,
                                            $user1_cell,
                                            $entry->user1percent.' %',
                                            $user2_cell,
                                            $entry->user2percent.' %',
                                            $entry->linecount,
                                            '<button type="button" onclick = view_code("'.$cmid.'","'.$entry->id.'")>'.get_string('view_code', 'plagiarism_moss').'</button>',
                                            $confirm_button,
                                            $status_txt));
        }

        $row1->id = $entry->id;
        
        $table->data[] = $row1;
    }
    return $table;
}


$cmid = optional_param('cmid', 0, PARAM_INT);
require_login();

$url = new moodle_url('/plagiarism/moss/result_pages/view_add.php?');
$url->param('cmid',$cmid);
$PAGE->set_url($url);
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('view_all_title', 'plagiarism_moss'));
$PAGE->set_heading(get_string('view_all_heading', 'plagiarism_moss'));
$PAGE->navbar->add(get_string('plugin_name', 'plagiarism_moss'));
$PAGE->navbar->add(get_string('results', 'plagiarism_moss'));
$PAGE->navbar->add(get_string('view_all', 'plagiarism_moss'));

global $DB;
$form = new view_all_filter_form(NULL, array('cmid'=>$cmid));
$table;

$currenttab='tab1';
$tabs = array();
$tabs[] = new tabobject('tab1', "view_all.php?cmid=".$cmid, get_string('view_all', 'plagiarism_moss'), 'View all', false);
$tabs[] = new tabobject('tab3', "statistics.php?cmid=".$cmid, get_string('statistics', 'plagiarism_moss'), 'Statistics', false);
    
if(($data = $form->get_data()) && confirm_sesskey())
{
	//print_object($data);
	//die;
    global $DB;
    //we don't use BETWEEN ... AND ... because different database software have different rules
    $params = array();
    $sql = "SELECT * FROM {moss_results}
            WHERE cmid = ? ";
    $params[] = $cmid;

    //add table type
    $table_type = $data->general['tabletype'];
    switch($table_type)
    {
    	 case 'view_all_table':
    	 	break;   
         case 'cross_table':
         	$sql .= "AND iscross = 1 ";
            break;
         case 'confirmed_table':    
            $sql .= "AND confirmed = 1 ";
            break;
         case 'unconfirmed_table':  
            $sql .= "AND confirmed = 0 ";
            break;
    }
    
    //add rank range
    if(isset($data->rank['rank_not_include']))
       $rank_not_include = $data->rank['rank_not_include'];
    $sql .= "AND rank >= ? AND rank <= ? ";
    if(isset($rank_not_include))//not include edges
    {//in our table 'rank','userpercent' and 'linecount' are all integer so ...
        $params[] = $data->rank['rank_from'] + 1;
        $params[] = $data->rank['rank_to'] - 1;
    }
    else 
    {
        $params[] = $data->rank['rank_from'];
        $params[] = $data->rank['rank_to'];
    }

    //add percentage range
    if(isset($data->percent['percent_not_include']));
        $percent_not_include = $data->percent['percent_not_include'];
    $sql .= "AND user1percent >= ? AND user1percent <= ? AND user2percent >= ? AND user2percent <= ? ";
    if(isset($percent_not_include))
    {
        $params[] = $data->percent['percent_from'] + 1;
        $params[] = $data->percent['percent_to'] - 1;
        $params[] = $data->percent['percent_from'] + 1;
        $params[] = $data->percent['percent_to'] - 1;
    }
    else 
    {
        $params[] = $data->percent['percent_from'];
        $params[] = $data->percent['percent_to'];
        $params[] = $data->percent['percent_from'];
        $params[] = $data->percent['percent_to'];
    }
    
    //add linecount range
    if(isset($data->lines['lines_not_include']))
        $lines_not_include = $data->lines['lines_not_include'];
    $sql .= "AND linecount >= ? AND linecount <= ? ";
    if(isset($lines_not_include))
    {
        $params[] = $data->lines['lines_from'] + 1;
        $params[] = $data->lines['lines_to'] - 1;
    }
    else 
    {
        $params[] = $data->lines['lines_from'];
        $params[] = $data->lines['lines_to'];
    }

    $results = $DB->get_records_sql($sql, $params);
    $table = initial_table($cmid ,$results, $table_type);
}
else
{
    //read all
    $results = $DB->get_records('moss_results', array('cmid'=>$cmid));
    $table = initial_table($cmid, $results, 'view_all_table');
}

//print HTML page
echo $OUTPUT->header();
$form->display();
print_tabs(array($tabs), $currenttab);
echo '<b>'.get_string('undo_redo_describtion', 'plagiarism_moss').'<b> <br/>';
echo '<button id="undo_button" disabled="true" type="button" onclick=undo()>'.get_string('undo', 'plagiarism_moss').'</button>';
echo '<button id="redo_button" disabled="true" type="button" onclick=redo()>'.get_string('redo', 'plagiarism_moss').'</button><br/><br/>';
echo html_writer::table($table);
echo $OUTPUT->footer();

echo  '<div style="visibility:hidden">';
echo  '<div id="confirm_prompt">'.get_string('confirm_prompt','plagiarism_moss').'</div>';
echo  '<div id="unconfirm_prompt">'.get_string('unconfirm_prompt','plagiarism_moss').'</div>';
echo  '<div id="nothing_to_undot">'.get_string('nothing_to_undo','plagiarism_moss').'</div>';
echo  '<div id="nothing_to_redo">'.get_string('nothing_to_redo','plagiarism_moss').'</div>';
echo  '<div id="parse_xml_exception">'.get_string('parse_xml_exception','plagiarism_moss').'</div>';
echo  '<div id="request_rejected">'.get_string('request_rejected','plagiarism_moss').'</div>';
echo  '<div id="confirm">'.get_string('confirm','plagiarism_moss').'</div>';
echo  '<div id="confirmed">'.get_string('confirmed','plagiarism_moss').'</div>';
echo  '<div id="unconfirm">'.get_string('unconfirm','plagiarism_moss').'</div>';
echo  '<div id="unconfirmed">'.get_string('unconfirmed','plagiarism_moss').'</div>';
echo  '</div>';

?>



<head>
<script type="text/javascript">

//'sortdir' indicate the sorting direction 
//"ASC" is the abbreviation of "ascend" and "DESC" for "descend"
sortdir = new Array("ASC","ASC","ASC","ASC","ASC","ASC","ASC","ASC","ASC");
//column index
sort_index = 0;
function sort_table(cell_index)
{
    sort_index = cell_index;
    var table = document.getElementById('result_table');
    //the head row is counted, so the actual row number is 'length -1',
    //start from (1 to 'length-1')
    var length = table.rows.length;

    for(var i = 1; i <= length - 2; i++)
        for(var j = i + 1; j <= length -1; j++)
        {
            var value1 = table.rows[i].cells[cell_index].innerHTML;
            var value2 = table.rows[j].cells[cell_index].innerHTML;
            if(string_to_number(value1) > string_to_number(value2))
            {
                if(sortdir[cell_index] == "DESC")
                    swap_innerHTML(table.rows[i], table.rows[j]);
            }
            else
            {
                if(sortdir[cell_index] == "ASC")
                    swap_innerHTML(table.rows[i], table.rows[j]);
            }
        }
    //remove sorting arrow
    var arrow = document.getElementById('arror_img');
    arrow.parentNode.removeChild(arrow);
    //add sorting arrow
    table.rows[0].cells[cell_index].innerHTML += '<img id ="arror_img" src="pix/'+sortdir[cell_index]+'.gif">';
    //change direction every time
    sortdir[cell_index] = (sortdir[cell_index] == "ASC") ? "DESC" : "ASC";	
}

//convert string to number, the function can convert:
//float int percentage
function string_to_number(string)
{
	//percentage
    if(string[string.length-1] == '%')
        return (parseFloat(string.substring(0,string.length-1)))/100;
    
    //we believe the string is valid
    var val1 = parseInt(string);
    var val2 = parseFloat(string);
    return val1 > val2 ? val1 : val2;
}

//swap tow table rows' innerHTML, swap tow rows.
function swap_innerHTML(row1, row2)
{
    var tmp;
    //swap cells
    tmp = row1.innerHTML;
    row1.innerHTML = row2.innerHTML;
    row2.innerHTML = tmp;
    //swap style
    tmp = row1.style.color;
    row1.style.color = row2.style.color;
    row2.style.color = tmp;
    //swap id
    tmp = row1.id;
    row1.id = row2.id;
    row2.id = tmp;
}

function view_code(cmid, entryid)
{
    var height = window.screen.availHeight;
    var width = window.screen.availWidth;
    var w_height = parseInt(height * 2 / 3);
    var w_width = parseInt(width * 4 / 5);
    var w_top = parseInt((height - w_height) / 2);
    var w_left = parseInt((width - w_width) / 2);
	window.open("view_code.php?cmid="+cmid+"&entryid="+entryid,
			    "view code",
			    "height="+w_height+",width="+w_width+",top="+w_top+",left="+w_left);
}

function relevant_entry(cmid, uid1, uid2)
{
    var height = window.screen.availHeight;
    var width = window.screen.availWidth;
    var w_height = parseInt(height * 2 / 3);
    var w_width = parseInt(width * 4 / 5);
    var w_top = parseInt((height - w_height) / 2);
    var w_left = parseInt((width - w_width) / 2);
    window.open("relevant?cmid="+cmid+"&uid1="+uid1+"&uid2="+uid2,
    	        "Relevant entrys",
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
	//var element = document.getElementById(id);
	//if(element != null)
	//	return element.innerHTML;
	//else
	if(default_txt == 'Confirmed')
		return '<font color="#FF0000"><b>Confirmed</b></font>';
	return default_txt;	
}

function confirm_entry(element)
{
	if(confirm(get_label('confirm_prompt', 'Are you sure you want to confirm this entry ?')))
	{
        var entryid = element.parentNode.parentNode.id;
        connect_server('view_all_page', 
    	               'confirm', 
    	                entryid, 
    	               'new');
	}
}

function confirm_by_entry_id(id)
{
    connect_server('view_all_page', 
                   'confirm', 
                    id, 
                   'new');
}

function unconfirm_entry(element)
{ 
	if(confirm(get_label('unconfirm_prompt', 'Are you sure you want to unconfirm this entry ?')))
	{
        var entryid = element.parentNode.parentNode.id;
        connect_server('view_all_page', 
    	               'unconfirm', 
    	                entryid, 
    	               'new');
	}
}

function unconfirm_by_entry_id(id)
{  
    connect_server('view_all_page', 
    	           'unconfirm', 
    	            id, 
    	           'new');
}

//In this function we create a "undo & redo" stack, use an array as a stack,
//every items in the stack array is an array that contains an operation and the corresponding table entry details,
//Items structure detail = ('entryinnerHtml' 'id', 'request');
//we store the 'request', use this we can perform 'undo' or 'redo' easily.
function undo_redo_stack()
{
    this.size = 0;//stack actual size
    this.pointer = 0;//index of stack head
    this.stack = new Array();//stack[1] == stack bottom, stack[size] == stack head
    this.undo_button = document.getElementById("undo_button");
    this.redo_button = document.getElementById("redo_button");

    
    //undo an operation, do not move pointer,
    //move pointer when server accepted the request,and responsed.
    //return array(0->'id', 1->'counter-request')
    //return null if stack empty
    this.undo_operation = function()
    {
        if(this.pointer == 0 || this.pointer > this.size)//stack empty
        {
            this.restore_button()
            return null
        }

        var op = new Array();
        op[0] = this.stack[this.pointer][1];//id
        op[1] = (this.stack[this.pointer][2] == 'confirm') ? 'unconfirm' : 'confirm';//counter-operation
        this.undo_button.disable = true;//low bandwidth concern, once a time
        this.redo_button.disable = true;//low bandwidth concern, once a time
        return op;
    }
    
    this.undo_operation_succeed = function()
    {
        var item = this.stack[this.pointer];
        this.pointer -= 1;
        this.restore_button();
        return item;
    }
    
    this.restore_button = function()
    {
        if(this.pointer != 0)//still able to undo
            this.undo_button.disabled = false;
        else   
            this.undo_button.disabled = true;

        if(this.pointer < this.size)//still able to redo  
            this.redo_button.disabled = false;
        else
            this.redo_button.disabled = true;
    }
    
    //redo an operation, do not move pointer,
    //move pointer when server accepted the request,and responsed.
    //return array(0->'id', 1->'request')
    this.redo_operation = function()
    {
        if(this.pointer >= this.size)//nothing to redo or stack error
        {
            this.restore_button();
            return null;
        }
        
        var op = new Array();
        op[0] = this.stack[this.pointer + 1][1];//id
        op[1] = this.stack[this.pointer + 1][2];//operation  
        this.undo_button.disable = true;//low bandwidth concern, once a time
        this.redo_button.disable = true;//low bandwidth concern, once a time
        return op;
    }
    
    this.redo_operation_succeed = function()
    {
        this.pointer += 1;
        this.restore_button();
        return this.stack[this.pointer];//not need to return anything, vain return
    }

    //push an new operation into the stack.
    //firstly clear those item that have a index high than pointer,
    //to do this, we simply change the stack size ($this.size).
    //secondly we push the new operation in the stack.
    this.new_operation = function(entryinnerHtml, id, request)
    {
        this.size = this.pointer;
        this.stack[this.pointer + 1] = new Array(entryinnerHtml, id, request);
        this.size += 1;
        this.pointer += 1;
        this.redo_button.disabled = true;//unable to redo after a new operation added
        this.undo_button.disabled = false;//able to undo
    }   
}

//undo_redo_stack
op_stack = new undo_redo_stack();

function redo()
{
    var op = op_stack.redo_operation();
    if(op == null)
        alert(get_label('nothing_to_redo', 'Nothing to redo.'));
    else
    	connect_server('view_all_page', 
    	    	        op[1], 
    	    	        op[0], 
    	    	       'redo');
}

function undo()
{	
    var op = op_stack.undo_operation();
    if(op == null)
        alert(get_label('nothing_to_undo', 'Nothing to undo.'));   
    else
    	connect_server('view_all_page', 
    	    	        op[1], 
    	    	        op[0], 
    	    	       'undo');
}

function connect_server(page, request, entryid, type)
{
    if (window.XMLHttpRequest)// code for IE7+, Firefox, Chrome, Opera, Safari
        xmlhttp=new XMLHttpRequest();
    else// code for IE6, IE5
        xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
    
    xmlhttp.onreadystatechange=function()
    {
        if(xmlhttp.readyState==4 && xmlhttp.status==200)
        {
            try{
            	var response = xmlhttp.responseXML.documentElement.getElementsByTagName("RESPONSE");
                var status = response[0].getElementsByTagName("STATUS")[0].firstChild.nodeValue;
                var request = response[0].getElementsByTagName("REQUEST")[0].firstChild.nodeValue;
                var id = response[0].getElementsByTagName("ID")[0].firstChild.nodeValue;
                var op_type = response[0].getElementsByTagName("TYPE")[0].firstChild.nodeValue;
            }
            catch(er)
            {
                alert(get_label('parse_xml_exception', 'Parse XML exception.'));
                op_stack.restore_button();
                return;
            }
            
            if(status == 1)
            {
                alert(get_label('request_rejected', 'Request rejected by server.'));
                op_stack.restore_button();
                return;
            }
            else
            {
                if(status == 0)
                {
                    var table = document.getElementById('result_table'); 
                    var table_type = table.summary;
                    switch(table_type)
                    {
                    case 'view_all_table':     
                    case 'cross_table':
                        modify_view_all_table(id, request, op_type);
                        break;
                    case 'confirmed_table':    
                        modify_confirmed_table(id, request, op_type);
                        break;
                    case 'unconfirmed_table':  
                        modify_unconfirmed_table(id, request, op_type);
                        break;
                    }
                }
                return;
            }
        }
    }
    xmlhttp.open("GET","ajax_response.php?page="+page+"&request="+request+"&id="+entryid+"&type="+type,true);
    xmlhttp.send();
}

function modify_view_all_table(id, request, op_type)
{
	var table = document.getElementById('result_table'); 
    var row = null;
    var length = table.rows.length;
    for(var i = 0; i <= length - 1; i++)
        if(table.rows[i].id == id)
        {
            row = table.rows[i];
            break;
        }

    if(row == null)
        return;
    
    switch(op_type)
    {
    case 'undo':
        op_stack.undo_operation_succeed();
        break;
    case 'redo':
        op_stack.redo_operation_succeed();
        break;
    case 'new':
    	op_stack.new_operation(row.innerHTML, id, request);
        break;
    default:
        break;
    }
    
    //change row status and request button
    if(request == 'confirm')
    {
    	row.cells[9].innerHTML = get_label('confirmed', 'Confirmed');
    	var bl = get_label('unconfirm', 'Unconfirm');
    	row.cells[7].innerHTML = '<button type="button" onclick = unconfirm_entry(this)>'+bl+'</button>';
    }
    else
    {
    	row.cells[9].innerHTML = get_label('unconfirmed', 'Unconfirmed');
    	var bl = get_label('confirm', 'Confirm');
    	row.cells[7].innerHTML = '<button type="button" onclick = confirm_entry(this)>'+bl+'</button>';
    }   
}

function modify_confirmed_table(id, request, op_type)
{  
	var table = document.getElementById('result_table'); 
    var length = table.rows.length;
    var row = null;
    	
    switch(op_type)
    {
    case 'undo':
        if(request == 'confirm')//in a normal case undo's request must be confirm
        {    
            //add entry to table, and sort
            var entry = op_stack.undo_operation_succeed();
            //it's more convenient that we insert the entry at first row, and call sort function
            row = table.insertRow(1);
            row.innerHTML = entry[0];
            row.id = entry[1];
            
            var bl = get_label('unconfirm', 'Unconfirm');
            row.cells[7].innerHTML = '<button type="button" onclick = unconfirm_entry(this)>'+bl+'</button>';
            row.cells[8].innerHTML = get_label('confirmed', 'Confirmed');

            //change back sorting order, and sort table 
            sortdir[sort_index] = (sortdir[sort_index] == "ASC") ? "DESC" : "ASC";
            sort_table(sort_index);
        }
        break;
    case 'redo':
    case 'new' :
        //this is a confirmed_table, the only operation a user can do is unconfirm an entry.
        //redo's request will always be 'unconfirm'
        if(request == 'unconfirm')//otherwise error
        {
            for(var i = 0; i <= length - 1; i++)
                if(table.rows[i].id == id)
                {    
                    row = table.rows[i];
                    break;
                }
            
            if(row == null)
                return;
            
            if(op_type == 'redo')
            	op_stack.redo_operation_succeed();
            else  
                op_stack.new_operation(row.innerHTML, id, request);
            //remove entry
            row.parentNode.removeChild(row);
        } 
        break;
    default    ://error unrecognizable op_type
        break;
    }

}

function modify_unconfirmed_table(id, request, op_type)
{  
    var table = document.getElementById('result_table'); 
    var row = null;
    var length = table.rows.length;
    
    switch(op_type)
    {
    case 'undo':
        if(request == 'unconfirm')//in a normal case undo's request must be confirm
        {    
            var entry = op_stack.undo_operation_succeed();
            row = table.insertRow(1);
            row.innerHTML = entry[0];
            row.id = entry[1];
            
            var bl = get_label('confirm', 'Confirm');
            row.cells[7].innerHTML = '<button type="button" onclick = confirm_entry(this)>'+bl+'</button>';
            row.cells[8].innerHTML = get_label('unconfirmed', 'Unconfirmed');

            sortdir[sort_index] = (sortdir[sort_index] == "ASC") ? "DESC" : "ASC";
            sort_table(sort_index);
        }
        break;
    case 'redo':
    case 'new' :
        //it's a unconfirmed_table, the only operation a user can do is confirm an entry.
        //redo's request will always be 'confirm'
        if(request == 'confirm')//otherwise error
        {
            for(var i = 0; i <= length - 1; i++)
                if(table.rows[i].id == id)
                {
                    row = table.rows[i];
                    break;
                }

            if(row == null)
            {
                alert('entry id = '+id+' not found');
                return;
            }
            
            if(op_type == 'redo')
            	op_stack.redo_operation_succeed();
            else
            	op_stack.new_operation(row.innerHTML, id, request);
            //remove entry
            row.parentNode.removeChild(row);
        } 
        break;
    default    ://error unrecognizable op_type
        break;
    }
}

</script>
</head>
