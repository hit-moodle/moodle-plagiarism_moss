<?php
$capabilities = array(

    'moodle/moss:all_result' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
         'legacy' => array(
         'editingteacher' => CAP_ALLOW,
         'manager' => CAP_ALLOW
        )
    )
);
