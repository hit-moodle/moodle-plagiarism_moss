<?php

/**
 * Backup moss settings
 *
 * We do not backup results since it is hard to restore 'pair' field, :-(
 */
defined('MOODLE_INTERNAL') || die();


class backup_plagiarism_moss_plugin extends backup_plagiarism_plugin {
    function define_module_plugin_structure() {
        // Define the virtual plugin element without conditions as the global class checks already.
        $plugin = $this->get_plugin_element();

        // Create one standard named plugin element (the visible container)
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());

        // connect the visible container ASAP
        $plugin->add_child($pluginwrapper);

        $tags = new backup_nested_element('tags');
        $tag = new backup_nested_element('tag', array('id'), array('name'));
        $pluginwrapper->add_child($tags);
        $tags->add_child($tag);
        $sql = 'SELECT t.*
                FROM {plagiarism_moss_tags} t
                LEFT JOIN {moss} m 
                    ON m.tag = t.id
                WHERE m.cmid = ?
        ';
        $tag->set_source_sql($sql, array(backup::VAR_PARENTID));

        $mosses = new backup_nested_element('mosses');
        $moss = new backup_nested_element('moss', array('id'), array('cmid', 'timetomeasure', 'timemeasured', 'tag', 'sensitivity', 'enabled', 'coursename', 'modulename'));
        $pluginwrapper->add_child($mosses);
        $mosses->add_child($moss);
        $moss->set_source_table('plagiarism_moss', array('cmid' => backup::VAR_PARENTID));

        $configs = new backup_nested_element('configs');
        $config = new backup_nested_element('config', array('id'), array('moss', 'filepatterns', 'language'));
        $moss->add_child($configs);
        $configs->add_child($config);
        $config->set_source_table('plagiarism_moss_configs', array('moss' => backup::VAR_PARENTID));

        $config->annotate_files('plagiarism_moss', 'basefiles', 'id');

        return $plugin;
    }
}

