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
 * Moss anti-plagiarism results page
 *
 * @package   plagiarism_moss
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/plagiarism/moss/locallib.php');

$cmid = optional_param('id', 0, PARAM_INT);  // Course Module ID
$mossid = optional_param('moss', 0, PARAM_INT);  // Moss ID
$userid  = optional_param('user', 0, PARAM_INT);   // User ID
$tab = optional_param('tab', 0, PARAM_INT);
$from = optional_param('from', 0, PARAM_INT);
$num = optional_param('num', 30, PARAM_INT);

if ($cmid) {
    if (! $cm = get_coursemodule_from_id('', $cmid)) {
        print_error('invalidcoursemodule');
    }
    if (! $moss = $DB->get_record("plagiarism_moss", array('cmid'=>$cmid))) {
        print_error('unsupportedmodule', 'plagiarism_moss');
    }
    if (! $course = $DB->get_record("course", array("id"=>$cm->course))) {
        print_error('coursemisconf', 'assign');
    }
} else if ($moss) {
    if (! $moss = $DB->get_record("plagiarism_moss", array('cmid'=>$cmid))) {
        print_error('unsupportedmodule', 'plagiarism_moss');
    }
    if (! $cm = get_coursemodule_from_id('', $moss->cmid)) {
        print_error('invalidcoursemodule');
    }
    if (! $course = $DB->get_record("course", array("id"=>$cm->course))) {
        print_error('coursemisconf', 'assign');
    }
} else {
    require_param('id', PARAM_INT);
}

$url = new moodle_url('/plagiarism/moss/view.php', array('id' => $cmid, 'tab' => $tab, 'from' => $from, 'num' => $num));
if ($userid != 0) {
    $url->param('user', $userid);
}

$PAGE->set_url($url);
require_login($course, true, $cm);

$context = context_module::instance($cmid);

if ($userid != $USER->id) {
    require_capability('plagiarism/moss:viewallresults', $context);
}

$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');

$modname = get_string('modulename', $cm->modname);
$activityname = $DB->get_field($cm->modname, 'name', array('id' => $cm->instance));
$pagetitle = strip_tags(format_string($activityname, true).': '.get_string('moss', 'plagiarism_moss'));
$heading = $course->fullname.': '.get_string('moss', 'plagiarism_moss');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($heading);

$PAGE->navbar->add(get_string('allresults', 'plagiarism_moss'), new moodle_url('/plagiarism/moss/view.php', array('id' => $cmid)));
if ($userid != 0) {
    $PAGE->navbar->add(get_string('personalresults', 'plagiarism_moss'));
}

$output = $PAGE->get_renderer('plagiarism_moss');
$output->moss = $moss;
$output->cm = $cm;

// confirm
$result  = optional_param('result', 0, PARAM_INT);
if ($result) {
    require_sesskey();
    require_capability('plagiarism/moss:confirm', $context);
    $r = $DB->get_record('plagiarism_moss_results', array('id' => $result), 'moss, config, userid');
    $r->confirmed = required_param('confirm', PARAM_BOOL);
    $r->confirmer = $USER->id;
    $r->timeconfirmed = time();
    $results = $DB->get_records('plagiarism_moss_results', array('moss' => $r->moss, 'config' => $r->config, 'userid' => $r->userid), 'id');
    foreach ($results as $o) {
        $r->id = $o->id;
        $DB->update_record('plagiarism_moss_results', $r);
    }

    moss_message_send($r);

    if (optional_param('ajax', 0, PARAM_BOOL)) {
        echo $output->confirm_button($r, true);
        die;
    }
}

$jsmodule = array(
    'name'     => 'plagiarism_moss',
    'fullpath' => '/plagiarism/moss/module.js',
    'requires' => array('base', 'io', 'node', 'json'),
    'strings' => array(
        array('confirmmessage', 'plagiarism_moss')
    )
);
$updating_html = $output->pix_icon('i/loading_small', get_string('updating', 'plagiarism_moss'));
$PAGE->requires->js_init_call('M.plagiarism_moss.init', array($updating_html), false, $jsmodule);

/// Output starts here
echo $output->header();

if ($userid) {
    $user = $DB->get_record('user', array('id' => $userid));
    echo $output->user_result($user);
} else {
    echo $output->cm_result($tab, $from, $num);
}

echo $output->footer();

