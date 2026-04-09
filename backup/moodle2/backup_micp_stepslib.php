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

require_once($CFG->dirroot . '/backup/moodle2/backup_stepslib.php');

/**
 * Backup structure step for mod_micp.
 */
class backup_micp_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the backup structure for the activity.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $micp = new backup_nested_element('micp', ['id'], [
            'name',
            'intro',
            'introformat',
            'grade',
            'launchpath',
            'timemodified',
        ]);

        $events = new backup_nested_element('events');
        $event = new backup_nested_element('event', ['id'], [
            'userid',
            'eventtype',
            'payload',
            'clientts',
            'timecreated',
        ]);

        $submissions = new backup_nested_element('submissions');
        $submission = new backup_nested_element('submission', ['id'], [
            'userid',
            'rawjson',
            'clientmeta',
            'score',
            'reviewstatus',
            'finalscore',
            'reviewjson',
            'reviewedby',
            'reviewedat',
            'timecreated',
            'timemodified',
        ]);

        $micp->add_child($events);
        $events->add_child($event);

        $micp->add_child($submissions);
        $submissions->add_child($submission);

        $micp->set_source_table('micp', ['id' => backup::VAR_ACTIVITYID]);

        if ($userinfo) {
            $event->set_source_table('micp_events', ['micpid' => backup::VAR_PARENTID]);
            $submission->set_source_table('micp_submissions', ['micpid' => backup::VAR_PARENTID]);

            $event->annotate_ids('user', 'userid');
            $submission->annotate_ids('user', 'userid');
            $submission->annotate_ids('user', 'reviewedby');
        }

        $micp->annotate_files('mod_micp', 'intro', null);
        $micp->annotate_files('mod_micp', 'launchpackagezip', null);
        $micp->annotate_files('mod_micp', 'launchpackage', null);

        return $this->prepare_activity_structure($micp);
    }
}
