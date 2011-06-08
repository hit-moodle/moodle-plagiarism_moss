<?php 
require_once(dirname(dirname(__FILE__)) . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/lib/form/button.php');
require_once($CFG->libdir.'/tablelib.php');
global $CFG;

class view_all_filter_form extends moodleform 
{

    function definition () 
    {
        global $CFG;
        $cmid  = $this->_customdata['cmid'];
         
        $mform =& $this->_form;
        $mform->addElement('header', 'head', get_string('viewallpagefilter', 'plagiarism_moss'));
        $choices = array('view_all_table' => 'view all entrys',
                         'confirmed_table' => 'confirmed entrys',
                         'unconfirmed_table' => 'unconfirmed entrys',
                         'cross_table' => 'cross-course plagiarism entrys');
        $general[] = $mform->createElement('select','tabletype', get_string('tabletype','plagiarism_moss'),$choices);
        $general[] = $mform->createElement('submit', 'submit1', 'Submit');
        $mform->addElement('group', 'general', 'Display table type', $general);
        
        $mform->addElement('text', 'student', 'Student name');
        
        $rank[] = $mform->createElement('text', 'rank_from', null);
        $rank[] = $mform->createElement('text', 'rank_to', null);
        $mform->addElement('group', 'rank', 'Rank range', $rank);
        
        $percent[] = $mform->createElement('text', 'percent_from', null);
        $percent[] = $mform->createElement('text', 'percent_to', null);
        $mform->addElement('group', 'percent', 'Match percentage range', $percent);
        
        $lines[] = $mform->createElement('text', 'lines_from', null);
        $lines[] = $mform->createElement('text', 'lines_to', null);
        $mform->addElement('group', 'lines', 'Match lines range', $lines);
        
        $mform->addElement('submit', 'submit2', 'Submit');
        
        $mform->setAdvanced('student');
        $mform->setAdvanced('rank');
        $mform->setAdvanced('percent');
        $mform->setAdvanced('lines');
        $mform->setAdvanced('submit2');
        
        //url param
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $this->set_data(array('id'=>$cmid));
                 
    }

}


function initial_table($DB_results, $tablesummary)
{
    global $DB;
    $table = new html_table();
    $table->id = 'result_table';
    $table->summary = $tablesummary;

    //initialize sortable columns of the table
    $rank_cell = new html_table_cell('<font color="#3333FF">Rank<span> </span><img id ="arror_img" src="pix/DESC.gif"></font>');
    $rank_cell -> attributes['onclick'] = 'sort_table(0)';
    $rank_cell -> style = 'cursor:move';
    
    $match1_cell = new html_table_cell('<font color="#3333FF">Match percent 1<span> </span></font>');
    $match1_cell -> attributes['onclick'] = 'sort_table(2)';
    $match1_cell -> style = 'cursor:move';
    
    $match2_cell = new html_table_cell('<font color="#3333FF">Match percent 2<span> </span></font>');
    $match2_cell -> attributes['onclick'] = 'sort_table(4)';
    $match2_cell -> style = 'cursor:move';
    
    $line_count_cell = new html_table_cell('<font color="#3333FF">Lines match<span> </span></font>');
    $line_count_cell -> attributes['onclick'] = 'sort_table(5)';
    $line_count_cell -> style = 'cursor:move';
    
    //initialize unsortable columns
    $name1_cell = new html_table_cell('Student 1');
    $name2_cell = new html_table_cell('Student 2');
    $detail_cell = new html_table_cell('Code detail');
    $confirm_cell = new html_table_cell('Confirmed');
    $status_cell = new html_table_cell('Status');
    
    //add head cells to $table, notice that the order of head cells isn't random,
    //for example $rank_cell must at the head of the array, 
    //because if the 'onclick' event is triggered JS function 'sort(0)' will be called.
    $table->head = array ($rank_cell,
                          $name1_cell,
                          $match1_cell,
                          $name2_cell,
                          $match2_cell,
                          $line_count_cell,
                          $detail_cell,
                          $confirm_cell,
                          $status_cell);
						  
    $table->align = array ("center","left", "center", "left","center", "center", "center","center" ,"center");
    $table->width = "100%";
	
    foreach($DB_results as $entry)
    {
        if($entry->confirmed == 1)
        {
            $status_txt = "confirmed"; 	
            $confirm_button_txt = '<button type="button" onclick = unconfirm(this)>Cancel</button>';
        }
        else 
        {
            $status_txt = "unconfirmed";
            $confirm_button_txt = '<button type="button" onclick = confirm(this)>Confirm</button>';
        }
        
        $user1 = $DB->get_record('user', array('id'=>$entry->user1id));
        $user2 = $DB->get_record('user', array('id'=>$entry->user2id));
        
        $user1_cell = new html_table_cell('<font color="#3333FF">'.$user1->firstname.' '.$user1->lastname.'</font>');
        $user1_cell -> attributes['onclick'] = 'show_user_profile('.$entry->user1id.')';
        $user1_cell -> style = 'cursor:move';
        $user2_cell = new html_table_cell('<font color="#3333FF">'.$user2->firstname.' '.$user2->lastname.'</font>');
        $user2_cell -> attributes['onclick'] = 'show_user_profile('.$entry->user2id.')';
        $user2_cell -> style = 'cursor:move';
        
        $row1 = new html_table_row(array(
                                        $entry->rank,
                                        $user1_cell,
                                        $entry->user1percent.' %',
                                        $user2_cell,
                                        $entry->user2percent.' %',
                                        $entry->linecount,
                                        '<button type="button" onclick = view_code(this,"'.$entry->link.'")>View code</button>',
                                        $confirm_button_txt,
                                        $status_txt
    	                                )
    	                                );
        $row1->id = $entry->id;
      /*
        if($entry->confirmed == 1)
            $row1->style = 'color:red';
        else 
            $row1->style = 'color:black';
       */
        $table->data[] = $row1;
    }
    return $table;
}


$cmid = optional_param('id', 0, PARAM_INT);
require_login();
$url = new moodle_url('/plagiarism/moss/result_pages/view_add.php?');
$url->param('id',$cmid);
$PAGE->set_url($url);
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
$PAGE->set_title('anti-plagiarism view all page');
$PAGE->set_heading('View all page');
$PAGE->navbar->add('Anti-plagiarism');
$PAGE->navbar->add('Results');
$PAGE->navbar->add('View all');

global $DB;
$form = new view_all_filter_form(NULL, array('cmid'=>$cmid));
$table;

$currenttab='tab1';
$tabs = array();
$tabs[] = new tabobject('tab1', "view_all.php?id=".$cmid, 'View all', 'View all', false);
$tabs[] = new tabobject('tab3', "statistics.php?id=".$cmid, 'Statistics', 'Statistics', false);
    
if(($data = $form->get_data()) && confirm_sesskey())
{
    global $DB;
    print_object($data);
    //cross table 与 view all table 一样 tablesummary 都设置为一样
    $table = initial_table($result, '');
}
else
{
    //read all
    $result = $DB->get_records('moss_results', array('cmid'=>$cmid));
    $table = initial_table($result, 'view_all_table');
}

//print HTML page
echo $OUTPUT->header();
$form->display();
print_tabs(array($tabs), $currenttab);
echo '<b>Press "Undo" button to reverse an operation...<b> <br/>';
echo '<button id="undo_button" disabled="true" type="button" onclick=undo()>Undo</button>';
echo '<button id="redo_button" disabled="true" type="button" onclick=redo()>Redo</button><br/><br/>';
echo html_writer::table($table);
echo $OUTPUT->footer();
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

function view_code(element, link)
{
    var entryid = element.parentNode.parentNode.id;
    var height = window.screen.availHeight;
    var width = window.screen.availWidth;
    var w_height = parseInt(height * 2 / 3);
    var w_width = parseInt(width * 4 / 5);
    var w_top = parseInt((height - w_height) / 2);
    var w_left = parseInt((width - w_width) / 2);
	window.open("view_code/code.php","view code",
			    "height="+w_height+",width="+w_width+",top="+w_top+",left="+w_left); 
	//connect_server('view_all_page', 'view_code', entryid);
	
}
function show_user_profile(uid)
{
    alert('show user profile uid = '+uid);
}
function confirm(element)
{
    var entryid = element.parentNode.parentNode.id;
    connect_server('view_all_page', 'confirm', entryid, 'new');
}

function unconfirm(element)
{  
    var entryid = element.parentNode.parentNode.id;
    connect_server('view_all_page', 'unconfirm', entryid, 'new');
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
op_stack = new undo_redo_stack();;

function redo()
{	
    var op = op_stack.redo_operation();
    if(op == null)
        alert('unable to redo');
    else
    	connect_server('view_all_page', op[1], op[0], 'redo');
}

function undo()
{
    var op = op_stack.undo_operation();
    if(op == null)
        alert('unable to undo');   
    else{
    	connect_server('view_all_page', op[1], op[0], 'undo');
    }
}

function connect_server(page, request, entryid, type)
{
    if (window.XMLHttpRequest)
    {   // code for IE7+, Firefox, Chrome, Opera, Safari
        xmlhttp=new XMLHttpRequest();
    }
    else
    {   // code for IE6, IE5
        xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
    }
    
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
                alert('xml parse exception');
                op_stack.restore_button();
                return;
            }
            if(status == 1)
            {    
                alert("request rejected");
                op_stack.restore_button();
                return;
            }
            else
                if(status == 0)
                {
                    alert("request accepted");
                    var table = document.getElementById('result_table'); 
                    var table_type = table.summary;
                    switch(table_type)
                    {
                    case 'view_all_table':     
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
        }
    }
    xmlhttp.open("GET","result_page_ajax.php?page="+page+"&request="+request+"&id="+entryid+"&type="+type,true);
    xmlhttp.send();
}

function modify_view_all_table(id, request, op_type)
{
	var table = document.getElementById('result_table'); 
    var row = null;
    var length = table.rows.length;
    for(var i = 0; i <= length - 1; i++)
        if(table.rows[i].id == id)
            row = table.rows[i];
    if(row == null)
    {
        alert('entry id = '+id+' not found');
        return;
    }

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
    var status_txt = (request == 'confirm') ? 'confirmed' : 'unconfirmed';
    row.cells[8].innerHTML = status_txt;
    
    if(status_txt == 'confirmed')
    	row.cells[7].innerHTML = '<button type="button" onclick = unconfirm(this)>Cancel</button>';
    else
    	row.cells[7].innerHTML = '<button type="button" onclick = confirm(this)>Confirm</button>';     
   
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
            row.cells[7].innerHTML = '<button type="button" onclick = unconfirm(this)>Cancel</button>';
            row.cells[8].innerHTML = 'confirmed';

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
                    row = table.rows[i];
            if(row == null)
            {
                alert('entry id = '+id+' not found');
                return;
            }
            
            if(op_type == 'redo')
            	op_stack.redo_operation_succeed();
            else
                if(op_type == 'new')
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
            row.cells[7].innerHTML = '<button type="button" onclick = confirm(this)>Confirm</button>';
            row.cells[8].innerHTML = 'unconfirmed';

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
                    row = table.rows[i];
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