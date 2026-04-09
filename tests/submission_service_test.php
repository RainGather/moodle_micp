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

namespace mod_micp;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

use mod_micp\local\submission_repository;
use mod_micp\local\submission_service;

final class submission_service_test extends \advanced_testcase {
    public function test_submit_uses_server_evaluation_not_client_claimed_score(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $micp = $this->create_micp_instance($course->id);
        $repository = new testable_submission_repository();
        $service = new submission_service($repository);

        $result = $service->submit(
            $micp,
            21,
            '{"actions":[{"action":"submit_click"}],"claimedscore":999}',
            '{"clientts":1710000300}'
        );

        $this->assertSame(0, $result['evaluation']['score']);
        $this->assertSame(0, $result['evaluation']['rawgrade']);
        $this->assertSame(0, $repository->capturedscore);
        $this->assertSame('{"actions":[{"action":"submit_click"}],"claimedscore":999}', $repository->capturedrawjson);
        $this->assertSame('{"clientts":1710000300}', $repository->capturedclientmeta);
    }

    private function create_micp_instance(int $courseid): \stdClass {
        global $DB;

        $micp = (object) [
            'course' => $courseid,
            'name' => 'Trust boundary activity',
            'intro' => '',
            'introformat' => 0,
            'grade' => 100,
            'launchpath' => 'sample_content/demo.html',
            'timemodified' => time(),
        ];

        $micp->id = $DB->insert_record('micp', $micp);
        $module = $DB->get_record('modules', ['name' => 'micp'], '*', \MUST_EXIST);
        $DB->insert_record('course_modules', (object) [
            'course' => $courseid,
            'module' => $module->id,
            'instance' => $micp->id,
            'section' => 0,
            'added' => time(),
            'score' => 0,
            'indent' => 0,
            'visible' => 1,
            'visibleoncoursepage' => 1,
            'visibleold' => 1,
            'groupmode' => 0,
            'groupingid' => 0,
            'completion' => 0,
            'completiongradeitemnumber' => null,
            'completionview' => 0,
            'completionexpected' => 0,
            'showdescription' => 0,
            'availability' => null,
            'downloadcontent' => 1,
            'lang' => '',
            'deletioninprogress' => 0,
        ]);

        return $micp;
    }
}

final class testable_submission_repository extends submission_repository {
    public ?int $capturedmicpid = null;
    public ?int $captureduserid = null;
    public ?string $capturedrawjson = null;
    public ?string $capturedclientmeta = null;
    public ?int $capturedscore = null;

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
        $this->capturedmicpid = $micpid;
        $this->captureduserid = $userid;
        $this->capturedrawjson = $rawjson;
        $this->capturedclientmeta = $clientmeta;
        $this->capturedscore = $score;

        return (object) [
            'id' => 9001,
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
            'timecreated' => time(),
            'timemodified' => time(),
        ];
    }
}
