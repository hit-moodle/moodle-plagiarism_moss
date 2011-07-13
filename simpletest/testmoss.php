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
 * Unit tests for (some of) the main features.
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package plagiarism_moss
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

// access to use global variables.
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

// Make sure the code being tested is accessible.
require_once($CFG->dirroot . '/plagiarism/moss/lib.php');

/** This class contains the test cases for the functions in judegelib.php. */
class plagiarism_moss_test extends UnitTestCase {
	function setUp() {
        global $DB, $CFG;

        $this->realDB = $DB;
        $dbclass = get_class($this->realDB);
        $DB = new $dbclass();
        $DB->connect($CFG->dbhost, $CFG->dbuser, $CFG->dbpass, $CFG->dbname, $CFG->unittestprefix);

        if ($DB->get_manager()->table_exists('moss')) {
            $DB->get_manager()->delete_tables_from_xmldb_file($CFG->dirroot . '/plagiarism/moss/db/install.xml');
        }
        $DB->get_manager()->install_from_xmldb_file($CFG->dirroot . '/plagiarism/moss/db/install.xml');

        if ($DB->get_manager()->table_exists('files')) {
            $table = new xmldb_table('files');
            $DB->get_manager()->drop_table($table);
            $table = new xmldb_table('config_plugins');
            $DB->get_manager()->drop_table($table);
        }
        $DB->get_manager()->install_one_table_from_xmldb_file($CFG->dirroot . '/lib/db/install.xml', 'files');
        $DB->get_manager()->install_one_table_from_xmldb_file($CFG->dirroot . '/lib/db/install.xml', 'config_plugins');

        set_config('mossuserid', 580031178, 'plagiarism_moss');
        set_config('cygwinpath', 'C:\\cygwin', 'plagiarism_moss');
        set_config('moss_use', 1, 'plagiarism');

        $mosses = array(
            array('cmid' => 1, 'tag' => 1, 'enabled' => 0, 'timetomeasure' => time()),
            array('cmid' => 2, 'tag' => 1, 'enabled' => 0, 'timetomeasure' => time()),
            array('cmid' => 3, 'tag' => 1, 'enabled' => 0, 'timetomeasure' => time()),
            array('cmid' => 4, 'tag' => 2, 'enabled' => 0, 'timetomeasure' => time()),
        );
        $tags = array(
            array('name' => 'test1'),
            array('name' => 'test2'),
            array('name' => 'test3'),
            array('name' => 'test4')
        );
        foreach ($tags as $tag) {
            $DB->insert_record('moss_tags', $tag);
        }
        foreach ($mosses as $moss) {
            $DB->insert_record('moss', $moss);
        }
    }

	function tearDown() {
		global $DB, $CFG;
        $DB = $this->realDB;
	}

	function test_onefile() {
        $events = array(
            array('userid' => 1, 'cmid' => 1),
            array('userid' => 2, 'cmid' => 1),
            array('userid' => 3, 'cmid' => 1),
            array('userid' => 4, 'cmid' => 1)
        );
        $contents = array(
            array(
                '/file1.txt' => 'What is Moss?

            Moss (for a Measure Of Software Similarity) is an automatic system for determining the similarity of programs. To date, the main application of Moss has been in detecting plagiarism in programming classes. Since its development in 1994, Moss has been very effective in this role. The algorithm behind moss is a significant improvement over other cheating detection algorithms (at least, over those known to us).',
                '/source1.c' => 'What is Moss?

                Moss (for a Measure Of Software Similarity) is an automatic system for determining the similarity of programs. To date, the main application of Moss has been in detecting plagiarism in programming classes. Since its development in 1994, Moss has been very effective in this role. The algorithm behind moss is a significant improvement over other cheating detection algorithms (at least, over those known to us).'
            ),
            array(
                '/file2.txt' => 'What is Moss?

            Moss (for a Measure Of Software Similarity) is an automatic system for determining the similarity of programs. To date, the main application of Moss has been in detecting plagiarism in programming classes. Since its development in 1994, Moss has been very effective in this role. The algorithm behind moss is a significant improvement over other cheating detection algorithms (at least, over those known to us).',
                '/source2.c' => 'What is Moss?

                Moss (for a Measure Of Software Similarity) is an automatic system for determining the similarity of programs. To date, the main application of Moss has been in detecting plagiarism in programming classes. Since its development in 1994, Moss has been very effective in this role. The algorithm behind moss is a significant improvement over other cheating detection algorithms (at least, over those known to us).'
            ),
            array('/file3.txt' => 'What is Moss?

            Moss (for a Measure Of Software Similarity) is an automatic system for determining the similarity of programs. To date, the main application of Moss has been in detecting plagiarism in programming classes. Since its development in 1994, Moss has been very effective in this role. The algorithm behind moss is a significant improvement over other cheating detection algorithms (at least, over those known to us).
            Languages

                Moss can currently analyze code written in the following languages:
                C, C++, Java, C#, Python, Visual Basic, Javascript, FORTRAN, ML, Haskell, Lisp, Scheme, Pascal, Modula2, Ada, Perl, TCL, Matlab, VHDL, Verilog, Spice, MIPS assembly, a8086 assembly, a8086 assembly, MIPS assembly, HCL2.'),
            array('/file4.txt' => 'Languages

                Moss can currently analyze code written in the following languages:
                C, C++, Java, C#, Python, Visual Basic, Javascript, FORTRAN, ML, Haskell, Lisp, Scheme, Pascal, Modula2, Ada, Perl, TCL, Matlab, VHDL, Verilog, Spice, MIPS assembly, a8086 assembly, a8086 assembly, MIPS assembly, HCL2.')
        );

        $fs = get_file_storage();
        $i = 0;
        $files = array();
        foreach ($contents as $oneuser) {
            foreach ($oneuser as $key => $content) {
                $file_record = new stdClass();
                $file_record->contextid = 1;
                $file_record->component = 'test';
                $file_record->filearea = 'test';
                $file_record->filepath = dirname($key).'/';
                $file_record->filename = basename($key);
                $file_record->itemid = $i;
                $fs->create_file_from_string($file_record, $content);
            }
            $files[] = $fs->get_area_files(1, 'test', 'test', $i, 'sortorder, filename', false);
            $i++;
        }

        foreach($events as $i => & $event) {
            $event = (object)$event;
            $event->files = current($files);
            next($files);
        }

        $this->add_config(1, '*.txt');
        $this->trigger(1, $events);
	}

    function add_config($cmid, $filepatterns = '*', $language = 'ascii', $sensitivity = 10) {
        global $DB;
        $moss = $cmid; // Yes, they should be same
        $DB->insert_record(
            'moss_configs',
            array(
                'moss' => $moss,
                'filepatterns' => $filepatterns,
                'language' => $language,
                'sensitivity' => $sensitivity
            )
        );
    }

    function trigger($cmid, $events) {
        global $DB;

        $DB->set_field('moss', 'enabled', 1, array('cmid' => $cmid));
        foreach ($events as $event) {
            moss_event_file_uploaded($event);
        }

        $plagiarism = new moss($cmid);
        $this->assertTrue($plagiarism->measure());
    }
}

