<?php
    require_once(dirname(dirname(__FILE__)) . '/../config.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once($CFG->libdir.'/plagiarismlib.php');
    require_once($CFG->dirroot.'/plagiarism/moss/lib.php');
    require_once($CFG->dirroot.'/lib/formslib.php');
    
    /**
     * 
     * Enter description here ...
     * @author ycc
     *
     */
    class log_filter_form extends moodleform 
    {
    	
	    function definition () 
	    {
           
	    }
    }

    /**
     * 
     * Enter description here ...
     * @param unknown_type $DB_results
     */
    function init_table($DB_results)
    {
    	$cnf_xml = new config_xml();
        if($cnf_xml->get_config('enable_log') != 'YES')
            return NULL;
        $table = new html_table();
        $table->id = 'errors_table';
        
        //initial column head
        $date_cell = new html_table_cell(get_string('error_date', 'plagiarism_moss').'</font>');
        $date_cell -> attributes['onclick'] = 'sort_table(0)';
        $date_cell -> style = 'cursor:move';
        
        $type_cell = new html_table_cell('<font color="#3333FF">'.get_string('error_type', 'plagiarism_moss').'</font>');
        $type_cell -> attributes['onclick'] = 'sort_table(1)';
        $type_cell -> style = 'cursor:move';
    
        $description_cell = new html_table_cell(get_string('error_description', 'plagiarism_moss'));
        $solution_cell = new html_table_cell(get_string('error_solution', 'plagiarism_moss'));
        $status_cell = new html_table_cell(get_string('error_status', 'plagiarism_moss'));
        $test_cell = new html_table_cell(get_string('error_test', 'plagiarism_moss'));
    
        $table->head = array($date_cell,
                             $type_cell,
                             $description_cell,
                             $solution_cell,
                             $status_cell,
                             $test_cell);
        $table->align = array ('center','center', 'center', 'center', 'center', 'center');
        $table->width = "100%";  
        
        foreach($DB_results as $entry)
        {
            //unsolved
            if($entry->errstatus == 1)
            {
                $status = get_string('unsolved', 'plagiarism_moss'); 	
                $status_button = '<button type="button" onclick = test("'.$entry->id.'")>'.get_string('test', 'plagiarism_moss').'</button>';
            }
            else 
            {
                $status = get_string('solved', 'plagiarism_moss');;
                $status_button = '';
            }
            if($entry->testable == 0)
            {
            	$status = 'Unknown';
            	$status_button = '';
            }
            $row = new html_table_row(array(
                                            userdate($entry->errdate),
                                            $entry->errtype,
                                            $entry->errdescription,
                                            $entry->errsolution,
                                            $status,
                                            $status_button
                                            ));
            $row->id = $entry->id;
            if(($entry->errstatus == 1) && ($entry->testable == 1))
                $row->style = 'color:red';
            $table->data[] = $row;
        }
        
        return $table;
    }
    
    require_login();
    admin_externalpage_setup('plagiarismmoss');
    $context = get_context_instance(CONTEXT_SYSTEM);
    require_capability('moodle/site:config', $context, $USER->id, true, "nopermissions");
    //$form = new log_filter_form();
    global $DB;
    $table;
    
    $currenttab='tab2';
    $tabs = array();
    $tabs[] = new tabobject('tab1', 
                            'settings.php', 
                            get_string('general_settings', 'plagiarism_moss'), 
                            'General_settings', 
                            false);
                            
    $tabs[] = new tabobject('tab2', 
                            'log.php', 
                            get_string('error_log', 'plagiarism_moss'), 
                            'Error_log', 
                            false);
    
    //if(($data = $form->get_data()) && confirm_sesskey())
    //{
    //    global $DB;
        //read DB accoding to form data
    //    $table = init_table(null);
    //}
    //else
   // {
        //read all
        $result = $DB->get_records('moss_plugin_errors');
        $table = init_table($result);
    //}

    echo $OUTPUT->header();
    print_tabs(array($tabs), $currenttab);
    //echo $OUTPUT->box_start();
    //$form->display();
    //echo $OUTPUT->box_end();
    if($table != NULL)
    echo html_writer::table($table);
    echo $OUTPUT->footer();

echo  '<div style="visibility:hidden">';
echo  '<div id="parse_xml_exception">'.get_string('parse_xml_exception','plagiarism_moss').'</div>';
echo  '<div id="error_solved">'.get_string('error_solved','plagiarism_moss').'</div>';
echo  '<div id="error_still_unsolved">'.get_string('error_still_unsolved','plagiarism_moss').'</div>';
echo  '<div id="unsolved">'.get_string('unsolved','plagiarism_moss').'</div>';
echo  '<div id="solved">'.get_string('solved','plagiarism_moss').'</div>';
echo  '</div>';
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

function get_label(id, default_txt)
{
	var element = document.getElementById(id);
	if(element != null)
		return element.innerHTML;
	else
		return default_txt;	
}

function test(id)
{
	connect_server('error_test', id);
}

function connect_server(page, entryid)
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
                var id = response[0].getElementsByTagName("ID")[0].firstChild.nodeValue;
            }
            catch(er)
            {
                alert(get_label('parse_xml_exception', 'Parse XML exception.'));
                return;
            }
            if(status == 0)//solved
            {
                var row = document.getElementById(id);
                row.cells[4].innerHTML = get_label('solved', 'Solved');
                row.cells[5].innerHTML = '';
                alert(get_label('error_solved', 'Error solved'));
            }
            else
            {
                alert(get_label('error_still_unsolved', 'Error still unsolved'));
            }
        }
    }
    xmlhttp.open("GET","result_pages/ajax_response.php?page="+page+"&id="+entryid,true);
    xmlhttp.send();
}
</script>
</head>
