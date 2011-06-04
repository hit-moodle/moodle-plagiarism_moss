<?php
    require_once(dirname(dirname(__FILE__)) . '/../config.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once($CFG->libdir.'/plagiarismlib.php');
    require_once($CFG->dirroot.'/plagiarism/moss/lib.php');
    require_once($CFG->dirroot.'/lib/formslib.php');
    
    class log_filter_form extends moodleform 
    {
        function definition () 
        {
            global $CFG;
            $mform =& $this->_form;
            $mform->addElement('html', get_string('mossexplain', 'plagiarism_moss'));
            $this->add_action_buttons(true);  
        }
    }
    
    function init_table($DB_results)
    {
        $table = new html_table();
        $table->id = 'errors_table';
        
        //initial column head
        $date_cell = new html_table_cell('<font color="#3333FF">Date</font>');
        $date_cell -> attributes['onclick'] = 'sort_table(0)';
        $date_cell -> style = 'cursor:move';
        
        $type_cell = new html_table_cell('<font color="#3333FF">Error type</font>');
        $type_cell -> attributes['onclick'] = 'sort_table(1)';
        $type_cell -> style = 'cursor:move';
    
        $describtion_cell = new html_table_cell('Describtion');
        $status_cell = new html_table_cell('Status');
        $test_cell = new html_table_cell('Test');
    
        $table->head = array($date_cell,
                             $type_cell,
                             $describtion_cell,
                             $status_cell,
                             $test_cell);
        $table->align = array ('center','center', 'center', 'center', 'center');
        $table->width = "100%";  
        
        foreach($DB_results as $entry)
        {
            //unsolved
            if($entry->status == 0)
            {
                $status = "Unsolved"; 	
                $status_button = '<button type="button" onclick = test(this)>test</button>';
            }
            else 
            {
                $status = "Solved";
                $status_button = '';
            }
            $row1 = new html_table_row(array(
                                            $entry->errordate,
                                            $entry->type,
                                            $entry->describtion,
                                            $status,
                                            $status_button
                                            ));
            $row1->id = $entry->id;
            if($entry->status == 0)
                $row1->style = 'color:red';
            else 
                $row1->style = 'color:black';
            $table->data[] = $row1;
        }
        
        return $table;

    }
    
    require_login();
    admin_externalpage_setup('plagiarismmoss');
    $context = get_context_instance(CONTEXT_SYSTEM);
    require_capability('moodle/site:config', $context, $USER->id, true, "nopermissions");
    $form = new log_filter_form();
    global $DB;
    $table;
    
    $currenttab='tab2';
    $tabs = array();
    $tabs[] = new tabobject('tab1', 'settings.php', 'Moss general settings', 'Moss general settings', false);
    $tabs[] = new tabobject('tab2', 'log.php', 'Moss error log', 'Moss error log', false);
    $tabs[] = new tabobject('tab3', 'backup.php', 'Plugin backup', 'Plugin_backup', false);
    
    if(($data = $form->get_data()) && confirm_sesskey())
    {
        global $DB;
        //read DB accoding to form data
        $table = init_table(null);
    }
    else
    {
        //read all
        $result = $DB->get_records('moss_plugin_errors');
        $table = init_table($result);
    }

    echo $OUTPUT->header();
    print_tabs(array($tabs), $currenttab);
    echo $OUTPUT->box_start();
    $form->display();
    echo $OUTPUT->box_end();
    echo html_writer::table($table);
    echo $OUTPUT->footer();
   
?>



<head>
<script type="text/javascript">

//'sortdir' indicate the sorting direction 
//"ASC" is the abbreviation of "ascend" and "DESC" for "descend"
sortdir = new Array("ASC","ASC","ASC","ASC","ASC");

function sort_table(cell_index)
{
    var table = document.getElementById('errors_table');
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

function test(element)
{
	alert(element.parentNode.parentNode.id);
}

function connect_server(pageid, requestid, value)
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
            //document.getElementById("txtHint").innerHTML=xmlhttp.responseText;
        }
    }
    xmlhttp.open("GET","javascript.php?pid="+pageid+"&rid="+requestid+"&value="+value,true);
    xmlhttp.send();
}
</script>
</head>
