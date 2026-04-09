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

require_once($CFG->dirroot . '/backup/moodle2/restore_activity_task.class.php');
require_once(__DIR__ . '/restore_micp_stepslib.php');

/**
 * Restore task for mod_micp.
 */
class restore_micp_activity_task extends restore_activity_task {
    /**
     * Define module-specific restore settings.
     */
    protected function define_my_settings() {
        // No additional settings required.
    }

    /**
     * Define module restore steps.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_micp_activity_structure_step('micp_structure', 'micp.xml'));
    }

    /**
     * Define the contents that need link decoding.
     *
     * @return array
     */
    public static function define_decode_contents() {
        return [
            new restore_decode_content('micp', ['intro'], 'micp'),
        ];
    }

    /**
     * Define the rules used to decode links.
     *
     * @return array
     */
    public static function define_decode_rules() {
        return [];
    }

    /**
     * Define restore log rules for module-level logs.
     *
     * @return array
     */
    public static function define_restore_log_rules() {
        return [];
    }

    /**
     * Define restore log rules for course-level logs.
     *
     * @return array
     */
    public static function define_restore_log_rules_for_course() {
        return [];
    }
}
