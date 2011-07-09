<?php
$capabilities = array(

    'moodle/moss:viewselfresult'    => array(
         'captype'        => 'read',
         'contextlevel'   => CONTEXT_MODULE,
         'archetypes' => array(
             'student'        => CAP_ALLOW,
             'teacher'        => CAP_ALLOW,
             'editingteacher' => CAP_ALLOW,
             'manager'        => CAP_ALLOW
         )
    ),

    'moodle/moss:viewunconfirmed'    => array(
         'captype'        => 'read',
         'contextlevel'   => CONTEXT_MODULE,
         'archetypes' => array(
             'student'        => CAP_ALLOW,
             'teacher'        => CAP_ALLOW,
             'editingteacher' => CAP_ALLOW,
             'manager'        => CAP_ALLOW
         )
    ),

    'moodle/moss:viewallresults'    => array(
         'captype'        => 'read',
         'contextlevel'   => CONTEXT_MODULE,
         'archetypes' => array(
             'teacher'        => CAP_ALLOW,
             'editingteacher' => CAP_ALLOW,
             'manager'        => CAP_ALLOW
         )
    ),

    'moodle/moss:viewdiff'    => array(
         'captype'        => 'read',
         'contextlevel'   => CONTEXT_MODULE,
         'archetypes' => array(
             'teacher'        => CAP_ALLOW,
             'editingteacher' => CAP_ALLOW,
             'manager'        => CAP_ALLOW
         )
    ),

    'moodle/moss:confirm'    => array(
         'captype'        => 'write',
         'contextlevel'   => CONTEXT_MODULE,
         'archetypes' => array(
             'teacher'        => CAP_ALLOW,
             'editingteacher' => CAP_ALLOW,
             'manager'        => CAP_ALLOW
         )
    )
);

