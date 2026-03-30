<?php

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

final class mod_micp_lib_test extends advanced_testcase {
    public function test_micp_evaluate_returns_weighted_score_from_scoring_config(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $micp = $this->create_micp_instance($course->id);

        $DB->insert_record('micp_events', (object) [
            'micpid' => $micp->id,
            'userid' => $user->id,
            'eventtype' => 'interaction',
            'payload' => json_encode(['interactionid' => 'button_demo', 'response' => 'clicked']),
            'clientts' => 1710000000,
            'timecreated' => time(),
        ]);
        $DB->insert_record('micp_events', (object) [
            'micpid' => $micp->id,
            'userid' => $user->id,
            'eventtype' => 'interaction',
            'payload' => json_encode(['interactionid' => 'completion_marker', 'completed' => true, 'outcome' => 'completed']),
            'clientts' => 1710000002,
            'timecreated' => time(),
        ]);

        $evaluation = micp_evaluate($micp, (int) $user->id);

        $this->assertSame(60, $evaluation['score']);
        $this->assertSame(60, $evaluation['rawgrade']);
        $this->assertCount(3, $evaluation['details']);
    }

    public function test_micp_evaluate_returns_zero_without_interaction_event(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $micp = $this->create_micp_instance($course->id);

        $DB->insert_record('micp_events', (object) [
            'micpid' => $micp->id,
            'userid' => $user->id,
            'eventtype' => 'view',
            'payload' => json_encode(['action' => 'page_view']),
            'clientts' => 1710000001,
            'timecreated' => time(),
        ]);

        $evaluation = micp_evaluate($micp, (int) $user->id);

        $this->assertSame(0, $evaluation['score']);
        $this->assertSame(0, $evaluation['rawgrade']);
    }

    private function create_micp_instance(int $courseid): stdClass {
        global $DB;

        $micp = (object) [
            'course' => $courseid,
            'name' => 'Demo MICP activity',
            'intro' => '',
            'introformat' => 0,
            'grade' => 100,
            'launchpath' => 'sample_content/demo.html',
            'timemodified' => time(),
        ];

        $micp->id = $DB->insert_record('micp', $micp);
        $this->create_course_module($courseid, $micp->id);

        return $micp;
    }

    private function create_course_module(int $courseid, int $instanceid): void {
        global $DB;

        $module = $DB->get_record('modules', ['name' => 'micp'], '*', MUST_EXIST);
        $DB->insert_record('course_modules', (object) [
            'course' => $courseid,
            'module' => $module->id,
            'instance' => $instanceid,
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
    }
}
