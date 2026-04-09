<?php

// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * mod_micp plugin file.
 *
 * @package     mod_micp
 * @copyright   2026 RainGather
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/backup/moodle2/restore_stepslib.php');

/**
 * Restore structure step for mod_micp.
 */
class restore_micp_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define the structure expected in the backup file.
     *
     * @return array
     */
    protected function define_structure() {
        $paths = [];
        $paths[] = new restore_path_element('micp', '/activity/micp');

        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('micp_event', '/activity/micp/events/event');
            $paths[] = new restore_path_element('micp_submission', '/activity/micp/submissions/submission');
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore the activity instance.
     *
     * @param array $data Parsed backup data.
     */
    protected function process_micp($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();
        unset($data->id);

        $newitemid = $DB->insert_record('micp', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restore a learner event record.
     *
     * @param array $data Parsed backup data.
     */
    protected function process_micp_event($data) {
        global $DB;

        $data = (object)$data;
        $data->micpid = $this->get_new_parentid('micp');
        $data->userid = $this->get_mappingid('user', $data->userid);
        unset($data->id);

        if (empty($data->userid)) {
            return;
        }

        $DB->insert_record('micp_events', $data);
    }

    /**
     * Restore a learner submission record.
     *
     * @param array $data Parsed backup data.
     */
    protected function process_micp_submission($data) {
        global $DB;

        $data = (object)$data;
        $data->micpid = $this->get_new_parentid('micp');
        $data->userid = $this->get_mappingid('user', $data->userid);
        unset($data->id);

        if (empty($data->userid)) {
            return;
        }

        if (!empty($data->reviewedby)) {
            $data->reviewedby = $this->get_mappingid('user', $data->reviewedby);
        } else {
            $data->reviewedby = null;
        }

        $DB->insert_record('micp_submissions', $data);
    }

    /**
     * Restore files associated with the activity.
     */
    protected function after_execute() {
        $this->add_related_files('mod_micp', 'intro', null);
        $this->add_related_files('mod_micp', 'launchpackagezip', null);
        $this->add_related_files('mod_micp', 'launchpackage', null);
    }
}
