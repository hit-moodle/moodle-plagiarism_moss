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
require_once($CFG->libdir.'/gradelib.php');

/**
 * moss result page renderer class
 */
class plagiarism_moss_renderer extends plugin_renderer_base {
    protected $can_viewdiff;
    protected $can_confirm;
    protected $can_viewunconfirmed;
    protected $context;
    protected $confirm_htmls;
    protected $showidnumber;
    var $moss = null;
    var $cm = null;

    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);

        $this->context = $page->context;
        $this->can_viewdiff = has_capability('plagiarism/moss:viewdiff', $this->context);
        $this->can_viewunconfirmed = has_capability('plagiarism/moss:viewunconfirmed', $this->context);
        $this->can_confirm = has_capability('plagiarism/moss:confirm', $this->context);
        $this->can_grade  = has_capability('mod/assignment:grade', $this->context);
        $this->showidnumber = get_config('plagiarism_moss', 'showidnumber');

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
        $user_timesubmitted = moss_get_submit_time($this->moss->cmid, $user->id);

        $table = new html_table();

        $table->head = array(
            get_string('user'),
            get_string('filepatterns', 'plagiarism_moss'),
            get_string('confirm', 'plagiarism_moss').$this->help_icon('confirm', 'plagiarism_moss'),
            get_string('percentage', 'plagiarism_moss'),
            get_string('matchedlines', 'plagiarism_moss'),
            get_string('timesubmitted', 'plagiarism_moss'),
            get_string('matchedusers', 'plagiarism_moss')
        );
        if ($this->showidnumber) {
            $table->head[] = get_string('idnumber');
        }
        $table->head[] = get_string('confirm', 'plagiarism_moss').$this->help_icon('confirm', 'plagiarism_moss');

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

                // time submitted
                $delta = $user_timesubmitted - moss_get_submit_time($this->moss->cmid, $match->other);
                if ($delta > 0) {
                    $delta_text = get_string('late', 'assignment', format_time($delta));
                } else if ($delta <0) {
                    $delta_text = get_string('early', 'assignment', format_time($delta));
                } else {
                    $delta_text = get_string('early', 'assignment', get_string('numseconds', '', 0));
                }
                $cells[] = new html_table_cell($delta_text);

                // other user
                $other = $DB->get_record('user', array('id' => $match->other));
                $cells[] = new html_table_cell($this->user($other));
                if ($this->showidnumber) {
                    $cells[] = new html_table_cell($other->idnumber);
                }
                $cells[] = new html_table_cell($this->confirm_button($match->pair));

                if ($first_match) { // first row of the filepatterns
                    // confirm button
                    $confirmcell = new html_table_cell($this->confirm_button($match));
                    $confirmcell->rowspan = count($matches);

                    $pattern_cell = new html_table_cell($config->filepatterns);
                    $pattern_cell->rowspan = count($matches);

                    $cells = array_merge(array($pattern_cell, $confirmcell), $cells);

                    $first_match = false;
                }

                if (empty($table->data)) { //first row
                    $user_text = $this->user_picture($user).html_writer::empty_tag('br').fullname($user).html_writer::empty_tag('br').$user->idnumber;
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
            $output .= $this->notification(get_string('nouserresults', 'plagiarism_moss', fullname($user)));
        } else {
            $output .= html_writer::table($table);
        }

        return $output;
    }

    /**
     * List all results in a course module
     */
    function cm_result($from=0, $num=30) {
        global $DB, $CFG, $PAGE;
        $output = '';

        /// find out current groups mode
        $groupmode = groups_get_activity_groupmode($this->cm);
        $currentgroup = groups_get_activity_group($this->cm, true);
        $output .= groups_print_activity_menu($this->cm, $CFG->wwwroot . '/plagiarism/moss/view.php?id=' . $this->cm->id, true);

        /// Table header
        $head = array();
        $head[] = get_string('user').'1';
        if ($this->showidnumber) {
            $head[] = get_string('idnumber').'1';
        }
        $head[] = get_string('confirm', 'plagiarism_moss').$this->help_icon('confirm', 'plagiarism_moss');
        $head[] = get_string('percentage', 'plagiarism_moss');
        $head[] = get_string('matchedlines', 'plagiarism_moss');
        $head[] = get_string('user').'2';
        if ($this->showidnumber) {
            $head[] = get_string('idnumber').'2';
        }
        $head[] = get_string('confirm', 'plagiarism_moss').$this->help_icon('confirm', 'plagiarism_moss');
        $head[] = get_string('percentage', 'plagiarism_moss');
        $head[] = get_string('matchedlines', 'plagiarism_moss');
        $head[] = get_string('filepatterns', 'plagiarism_moss');
        $head[] = get_string('deltatime', 'plagiarism_moss').$this->help_icon('deltatime', 'plagiarism_moss');
        $table = new html_table();
        $table->head = $head;

        $configs = $DB->get_records('moss_configs', array('moss' => $this->moss->id));

        $select = 'SELECT r1.*,
                          r2.id AS id2, r2.userid AS userid2, r2.percentage AS percentage2, r2.linesmatched AS linesmatched2,
                          r2.confirmed AS confirmed2, r2.confirmer AS confirmer2, r2.timeconfirmed AS timeconfirmed2
                   FROM {moss_results} r1 LEFT JOIN {moss_results} r2 ON r1.pair = r2.id ';
        $where = 'WHERE r1.moss = ? AND r1.userid < r2.userid ';
        $orderby = 'ORDER BY r1.rank ASC';
        if ($currentgroup) {
            if ($users = groups_get_members($currentgroup, 'u.id', 'u.id')) {
                $users = array_keys($users);
                $userids = implode(',',$users);
            } else {
                $userids = '0';
            }
            $where .= "AND (r1.userid IN ($userids) OR r2.userid IN ($userids)) ";
        }

        $results = $DB->get_records_sql($select.$where.$orderby, array($this->moss->id), $from, $num);

        foreach ($results as $result) {
            $user2result = clone($result);
            $user2result->id = $result->id2;
            $user2result->userid = $result->userid2;
            $user2result->percentage = $result->percentage2;
            $user2result->linesmatched = $result->linesmatched2;
            $user2result->confirmed = $result->confirmed2;
            $user2result->confirmer = $result->confirmer2;
            $user2result->timeconfirmed = $result->timeconfirmed2;

            // The persons who submitted later are displayed in the left column
            // since they are more likely to be copiers.
            $delta = moss_get_submit_time($this->moss->cmid, $result->userid) - moss_get_submit_time($this->moss->cmid, $user2result->userid);
            if ($delta >= 0) {
                $user1cells = $this->fill_result_in_cells($result);
                $user2cells = $this->fill_result_in_cells($user2result);
            } else {
                $user2cells = $this->fill_result_in_cells($result);
                $user1cells = $this->fill_result_in_cells($user2result);
            }

            $cells = array_merge($user1cells, $user2cells);
            $cells[] = $configs[$result->config]->filepatterns;
            $cells[] = $delta != 0 ? format_time($delta) : get_string('numseconds', '', 0);
            $table->data[] = new html_table_row($cells);
        }

        if (empty($table->data)) {
            $output .= $this->notification(get_string('nocmresults', 'plagiarism_moss'));
        } else {
            // append pager line
            $prevlink = '';
            $nextlink = '';
            if ($from != 0) {
                $url = clone($PAGE->url);
                $newfrom = $from-$num >= 0 ? $from-$num : 0;
                $url->param('from', $newfrom);
                $prevlink = html_writer::link($url, get_string('previous'));
            }
            if ($num <= count($results)) {
                $url = clone($PAGE->url);
                $newfrom = $from+$num;
                $url->param('from', $newfrom);
                $nextlink = html_writer::link($url, get_string('next'));
            }
            $pager = new html_table_cell($prevlink.' '.$nextlink);
            $pager->attributes['class'] = 'pager';
            $pager->colspan = count($table->head);
            $table->data[] = new html_table_row(array($pager));
            $output .= html_writer::table($table);
        }

        return $output;
    }

    protected function user($user) {
        global $PAGE, $COURSE, $USER;

        if (is_enrolled($this->context, $user)) {
            if ($user->id == $USER->id or has_capability('plagiarism/moss:viewallresults', $this->context)) {
                $url = $PAGE->url;
                $url->param('user', $user->id);
            } else {
                $url = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $COURSE->id));
            }
        } else {
            $url = new moodle_url('/user/view.php', array('id' => $user->id));
        }
        return html_writer::link($url, fullname($user));
    }

    /**
     * return html of confirm/unconfirm button and grade button
     */
    function confirm_button($result, $ajax = false) {
        global $DB, $PAGE;

        if (!is_object($result)) { // $result is id
            $result = $DB->get_record('moss_results', array('id' => $result), '*', MUST_EXIST);
        }

        if (!is_enrolled($this->context, $result->userid)) { // show nothing for unenrolled users
            return '';
        }

        // Confirm/unconfirm button
        $output = $this->confirm_htmls[$result->confirmed];
        if ($this->can_confirm) {
            $url = $PAGE->url;
            $url->param('sesskey', sesskey());
            $url->param('result', $result->id);
            $url->param('confirm', !$result->confirmed);
            $output = html_writer::link($url, $output, array('class' => 'confirmbutton'));
        }

        // Grade button
        if ($this->can_grade) {
            $url = new moodle_url('/mod/assignment/submissions.php',
                array(
                    'id' => $this->cm->id,
                    'userid' => $result->userid,
                    'mode' => 'single',
                    'filter' => 0,
                    'offset' => 99999999  // HACK: Use a big offset to hide the next buttons
                )
            );

            // get grade
            $grading_info = grade_get_grades($this->cm->course, 'mod', 'assignment', $this->cm->instance, $result->userid);
            if (!empty($grading_info->items[0]->grades)) {
                $grade = reset($grading_info->items[0]->grades)->grade;
                if (empty($grade)) {
                    $grade = '-';
                } else {
                    $grade = round($grade);
                }
            } else {
                $grade = '-';
            }

            $output .= html_writer::link($url, ' ('.$grade.')', array('target' => '_blank', 'title' => get_string('grade')));
        }

        if ($ajax) {
            return $output;
        } else {
            return html_writer::tag('span', $output, array('id' => "user$result->userid-config$result->config"));
        }
    }

    public function get_confirm_htmls() {
        return $this->confirm_htmls;
    }

    protected function fill_result_in_cells($result) {
        global $DB;
        $cells = array();

        // user name
        $user = $DB->get_record('user', array('id' => $result->userid), 'id, firstname, lastname, idnumber');
        $cells[] = new html_table_cell($this->user($user));
        if ($this->showidnumber) {
            $cells[] = new html_table_cell($user->idnumber);
        }
        $cells[] = new html_table_cell($this->confirm_button($result));

        // percentage and linesmatched
        $percentage = $result->percentage.'%';
        $linesmatched = $result->linesmatched;
        if ($this->can_viewdiff) {
            $url = new moodle_url($result->link);
            $attributes = array('target' => '_blank');
            $percentage = html_writer::link($url, $percentage, $attributes);
            $linesmatched = html_writer::link($url, $linesmatched, $attributes);
        }
        $cells[] = new html_table_cell($percentage);
        $cells[] = new html_table_cell($linesmatched);

        return $cells;
    }
}

