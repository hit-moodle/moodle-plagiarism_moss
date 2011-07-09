<?php

$handlers = array (

/*
 * Event Handlers
 */
    'assessable_file_uploaded' => array (
        'handlerfile'      => '/plagiarism/moss/lib.php',
        'handlerfunction'  => 'moss_event_file_uploaded',
        'schedule'         => 'cron'
    ),
);
