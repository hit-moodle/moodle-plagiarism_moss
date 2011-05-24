<?php
    require_once(dirname(dirname(__FILE__)) . '/../config.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once($CFG->libdir.'/plagiarismlib.php');
    require_once($CFG->dirroot.'/plagiarism/moss/lib.php');
    require_once($CFG->dirroot.'/lib/formslib.php');
    
    require_login();
    admin_externalpage_setup('plagiarismmoss');
    $context = get_context_instance(CONTEXT_SYSTEM);
    require_capability('moodle/site:config', $context, $USER->id, true, "nopermissions");


    $plagiarismplugin = new plagiarism_plugin_moss();
    
    $currenttab='tab3';
    $tabs = array();
    $tabs[] = new tabobject('tab1', 'settings.php', 'Moss general settings', 'General_settings', false);
    $tabs[] = new tabobject('tab2', 'log.php', 'Moss error log', 'Error_log', false);
    $tabs[] = new tabobject('tab3', 'backup.php', 'Plugin backup', 'Plugin_backup', false);
    


    echo $OUTPUT->header();
    print_tabs(array($tabs), $currenttab);
    
    echo $OUTPUT->box_start();
    
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();