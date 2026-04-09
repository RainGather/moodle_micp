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
