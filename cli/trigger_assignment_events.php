<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
//                   Moss Anti-Plagiarism for Moodle                     //
//         https://github.com/hit-moodle/moodle-plagiarism_moss          //
//                                                                       //
// Copyright (C) 2009 onwards  Sun Zhigang  http://sunner.cn             //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 3 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Trigger assessable_file_uploaded and assessable_files_done events of
 * specified assignment
 *
 * @package   plagiarism_moss
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/clilib.php');      // cli only functions
require_once($CFG->dirroot.'/plagiarism/moss/lib.php');

// now get cli options
$longoptions  = array('help'=>false, 'nodone'=>false);
$shortoptions = array('h'=>'help', 'n'=>'nodone');
list($options, $unrecognized) = cli_get_params($longoptions, $shortoptions);

if ($unrecognized) {
    $cmid = (int)current($unrecognized);
    if ($cmid === 0) {
        $unrecognized = implode("\n  ", $unrecognized);
        cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
    }
}

if ($options['help'] or !isset($cmid)) {
    $help =
"Trigger assessable_file_uploaded and assessable_files_done events of specified assignment.

trigger_assignment_events.php cmid

Options:
-h, --help            Print out this help
-n, --nodone          Do not trigger assessable_files_done event

Example:
\$sudo -u www-data /usr/bin/php plagiarism/moss/cli/trigger_assignment_events.php 1234
";

    echo $help;
    die;
}

$count = moss_trigger_assignment_events($cmid, !$options['nodone']);

if ($count === false) {
    cli_error('Failed!');
} else {
    mtrace("$count submission(s) are found and events are triggered.");
}

