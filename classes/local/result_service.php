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
 * Result and grade handling for mod_micp.
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
 * Builds learner summaries and synchronises gradebook records.
 */
class result_service {
    /** @var scoring_service */
    private scoring_service $scoringservice;

    /**
     * Constructor.
     *
     * @param scoring_service|null $scoringservice
     */
    public function __construct(?scoring_service $scoringservice = null) {
        $this->scoringservice = $scoringservice ?? new scoring_service();
    }

    /**
     * Evaluate a learner attempt.
     *
     * @param stdClass $micp
     * @param int $userid
     * @return array
     */
    public function evaluate(stdClass $micp, int $userid): array {
        $cm = \get_coursemodule_from_instance('micp', $micp->id, $micp->course, false, \MUST_EXIST);
        $context = context_module::instance((int)$cm->id);

        return $this->scoringservice->evaluate($micp, $userid, $context);
    }

    /**
     * Build a single grade_update entry.
     *
     * @param stdClass $micp
     * @param int $userid
     * @return stdClass
     */
    public function build_grade_entry(stdClass $micp, int $userid): stdClass {
        $submission = $this->get_submission_record($micp, $userid);
        $evaluation = $this->evaluate($micp, $userid);
        $rawgrade = $this->get_effective_raw_grade($micp, $submission, $evaluation);

        return (object)[
            'userid' => $userid,
            'rawgrade' => $rawgrade,
        ];
    }

    /**
     * Get a learner submission row.
     *
     * @param stdClass $micp
     * @param int $userid
     * @return stdClass|null
     */
    public function get_submission_record(stdClass $micp, int $userid): ?stdClass {
        global $DB;

        return $DB->get_record('micp_submissions', [
            'micpid' => $micp->id,
            'userid' => $userid,
        ]) ?: null;
    }

    /**
     * Decode stored review data safely.
     *
     * @param stdClass|null $submission
     * @return array
     */
    public function get_review_data(?stdClass $submission): array {
        if (!$submission || empty($submission->reviewjson)) {
            return [];
        }

        $data = json_decode((string)$submission->reviewjson, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Return the visible percentage score for a learner.
     *
     * @param stdClass|null $submission
     * @param array $evaluation
     * @return int|null
     */
    public function get_effective_score_percent(?stdClass $submission, array $evaluation): ?int {
        if ($submission && ($submission->reviewstatus ?? '') === 'pending') {
            return null;
        }

        if ($submission && ($submission->reviewstatus ?? '') === 'reviewed' && $submission->finalscore !== null) {
            return (int)$submission->finalscore;
        }

        return (int)($evaluation['score'] ?? 0);
    }

    /**
     * Convert a visible score into a gradebook raw grade.
     *
     * @param stdClass $micp
     * @param stdClass|null $submission
     * @param array $evaluation
     * @return float|null
     */
    public function get_effective_raw_grade(stdClass $micp, ?stdClass $submission, array $evaluation): ?float {
        $score = $this->get_effective_score_percent($submission, $evaluation);

        if ($score === null) {
            return null;
        }

        return (float)round((activity_settings::normalize_grade($micp->grade ?? null) * $score) / 100, 2);
    }

    /**
     * Extract action rows from a stored submission payload.
     *
     * @param stdClass|null $submission
     * @return array
     */
    public function extract_submission_actions(?stdClass $submission): array {
        if (!$submission || empty($submission->rawjson)) {
            return [];
        }

        $raw = json_decode((string)$submission->rawjson, true);
        if (!is_array($raw)) {
            return [];
        }

        $actions = $raw['raw']['actions'] ?? $raw['actions'] ?? [];

        return is_array($actions) ? $actions : [];
    }

    /**
     * Index the latest action by interaction id.
     *
     * @param stdClass|null $submission
     * @return array
     */
    public function get_latest_submission_actions_by_interactionid(?stdClass $submission): array {
        $latest = [];

        foreach ($this->extract_submission_actions($submission) as $action) {
            if (!is_array($action)) {
                continue;
            }

            $interactionid = trim((string)($action['interactionid'] ?? ''));
            if ($interactionid === '') {
                continue;
            }

            $latest[$interactionid] = $action;
        }

        return $latest;
    }

    /**
     * Find all users with grade-relevant data in this activity.
     *
     * @param int $micpid
     * @return array
     */
    public function get_graded_userids(int $micpid): array {
        global $DB;

        $sql = 'SELECT userid
                  FROM {micp_events}
                 WHERE micpid = :eventmicpid
              GROUP BY userid
                UNION
                SELECT userid
                  FROM {micp_submissions}
                 WHERE micpid = :submissionmicpid';

        return array_map('intval', array_keys($DB->get_records_sql($sql, [
            'eventmicpid' => $micpid,
            'submissionmicpid' => $micpid,
        ])));
    }

    /**
     * Create or update the gradebook item definition.
     *
     * @param stdClass $micp
     * @param array|null $grades
     * @return int
     */
    public function grade_item_update(stdClass $micp, ?array $grades = null): int {
        $grademax = activity_settings::normalize_grade($micp->grade ?? null);

        $item = [
            'itemname' => \clean_param($micp->name, \PARAM_NOTAGS),
            'gradetype' => \GRADE_TYPE_VALUE,
            'grademax' => $grademax,
            'grademin' => 0,
        ];

        return \grade_update('mod/micp', $micp->course, 'mod', 'micp', $micp->id, 0, $grades, $item);
    }

    /**
     * Update one learner grade or the whole activity gradebook set.
     *
     * @param stdClass $micp
     * @param int $userid
     * @return int
     */
    public function update_grades(stdClass $micp, int $userid = 0): int {
        if (!empty($userid)) {
            return $this->grade_item_update($micp, [
                $userid => $this->build_grade_entry($micp, $userid),
            ]);
        }

        $grades = [];
        foreach ($this->get_graded_userids($micp->id) as $gradeduserid) {
            $grades[$gradeduserid] = $this->build_grade_entry($micp, $gradeduserid);
        }

        return $this->grade_item_update($micp, $grades ?: null);
    }

    /**
     * Build the learner-facing result summary used by pages and AJAX responses.
     *
     * @param stdClass $micp
     * @param int $userid
     * @return array
     */
    public function get_user_result_summary(stdClass $micp, int $userid): array {
        global $DB;

        $submission = $this->get_submission_record($micp, $userid);
        $interactionfallback = \get_string('interactionfallbacklabel', 'mod_micp');

        $hasinteraction = $DB->record_exists('micp_events', [
            'micpid' => $micp->id,
            'userid' => $userid,
            'eventtype' => 'interaction',
        ]);

        $evaluation = $this->evaluate($micp, $userid);
        $reviewdata = $this->get_review_data($submission);
        $grademax = activity_settings::normalize_grade($micp->grade ?? null);
        $submitted = !empty($submission);
        $effectivescore = $this->get_effective_score_percent($submission, $evaluation);
        $effectiverawgrade = $this->get_effective_raw_grade($micp, $submission, $evaluation);
        $reviewstatus = $submission->reviewstatus ?? 'not_required';

        if (!$submitted) {
            $statuslabel = \get_string('notsubmitted', 'mod_micp');
            $statusclass = 'pending';
            $reviewstatuslabel = \get_string('reviewstatusnotapplicable', 'mod_micp');
        } else if ($reviewstatus === 'pending') {
            $statuslabel = \get_string('pendingteacherreview', 'mod_micp');
            $statusclass = 'pending-review';
            $reviewstatuslabel = \get_string('reviewstatuspending', 'mod_micp');
        } else if ($reviewstatus === 'reviewed') {
            $statuslabel = \get_string('reviewedbyteacher', 'mod_micp');
            $statusclass = 'reviewed';
            $reviewstatuslabel = \get_string('reviewstatusreviewed', 'mod_micp');
        } else {
            $statuslabel = \get_string('submitted', 'mod_micp');
            $statusclass = 'submitted';
            $reviewstatuslabel = \get_string('reviewstatusnotrequired', 'mod_micp');
        }

        return [
            'submitted' => $submitted,
            'statuslabel' => $statuslabel,
            'statusclass' => $statusclass,
            'reviewstatus' => $reviewstatus,
            'reviewstatuslabel' => $reviewstatuslabel,
            'hasinteraction' => $hasinteraction,
            'interactionslabel' => $hasinteraction
                ? \get_string('interactionrecorded', 'mod_micp')
                : \get_string('nointeractionrecorded', 'mod_micp'),
            'scorelabel' => $effectivescore === null
                ? \get_string('pendingreviewshort', 'mod_micp')
                : (string)$effectivescore,
            'rawgradelabel' => $effectiverawgrade === null
                ? \get_string('pendingreviewshort', 'mod_micp')
                : \format_float($effectiverawgrade, 2),
            'grademaxlabel' => (string)$grademax,
            'showsubmittedat' => $submitted && !empty($submission->timemodified),
            'submittedatlabel' => $submitted && !empty($submission->timemodified)
                ? \userdate((int)$submission->timemodified)
                : '',
            'details' => array_map(static function(array $detail) use ($interactionfallback, $reviewdata): array {
                $reviewitem = $reviewdata['items'][$detail['interactionid'] ?? ''] ?? [];
                $isreviewedmanual = ($detail['gradingmode'] ?? 'auto') === 'manual' &&
                    array_key_exists('score', $reviewitem);
                $scorelabel = \format_float((float)($detail['earned'] ?? 0), 2) . ' / ' .
                    \format_float((float)($detail['max'] ?? 0), 2);

                if (($detail['gradingmode'] ?? 'auto') === 'manual') {
                    if ($isreviewedmanual) {
                        $scorelabel = \format_float((float)$reviewitem['score'], 2) . ' / ' .
                            \format_float((float)($detail['max'] ?? 0), 2);
                    } else if (!empty($detail['manualreviewrequired'])) {
                        $scorelabel = \get_string('pendingreviewdetail', 'mod_micp') . ' / ' .
                            \format_float((float)($detail['max'] ?? 0), 2);
                    }
                }

                return [
                    'interactionid' => (string)($detail['interactionid'] ?? ''),
                    'label' => (string)($detail['label'] ?? $detail['interactionid'] ?? $interactionfallback),
                    'scorelabel' => $scorelabel,
                    'complete' => !empty($detail['complete']),
                ];
            }, $evaluation['details'] ?? []),
            'showdetails' => !empty($evaluation['details']),
        ];
    }

    /**
     * Fetch the learner gradebook row for this activity.
     *
     * @param stdClass $micp
     * @param int $userid
     * @return stdClass|null
     */
    public function get_user_grade_record(stdClass $micp, int $userid): ?stdClass {
        global $DB;

        $gradeitem = $DB->get_record('grade_items', [
            'itemmodule' => 'micp',
            'iteminstance' => $micp->id,
            'itemnumber' => 0,
        ]);

        if (!$gradeitem) {
            return null;
        }

        return $DB->get_record('grade_grades', [
            'itemid' => $gradeitem->id,
            'userid' => $userid,
        ]) ?: null;
    }
}
