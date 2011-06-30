<?php 
require_once(dirname(dirname(__FILE__)) . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/lib/form/button.php');
require_once($CFG->libdir.'/tablelib.php');
global $CFG;
global $DB;

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
        $cmid = $this->_customdata['cmid'];
        $uid1 = $this->_customdata['uid1'];
        $uid2 = $this->_customdata['uid2'];
         
        $mform =& $this->_form;
        $mform->addElement('header', 'head', get_string('relevant_type_filter', 'plagiarism_moss'));
        $type_choices = array('all_relevant'    => get_string('all_relevant', 'plagiarism_moss'),
                              'complete_subgraph'   => get_string('complete_subgraph', 'plagiarism_moss'));
        
        $general[] = $mform->createElement('select','type', get_string('relevant_type','plagiarism_moss'), $type_choices);
        $general[] = $mform->createElement('submit', 'submit1', 'Submit');
        $mform->addElement('group', 'general', get_string('relevant_type','plagiarism_moss'), $general);
        $mform->addHelpButton('general', 'relevant_type', 'plagiarism_moss');
        
        //url param
        $mform->addElement('hidden', 'cmid');
        $mform->addElement('hidden', 'uid1');
        $mform->addElement('hidden', 'uid2');
        $mform->setType('cmid', PARAM_INT);
        $mform->setType('uid1', PARAM_INT);
        $mform->setType('uid2', PARAM_INT);
        $this->set_data(array('cmid'=>$cmid, 'uid1'=>$uid1, 'uid2'=>$uid2));
                 
    }
}

/**
 * 
 * Enter description here ...
 * @param unknown_type $cmid
 * @param unknown_type $uid1
 * @param unknown_type $uid2
 */
function get_all_relevant_entrys($cmid, $uid1, $uid2)
{
	global $DB;
    $sql = "SELECT *
            FROM {moss_results}  
            WHERE (cmid = ? AND user1id = ?) OR (cmid = ? AND user2id = ?) OR
                  (cmid = ? AND user1id = ?) OR (cmid = ? AND user2id = ?)";

    $params = array($cmid, $uid1, $cmid, $uid1,
                    $cmid, $uid2, $cmid, $uid2);
                   
    $entrys = $DB->get_records_sql($sql,$params);
	return $entrys;
}

/**
 * 
 * Enter description here ...
 * @param unknown_type $cmid
 * @param unknown_type $uid1
 * @param unknown_type $uid2
 */

function get_complete_subgraph($cmid, $uid1, $uid2)
{
	return NULL;
}


/**
 * 
 * Enter description here ...
 * @param unknown_type $entrys
 * @param unknown_type $type
 */
function init_table($cmid ,$entrys, $type)
{
    global $DB;
    global $CFG;
    $table = new html_table();
    $table->id = 'relevant_table';

    //initialize head
    $rank_head_cell = new html_table_cell(get_string('rank','plagiarism_moss'));  
    $match1_head_cell = new html_table_cell(get_string('match_percent', 'plagiarism_moss')); 
    $match2_head_cell = new html_table_cell(get_string('match_percent', 'plagiarism_moss'));
    $line_count_head_cell = new html_table_cell(get_string('lines_match', 'plagiarism_moss'));
    $name1_head_cell    = new html_table_cell(get_string('student_name', 'plagiarism_moss').' 1');
    $name2_head_cell    = new html_table_cell(get_string('student_name', 'plagiarism_moss').' 2');
    $detail_head_cell   = new html_table_cell(get_string('view_code', 'plagiarism_moss'));
    $action_head_cell   = new html_table_cell(get_string('action', 'plagiarism_moss'));
    $status_head_cell   = new html_table_cell(get_string('entry_status', 'plagiarism_moss'));
    
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
    $table->width = "100%";
	
    foreach($entrys as $entry)
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
        $row1->id = $entry->id;
        $table->data[] = $row1;
    }
    return $table;
}

require_login();

$cmid = optional_param('cmid', -1, PARAM_INT);
$uid1 = optional_param('uid1', -1, PARAM_INT);
$uid2 = optional_param('uid2', -1, PARAM_INT);
//TODO validation of cmid and entryid

$PAGE->set_context(null);
$PAGE->set_title(get_string('relevant_title', 'plagiarism_moss'));
$PAGE->set_heading(get_string('relevant_heading', 'plagiarism_moss'));
$url = new moodle_url('/plagiarism/moss/result_pages/view_code.php?');
$url->param('cmid', $cmid);
$url->param('uid1', $uid1);
$url->param('uid2', $uid2);
$PAGE->set_url($url);

if(($cmid == -1) || ($uid1 == -1) || ($uid2 == -1))
{
    echo 'Invalid course module id or student id';
    echo $OUTPUT->footer();
    die;
}

$form = new view_all_filter_form(NULL, array('cmid'=>$cmid, 'uid1'=>$uid1, 'uid2'=>$uid2));
$table;

if(($data = $form->get_data()) && confirm_sesskey())
{
    switch($data->general['type'])
    {
    	 case 'all_relevant':
    	 	$entrys = get_all_relevant_entrys($cmid, $uid1, $uid2);
    	 	$table = init_table($cmid,$entrys, 1);
    	 	break;   
         case 'complete_subgraph':
         	$entrys = get_complete_subgraph($cmid, $uid1, $uid2);
         	$table = init_table($cmid,$entrys, 0);
            break;
         default:
         	die;
    }
}
else
{
    $entrys = get_all_relevant_entrys($cmid, $uid1, $uid2);
    $table = init_table($cmid,$entrys, 1);
}


echo $OUTPUT->header();
$form->display();
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
		var row = element.parentNode.parentNode;
        var entryid = row.id;
        self.opener.confirm_by_entry_id(entryid);

        var bl = get_label('unconfirm', 'Unconfirm');
        row.cells[7].innerHTML = '<button type="button" onclick = unconfirm_entry(this)>'+bl+'</button>';
        row.cells[8].innerHTML = get_label('confirmed', 'Confirmed');
	}
}

function unconfirm_entry(element)
{  
	if(confirm(get_label('unconfirm_prompt', 'Are you sure you want to unconfirm this entry ?')))
	{
		var row = element.parentNode.parentNode;
        var entryid = row.id;
    	self.opener.unconfirm_by_entry_id(entryid);
    	
        var bl = get_label('confirm', 'Confirm');
        row.cells[7].innerHTML = '<button type="button" onclick = confirm_entry(this)>'+bl+'</button>';
        row.cells[8].innerHTML = get_label('unconfirmed', 'Unconfirmed');
	}
}

function confirm_by_entry_id(id)
{
	self.opener.confirm_by_entry_id(id);
}

function unconfirm_by_entry_id(id)
{  
	self.opener.unconfirm_by_entry_id(id);
}

</script>
</head>


