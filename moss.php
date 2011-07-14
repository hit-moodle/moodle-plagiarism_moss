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
        global $CFG, $DB, $UNITTEST;
        $this->moss = $DB->get_record('moss', array('cmid' => $cmid));
        if (!isset($UNITTEST)) { // testcase can not construct course structure
            $this->moss->course = $DB->get_field('course_modules', 'course', array('id' => $this->moss->cmid));
        }
        $this->tempdir = $CFG->dataroot.'/temp/moss/'.$this->moss->id;
        if ($CFG->ostype == 'WINDOWS') {
            // the tempdir will be passed to cygwin which require '/' path spliter
            $this->tempdir = str_replace('\\', '/', $this->tempdir);
        }
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

        $mosses = $DB->get_records_select('moss', 'tag = ? AND tag != 0', array($this->moss->tag));
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
            // Convert content charset to UTF-8 if necessary
            $content = mb_convert_encoding($file->get_content(), 'UTF-8', 'UTF-8, '.get_string('localewincharset', 'langconfig'));
            file_put_contents($fullpath, $content);
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
                mtrace($out);
                mtrace($err);
                return false;
            }

            $url_p = '/http:\/\/moss\.stanford\.edu\/results\/\d+/';
            if (!preg_match($url_p, $out, $match)) {
                mtrace($out);
                mtrace($err);
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

            if ($CFG->ostype == 'WINDOWS') {
                $cygwin = get_config('plagiarism_moss', 'cygwinpath');
                $perl = $cygwin.'\\bin\\perl.exe';
                $moss = $CFG->dirroot.'\\plagiarism\\moss\\moss';
                $cmd = str_replace(' ', '\\ ', $CFG->dirroot.'\\plagiarism\\moss\\moss.bat');
                $cmd .= ' "'.$perl.'" "'.$moss.'"';
            } else {
                $cmd = '"'.$CFG->dirroot.'/plagiarism/moss/moss'.'"';
            }
            $cmd .= ' -d';
            $cmd .= ' -u '.get_config('plagiarism_moss', 'mossuserid');;
            $cmd .= ' -l '.$setting->language;
            $cmd .= ' -m '.$setting->sensitivity;

            $basefiles = $fs->get_area_files($context->id, 'plagiarism_moss', 'basefiles', $setting->id, 'filename', false);
            foreach ($basefiles as $basefile) {
                $realpath = $this->tempdir.'/'.$setting->id.'_'.$basefile->get_filename();
                $basefile->copy_content_to($realpath);
                $cmd .= ' -b "'.$realpath.'"';
            }
            $filepatterns = explode(' ', $setting->filepatterns);
            foreach ($filepatterns as $filepattern) {
                $cmd .= ' '.str_replace(' ', '\\ ', $this->tempdir.'/*/'.$filepattern);
            }
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace($cmd);
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
        global $DB, $UNITTEST;

        mtrace("Processing $url");

        if (!$result_page = download_file_content($url)) {
            mtrace("can not read $url");
            return false;
        }

        preg_match_all(
            '/(?P<link>http:\/\/moss\.stanford\.edu\/results\/\d+\/match\d+\.html)">.+\/(?P<user1>\d+)\/ \((?P<percentage1>\d+)%\).+\/(?P<user2>\d+)\/ \((?P<percentage2>\d+)%\).+right>(?P<linesmatched>\d+)\\n/Us',
            $result_page,
            $matches,
            PREG_SET_ORDER
        );

        if (empty($matches)) {
            mtrace("can not parse $url");
            return false;
        }

        if (!isset($UNITTEST)) { // testcase can not construct course structure
            $context = get_context_instance(CONTEXT_COURSE, $this->moss->course);
        }

        $filepatterns = $DB->get_field('moss_configs', 'filepatterns', array('id' => $configid));
        $filepatterns = explode(' ', $filepatterns);
        $fs = get_file_storage();
        $rank = 1;
        foreach ($matches as $result) {
            $result['moss'] = $this->moss->id;
            $result['config'] = $configid;
            $result['rank'] = $rank + 1;
            $result1 = (object)$result;
            $result1->userid = $result['user1'];
            $result1->percentage = $result['percentage1'];
            $result2 = (object)$result;
            $result2->userid = $result['user2'];
            $result2->percentage = $result['percentage2'];

            if (!isset($UNITTEST)) { // testcase can not construct course structure
                // skip unenrolled users
                if (!is_enrolled($context, $result1->userid) and !is_enrolled($context, $result2->userid)) {
                    continue;
                }
            }

            $result1->id = $DB->insert_record('moss_results', $result1);
            $result2->pair = $result1->id;
            $result2->id = $DB->insert_record('moss_results', $result2);
            $result1->pair = $result2->id;
            $DB->update_record('moss_results', $result1);

            // update moss_matched_files db
            for ($i=1; $i<=2; $i++) {
                $userid = eval('return $result'.$i.'->userid;');
                $resultid = eval('return $result'.$i.'->id;');

                $files = $fs->get_directory_files(get_system_context()->id, 'plagiarism_moss', 'files', $this->moss->cmid, "/$userid/");
                foreach ($files as $file) {
                    foreach ($filepatterns as $pattern) {
                        if (fnmatch($pattern, $file->get_filename())) {
                            $obj = new stdClass();
                            $obj->result = $resultid;
                            $obj->contenthash = $file->get_contenthash();
                            $DB->insert_record('moss_matched_files', $obj);
                        }
                    }
                }
            }

            $rank++;
        }

        mtrace("Got $rank pairs");

        return true;
    }

    /**
     * Clean current stored results
     */
    protected function clean_results() {
        global $DB;

        $sql = 'DELETE FROM {moss_matched_files}
                WHERE result in (
                    SELECT id FROM {moss_results}
                    WHERE moss = ?
                )';
        $DB->execute($sql, array($this->moss->id));
        $DB->delete_records('moss_results', array('moss' => $this->moss->id));
        //TODO: remove cached pages
    }
}

