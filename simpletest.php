<?php
include_once "textlib.php";

$content = pdf2text('1.pdf');
echo $content;

?>