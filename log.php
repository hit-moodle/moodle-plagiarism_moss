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

    
    $currenttab='tab2';
    $tabs = array();
    $tabs[] = new tabobject('tab1', 'settings.php', 'Moss general settings', 'Moss general settings', false);
    $tabs[] = new tabobject('tab2', 'log.php', 'Moss error log', 'Moss error log', false);
    $tabs[] = new tabobject('tab3', 'backup.php', 'Plugin backup', 'Plugin_backup', false);
    
    echo $OUTPUT->header();
    print_tabs(array($tabs), $currenttab);
    
    $helplink = get_string('mossexplain', 'plagiarism_moss');
    $helplink .= '<a href='.$CFG->wwwroot.'/plagiarism/moss/help.php></a>';
            
    echo $OUTPUT->box('This is an advanced feature of anti-plagiarism plugin, to use this feature you need to enable it in the general settings page.<br/>'.
                      'The table below describe all errors that were detected by anti-plagiarism pulgin.<br/>'.
                      'For more information see: '.$helplink);
    
    $table = new html_table();
	$table->head = array ('Date','Error type','Describtion','Status');//, "", "");
	$table->align = array ('center','center', 'center', 'center');//, "center");
	$table->width = "100%";
	$table->data[] = array(
						  '2011.04.13',
                          '1',
                          'file format error',
						  'unsolved',
                          );
    $table->data[] = array(
						  '2011.04.15',
                          '2',
                          'connect moss timeout',
						  'solved',
                          );
    $table->data[] = array(
						  '2011.04.15',
                          '1',
                          'damaged zip file, unable to unpack',
						  'solved',
                          );
	echo html_writer::table($table);
    
    
    
    
    echo $OUTPUT->footer();