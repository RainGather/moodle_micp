<?php

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
