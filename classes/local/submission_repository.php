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

namespace mod_micp\local;

defined('MOODLE_INTERNAL') || die();

class submission_repository {
    public function upsert(
        int $micpid,
        int $userid,
        string $rawjson,
        ?string $clientmeta,
        int $score,
        string $reviewstatus,
        ?int $finalscore = null,
        ?string $reviewjson = null,
        ?int $reviewedby = null,
        int $reviewedat = 0
    ): \stdClass {
        global $DB;

        $now = time();
        $existing = $DB->get_record('micp_submissions', [
            'micpid' => $micpid,
            'userid' => $userid,
        ]);

        if ($existing) {
            $existing->rawjson = $rawjson;
            $existing->clientmeta = $clientmeta;
            $existing->score = $score;
            $existing->reviewstatus = $reviewstatus;
            $existing->finalscore = $finalscore;
            $existing->reviewjson = $reviewjson;
            $existing->reviewedby = $reviewedby;
            $existing->reviewedat = $reviewedat;
            $existing->timemodified = $now;
            $DB->update_record('micp_submissions', $existing);

            return $DB->get_record('micp_submissions', ['id' => $existing->id], '*', \MUST_EXIST);
        }

        $record = (object) [
            'micpid' => $micpid,
            'userid' => $userid,
            'rawjson' => $rawjson,
            'clientmeta' => $clientmeta,
            'score' => $score,
            'reviewstatus' => $reviewstatus,
            'finalscore' => $finalscore,
            'reviewjson' => $reviewjson,
            'reviewedby' => $reviewedby,
            'reviewedat' => $reviewedat,
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        $record->id = $DB->insert_record('micp_submissions', $record);

        return $record;
    }

    public function get_latest(int $micpid, int $userid): ?\stdClass {
        global $DB;

        return $DB->get_record('micp_submissions', [
            'micpid' => $micpid,
            'userid' => $userid,
        ]) ?: null;
    }

    public function save_review(
        int $micpid,
        int $userid,
        int $finalscore,
        string $reviewjson,
        int $reviewedby,
        int $reviewedat,
        string $reviewstatus = 'reviewed'
    ): \stdClass {
        global $DB;

        $submission = $this->get_latest($micpid, $userid);
        if (!$submission) {
            throw new \coding_exception('Cannot save review for missing submission.');
        }

        $submission->finalscore = $finalscore;
        $submission->reviewjson = $reviewjson;
        $submission->reviewedby = $reviewedby;
        $submission->reviewedat = $reviewedat;
        $submission->reviewstatus = $reviewstatus;
        $submission->timemodified = $reviewedat;

        $DB->update_record('micp_submissions', $submission);

        return $DB->get_record('micp_submissions', ['id' => $submission->id], '*', \MUST_EXIST);
    }
}
