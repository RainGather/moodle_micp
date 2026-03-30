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

namespace mod_micp\local;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/micp/lib.php');

class submission_service {
    private submission_repository $submissions;

    public function __construct(?submission_repository $submissions = null) {
        $this->submissions = $submissions ?? new submission_repository();
    }

    public function submit(\stdClass $micp, int $userid, string $rawjson, ?string $clientmeta): array {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $evaluation = \micp_evaluate($micp, $userid);
        $submission = $this->submissions->upsert(
            $micp->id,
            $userid,
            $rawjson,
            $clientmeta,
            (int) ($evaluation['score'] ?? 0)
        );

        $transaction->allow_commit();

        $gradeupdatestatus = \micp_update_grades($micp, $userid);

        return [
            'submission' => $submission,
            'evaluation' => $evaluation,
            'gradeupdatestatus' => $gradeupdatestatus,
        ];
    }
}
