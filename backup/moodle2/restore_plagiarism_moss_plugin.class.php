<?php

defined('MOODLE_INTERNAL') || die();


class restore_plagiarism_moss_plugin extends restore_plagiarism_plugin {
    protected $existingcourse;

    /**
     * Returns the paths to be handled by the plugin at module level
     */
    protected function define_module_plugin_structure() {
        $paths = array();

        // Add own format stuff
        $elename = $this->get_namefor('tag');
        $elepath = $this->get_pathfor('tags/tag'); // we used get_recommended_name() so this works
        $paths[] = new restore_path_element($elename, $elepath);

        $elename = $this->get_namefor('moss');
        $elepath = $this->get_pathfor('mosses/moss'); // we used get_recommended_name() so this works
        $paths[] = new restore_path_element($elename, $elepath);

        $elename = $this->get_namefor('config');
        $elepath = $this->get_pathfor('/mosses/moss/configs/config'); // we used get_recommended_name() so this works
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths; // And we return the interesting paths
    }

    public function process_plagiarism_moss_tag($data) {
        global $DB;
        $data = (object)$data;

        if ($existingtag = $DB->get_record('moss_tags', array('name' => $data->name))) {
            $newid = $existingtag->id;
        } else {
            $newid = $DB->insert_record('moss_tags', $data);
        }
        $this->set_mapping('moss_tags', $data->id, $newid);
    }

    public function process_plagiarism_moss_moss($data) {
        global $DB;
        $data = (object)$data;

        $data->cmid = $this->task->get_moduleid();
        $data->timetomeasure = $this->apply_date_offset($data->timetomeasure);
        $data->timemeasured = $this->apply_date_offset($data->timemeasured);
        if ($data->tag != 0) {
            $data->tag = $this->get_mappingid('moss_tags', $data->tag);
        }
        $newid = $DB->insert_record('moss', $data);
        $this->set_mapping('moss_moss', $data->id, $newid);
    }

    public function process_plagiarism_moss_config($data) {
        global $DB;
        $data = (object)$data;

        $data->moss = $this->get_mappingid('moss_moss', $data->moss);
        $DB->insert_record('moss_configs', $data);

        $this->add_related_files('plagiarism_moss', 'basefiles', 'moss_configs', get_system_context()->id, $data->id);
    }
}
