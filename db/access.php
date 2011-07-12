<?php
$capabilities = array(

    'plagiarism/moss:viewunconfirmed'    => array(
        'captype'        => 'read',
        'contextlevel'   => CONTEXT_MODULE,
        'archetypes' => array(
            'student'        => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        )
    ),

    'plagiarism/moss:viewallresults'    => array(
        'riskbitmask' => RISK_PERSONAL,

        'captype'        => 'read',
        'contextlevel'   => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        )
    ),

    'plagiarism/moss:viewdiff'    => array(
        'captype'        => 'read',
        'contextlevel'   => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        )
    ),

    'plagiarism/moss:confirm'    => array(
        'riskbitmask' => RISK_XSS,

        'captype'        => 'write',
        'contextlevel'   => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        )
    )
);

