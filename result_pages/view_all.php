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
        $mform =& $this->_form;
        $choices = array('No','Yes');
        $mform->addElement('html', get_string('mossexplain', 'plagiarism_moss'));
        $this->add_action_buttons(true);    
    }
}

function initial_table($DB_results)
{
    $table = new html_table();
    $table->id = 'view_all_table';

    //initialize sortable columns of the table
    $rank_cell = new html_table_cell('<font color="#3333FF">Rank</font>');
    $rank_cell -> attributes['onclick'] = 'sort_table(0)';
    $rank_cell -> style = 'cursor:move';
    
    $match1_cell = new html_table_cell('<font color="#3333FF">Match percent 1</font>');
    $match1_cell -> attributes['onclick'] = 'sort_table(2)';
    $match1_cell -> style = 'cursor:move';
    
    $match2_cell = new html_table_cell('<font color="#3333FF">Match percent 2</font>');
    $match2_cell -> attributes['onclick'] = 'sort_table(4)';
    $match2_cell -> style = 'cursor:move';
    
    $line_count_cell = new html_table_cell('<font color="#3333FF">Lines match</font>');
    $line_count_cell -> attributes['onclick'] = 'sort_table(5)';
    $line_count_cell -> style = 'cursor:move';
    
    //initialize unsortable columns
    $name1_cell = new html_table_cell('student 1');
    $name2_cell = new html_table_cell('student 2');
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
						  
    $table->align = array ("center","center", "center", "left","center", "center", "center","center" ,"center");
    $table->width = "100%";
	
    foreach($DB_results as $entry)
    {
    	if($entry->confirmed == 1)
    	{
    		$status = "confirmed"; 	
    		$status_button = '<button type="button" onclick = unconfirm(this)>Cancel</button>';
    	}
    	else 
    	{
    		$status = "unconfirmed";
    		$status_button = '<button type="button" onclick = confirm(this)>Confirm</button>';
    	}
    	$row1 = new html_table_row(array(
    	                                $entry->rank,
    	                                $entry->user1id,
    	                                $entry->user1percent,
    	                                $entry->user2id,
    	                                $entry->user2percent,
    	                                $entry->linecount,
    	                                '<button type="button" onclick = view_code(this,"'.$entry->link.'")>View code</button>',
    	                                $status_button,
    	                                $status
    	                                )
    	                                );
        $row1->id = $entry->id;
        if($entry->confirmed == 1)
            $row1->style = 'color:red';
        else 
            $row1->style = 'color:black';
        $table->data[] = $row1;
    }
    return $table;
}

function initial_table2()
{
    $table = new html_table();
    $table->align = array ("center","center");
    $row1 = new html_table_row(array(
                                    '<button type="button" onclick = undo()>Undo</button>',
                                    '<button type="button" onclick = redo()>Redo</button>'   
                                    )
                                    );
    $table->data[] = $row1;
    return $table;
}

require_login();
$PAGE->set_url('/plagiarism/moss/result_pages/view_all.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
$PAGE->set_title('anti-plagiarism view all page');
$PAGE->set_heading('View all page');
$PAGE->navbar->add('anti-plagiarism');
$PAGE->navbar->add('results');
$PAGE->navbar->add('view all');

global $DB;
$form = new view_all_filter_form();
$cmid = optional_param('id', 0, PARAM_INT);  
$table;
$table2;

$currenttab='tab1';
$tabs = array();
$tabs[] = new tabobject('tab1', "view_all.php?id=".$cmid, 'View all', 'View all', false);
$tabs[] = new tabobject('tab2', "confirmed.php?id=".$cmid, 'Confirmed', 'Confirmed', false);
$tabs[] = new tabobject('tab3', "statistics.php?id=".$cmid, 'Statistics', 'Statistics', false);
    
if(($data = $form->get_data()) && confirm_sesskey())
{
    global $DB;
    //read DB accoding to form data
    $table = initial_table($result);
    $table2 = initial_table2();
}
else
{
    //read all
    $result = $DB->get_records('moss_results', array('cmid'=>$cmid));
    $table = initial_table($result);
    $table2 = initial_table2();
}

//print HTML page
echo $OUTPUT->header();
echo $OUTPUT->box_start();
$form->display();
echo $OUTPUT->box_end();
print_tabs(array($tabs), $currenttab);
echo html_writer::table($table2);
echo html_writer::table($table);
echo $OUTPUT->footer();
?>



<head>
<script type="text/javascript">

//'sortdir' indicate the sorting direction 
//"ASC" is the abbreviation of "ascend" and "DESC" for "descend"
sortdir = new Array("ASC","ASC","ASC","ASC","ASC","ASC","ASC","ASC","ASC");

function sort_table(cell_index)
{
    var table = document.getElementById('view_all_table');
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
                if(sortdir[cell_index] == "ASC")
                    swap_innerHTML(table.rows[i], table.rows[j]);
            }
            else
            {
                if(sortdir[cell_index] == "DESC")
                    swap_innerHTML(table.rows[i], table.rows[j]);
            }
        }
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
	//element.innerHTML = '<input type = "textbox">fuck</input>';
	var entryid = element.parentNode.parentNode.id;
	alert(entryid+"    "+link);
	//connect_server('view_all_page', 'view_code', entryid);
	
}

function confirm(element)
{
	var entryid = element.parentNode.parentNode.id;
	alert(entryid);
	connect_server('view_all_page', 'confirm', entryid);
}

function unconfirm(element)
{
    var entryid = element.parentNode.parentNode.id;
	alert(entryid);
	connect_server('view_all_page', 'unconfirm', entryid);
}

function redo()
{
    alert('redo');
}

function undo()
{
    alert('undo');
}

function connect_server(page, request, entryid)
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
            //receive a xml file contain requestid and value
            //<response>
            //    <status>status</status> 1==abnormal 0==normal
            //    <requestid>requestid</requestid>
            //    <value>value</value> 
            //</response>
            //document.getElementById("txtHint").innerHTML=xmlhttp.responseText;
        }
    }
    xmlhttp.open("GET","ajax.php?page="+page+"&request="+request+"&value="+entryid,true);
    xmlhttp.send();
}
</script>
</head>







<!-- 
 $table1 = new html_table();
$table1->width = "100%";
$table1->data[] = array('<iframe src="http://www.baidu.com" frameborder="no" border="0" marginwidth="0" marginheight="0" width="100%"></iframe>',
						'<iframe src="http://www.baidu.com" frameborder="no" border="0" marginwidth="0" marginheight="0" width="100%"></iframe>'
					   );
echo html_writer::table($table1);
-->


