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
 * Upgrade database
 *
 * @package   plagiarism_moss
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_plagiarism_moss_upgrade($oldversion=0) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    if ($oldversion < 2012052700){
        $table = new xmldb_table('moss_diff');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('resultid', XMLDB_TYPE_CHAR, '255', null,
                null, null, null);
        $table->add_field('user_1', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                XMLDB_NOTNULL, null, null);
        $table->add_field('user_2', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                XMLDB_NOTNULL, null, null);
       $table->add_field('content_1', XMLDB_TYPE_TEXT, null, null,
                null, null, null);
       $table->add_field('content_2', XMLDB_TYPE_TEXT, null, null,
                null, null, null);
       $table->add_field('content_top', XMLDB_TYPE_TEXT, null, null,
                null, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2012052700, 'plagiarism', 'moss');
    }

    if ($oldversion < 2011071500) {

        // Define field sensitivity to be dropped from moss_configs
        $table = new xmldb_table('moss_configs');
        $field = new xmldb_field('sensitivity');

        // Conditionally launch drop field sensitivity
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field sensitivity to be added to moss
        $table = new xmldb_table('moss');
        $field = new xmldb_field('sensitivity', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'tag');

        // Conditionally launch add field sensitivity
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // moss savepoint reached
        upgrade_plugin_savepoint(true, 2011071500, 'plagiarism', 'moss');
    }

    if ($oldversion < 2011100700) {
        set_config('maxfilesize', 64 * 1024 * 1024, 'plagiarism_moss');
        // moss savepoint reached
        upgrade_plugin_savepoint(true, 2011100700, 'plagiarism', 'moss');
    }

    if ($oldversion < 2011102000) {

        // Define table moss to be renamed to plagiarism_moss
        $table = new xmldb_table('moss');
        // Launch rename table for moss
        $dbman->rename_table($table, 'plagiarism_moss');

        // Define table moss_configs to be renamed to plagiarism_moss_configs
        $table = new xmldb_table('moss_configs');
        // Launch rename table for moss_configs
        $dbman->rename_table($table, 'plagiarism_moss_configs');

        // Define table moss_tags to be renamed to plagiarism_moss_tags
        $table = new xmldb_table('moss_tags');
        // Launch rename table for moss_tags
        $dbman->rename_table($table, 'plagiarism_moss_tags');

        // Define table moss_results to be renamed to plagiarism_moss_results
        $table = new xmldb_table('moss_results');
        // Launch rename table for moss_results
        $dbman->rename_table($table, 'plagiarism_moss_results');

        // Define table plagiarism_moss_matchedfiles to be renamed to plagiarism_moss_matchedfiles
        $table = new xmldb_table('moss_matched_files');
        // Launch rename table for moss_matched_files
        $dbman->rename_table($table, 'plagiarism_moss_matchedfiles');

        // moss savepoint reached
        upgrade_plugin_savepoint(true, 2011102000, 'plagiarism', 'moss');
    }

    return true;
}
