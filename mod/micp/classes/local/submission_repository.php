<?php

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
