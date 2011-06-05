<?php
require_once(dirname(dirname(__FILE__)) . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/lib/formslib.php');

class confirmed_filter_form extends moodleform 
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
$PAGE->set_url('/plagiarism/moss/result_pages/confirmed.php');
$PAGE->set_context(null);
$PAGE->set_pagelayout('admin');
$PAGE->set_title('anti-plagiarism confirm page');
$PAGE->set_heading('Confirmed page');
$PAGE->navbar->add('anti-plagiarism');
$PAGE->navbar->add('results');

global $DB;
$form = new confirmed_filter_form();
$cmid = optional_param('id', 0, PARAM_INT);  
$table;

$currenttab='tab2';
$tabs = array();
$tabs[] = new tabobject('tab1', "view_all.php?id=".$cmid, 'View all', 'View all', false);
$tabs[] = new tabobject('tab2', "confirmed.php?id=".$cmid, 'Confirmed', 'Confirmed', false);
$tabs[] = new tabobject('tab3', "statistics.php?id=".$cmid, 'Statistics', 'Statistics', false);

if(($data = $form->get_data()) && confirm_sesskey()) 
    echo 'save tab2';
    
    
echo $OUTPUT->header();
echo $OUTPUT->box_start();
$form->display();
echo $OUTPUT->box_end();
print_tabs(array($tabs), $currenttab);
echo $OUTPUT->footer();
?>

<head>
<script type="text/javascript">
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