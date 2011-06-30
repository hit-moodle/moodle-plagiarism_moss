<?php

require_once(dirname(dirname(__FILE__)) . '/../../config.php');
//global class
global $CFG;
require_once($CFG->dirroot.'/plagiarism/moss/file_operator.php');

require_login();

$cmid = optional_param('cmid', 0, PARAM_INT);
$entryid = optional_param('entryid', 0, PARAM_INT);
$page = optional_param('page', '', PARAM_ALPHANUM);
$role = optional_param('role', '', PARAM_ALPHANUM);

//$file_op = new file_operator();
//$file_op->delete_results_files_by_cm($cmid);
//die;

switch($page)
{
    case '':
        //read database set confirm or unconfirm button and close window button.
        //if result code was not downloaded yet, download first, print waiting message and whe finished redirect to this page
        //if download failed set iframe src = original link
        $record = $DB->get_record('moss_results', array('id'=>$entryid));
        if($record->link == 'downloaded')
        {
        	$PAGE->set_context(null);
            $PAGE->set_title(get_string('view_code_title', 'plagiarism_moss'));
            $PAGE->set_heading(get_string('view_code_heading', 'plagiarism_moss'));
            $url = new moodle_url('/plagiarism/moss/result_pages/view_code.php?');
            $url->param('cmid', $cmid);
            $url->param('entryid', $entryid);
            $PAGE->set_url($url);
            echo $OUTPUT->header();
            if($role != 'student')
            {   //is not a bug, even a student change the url, he still can't change the entry's status
            	//the tow button are useless if this page is not open by /view_vall.php
        	    if($record->confirmed == 1)
        	    {
        	    	echo '<button onclick=unconfirm_by_id('.$entryid.')>'.get_string('unconfirm', 'plagiarism_moss').'</button>
        	              <button onclick=close_window()>'.get_string('close_window', 'plagiarism_moss').'</button>
        	              </br>
        	              </br>';
        	    }
        	    else 
        	    {
        	    	echo '<button onclick=confirm_by_id('.$entryid.')>'.get_string('confirm', 'plagiarism_moss').'</button>
        	              <button onclick=close_window()>'.get_string('close_window', 'plagiarism_moss').'</button>
        	              </br>
        	              </br>';
        	    }
            }
            echo '<iframe src = "view_code.php?cmid='.$cmid.'&entryid='.$entryid.'&page=match" 
                          height = "500" 
                          width = "100%">
                  </iframe>';
            echo $OUTPUT->footer();
        }
        else 
        {
        	echo '<b>Downloading code detail from moss server, this page will automatically redirect, please wait...</b></br>';
        	$file_op = new file_operator();
        	$result = $file_op->save_results_files($cmid, $entryid);
        	if($result == false) //TODO download view code page from moss server error
        	    redirect('view_code.php?cmid='.$cmid.'&entryid='.$entryid.'&page=original');
        	else 
        	    redirect('view_code.php?cmid='.$cmid.'&entryid='.$entryid);
        }
        break;
    case 'match':
    case 'top':
    case '0':
    case '1':
    	$file_op = new file_operator();
    	$contents = $file_op->get_results_files_contents($cmid, $entryid, $page);
    	echo $contents;
    	break;
    case 'original':
    	$PAGE->set_context(null);
        $PAGE->set_title(get_string('view_code_title', 'plagiarism_moss'));
        $PAGE->set_heading(get_string('view_code_heading', 'plagiarism_moss'));
        $url = new moodle_url('/plagiarism/moss/result_pages/view_code.php?');
        $url->param('cmid', $cmid);
        $url->param('entryid', $entryid);
        $PAGE->set_url($url);
        echo $OUTPUT->header();
        
        if($role != 'student')
        {  
            $record = $DB->get_record('moss_results', array('id'=>$entryid));
            if($record->confirmed == 1)
            {
                echo '<button onclick=unconfirm_by_id('.$entryid.')>'.get_string('unconfirm', 'plagiarism_moss').'</button>
                      <button onclick=close_window()>'.get_string('close_window', 'plagiarism_moss').'</button>
                      </br>
                      </br>';
            }
            else 
            {
                echo '<button onclick=confirm_by_id('.$entryid.')>'.get_string('confirm', 'plagiarism_moss').'</button>
                      <button onclick=close_window()>'.get_string('close_window', 'plagiarism_moss').'</button>
                      </br>
                      </br>';
            }
        }
        echo '<iframe src = "'.$record->link.'" 
                      height = "500" 
                      width = "100%">
              </iframe>';
        echo $OUTPUT->footer();
    	break;
    default :
    	break;//error
}

if((($page == '') || ($page == 'original')) && ($role != 'student'))
{
    echo'<head>
             <script type="text/javascript">
             
             function get_label(id, default_txt)
             {
	             var element = document.getElementById(id);
	             if(element != null)
		             return element.innerHTML;
	             else
		             return default_txt;	
             }
             
             function confirm_by_id(id)
             {
                 if(confirm(get_label("confirm_prompt", "Are you sure you want to confirm this entry ?")))
                 {
                     self.opener.confirm_by_entry_id(id);
                     self.close();
                 }
             }

             function unconfirm_by_id(id)
             {
                 if(confirm(get_label("unconfirm_prompt", "Are you sure you want to unconfirm this entry ?")))
                 {
                     self.opener.unconfirm_by_entry_id(id);
                     self.close();
                 }
             }

             function close_window()
             {
                 self.close();
             }
             
             </script> 
         </head>';
}
/*
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

*/
