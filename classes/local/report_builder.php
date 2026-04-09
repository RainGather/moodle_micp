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
 * Report-building helpers for mod_micp.
 *
 * @package     mod_micp
 * @copyright   2026 RainGather
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_micp\local;

use context_module;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds teacher-facing activity report structures.
 */
class report_builder {
    /** @var result_service */
    private result_service $resultservice;

    /**
     * Constructor.
     *
     * @param result_service|null $resultservice
     */
    public function __construct(?result_service $resultservice = null) {
        $this->resultservice = $resultservice ?? new result_service();
    }

    /**
     * Build the per-participant report rows.
     *
     * @param stdClass $micp
     * @param mixed $cm
     * @param context_module $context
     * @param int $groupid
     * @return array
     */
    public function get_participant_rows(stdClass $micp, $cm, context_module $context, int $groupid = 0): array {
        $users = \get_enrolled_users(
            $context,
            '',
            $groupid,
            'u.id,u.firstname,u.lastname,u.username,u.email',
            'u.lastname ASC, u.firstname ASC'
        );

        $rows = [];
        foreach ($users as $user) {
            if (\has_capability('mod/micp:addinstance', $context, $user) ||
                    \has_capability('mod/micp:viewreports', $context, $user)) {
                continue;
            }

            $summary = $this->resultservice->get_user_result_summary($micp, (int)$user->id);
            $grade = $this->resultservice->get_user_grade_record($micp, (int)$user->id);
            $rawgrade = $grade && $grade->rawgrade !== null
                ? \format_float((float)$grade->rawgrade, 2)
                : \get_string('nograderecord', 'mod_micp');
            $finalgrade = $grade && $grade->finalgrade !== null
                ? \format_float((float)$grade->finalgrade, 2)
                : \get_string('nograderecord', 'mod_micp');
            $reviewurl = new \moodle_url('/mod/micp/review.php', ['id' => $cm->id, 'userid' => $user->id]);

            $rows[] = [
                'fullname' => \fullname($user),
                'submitted' => !empty($summary['submitted']),
                'hasfinalgrade' => $grade && $grade->finalgrade !== null,
                'submissionstatus' => $summary['submitted']
                    ? \get_string('submitted', 'mod_micp')
                    : \get_string('notsubmitted', 'mod_micp'),
                'reviewstatus' => $summary['reviewstatuslabel'],
                'lastsubmission' => $summary['showsubmittedat']
                    ? $summary['submittedatlabel']
                    : \get_string('never', 'mod_micp'),
                'activityscore' => is_numeric($summary['scorelabel'])
                    ? $summary['scorelabel'] . '%'
                    : $summary['scorelabel'],
                'grade' => $rawgrade . ' / ' . $summary['grademaxlabel'],
                'finalgrade' => $finalgrade,
                'interactiondetails' => array_values($summary['details'] ?? []),
                'interactionbreakdown' => implode(', ', array_map(static function(array $detail): string {
                    return ($detail['label'] ?? \get_string('interactionfallbacklabel', 'mod_micp')) .
                        ': ' . ($detail['scorelabel'] ?? '0 / 0');
                }, $summary['details'] ?? [])),
                'reviewaction' => $summary['submitted'] && ($summary['reviewstatus'] ?? '') !== 'not_required'
                    ? \html_writer::link($reviewurl, \get_string('reviewsubmission', 'mod_micp'))
                    : '',
            ];
        }

        return $rows;
    }

    /**
     * Discover the report's interaction columns.
     *
     * @param array $rows
     * @return array
     */
    public function get_interaction_columns(array $rows): array {
        $columns = [];

        foreach ($rows as $row) {
            foreach (($row['interactiondetails'] ?? []) as $detail) {
                $interactionid = trim((string)($detail['interactionid'] ?? ''));
                if ($interactionid === '' || isset($columns[$interactionid])) {
                    continue;
                }

                $columns[$interactionid] = [
                    'interactionid' => $interactionid,
                    'label' => (string)($detail['label'] ?? $interactionid),
                ];
            }
        }

        return array_values($columns);
    }

    /**
     * Describe the currently selected group.
     *
     * @param mixed $cm
     * @param int $groupid
     * @return string
     */
    public function get_group_label($cm, int $groupid): string {
        if (!\groups_get_activity_groupmode($cm)) {
            return \get_string('groupmodedisabled', 'mod_micp');
        }

        if ($groupid <= 0) {
            return \get_string('allparticipantsgroup', 'mod_micp');
        }

        $group = \groups_get_group($groupid);

        return $group ? \format_string($group->name) : \get_string('allparticipantsgroup', 'mod_micp');
    }

    /**
     * Build group filter options for the report.
     *
     * @param stdClass $course
     * @param mixed $cm
     * @return array
     */
    public function get_group_options(stdClass $course, $cm): array {
        $groups = [];

        if (function_exists('groups_get_activity_allowed_groups')) {
            $groups = \groups_get_activity_allowed_groups($cm);
        }

        if (empty($groups)) {
            $groups = \groups_get_all_groups($course->id, 0, $cm->groupingid ?? 0);
        }

        $options = [0 => \get_string('allparticipantsgroup', 'mod_micp')];

        foreach ($groups as $group) {
            if (!empty($group->id)) {
                $options[(int)$group->id] = \format_string((string)$group->name);
            }
        }

        return $options;
    }
}
