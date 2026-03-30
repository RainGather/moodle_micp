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

class submission_repository {
    public function upsert(int $micpid, int $userid, string $rawjson, ?string $clientmeta, int $score): \stdClass {
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
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        $record->id = $DB->insert_record('micp_submissions', $record);

        return $record;
    }
}
