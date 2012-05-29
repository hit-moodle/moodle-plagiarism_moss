<?php
require_once(dirname(__FILE__) . '..\\..\\..\\config.php');
global $DB;

$ar = $DB->get_record('moss_diff', array('resultid' => $_GET['id']));

echo $ar->content_1;
?>