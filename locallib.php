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
 * Anti-Plagiarism by Moss
 *
 * @package   plagiarism_moss
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot.'/plagiarism/moss/moss.php');

define('MOSS_DEFAULT_MAXFILESIZE', 64 * 1024 * 1024);   // default max size of files to be submitted. I think 64K is big enough

/**
 * Whether moss is enabled
 *
 * @param int cmid
 * @return bool
 */
function moss_enabled($cmid = 0) {
    global $DB;

    if (!get_config('plagiarism', 'moss_use')) {
        return false;
    } else if ($cmid == 0) {
        return true;
    } else {
        return $DB->get_field('plagiarism_moss', 'enabled', array('cmid' => $cmid));
    }
}

/**
 * Save files in $eventdata to moss file area
 *
 * @param object $eventdata
 */
function moss_save_files_from_event($eventdata) {
    global $DB;
    $result = true;

    if (!moss_enabled($eventdata->cmid)) {
        return $result;
    }

    if (!empty($eventdata->file) && empty($eventdata->files)) { //single assignment type passes a single file
        $eventdata->files[] = $eventdata->file;
    }

    if (isset($eventdata->files)) {
        moss_save_storedfiles($eventdata->files, $eventdata->cmid, $eventdata->userid);
    }

    return $result;
}

/**
 * Save storedfiles into moss
 *
 * The function will clean all previous stored files
 * @param array $storedfiles
 * @param int $cmid
 * @param int $userid
 */
function moss_save_storedfiles($storedfiles, $cmid, $userid) {
    $context = get_system_context();
    $fs = get_file_storage();

    // remove all old files
    $old_files = $fs->get_directory_files($context->id, 'plagiarism_moss', 'files', $cmid, "/$userid/", true, true);
    foreach($old_files as $oldfile) {
        $oldfile->delete();
    }

    // store files
    foreach($storedfiles as $file) {
        if ($file->get_filename() === '.') {
            continue;
        }
        //hacky way to check file still exists
        $fileid = $fs->get_file_by_id($file->get_id());
        if (empty($fileid)) {
            mtrace("nofilefound!");
            continue;
        }

        $fileinfo = array(
            'contextid' => $context->id,
            'component' => 'plagiarism_moss',
            'filearea'  => 'files',
            'itemid'    => $cmid,
            'filepath'  => "/$userid/",
            // save /abc/def/ghi.c as abc_def_ghi.c
            'filename'  => str_replace('/', '_', ltrim($file->get_filepath(), '/')).$file->get_filename());
        $fs->create_file_from_storedfile($fileinfo, $file);
    }
}

/**
 * Trigger assessable_file_uploaded and assessable_files_done events of specified assignment.
 *
 * @param int $cmid
 * @param bool $trigger_done whether trigger assessable_files_done event. Advanced upload assignment only.
 * @return number of submissions found, or false on failed
 */
function moss_trigger_assignment_events($cmid, $trigger_done = true) {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/mod/assignment/lib.php');

    $cm = get_coursemodule_from_id('assignment', $cmid);
    if (empty($cm)) {
        return false;
    }

    $context = get_context_instance(CONTEXT_MODULE, $cmid);
    $fs = get_file_storage();
    $assignment = $DB->get_record('assignment', array('id' => $cm->instance), '*', MUST_EXIST);
    $submissions = assignment_get_all_submissions($assignment);

    foreach ($submissions as $submission) {
        $files = $fs->get_area_files($context->id, 'mod_assignment', 'submission', $submission->id, "timemodified", false);
        $eventdata = new stdClass();
        $eventdata->modulename   = 'assignment';
        $eventdata->cmid         = $cmid;
        $eventdata->itemid       = $submission->id;
        $eventdata->courseid     = $cm->course;
        $eventdata->userid       = $submission->userid;
        if ($files) {
            if ($assignment->assignmenttype == 'upload') {
                $eventdata->files = $files;
            } else { // uploadsingle
                $eventdata->file = current($files);
            }
        }
        events_trigger('assessable_file_uploaded', $eventdata);
        if ($trigger_done and $assignment->assignmenttype == 'upload') {
            unset($eventdata->files);
            events_trigger('assessable_files_done', $eventdata);
        }
    }

    return count($submissions);
}

/**
 * Sent notification
 *
 * @param object $result
 */
function moss_message_send($result) {
    global $DB, $CFG;

    $teacher = $DB->get_record('user', array('id' => $result->confirmer));
    $user = $DB->get_record('user', array('id' => $result->userid));

    $subject = get_string('messagesubject', 'plagiarism_moss');

    $moss = $DB->get_record('plagiarism_moss', array('id' => $result->moss));
    $moss->link = $CFG->wwwroot."/plagiarism/moss/view.php?id=$moss->cmid&user=$result->userid";
    $html = '';
    if ($result->confirmed) {
        $text = get_string('messageconfirmedtext', 'plagiarism_moss', $moss);
        if ($user->mailformat == 1) {  // HTML
            $html = get_string('messageconfirmedhtml', 'plagiarism_moss', $moss);
        }
    } else {
        $text = get_string('messageunconfirmedtext', 'plagiarism_moss', $moss);
        if ($user->mailformat == 1) {  // HTML
            $html = get_string('messageunconfirmedhtml', 'plagiarism_moss', $moss);
        }
    }

    $eventdata = new stdClass();
    $eventdata->modulename       = 'moss';
    $eventdata->userfrom         = $teacher;
    $eventdata->userto           = $user;
    $eventdata->subject          = $subject;
    $eventdata->fullmessage      = $text;
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml  = $html;
    $eventdata->smallmessage     = $subject;

    $eventdata->name            = 'moss_updates';
    $eventdata->component       = 'plagiarism_moss';
    $eventdata->notification    = 1;
    $eventdata->contexturl      = $moss->link;
    $eventdata->contexturlname  = $moss->modulename;

    message_send($eventdata);
}

function moss_get_supported_languages() {
    $langs = array(
        'ada'     => 'Ada',
        'a8086'   => get_string('langa8086', 'plagiarism_moss'),
        'c'       => 'C',
        'cc'      => 'C++',
        'csharp'  => 'C#',
        'fortran' => 'FORTRAN',
        'haskell' => 'Haskell',
        'java'    => 'Java',
        'javascript' => 'Javascript',
        'lisp'    => 'Lisp',
        'matlab'  => 'Matlab',
        'mips'    => get_string('langmips', 'plagiarism_moss'),
        'ml'      => 'ML',
        'modula2' => 'Modula2',
        'pascal'  => 'Pascal',
        'perl'    => 'Perl',
        'ascii'   => get_string('langascii', 'plagiarism_moss'),
        'plsql'   => 'PLSQL',
        'prolog'  => 'Prolog',
        'python'  => 'Python',
        'scheme'  => 'Scheme',
        'spice'   => 'Spice',
        'vhdl'    => 'VHDL',
        'vb'      => 'Visual Basic');

    core_collator::asort($langs);
    return $langs;
}

/**
 * Clean up all data related with cmid
 *
 * @param int $cmid
 * @return true or false
 */
function moss_clean_cm($cmid) {
    global $DB;

    if ($moss = $DB->get_record('plagiarism_moss', array('cmid' => $cmid))) {
        // clean up configs
        $DB->delete_records('plagiarism_moss_configs', array('moss' => $moss->id));

        // clean up results
        $results = $DB->get_records('plagiarism_moss_results', array('moss' => $moss->id));
        foreach ($results as $result) {
            $DB->delete_records('plagiarism_moss_matchedfiles', array('result' => $result->id));
        }

        // clean up files and results
        if ($moss->tag == 0) {
            // if no tag setted, no need to keep the files and moss record for further detection
            $fs = get_file_storage();
            $fs->delete_area_files(get_system_context()->id, 'plagiarism_moss', 'files', $cmid);
            $DB->delete_records('plagiarism_moss', array('cmid' => $cmid));

            // Clean results
            $DB->delete_records('plagiarism_moss_results', array('moss' => $moss->id));
        } else {
            moss_clean_noise($moss);

            // Disable moss record related with a deleted cm
            // The record will be reserved for further reference by other mosses
            // with the same tags
            $moss->enabled = 0;
            $DB->update_record('plagiarism_moss', $moss);

            // Clean results
            $DB->delete_records('plagiarism_moss_results', array('moss' => $moss->id));
        }
    }

    return true;
}

/**
 * Remove noise files.
 *
 * Noise files were uploaded by unenrolled users and confirmed as plagiarism.
 * They may be noise in further detection
 *
 * @param object $moss  null means all moss instance
 */
function moss_clean_noise($moss = null) {
    global $DB;

    $mosses = array();
    if (empty($moss)) {
        $mosses = $DB->get_records('plagiarism_moss');
    } else {
        $mosses[] = $moss;
    }

    foreach ($mosses as $moss) {
        $sql = 'SELECT DISTINCT userid
            from {plagiarism_moss_results}
            where moss = ? and confirmed = 1';
        if (! $confirmed_results = $DB->get_records_sql($sql, array($moss->id))) {
            continue;
        }

        $fs = get_file_storage();
        $systemcontext = get_system_context();
        $cmcontext = get_context_instance(CONTEXT_MODULE, $moss->cmid);

        foreach ($confirmed_results as $result) {
            if (!is_enrolled($cmcontext, $result->userid)) {
                $old_files = $fs->get_directory_files($systemcontext->id, 'plagiarism_moss', 'files', $moss->cmid, "/$result->userid/", true, true);
                foreach($old_files as $oldfile) {
                    $oldfile->delete();
                }
                if ($userdir = $fs->get_file($systemcontext->id, 'plagiarism_moss', 'files', $moss->cmid, "/$result->userid/", '.')) {
                    $userdir->delete();
                }
            }
        }
    }
}

/**
 * Return the submit time
 */
function moss_get_submit_time($cmid, $userid) {
    global $DB;

    $fs = get_file_storage();
    $context = get_system_context();
    $time = 0;
    if ($files = $fs->get_directory_files($context->id, 'plagiarism_moss', 'files', $cmid, "/$userid/")) {
        // search for the latest submitted file
        foreach ($files as $file) {
            if ($file->get_timemodified() > $time) {
                $time = $file->get_timemodified();
            }
        }
    } else {
        // lookup in cm with the same tag
        $moss = $DB->get_record('plagiarism_moss', array('cmid' => $cmid), '*', MUST_EXIST);
        $mosses = $DB->get_records_select('plagiarism_moss', 'tag = ? AND tag != 0', array($moss->tag));
        foreach ($mosses as $moss) {
            if ($files = $fs->get_directory_files($context->id, 'plagiarism_moss', 'files', $moss->cmid, "/$userid/")) {
                // search for the latest submitted file
                foreach ($files as $file) {
                    if ($file->get_timemodified() > $time) {
                        $time = $file->get_timemodified();
                    }
                }
            }
            // Do not lookup other mosses any more if got one matched
            if ($time > 0) {
                break;
            }
        }
    }

    return $time;
}

/**
 * Measure all moss instances that should be measured
 */
function moss_measure_all() {
    global $DB;

    // get mosses measure on specified time
    $select  = 'timetomeasure < ? AND timetomeasure > timemeasured AND enabled = 1 AND timetomeasure != 0';
    $mosses = $DB->get_records_select('plagiarism_moss', $select, array(time()));

    // get mosses measure on activity due date
    $duemosses = $DB->get_records('plagiarism_moss', array('enabled' => 1, 'timetomeasure' => 0));
    foreach ($duemosses as $moss) {
        if ($cm = get_coursemodule_from_id('', $moss->cmid)) {
            switch ($cm->modname) {
            case 'assignment':
                $duedate = $DB->get_field('assignment', 'timedue', array('id' => $cm->instance));
            }
            if (!empty($duedate)) {
                if ($moss->timemeasured < $duedate and $duedate < time()) {
                    // it should be measured
                    $mosses[] = $moss;
                }
            }
        }
    }

    mtrace("\tFound ".count($mosses)." moss instances to measure");

    foreach ($mosses as $moss) {
        mtrace("\tMeasure $moss->modulename ($moss->cmid) in $moss->coursename");
        $moss_obj = new moss($moss->cmid);
        $moss_obj->measure();
    }
}

