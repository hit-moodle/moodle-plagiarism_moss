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
    protected $can_viewdiff;
    protected $can_confirm;
    protected $can_viewunconfirmed;
    protected $context;
    protected $confirm_htmls;
    var $moss = null;

    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);

        $this->context = $page->context;
        $this->can_viewdiff = has_capability('plagiarism/moss:viewdiff', $this->context);
        $this->can_viewunconfirmed = has_capability('plagiarism/moss:viewunconfirmed', $this->context);
        $this->can_confirm = has_capability('plagiarism/moss:confirm', $this->context);

        $this->confirm_htmls = array (
            $this->pix_icon('i/completion-manual-n', get_string('unconfirmed', 'plagiarism_moss')),
            $this->pix_icon('i/completion-manual-y', get_string('confirmed', 'plagiarism_moss'))
        );
    }

    function user_stats($user) {
        global $DB;

        $sql = 'SELECT COUNT(DISTINCT moss)
                FROM {moss_results}
                WHERE userid = ? AND confirmed = 1';
        $a->total = $DB->count_records_sql($sql, array($user->id));
        $a->fullname = fullname($user);

        return $this->container(get_string('confirmedresults', 'plagiarism_moss', $a));
    }

    function user_result($user) {
        global $DB;

        $output = $this->user_stats($user);

        $table = new html_table();

        $table->head = array(
            get_string('user'),
            get_string('filepatterns', 'plagiarism_moss'),
            get_string('matchedusers', 'plagiarism_moss'),
            get_string('percentage', 'plagiarism_moss'),
            get_string('matchedlines', 'plagiarism_moss'),
        );

        if ($this->can_viewunconfirmed || $this->can_confirm) {
            $table->head[] = get_string('confirm');
        }

        $configs = $DB->get_records('moss_configs', array('moss' => $this->moss->id));
        foreach ($configs as $config) {
            if (empty($config->filepatterns)) {
                continue;
            }

            $sql = 'SELECT r1.*, r2.userid AS other
                FROM {moss_results} r1
                LEFT JOIN {moss_results} r2 ON r1.pair = r2.id
                WHERE r1.userid = ? AND r1.moss = ? AND r1.config = ? ';
            if (!$this->can_viewunconfirmed) {
                $sql .= 'AND r1.confirmed = 1 ';
            }
            $sql .= 'ORDER BY rank ASC';
            $params = array($user->id, $this->moss->id, $config->id);
            $matches = $DB->get_records_sql($sql, $params);

            $first_match = true;
            foreach ($matches as $match) {
                $cells = array();

                // other user
                $other = $DB->get_record('user', array('id' => $match->other));
                $cells[] = new html_table_cell($this->user($other).$this->confirm_button($match->pair));

                // percentage and linesmatched
                $percentage = $match->percentage.'%';
                $linesmatched = $match->linesmatched;
                if ($this->can_viewdiff) {
                    $url = new moodle_url($match->link);
                    $attributes = array('target' => '_blank');
                    $percentage = html_writer::link($url, $percentage, $attributes);
                    $linesmatched = html_writer::link($url, $linesmatched, $attributes);
                }
                $cells[] = new html_table_cell($percentage);
                $cells[] = new html_table_cell($linesmatched);

                // confirm
                $cells[] = new html_table_cell($this->confirm_button($match));

                if ($first_match) { // first row of the filepatterns
                    $pattern_cell = new html_table_cell($config->filepatterns);
                    $pattern_cell->rowspan = count($matches);
                    $cells = array_merge(array($pattern_cell), $cells);
                    $first_match = false;
                }

                if (empty($table->data)) { //first row
                    $user_text = $this->user_picture($user, array('popup' => true)).html_writer::empty_tag('br').fullname($user);
                    $cell = new html_table_cell($user_text);
                    $rowcount = &$cell->rowspan; // assign it later
                    $rowcount = 0;
                    $cells = array_merge(array($cell), $cells);
                }

                $table->data[] = new html_table_row($cells);
                $rowcount++;
            }
        }

        if (empty($table->data)) {
            $output .= $this->notification(get_string('noresults', 'plagiarism_moss', fullname($user)));
        } else {
            $output .= html_writer::table($table);
        }

        return $output;
    }

    function cm_result() {
        $output = '';

        return $output;
    }

    protected function user($user) {
        global $PAGE;

        if (is_enrolled($this->context, $user)) {
            $url = $PAGE->url;
            $url->param('user', $user->id);
        } else {
            $url = new moodle_url('/user/view.php', array('id' => $user->id));
        }
        return html_writer::link($url, fullname($user));
    }

    protected function confirm_button($result) {
        global $DB, $PAGE;

        if (!is_object($result)) { // $result is id
            $result = $DB->get_record('moss_results', array('id' => $result));
        }

        if (!is_enrolled($this->context, $result->userid)) { // show nothing for unenrolled users
            return '';
        }

        $output = $this->confirm_htmls[$result->confirmed];

        if ($this->can_confirm) {
            $url = $PAGE->url;
            $url->param('sesskey', sesskey());
            $url->param('result', $result->id);
            $url->param('confirm', !$result->confirmed);
            $output = html_writer::link($url, $output, array('class' => 'confirmbutton'));
        }

        return $output;
    }

    public function get_confirm_htmls() {
        return $this->confirm_htmls;
    }
}

