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

require_once($CFG->dirroot.'/plagiarism/lib.php');
require_once($CFG->dirroot.'/plagiarism/moss/locallib.php');

define('MOSS_MAX_PATTERNS', 3);

/**
 * plagiarism_plugin_moss inherit from plagiarism_plugin class, this is the most important class in plagiarism plugin,
 * Moodle platform will automatically call the function of this class.
 * @author Sun Zhigang
 *
 */
class plagiarism_plugin_moss extends plagiarism_plugin {
	/**
	 * (non-PHPdoc)
	 * @see plagiarism_plugin::print_disclosure()
	 */
    public function print_disclosure($cmid) {
    	global $OUTPUT, $DB;
        if (moss_enabled($cmid)) {
            echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');

            $moss = $DB->get_record('moss', array('cmid' => $cmid), 'timetomeasure, timemeasured');
            $a->timemeasured = userdate($moss->timemeasured);
            if ($moss->timemeasured == 0) {
                $disclosure = get_string('disclosurenevermeasured', 'plagiarism_moss', $a);
            } else {
                $disclosure = get_string('disclosurehasmeasured', 'plagiarism_moss', $a);
            }

            if ($moss->timemeasured != 0 and has_capability('plagiarism/moss:viewallresults', get_context_instance(CONTEXT_MODULE, $cmid))) {
                $url = new moodle_url('/plagiarism/moss/view.php', array('id' => $cmid));
                $disclosure .= ' '.html_writer::link($url, get_string('clicktoviewresults', 'plagiarism_moss'));
            }

            echo format_text($disclosure, FORMAT_MOODLE);
            echo $OUTPUT->box_end();
    	}
    }

    /**
     * Hook to save plagiarism specific settings on a module settings page
     * @param object $data - data from an mform submission.
     */
    public function save_form_elements($data) {
        global $DB;

        if (!moss_enabled()) {
            return;
        }

        $moss = new stdClass();
        $moss->enabled = empty($data->enabled) ? 0 : 1;
        if (!$moss->enabled) {
            if (isset($data->mossid)) { // disable it
                $DB->set_field('moss', 'enabled', 0, array('id' => $data->mossid));
            }
            // disabled mosses keep old configs
            return;
        }

        $moss->timetomeasure = $data->timetomeasure;
        $moss->cmid = $data->coursemodule;
        $moss->sensitivity = $data->sensitivity;
        $moss->modulename = $data->name;
        $moss->coursename = $DB->get_field('course', 'shortname', array('id' => $data->course));

        // process tag
        if (empty($data->tag)) {
            $moss->tag = 0;
        } else {
            if ($tagid = $DB->get_field('moss_tags', 'id', array('name' => $data->tag))) {
                $moss->tag = $tagid;
            } else {
                $tag = new stdClass();
                $tag->name = $data->tag;
                $moss->tag = $DB->insert_record('moss_tags', $tag);
            }
        }

        if (isset($data->mossid)) {
            $moss->id = $data->mossid;
            $DB->update_record('moss', $moss);
        } else {
            $data->mossid = $DB->insert_record('moss', $moss);
        }

        // sub configs
        for($index = 0; $index < MOSS_MAX_PATTERNS; $index++) {
            $config = new stdClass();
            $config->moss = $data->mossid;
            $member = 'language'.$index;
            $config->language = isset($data->$member) ? $data->$member : get_config('plagiarism_moss', 'defaultlanguage');

            $member = 'filepatterns'.$index;
            $config->filepatterns = str_replace('\\', '_', str_replace('/', '_', $data->$member)); // filter out path chars
            if ($index == 0 and empty($config->filepatterns)) {
                $config->filepatterns = '*';
            }

            $member= 'configid'.$index;
            if (isset($data->$member)) {
                $config->id = $data->$member;
                $DB->update_record('moss_configs', $config);
            } else {
                $config->id = $DB->insert_record('moss_configs', $config);
            }

            $context = get_system_context();
            $member= 'basefile'.$index;
            file_save_draft_area_files($data->$member, $context->id, 'plagiarism_moss', 'basefiles', $config->id);
        }
    }

    /**
     * (non-PHPdoc)
     * @see plagiarism_plugin::get_form_elements_module()
     */
    public function get_form_elements_module($mform, $context) {
        global $DB;

        if (!moss_enabled()) {
            return;
        }

        // Construct the form
        $mform->addElement('header', 'mossdesc', get_string('moss', 'plagiarism_moss'));
        $mform->addHelpButton('mossdesc', 'moss', 'plagiarism_moss');

        $mform->addElement('checkbox', 'enabled', get_string('mossenabled', 'plagiarism_moss'));

        $mform->addElement('date_time_selector', 'timetomeasure', get_string('timetomeasure', 'plagiarism_moss'), array('optional' => true));
        $mform->addHelpButton('timetomeasure', 'timetomeasure', 'plagiarism_moss');
        $mform->disabledIf('timetomeasure', 'enabled');

        $mform->addElement('text', 'tag', get_string('tag', 'plagiarism_moss'));
        $mform->addHelpButton('tag', 'tag', 'plagiarism_moss');
        $mform->setType('tag', PARAM_TEXT);
        $mform->disabledIf('tag', 'enabled');

        $mform->addElement('text', 'sensitivity', get_string('sensitivity', 'plagiarism_moss'), 'size = "10"');
        $mform->addHelpButton('sensitivity', 'sensitivity', 'plagiarism_moss');
        $mform->setType('sensitivity', PARAM_NUMBER);
        $mform->addRule('sensitivity', null, 'numeric', null, 'client');
        $mform->disabledIf('sensitivity', 'enabled');

        // multi configs
        for($index = 0; $index < MOSS_MAX_PATTERNS; $index++) {
            if ($index == 0) {
                $subheader = get_string('configrequired', 'plagiarism_moss', $index+1);
            } else {
                $subheader = get_string('configoptional', 'plagiarism_moss', $index+1);
            }
            $subheader = html_writer::tag('strong', $subheader);
            $mform->addElement('static', 'subheader'.$index, $subheader);

            $mform->addElement('text', 'filepatterns'.$index, get_string('filepatterns', 'plagiarism_moss'));
            $mform->addHelpButton('filepatterns'.$index, 'filepatterns', 'plagiarism_moss');
            $mform->setType('filepatterns'.$index, PARAM_TEXT);
            $mform->disabledIf('filepatterns'.$index, 'enabled');

            $choices = moss_get_supported_languages();
            $mform->addElement('select', 'language'.$index, get_string('language', 'plagiarism_moss'), $choices);
            $mform->disabledIf('language'.$index, 'enabled');
            $mform->setDefault('language'.$index, get_config('plagiarism_moss', 'defaultlanguage'));

            $mform->addElement('filemanager', 'basefile'.$index, get_string('basefile', 'plagiarism_moss'), null, array('subdirs' => 0));
            $mform->addHelpButton('basefile'.$index, 'basefile', 'plagiarism_moss');
            $mform->disabledIf('basefile'.$index, 'enabled');

            if ($index != 0) {
                $mform->setAdvanced('subheader'.$index);
                $mform->setAdvanced('filepatterns'.$index);
                $mform->setAdvanced('language'.$index);
                $mform->setAdvanced('basefile'.$index);
            }
        }

        // set config values
        $cmid = optional_param('update', 0, PARAM_INT); //there doesn't seem to be a way to obtain the current cm a better way - $this->_cm is not available here.
        if ($cmid != 0 and $moss = $DB->get_record('moss', array('cmid'=>$cmid))) { // configed
            $mform->setDefault('enabled', $moss->enabled);
            $mform->setDefault('timetomeasure', $moss->timetomeasure);
            $mform->setDefault('tag', $DB->get_field('moss_tags', 'name', array('id' => $moss->tag)));
            if (!empty($moss->sensitivity)) {
                $mform->setDefault('sensitivity', $moss->sensitivity);
            }
            $mform->addElement('hidden', 'mossid', $moss->id);

            $subconfigs = $DB->get_records('moss_configs', array('moss'=>$moss->id));
            $index = 0;
            foreach ($subconfigs as $subconfig) {
                $mform->setDefault('filepatterns'.$index, $subconfig->filepatterns);
                $mform->setDefault('language'.$index, $subconfig->language);
                $mform->addElement('hidden', 'configid'.$index, $subconfig->id);

                $context = get_system_context();
                $draftitemid = 0;
                file_prepare_draft_area($draftitemid, $context->id, 'plagiarism_moss', 'basefiles', $subconfig->id);
                $mform->setDefault('basefile'.$index, $draftitemid);

                $index++;
            }
        } else { // new config
            $mform->setDefault('enabled', 0);
            $mform->setDefault('tag', '');
            $mform->setDefault('filepatterns0', '*');
            // leave other subconfig empty
        }
    }

    /**
     * hook to allow plagiarism specific information to be displayed beside a submission
     *
     * @param array  $linkarraycontains all relevant information for the plugin to generate a link
     * @return string
     */
    public function get_links($linkarray) {
        global $DB, $OUTPUT;

        $link = '';
        if (!moss_enabled($linkarray['cmid'])) {
            return $link;
        }

        $sql = 'SELECT r.*
                FROM {moss_results} r
                LEFT JOIN {moss_matched_files} f ON r.id = f.result
                WHERE f.contenthash = :contenthash AND r.userid = :userid AND r.moss = :mossid ';
        if (!has_capability('plagiarism/moss:viewunconfirmed', get_context_instance(CONTEXT_MODULE, $linkarray['cmid']))) {
            $sql .= 'AND r.confirmed = 1 ';
        }
        $sql .= 'ORDER BY r.rank ASC';
        $params = array(
            'userid'      => $linkarray['userid'],
            'contenthash' => $linkarray['file']->get_contenthash(),
            'mossid'      => $DB->get_field('moss', 'id', array('cmid' => $linkarray['cmid']))
        );

        $results = $DB->get_records_sql($sql, $params);
        if (!empty($results)){
            $result = current($results);

            $text = $result->percentage.'%'."($result->linesmatched)";
            $icon = $OUTPUT->pix_icon('i/completion-manual-n', get_string('unconfirmed', 'plagiarism_moss'));
            foreach ($results as $r) {
                if ($r->confirmed) {
                    $icon = $OUTPUT->pix_icon('i/completion-manual-y', get_string('confirmed', 'plagiarism_moss'));
                    break;
                }
            }
            $text .= $icon;

            $result->count = count($results);
            $title = get_string('resultlinktitle', 'plagiarism_moss', $result);

            $params = array('id' => $linkarray['cmid'], 'user' => $linkarray['userid']);
            $url = new moodle_url('/plagiarism/moss/view.php', $params);

            $link = html_writer::link($url, $text, array('title' => $title));
        }

        return $link;
    }

    /**
     * Hook for cron
     */
    public function cron() {
        global $DB;

        mtrace('---Moss begins---');

        // get mosses measure on specified time
        $select  = 'timetomeasure < ? AND timetomeasure > timemeasured AND enabled = 1 AND timetomeasure != 0';
        $mosses = $DB->get_records_select('moss', $select, array(time()));

        // get mosses measure on activity due date
        $duemosses = $DB->get_records('moss', array('enabled' => 1, 'timetomeasure' => 0));
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
        mtrace('---Moss done---');
    }
}

/**
 *
 * Enter description here ...
 * @param unknown_type $eventdata
 */
function moss_event_file_uploaded($eventdata) {
    return moss_save_files_from_event($eventdata);
}

/**
 * A module has been deleted
 */
function moss_event_mod_deleted($eventdata) {
    return moss_clean_cm($eventdata->cmid);
}
