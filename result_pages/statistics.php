<?php
require_once(dirname(dirname(__FILE__)) . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/lib/formslib.php');

class statistics_filter_form extends moodleform 
{
    function definition() 
    {
        global $CFG;
        $mform =& $this->_form;
        $choices = array('No','Yes');
        $mform->addElement('html', get_string('mossexplain', 'plagiarism_moss'));
        $this->add_action_buttons(true);    
    }
}

require_login();
$PAGE->set_url('/plagiarism/moss/result_pages/statistics.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
$PAGE->set_title('anti-plagiarism staticstics page');
$PAGE->set_heading('Statistics page');
$PAGE->navbar->add('anti-plagiarism');
$PAGE->navbar->add('results');
$PAGE->navbar->add('statistics');

global $DB;
$form = new statistics_filter_form();
$cmid = optional_param('id', 0, PARAM_INT);  
$table;

$currenttab='tab3';
$tabs = array();
$tabs[] = new tabobject('tab1', "view_all.php?id=".$cmid, 'View all', 'View all', false);
$tabs[] = new tabobject('tab3', "statistics.php?id=".$cmid, 'Statistics', 'Statistics', false);

if(($data = $form->get_data()) && confirm_sesskey()) 
    echo 'save tab3';

$table;   
$table = new html_table();    
$cell1 = new html_table_cell('<font color="#3333FF">Assignment 1</font>');
$cell1 -> style = 'cursor:move';
$cell2 = new html_table_cell('<font color="#3333FF">Assignment 2</font>');
$cell2 -> style = 'cursor:move';
$cell3 = new html_table_cell('<font color="#3333FF">Assignment 3</font>');
$cell3 -> style = 'cursor:move';
$cell4 = new html_table_cell('<font color="#3333FF">Assignment 4</font>');
$cell4 -> style = 'cursor:move';
$cell5 = new html_table_cell('<font color="#3333FF">Assignment 5</font>');
$cell5 -> style = 'cursor:move';
$cell6 = new html_table_cell('<font color="#3333FF">Assignment 6</font>');
$cell6 -> style = 'cursor:move';
$cell7 = new html_table_cell('<font color="#3333FF">Summary</font>');
$cell7 -> style = 'cursor:move';
$table->head = array ("",
                      $cell1,
                      $cell2,
                      $cell3,
                      $cell4,
                      $cell5,
                      $cell6,
                      $cell7);
						  
$table->align = array ("left","center", "center", "center","center", "center", "center", "center");
$table->width = "100%";
$row1 = new html_table_row(array(
                                 '<font color="#3333FF">Chunchun Ye</font>',
                                 '<input type="checkbox" onchange = reverse(this) checked="checked" />',
                                 '<input type="checkbox" onchange = reverse(this) />',
                                 '<input type="checkbox" onchange = reverse(this) />',
                                 '<input type="checkbox" onchange = reverse(this) />',
                                 '<input type="checkbox" onchange = reverse(this) checked="checked" />',
                                 '<input type="checkbox" onchange = reverse(this) checked="checked" />',
                                 '<b>3</b>'
                          ));
$table->data[] = $row1;
$row2 = new html_table_row(array(
                                 '<font color="#3333FF">Shihong Chen</font>',
                                 '<input type="checkbox" onchange = reverse(this) />',
                                 '<input type="checkbox" onchange = reverse(this) checked="checked" />',
                                 '<input type="checkbox" onchange = reverse(this) />',
                                 '<input type="checkbox" onchange = reverse(this) checked="checked" />',
                                 '<input type="checkbox" onchange = reverse(this) />',
                                 '<input type="checkbox" onchange = reverse(this) checked="checked" />',
                                 '<b>3</b>'
                          ));
$table->data[] = $row2;
$row3 = new html_table_row(array(
                                 '<font color="#3333FF">Jiafu Lin</font>',
                                 '<input type="checkbox" onchange = reverse(this) checked="checked" />',
                                 '<input type="checkbox" onchange = reverse(this) />',
                                 '<input type="checkbox" onchange = reverse(this) checked="checked" />',
                                 '<input type="checkbox" onchange = reverse(this) />',
                                 '<input type="checkbox" onchange = reverse(this) />',
                                 '<input type="checkbox" onchange = reverse(this) />',
                                 '<b>2</b>'
                          ));
$table->data[] = $row3;
$table->data[] = $row2;
$table->data[] = $row2;
$table->data[] = $row3;
$table->data[] = $row1;
$table->data[] = $row2;

/*  $minlastaccess = $DB->get_field_sql('SELECT min(timeaccess)
                                                   FROM {user_lastaccess}
                                                  WHERE courseid = ?
                                                        AND timeaccess != 0', array($course->id));*/
$row4 = new html_table_row(array(
                                 '...','...','...','...','...','...','...','...'
                          ));
$table->data[] = $row4;

$row5 = new html_table_row(array(
                                 '<b>Summary</b>','<b>2</b>','<b>0</b>','<b>2</b>','<b>0</b>','<b>2</b>','<b>2</b>',''
                          ));
$table->data[] = $row5;




    
echo $OUTPUT->header();
echo $OUTPUT->box_start();
echo '<b>Press "Expand" button to view all entrys...<b> <br/>';
echo  '<button type="button" onclick = undo()>Expand</button>';
echo  '<button type="button" onclick = redo()>Contract</button>';
echo $OUTPUT->box_end();
print_tabs(array($tabs), $currenttab);
echo html_writer::table($table);
echo $OUTPUT->footer();
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
            //receive a xml file contain requestid and value
            //<response>
            //    <status>status</status> 0==abnormal 1==normal
            //    <requestid>requestid</requestid>
            //    <value>value</value>
            //    <data>...</data>
            //</response>
            //document.getElementById("txtHint").innerHTML=xmlhttp.responseText;
        }
    }
    xmlhttp.open("GET","ajax.php?pid="+pageid+"&rid="+requestid+"&value="+value,true);
    xmlhttp.send();
}
</script>
</head>