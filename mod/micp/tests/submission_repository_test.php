<?php

namespace mod_micp;

defined('MOODLE_INTERNAL') || die();

use mod_micp\local\submission_repository;

final class submission_repository_test extends \advanced_testcase {
    public function test_upsert_inserts_then_updates_single_submission_row(): void {
        global $DB;

        $this->resetAfterTest();

        $repository = new submission_repository();
        $first = $repository->upsert(42, 7, '{"actions":[{"action":"button_click"}]}', '{"clientts":1710000100}', 0);

        $updated = $repository->upsert(42, 7, '{"actions":[{"action":"completion_click"}]}', '{"clientts":1710000200}', 100);

        $this->assertSame($first->id, $updated->id);
        $this->assertSame(1, $DB->count_records('micp_submissions', ['micpid' => 42, 'userid' => 7]));
        $this->assertSame('{"actions":[{"action":"completion_click"}]}', $updated->rawjson);
        $this->assertSame('{"clientts":1710000200}', $updated->clientmeta);
        $this->assertSame(100, (int) $updated->score);
    }
}
