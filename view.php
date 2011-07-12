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

if ($cmid) {
    if (! $cm = get_coursemodule_from_id('', $cmid)) {
        print_error('invalidcoursemodule');
    }
    if (! $moss = $DB->get_record("moss", array('cmid'=>$cmid))) {
        print_error('unsupportedmodule', 'plagiarism_moss');
    }
    if (! $course = $DB->get_record("course", array("id"=>$cm->course))) {
        print_error('coursemisconf', 'assignment');
    }
} else if ($moss) {
    if (! $moss = $DB->get_record("moss", array('cmid'=>$cmid))) {
        print_error('unsupportedmodule', 'plagiarism_moss');
    }
    if (! $cm = get_coursemodule_from_id('', $moss->cmid)) {
        print_error('invalidcoursemodule');
    }
    if (! $course = $DB->get_record("course", array("id"=>$cm->course))) {
        print_error('coursemisconf', 'assignment');
    }
} else {
    require_param('id', PARAM_INT);
}

$url = new moodle_url('/plagiarism/moss/view.php');
$url->param('id', $cmid);
if ($userid != 0) {
    $url->param('user', $userid);
}

$PAGE->set_url($url);
require_login($course, true, $cm);

$context = get_context_instance(CONTEXT_MODULE, $cmid);

// confirm
$result  = optional_param('result', 0, PARAM_INT);
if ($result) {
    require_sesskey();
    require_capability('plagiarism/moss:confirm', $context);
    $r = $DB->get_record('moss_results', array('id' => $result), 'moss, config, userid');
    $r->confirmed = required_param('confirm', PARAM_BOOL);
    $r->confirmer = $USER->id;
    $r->timeconfirmed = time();
    $results = $DB->get_records('moss_results', array('moss' => $r->moss, 'config' => $r->config, 'userid' => $r->userid), 'id');
    foreach ($results as $o) {
        $r->id = $o->id;
        $DB->update_record('moss_results', $r);
    }

    moss_message_send($r);

    if (optional_param('ajax', 0, PARAM_BOOL)) {
        die;
    }
}

if ($userid != $USER->id) {
    require_capability('plagiarism/moss:viewallresults', $context);
}

$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');

$modname = get_string('modulename', $cm->modname);
$activityname = $DB->get_field($cm->modname, 'name', array('id' => $cm->instance));
$pagetitle = strip_tags(format_string($activityname, true).': '.get_string('moss', 'plagiarism_moss'));
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->shortname);

$PAGE->navbar->add(get_string('moss', 'plagiarism_moss'));

$output = $PAGE->get_renderer('plagiarism_moss');

$jsmodule = array(
    'name'     => 'plagiarism_moss',
    'fullpath' => '/plagiarism/moss/module.js',
    'requires' => array('base', 'io', 'node', 'json'),
    'strings' => array(
        array('confirmmessage', 'plagiarism_moss')
    )
);
$PAGE->requires->js_init_call('M.plagiarism_moss.init', $output->get_confirm_htmls(), false, $jsmodule);

/// Output starts here
echo $output->header();

$output->moss = $moss;

if ($userid) {
    $user = $DB->get_record('user', array('id' => $userid));
    echo $output->user_result($user);
} else {
    $from = optional_param('from', 0, PARAM_INT);
    $count = optional_param('count', 30, PARAM_INT);
    echo $output->cm_result($from, $count);
}

echo $output->footer();

