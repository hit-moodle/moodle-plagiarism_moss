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
 * Moss anti-plagiarism rendered class
 *
 * @package   plagiarism_moss
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * moss result page renderer class
 */
class plagiarism_moss_renderer extends plugin_renderer_base {
    var $can_viewdiff = false;
    var $can_confirm = false;
    var $can_viewunconfirmed = false;

    function user_result($user, $moss) {
        global $DB;

        $output = '';

        $table = new html_table();

        $table->head = array(
            get_string('fullname'),
            get_string('filepatterns', 'plagiarism_moss'),
            get_string('matchedusers', 'plagiarism_moss'),
            get_string('percentage', 'plagiarism_moss'),
            get_string('matchedlines', 'plagiarism_moss'),
        );

        if ($this->can_viewunconfirmed || $this->can_confirm) {
            $table->head[] = get_string('confirm');
        }

        $configs = $DB->get_records('moss_configs', array('moss' => $moss->id));
        foreach ($configs as $config) {
            if (empty($config->filepatterns)) {
                continue;
            }

            $sql = 'SELECT r1.*, r2.userid AS other
                FROM {moss_results} r1
                LEFT JOIN {moss_results} r2 ON r1.pair = r2.id
                WHERE r1.userid = ? AND r1.moss = ? AND r1.config = ?
                ORDER BY rank ASC';
            $params = array($user->id, $moss->id, $config->id);
            $matches = $DB->get_records_sql($sql, $params);

            $first_match = true;
            foreach ($matches as $match) {
                $cells = array();
                $other = $DB->get_record('user', array('id' => $match->other));
                $cells[] = new html_table_cell(fullname($other));
                $cells[] = new html_table_cell($match->percentage.'%');
                $cells[] = new html_table_cell($match->linesmatched);
                $cells[] = new html_table_cell($match->confirmed);

                if ($first_match) {
                    $pattern_cell = new html_table_cell($config->filepatterns);
                    $pattern_cell->rowspan = count($matches);
                    $cells = array_merge(array($pattern_cell), $cells);
                    $first_match = false;
                }

                if (empty($table->data)) { //first row
                    $cell = new html_table_cell(fullname($user));
                    $rowcount = &$cell->rowspan; // assign it later
                    $rowcount = 0;
                    $cells = array_merge(array($cell), $cells);
                }

                $table->data[] = new html_table_row($cells);
                $rowcount++;
            }
        }

        $output .= html_writer::table($table);

        return $output;
    }

    function cm_result($moss) {
        $output = '';

        return $output;
    }
}

