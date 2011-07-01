<?php
$capabilities = array(

    'moodle/moss:student_page'    => array(
         'captype'        => 'write',
         'contextlevel'   => CONTEXT_COURSE,
         'legacy' => array(
         'guest'          => CAP_PREVENT,
         'student'        => CAP_ALLOW,
         'teacher'        => CAP_ALLOW,
         'editingteacher' => CAP_ALLOW,
         'coursecreator'  => CAP_ALLOW,
         'admin'          => CAP_ALLOW,
         'manager'        => CAP_ALLOW
        )
    ),
    
    'moodle/moss:statistics_page' => array(
        'captype'      => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
        'guest'          => CAP_PREVENT,
        'student'        => CAP_PREVENT,
        'teacher'        => CAP_ALLOW,
        'editingteacher' => CAP_ALLOW,
        'coursecreator'  => CAP_ALLOW,
        'admin'          => CAP_ALLOW
        )
    ),
    
    'moodle/moss:view_all_page' => array(
        'captype'      => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
        'guest'          => CAP_PREVENT,
        'student'        => CAP_PREVENT,
        'teacher'        => CAP_ALLOW,
        'editingteacher' => CAP_ALLOW,
        'coursecreator'  => CAP_ALLOW,
        'admin'          => CAP_ALLOW
        )
    ),
    
    'moodle/moss:view_code_page' => array(
        'captype'      => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
        'guest'          => CAP_PREVENT,
        'student'        => CAP_ALLOW,
        'teacher'        => CAP_ALLOW,
        'editingteacher' => CAP_ALLOW,
        'coursecreator'  => CAP_ALLOW,
        'admin'          => CAP_ALLOW
        )
    ),
);
/*
 * 
 *  $modulecontext = get_context_instance(CONTEXT_MODULE, $cmid);
        $output = '';

        //check if this is a user trying to look at their details, or a teacher with viewsimilarityscore rights.
        if (($USER->id == $userid) || has_capability('moodle/plagiarism_turnitin:viewsimilarityscore', $modulecontext)) {
 */