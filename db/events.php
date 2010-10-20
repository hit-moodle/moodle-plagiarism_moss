<?php

$handlers = array (

/*
 * Event Handlers
 */
    'assessable_file_uploaded' => array (
        'handlerfile'      => '/plagiarism/new/lib.php',
        'handlerfunction'  => 'event_file_uploaded',
        'schedule'         => 'cron'
    ),
    'assessable_files_done' => array (
        'handlerfile'      => '/plagiarism/new/lib.php',
        'handlerfunction'  => 'event_files_done',
        'schedule'         => 'cron'
    ),
    'mod_created' => array (
        'handlerfile'      => '/plagiarism/new/lib.php',
        'handlerfunction'  => 'event_mod_created',
        'schedule'         => 'cron'
    ),
    'mod_updated' => array (
        'handlerfile'      => '/plagiarism/new/lib.php',
        'handlerfunction'  => 'event_mod_updated',
        'schedule'         => 'cron'
    ),
    'mod_deleted' => array (
        'handlerfile'      => '/plagiarism/new/lib.php',
        'handlerfunction'  => 'event_mod_deleted',
        'schedule'         => 'cron'
    ),

);
