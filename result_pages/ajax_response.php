<?php

require_once(dirname(dirname(__FILE__)) . '/../../config.php');
require_once($CFG->dirroot.'/plagiarism/moss/lib.php');

/**
 * 
 * Enter description here ...
 * @author ycc
 *
 */
class verification_handler
{
    private $request;
    private $id;//indicate the entry's id in table 'moss_results'
    private $type;
    private $teacherid;
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $request
     * @param unknown_type $id
     * @param unknown_type $type
     * @param unknown_type $teacherid
     */
    function __construct($request = '', $id = -1, $type, $teacherid)
    {
        $this->request = $request;
        $this->id = $id;
        $this->type = $type;
        $this->teacherid = $teacherid;
    }
    
    /**
     * 
     * Enter description here ...
     */
    function response()
    {
        switch($this->request)
        {
        case 'view_code': 
            $this->view_code();
            break;
        case 'confirm'  : 
            $this->confirm();
            break;
        case 'unconfirm': 
            $this->unconfirm();
            break;
        }
    }
    
    /**
     * 
     * Enter description here ...
     */
    private function view_code()
    {
        echo "";
        global $DB;
    }
 
    /**
     * 
     * Enter description here ...
     */
    private function confirm()
    {
        global $DB;
        $entry = $DB->get_record('moss_results',array('id'=>$this->id));
        //verify $entry->confirmed because
        //low bandwidth, user maybe click "confirm" button several times
        //due to the design of the verifying page,this error will only trigger in low bandwidth situation, 
        if($entry == null || $entry->confirmed == 1)
        {
            echo $this->generatexml(1);
            return;
        }
        else 
        {
            $entry->confirmed = 1;
            $entry->teacherid = $this->teacherid;
            if($DB->update_record('moss_results', $entry)) 
            {
                echo $this->generatexml(0);
                return;
            }
            else
            {
                echo $this->generatexml(1);//TODO plugin error
                return;
            }
        }
    }
    
    /**
     * 
     * Enter description here ...
     */
    private function unconfirm()
    {
        global $DB;
        $entry = $DB->get_record('moss_results',array('id'=>$this->id));
        if($entry == null || $entry->confirmed == 0)
        {
            echo $this->generatexml(1);
            return;
        }
        else 
        {
            $entry->confirmed = 0;
            $entry->teacherid = $this->teacherid;
            if($DB->update_record('moss_results', $entry))
            {
                echo $this->generatexml(0);
                return;
            }
            else
            { 
                echo $this->generatexml(1);//TODO
                return;
            }
        }
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $status
     */
    private function generatexml($status)
    {
        $content = '<?xml version="1.0" encoding="ISO-8859-1"?>';
        $content.= '<ROOT><RESPONSE>';
        $content.= '<STATUS>'.$status.'</STATUS>';
        $content.= '<REQUEST>'.$this->request.'</REQUEST>';
        $content.= '<ID>'.$this->id.'</ID>';
        $content.= '<TYPE>'.$this->type.'</TYPE>';
        $content.= '</RESPONSE></ROOT>';
        return $content;
    }
}

/**
 * 
 * Enter description here ...
 * @author ycc
 *
 */
class bottom_x_node
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
class bottom_x
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
        $this->empty_head = new bottom_x_node();
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
    	while(($temp->get_next() != null) && ($temp->get_next()->get_compare_key() >= $compare_key))
    	{
    	    $temp = $temp->get_next();
    	}
    	$tail = $temp->get_next();
    	$new = new bottom_x_node($compare_key, $value);
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
 * @author ycc
 *
 */
class statistics_handler
{
    var $cmid;
    var $request;
    /**
     * 
     * Enter description here ...
     * @param unknown_type $rid
     * @param unknown_type $value
     */
    function __construct($request, $cmid)
    {
        $this->request = $request;
        $this->cmid = $cmid;
    }
    
    /**
     * 
     * Enter description here ...
     */
    function response()
    {
        switch($this->request)
        {
        case 'expand': 
            $this->expand();
            break;
        default:  
            break;
        }
    }
    
    /**
     * 
     * Enter description here ...
     */
    function expand()
    {
        $table_array = array();
        $cmid_array = array();
        $this->initial_array($table_array, $cmid_array);
        $this->get_bottom_x_list($table_array);
        if($table_array == NULL)
        {
        	$content  = '<?xml version="1.0" ?>';
            $content .= '<ROOT>';
            $content .= '<STATUS>1</STATUS>';
            $content .= '</ROOT>';
            echo $content;
        }
        else
            echo $this->generatexml($table_array, $cmid_array);
    }
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $cmid
     * @param unknown_type $table_array
     * @param unknown_type $cmid_array
     * @param unknown_type $summary_array
     */
    private function initial_array(&$table_array, &$cmid_array)
    {
        global $DB;
	    $sql = "SELECT id,cmid,iscross,user1id,user2id 
                FROM {moss_results}  
                WHERE confirmed=1 AND cmid IN
                                             (SELECT DISTINCT cmid 
                                              FROM {moss_settings}
                                              WHERE cmid IN
                                                           (SELECT b.id 
                                                            FROM {course_modules} AS a, {course_modules} AS b 
                                                            WHERE a.id = ? AND a.course = b.course))";
        $params = array($this->cmid);
        $results = $DB->get_records_sql($sql,$params);
    
        foreach($results as $result)
        {
            if($result->iscross != 1)
            {
        	    if(!isset($table_array[$result->user1id]))
                {
                    $table_array[$result->user1id] = array();
                }
                $table_array[$result->user1id][$result->cmid] = 1;
            }
            if(!isset($table_array[$result->user2id]))
                $table_array[$result->user2id] = array();
            $table_array[$result->user2id][$result->cmid] = 1;
            $cmid_array[$result->cmid] = $result->cmid;
        }
    }

    /**
     *
     * Enter description here ...
     * @param unknown_type $table_array
     */
    private function get_bottom_x_list(&$table_array)
    {
	    //sort top x students
	    $cnf_xml = new config_xml();
        $top = $cnf_xml->get_config('default_students_in_statistics_page');
        $bottom = count($table_array) - $top;
        if($bottom <= 0)
        {
        	$table_array = NULL;
        	return;
        }
        $bottom_x_list = new bottom_x($bottom);//sort top x 
        foreach($table_array as $student_id => $cm_array)
        {
            $bottom_x_list->insert(count($cm_array), array($student_id, $cm_array));
        }
        $table_array = $bottom_x_list->to_array();
}

    /**
     * 
     * Enter description here ...
     * @param unknown_type $flag
     * @param unknown_type $table_array
     * @param unknown_type $cmid_array
     * @param unknown_type $summary_array
     */
    private function generatexml($table_array, $cmid_array)
    {
        global $CFG;
        global $DB;
        
        $content  = '<?xml version="1.0" ?>';
        $content .= '<ROOT>';
        $content .= '<STATUS>0</STATUS>';    
        foreach($table_array as $student)
        {	
	        $content .= '<STUDENT>';
        	$user = $DB->get_record('user', array('id'=>$student[0]));
        	$content .= '<ID>'.$student[0].'</ID>';
            $content .= '<NAME>'.fullname($user).'</NAME>';
            $content .= '<LINK>'.$CFG->wwwroot."/user/profile.php?id=".$student[0].'</LINK>';
	        $count = 0;
	        //record cell
	        foreach($cmid_array as $cmid)
	        {
		        if(isset($student[1][$cmid]))
		        {
                    $content .= '<CM id="'.$cmid.'">1</CM>';
                    $count += 1;
		        }
		        else 
                    $content .= '<CM id="'.$cmid.'">0</CM>';
	        }
	        $content .= '<SUMMARY>'.$count.'</SUMMARY>';
	        $content .= '</STUDENT>';
        }
        $content .='</ROOT>';
        
        return $content;
    }

}

require_login();
$teacherid = $teacherid = $USER->id;
//TODO only authorized user can access

$page = optional_param('page', '', PARAM_ALPHAEXT);  
$request = optional_param('request', '', PARAM_ALPHAEXT);
$id = optional_param('id', 0, PARAM_INT);
$type = optional_param('type', '', PARAM_ALPHAEXT);//undo redo new 

header("Content-type:text/xml");

switch ($page)
{
case 'view_all_page'  :  
    $handler = new verification_handler($request, $id, $type, $teacherid);
    $handler -> response();         
    break;
case 'statistics_page' : 
    $handler = new statistics_handler($request, $id);
    $handler -> response();
    break; 
case 'error_test' :
	$handler = new plugin_error_test();
	$handler -> response($id);
	break;
default:                 
	break;
}
