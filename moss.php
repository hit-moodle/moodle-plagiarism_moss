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

require_once($CFG->dirroot.'/plagiarism/moss/locallib.php');

/**
 * moss script interface
 */
class moss {
    protected $moss;
    protected $tempdir;

    public function __construct($cmid) {
        global $CFG, $DB;
        $this->moss = $DB->get_record('moss', array('cmid' => $cmid));
        $this->moss->course = $DB->get_field('course_modules', 'course', array('id' => $this->moss->cmid));
        $this->tempdir = $CFG->dataroot.'/temp/moss/'.$this->moss->id;
    }

    public function __destruct() {
        if (!debugging('', DEBUG_DEVELOPER)) {
            remove_dir($this->tempdir);
        }
    }

    /**
     * Measure the current course module
     *
     * @return bool success or not
     */
    public function measure() {
        global $DB;

        if (!moss_enabled($this->moss->cmid)) {
            return false;
        }

        $mosses = $DB->get_records('moss', array('tag' => $this->moss->tag));
        foreach ($mosses as $moss) {
            if ($moss->cmid == $this->moss->cmid) {
                // current moss must be extracted lastly
                // to overwrite other files belong to the same person
                continue;
            }
            $this->extract_files($moss);
        }

        $this->extract_files();

        if (!$this->call_moss()) {
            return false;
        }

        $this->moss->timemeasured = time();
        $DB->update_record('moss', $this->moss);

        return true;
    }

    protected function extract_files($moss = null) {
        if ($moss == null) {
            $moss = $this->moss;
        }

        $fs = get_file_storage();
        $files = $fs->get_area_files(get_system_context()->id, 'plagiarism_moss', 'files', $moss->cmid, 'sortorder', false);
        foreach ($files as $file) {
            $path = $this->tempdir.$file->get_filepath();
            $fullpath = $path.$file->get_filename();
            if (!check_dir_exists($path)) {
                throw new moodle_exception('errorcreatingdirectory', '', '', $path);
            }
            $file->copy_content_to($fullpath);
        }
    }

	/**
	 * this function will call moss script and save anti-plagiarism results
     *
     * TODO: finish it
     * @return sucessful true or failed false
	 */
    protected function call_moss() {
        global $CFG, $DB;

        $commands = $this->get_commands();
        if(empty($commands)) {
            mtrace('No valid config to run');
            return false;
        }

        //delete previous results
        $this->clean_results();

        //connect moss server and save results
        foreach($commands as $configid => $cmd) {
            $descriptorspec = array(
                0 => array('pipe', 'r'),  // stdin
                1 => array('pipe', 'w'),  // stdout
                2 => array('pipe', 'w')   // stderr
            );
            $proc = proc_open($cmd, $descriptorspec, $pipes);
            if (!is_resource($proc)) {
                mtrace('Call moss failed.');
                return false;
            }

            //get standard output and standard error output
            $out = stream_get_contents($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            $rval = proc_close($proc);
            if ($rval != 0) {
                mtrace($err);
                return false;
            }

            $url_p = '/http:\/\/moss\.stanford\.edu\/results\/\d+/';
            if (!preg_match($url_p, $out, $match)) {
                mtrace($out);
                return false;
            }

            if (!$this->save_results($match[0], $configid)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Return commands of moss for all configs
     *
     * @return array
     */
    protected function get_commands() {
        global $CFG, $DB;

        $settings = $DB->get_records('moss_configs', array('moss' => $this->moss->id));
        $fs = get_file_storage();
        $context = get_system_context();
        $cmds = array();

        foreach($settings as $setting) {
            if (empty($setting->filepatterns)) { // no filepatterns means invalid
                continue;
            }

            $cmd = $CFG->dirroot.'/plagiarism/moss/moss';
            $cmd .= ' -d';
            $cmd .= ' -u '.get_config('plagiarism_moss', 'mossuserid');;
            $cmd .= ' -l '.$setting->language;
            $cmd .= ' -m '.$setting->sensitivity;

            $basefiles = $fs->get_area_files($context->id, 'plagiarism_moss', 'basefiles', $setting->id, 'filename', false);
            foreach ($basefiles as $basefile) {
                $realpath = $this->tempdir.'/'.$setting->id.'_'.$basefile->get_filename();
                $basefile->copy_content_to($realpath);
                $cmd .= ' -b '.realpath;
            }
            $filepatterns = explode(' ', $setting->filepatterns);
            foreach ($filepatterns as $filepattern) {
                $cmd .= ' '.$this->tempdir.'/*/'.$filepattern;
            }
            $cmds[$setting->id] = $cmd;
        }

        return $cmds;
    }

    /**
     * Parse moss result page and store to DB
     *
     * @param string $url moss result page url
     * @param int $configid
     * @return true or false
     */
    protected function save_results($url, $configid) {
    	global $DB;

        mtrace("Processing $url");

        if (!$result_page = download_file_content($url)) {
            mtrace("can not read $url");
            return false;
        }

        preg_match_all(
            '/(?P<link>http:\/\/moss\.stanford\.edu\/results\/\d+\/match\d+\.html)">.+\/(?P<user1>\d+)\/ \((?P<percentage1>\d+)%\).+\/(?P<user2>\d+)\/ \((?P<percentage2>\d+)%\).+right>(?P<linesmatched>\d+)/Us',
            $result_page,
            $matches,
            PREG_SET_ORDER
        );

        if (empty($matches)) {
            mtrace("can not parse $url");
            return false;
        }

        // save to db
        $context = get_context_instance(CONTEXT_COURSE, $this->moss->course);
        foreach ($matches as $rank => $result) {
            $result['moss'] = $this->moss->id;
            $result['config'] = $configid;
            $result['rank'] = $rank + 1;
            $result1 = (object)$result;
            $result1->userid = $result['user1'];
            $result1->percentage = $result['percentage1'];
            $result2 = (object)$result;
            $result2->userid = $result['user2'];
            $result2->percentage = $result['percentage2'];

            // keep enrolled users only
            if (is_enrolled($context, $result1->userid) or is_enrolled($context, $result2->userid)) {
                $result1->id = $DB->insert_record('moss_results', $result1);
                $result2->pair = $result1->id;
                $result1->pair = $DB->insert_record('moss_results', $result2);
                $DB->update_record('moss_results', $result1);
            }
        }

        mtrace("Got $rank pairs");

        return true;
    }

    /**
     * 
     * Enter description here ...
     * @param unknown_type $cmid
     */
    protected function clean_results() {
        global $DB;
        $DB->delete_records('moss_results', array('moss' => $this->moss->id));
        //TODO: remove cached pages
    }

    /**
     * 
     * Enter description here ...
     * @param unknown_type $description
     * @param unknown_type $type
     */
    private function trigger_error($description, $errsolution = NULL, $type, $argument)
    {
        global $CFG;
        global $DB;
        $err = new object();
        $err->errdate = time();
        $err->errtype = $type;
        $err->errdescription = $description;
        $err->errstatus = 1;//unsolved
        $err->errsolution = $errsolution;
        if($type == 25)
        {
        	$err->testable = 1;
            $err->errargument = $argument;
        }
        else
        {
        	$err->testable = 0; 
            $err->errargument = 'no argument';
        }
        $DB->insert_record('moss_plugin_errors', $err); 
    }

    /**
     * 
     * Enter description here ...
     * @param unknown_type $type
     * @param unknown_type $arrguments
     */
    public function error_test($type, $argument)
    {
        if($type == 25)
        {
            $fp = fopen($argument, 'r');
            if(!$fp)
                return false;
            else 
                return true;
        }
        else
            return true;
        
    }
}
