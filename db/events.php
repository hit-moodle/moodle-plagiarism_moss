<?php

$observers = array (

/*
 * Event Handlers
 */
    array(
        'eventname' => 'assignsubmission_file\event\assessable_uploaded',
        'callback' => 'moss_event_file_uploaded',
        'includefile' => '/plagiarism/moss/lib.php'
    ),
    array (
        'eventname'      => 'core\event\course_module_deleted',
        'callback'       => 'moss_event_file_uploaded',
        'includefile'    => '/plagiarism/moss/lib.php'
    ),
);
