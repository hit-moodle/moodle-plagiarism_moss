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

$string['basefile'] = 'Base file';
$string['basefile_help'] = 'Moss normally reports all code that matches in pairs of files. When a base file is supplied, program code that also appears in the base file is not counted in matches. A typical base file will include, for example, the instructor-supplied code for an assignment. You can provide multiple base files here. Base files improve results, but are not usually necessary for obtaining useful information.';
$string['clicktoviewresults'] = 'Click here to view results.';
$string['configrequired'] = 'Config {$a} (required):';
$string['configoptional'] = 'Config {$a} (optional):';
$string['confirmed'] = 'Confirmed plagiarism';
$string['confirmedresults'] = '{$a->fullname} has confirmed plagiarism records in <strong>{$a->total}</strong> activities.';
$string['confirmmessage'] = 'Are you sure this is plagiarism?';
$string['cygwinpath'] = 'Path to Cygwin installation';
$string['disclosurehasmeasured']  ='All files submitted here has been measured by a plagiarism detection service at {$a->timemeasured}.';
$string['disclosurenevermeasured']  ='All files submitted here will be measured by a plagiarism detection service.';
$string['err_cygwinpath']  ='Bad Cygwin path or perl for Cygwin is not installed';
$string['filepatterns'] = 'Filename patterns';
$string['filepatterns_help'] = 'Glob format. E.g. \*.c, hello.\*, a?c.java. Use blank space to seperate multi patterns. Leave blank to disable the config.';
$string['language'] = 'Programming language';
$string['matchedlines'] = 'Matched lines';
$string['matchedusers'] = 'Matched users';
$string['messageprovider:moss_updates'] = 'Moss anti-plagiarism notifications';
$string['messagesubject'] = 'Moss anti-plagiarism notification';
$string['messageconfirmedhtml'] = 'Your submissions of {$a->modulename} in {$a->coursename} have been confirmed as <em>plagiarism</em>. <br />
Visit <a href="{$a->link}">{$a->link}</a> for details.';
$string['messageconfirmedtext'] = 'Your submissions of {$a->modulename} in {$a->coursename} have been confirmed as PLAGIARISM.
Visit {$a->link} for details.';
$string['messageunconfirmedhtml'] = 'Your submissions of {$a->modulename} in {$a->coursename} have been confirmed as <em>not</em> plagiarism. <br />
Visit <a href="{$a->link}">{$a->link}</a> for details.';
$string['messageunconfirmedtext'] = 'Your submissions of {$a->modulename} in {$a->coursename} have been confirmed as NOT plagiarism.
Visit {$a->link} for details.';
$string['moss'] = 'Moss anti-plagiarism';
$string['moss_help'] = '<a href="http://theory.stanford.edu/~aiken/moss/">Moss</a> (for a Measure Of Software Similarity) is an automatic system for determining the similarity of programs and plain text files.';
$string['moss:confirm'] = 'Confirm plagiarism';
$string['moss:viewallresults'] = 'View results of everyone';
$string['moss:viewdiff'] = 'View pair compare';
$string['moss:viewunconfirmed'] = 'View unconfirmed results';
$string['mossexplain'] = '<a href="https://github.com/hit-moodle/moodle-plagiarism_moss">Moss Anti-Plagiarism Plugin</a> is developped by <a href="http://www.hit.edu.cn/">Harbin Institute of Technology</a>. The plagiarism engine is <a href="http://theory.stanford.edu/~aiken/moss/">Moss</a>.';
$string['mossenabled'] ='Enable moss';
$string['mossuserid'] ='Moss account';
$string['mossuserid_help'] ='To obtain a Moss account, send a  mail message to <a href="mailto:moss@moss.stanford.edu">moss@moss.stanford.edu</a>. The body of the message should be in <strong>PLAIN TEXT</strong>(without any HTML tags) format and appear exactly as follows:

    registeruser
    mail username@domain

After registration, you will get a reply mail which contains a perl script with one line of code just likes:

    $userid=1234567890;

The number is exactly your moss account.';
$string['nocmresults'] = 'No plagiarism records in this activity';
$string['nouserresults'] = 'No plagiarism records related with {$a}';
$string['percentage'] = 'Similarity';
$string['pluginname'] = 'Moss anti-plagiarism';
$string['resultlinktitle'] = 'Up to {$a->percentage}% ({$a->linesmatched} lines) is similar with other {$a->count} user(s)';
$string['savedconfigsuccess'] = 'Moss anti-plagiarism settings saved';
$string['sensitivity'] = 'Sensitivity';
$string['sensitivity_help'] = 'The sensitivity option sets the maximum number of times a given passage may appear before it is ignored. A passage of code that appears in many programs is probably legitimate sharing and not the result of plagiarism.  With sensitivity N, any passage appearing in more than N programs is treated as if it appeared in a base file (i.e., it is never reported). With sensitivity 2, moss reports only passages that appear in exactly two programs.  If one expects many very similar solutions (e.g., the short first assignments typical of introductory programming courses) then using 3 or 4 is a good way to eliminate all but truly unusual matches between programs while still being able to detect 3-way or 4-way plagiarism. With 1000000 (or any very large number), moss reports all matches, no matter how often they appear. The setting is most useful for large assignments where one also a base file expected to hold all legitimately shared code.';
$string['tag'] = 'Tag';
$string['tag_help'] = 'Different activities using the same tag will be measured together. Tag is helpful to prevent plagiarism among courses.';
$string['timetomeasure']  ='Time to start measuring';
$string['timetomeasure_help']  ='Set the time to measure all submissions to detect plagiarism. If not set, the measure will occur after the activity\'s due time.

The measure will be executed only once against all existing submissions. If you want to measure again, reset the time.';

$string['unconfirmed'] = 'Unconfirmed';
$string['unsupportedmodule']  ='Moss does not support this module.';

