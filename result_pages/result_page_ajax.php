<?php
require_once(dirname(dirname(__FILE__)) . '/../../config.php');

class verification_handler
{
    private $request;
    private $id;//indicate the entry's id in table 'moss_results'
    
    function __construct($request = "", $id = "")
    {
        $this->request = $request;
        $this->id = $id;
    }
    
    function response()
    {
        switch($this->request)
        {
        	case 'view_code': $this->view_code();
                              break;
        	case 'confirm'  : $this->confirm();
                              break;
        	case 'unconfirm': $this->unconfirm();
                              break;
        }
    }
    
    private function view_code()
    {
        echo "";
        global $DB;
    }
 
    private function confirm()
    {
        global $DB;
        $entry = $DB->get_record('moss_results',array('id'=>$this->id));
        //verify $entry->confirmed because
        //low bandwidth, user maybe click "confirm" button several times
        //due to the design of the verifying page,this error will only trigger in low bandwidth situation, 
        if($entry == null || $entry->confirmed == 1)
            echo $this->generatexml(1);
        $entry->confirmed = 1;
        if($DB->update_record('moss_results', $entry)) 
            echo $this->generatexml(0);
        else
            ;//TODO plugin error
    }
    
    private function unconfirm()
    {
        global $DB;
        $entry = $DB->get_record('moss_results',array('id'=>$this->id));
        if($entry == null || $entry->confirmed == 0)
            echo $this->generatexml(1);
        $entry->confirmed = 0;
        if($DB->update_record('moss_results', $entry))
            echo $this->generatexml(0);
        else 
            ;//TODO
    }
    
    private function generatexml($status)
    {
        $content = '<?xml version="1.0" encoding="ISO-8859-1"?>';
        $content.= '<response>';
        $content.= '<status>'.$status.'</status>';
        $content.= '<request>'.$this->request.'</request>';
        $content.= '<id>'.$this->id.'</id>';
        $content.= '</response>';
        return $content;
    }
}

class statistics_handler
{
    var $rid;
    var $value;
    
    function __construct($rid, $value)
    {
    }
    
    function response()
    {
    }
}

$page = optional_param('page', 0, PARAM_INT);  
$request = optional_param('request', 0, PARAM_INT);
$value = optional_param('value', 0, PARAM_INT);

switch ($page)
{
	case 'confirmed_page' :
    case 'view_all_page'  :  $handler = new verification_handler($request, $value);
                             $handler -> response();         
                             break;
    case 'statistics_page' : $handler = new statistics_handler($request, $value);
                             $handler -> response();
                             break; 
}