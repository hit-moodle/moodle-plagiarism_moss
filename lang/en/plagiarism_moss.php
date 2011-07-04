<?php
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
//                      Online Judge for Moodle                          //
//        https://github.com/hit-moodle/moodle-local_onlinejudge         //
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

$string['moss'] = 'Moss anti-plagiarism plugin';
$string['savedconfigsuccess'] = 'Moss anti-plagiarism settings saved';

//general setting page
$string['general_settings'] = 'Plugin general settings';
$string['mossexplain'] = 'For more information on this plugin see: ';
$string['mossenabled'] ='Enable moss';
$string['mossuserid'] ='Moss account';
$string['mossuserid_help'] ='To obtain a Moss account, send a  mail message to <a href="mailto:moss@moss.stanford.edu">moss@moss.stanford.edu</a>. The body of the message should be in <strong>PLAIN TEXT</strong>(without any HTML tags) format and appear exactly as follows:

    registeruser
    mail username@domain

After registration, you will get a reply mail which contains a perl script with one line of code just likes:

    $userid=1234567890;

The number is exactly your moss account.';

$string['studentdisclosure'] = 'Student Disclosure';
$string['studentdisclosure_help'] = 'This text will be displayed to all students on the file upload page.';
$string['studentdisclosuredefault']  ='All files uploaded will be submitted to a moss plagiarism detection service';

//error log page
$string['error_log'] = 'Plugin error log';
$string['error_date'] = 'Error date';
$string['error_type'] = 'Error type';
$string['error_description'] = 'Description';
$string['error_solution'] = 'Solution';
$string['error_status'] = 'Status';
$string['error_test'] = 'Test';
$string['test'] = 'test';
$string['unsolved'] = 'Unsolved';
$string['solved'] = 'Solved';
$string['error_still_unsolved'] = 'Error still unsolved!';
$string['error_solved'] = 'Error solved.';

//backup page
$string['plugin_backup'] = 'Plugin backup';

//specific setting form
$string['activatemoss'] = 'Activate moss plagiarism';
$string['tag'] = 'tag';
$string['activateentry'] = 'Activate entry';
$string['filepattern'] = 'File pattern';
$string['language'] = 'Programming language';
$string['sensitivity'] = 'Sensitivity';
$string['sensitivity_help'] = 'Moss needs a specific sensitivity value to conduct anti-plagiarism process, the value indicate the sensitivity of the engine';
$string['basefile'] = 'Base file';
$string['basefile_help'] = 'Moss normally reports all code that matches in pairs of files. but when a base file is supplied, program code that appears in the base file will not counted in matches';

//view_all page
$string['view_all_title'] = 'Anti-plagiarism view all page';
$string['view_all_heading'] = 'View all page';
$string['plugin_name'] = 'Anti-glagiarism';
$string['results'] = 'Results';
$string['view_all'] = 'View all';
$string['view_all_filter'] = 'Anti-plagiarism results filter';
$string['entry_type'] = 'Choose entry type';
$string['entry_type_help'] = 'TODO';
$string['entry_type_all'] = 'All entrys';
$string['entry_type_confirmed'] = 'Confirmed entrys only';
$string['entry_type_unconfirmed'] = 'Unconfirmed entrys only';
$string['entry_type_cross'] = 'Cross-course entrys only';
$string['student_from_other_course'] = 'Student from other course';
$string['student_name'] = 'Student name';
$string['student_name_help'] = 'TODO';
$string['student_name_western'] = 'Western name e.g "Peter Pan"';
$string['student_name_eastern'] = 'Eastern name e.g "张三"';
$string['rank_range'] = 'Rank range';
$string['rank_range_help'] = 'TODO';
$string['percentage_range'] = 'Matched percent range';
$string['percentage_range_help'] = 'TODO';
$string['lines_range'] = 'Matched lines range';
$string['lines_range_help'] = 'TODO';
$string['not_include'] = 'Not include';
$string['undo'] = 'Undo';
$string['redo'] = 'Redo';
$string['rank'] = 'Rank';
$string['student_name'] = 'Student name';
$string['match_percent'] = 'Match percent';
$string['lines_match'] = 'Lines match';
$string['code_detail'] = 'Code detail';
$string['action'] = 'Action';
$string['entry_status'] = 'Status';
$string['relevant_entry'] = 'Relevant entry';
$string['view_code'] = 'View code';
$string['confirm'] = 'Confirm';
$string['unconfirm'] = 'Unconfirm';
$string['unconfirmed'] = 'Unconfirmed';
$string['confirmed'] = 'Confirmed';
$string['undo_redo_describtion'] = 'Press "Undo" button to reverse an operation...';
//hidden label
$string['confirm_prompt'] = "Are you sure you want to confirm this entry ?";
$string['unconfirm_prompt'] = "Are you sure you want to unconfirm this entry ?";
$string['nothing_to_undo'] = "Nothing to undo.";
$string['nothing_to_redo'] = "Nothing to redo.";
$string['parse_xml_exception'] = "Parse XML exception.";
$string['request_rejected'] = "Request rejected by server.";

//statistics page
$string['statistics_title'] = 'Anti-plagiarism statistics page';
$string['statistics_heading'] = 'Statistics page';
$string['statistics'] = 'Statistics';
$string['expand'] = 'Expand';
$string['contract'] = 'Contract';
$string['expand_contract_describtion'] = 'Press "Expand" button to view all entrys...';
$string['summary'] = 'Summary';
$string['assignment'] = 'Assignment';

//view code page
$string['view_code_title'] = 'View code page';
$string['view_code_heading'] = 'View code';
$string['close_window'] = 'Close window';

//student page
$string['student_page_title'] = 'Student page';
$string['student_page_heading'] = 'Anti-plagiarism result page';
$string['appeal'] = 'Appeal';

//relevant page
$string['relevant_title'] = 'Anti-plagiarism relevant page';
$string['relevant_heading'] = 'Relevant page';
$string['relevant_type_filter'] = 'Entrys relevant type filter';
$string['all_relevant'] = 'All relevant entry';
$string['complete_subgraph'] = 'Complete subgraph';
$string['relevant_type'] = 'Choose relevant type';
$string['relevant_type_help'] = 'TODO';

