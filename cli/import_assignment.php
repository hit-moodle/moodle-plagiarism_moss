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
 * Import all submissions of specified assignment into moss
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
$longoptions  = array('help'=>false);
$shortoptions = array('h'=>'help');
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
"Import all submissions of specified assignment into moss.

import_assignment.php cmid

Options:
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php plagiarism/moss/cli/import_assignment.php 1234
";

    echo $help;
    die;
}

$count = moss_import_assignment($cmid);

if ($count === false) {
    cli_error('Import failed!');
} else {
    mtrace("$count submission(s) imported.");
}

