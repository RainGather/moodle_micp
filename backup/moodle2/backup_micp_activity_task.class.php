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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/backup/moodle2/backup_activity_task.class.php');
require_once(__DIR__ . '/backup_micp_stepslib.php');

/**
 * Backup task for mod_micp.
 */
class backup_micp_activity_task extends backup_activity_task {
    /**
     * Define module-specific backup settings.
     */
    protected function define_my_settings() {
        // No additional settings required.
    }

    /**
     * Define module backup steps.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_micp_activity_structure_step('micp_structure', 'micp.xml'));
    }

    /**
     * Encode any links to the activity.
     *
     * @param string $content Content being backed up.
     * @return string
     */
    public static function encode_content_links($content) {
        return (string)$content;
    }
}
