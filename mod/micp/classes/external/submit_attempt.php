<?php

namespace mod_micp\external;

use mod_micp\local\activity_locator;
use mod_micp\local\submission_service;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

class submit_attempt extends \external_api {
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'cmid' => new \external_value(\PARAM_INT, 'Course module id'),
            'rawjson' => new \external_value(\PARAM_RAW, 'JSON encoded submission payload'),
            'clientmeta' => new \external_value(\PARAM_RAW, 'Optional JSON encoded client metadata', \VALUE_DEFAULT, ''),
        ]);
    }

    public static function execute(int $cmid, string $rawjson, string $clientmeta = ''): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'rawjson' => $rawjson,
            'clientmeta' => $clientmeta,
        ]);

        \require_sesskey();
        self::assert_valid_json($params['rawjson'], 'rawjson');

        if ($params['clientmeta'] !== '') {
            self::assert_valid_json($params['clientmeta'], 'clientmeta');
        }

        $locator = new activity_locator();
        $resolved = $locator->resolve_from_cmid((int) $params['cmid']);
        self::validate_context($resolved['context']);
        \require_capability('mod/micp:submit', $resolved['context']);

        $service = new submission_service();
        $result = $service->submit(
            $resolved['micp'],
            (int) $USER->id,
            $params['rawjson'],
            $params['clientmeta'] !== '' ? $params['clientmeta'] : null
        );

        $submission = $result['submission'];
        $evaluation = $result['evaluation'];

        return [
            'status' => true,
            'submissionid' => (int) $submission->id,
            'score' => (int) ($evaluation['score'] ?? 0),
            'rawgrade' => (int) ($evaluation['rawgrade'] ?? 0),
            'timemodified' => (int) $submission->timemodified,
            'gradeupdated' => (int) $result['gradeupdatestatus'] === \GRADE_UPDATE_OK,
        ];
    }

    public static function execute_returns(): \external_single_structure {
        return new \external_single_structure([
            'status' => new \external_value(\PARAM_BOOL, 'Whether the submission was stored'),
            'submissionid' => new \external_value(\PARAM_INT, 'Stored submission row id'),
            'score' => new \external_value(\PARAM_INT, 'Server-computed score'),
            'rawgrade' => new \external_value(\PARAM_INT, 'Server-computed gradebook raw grade'),
            'timemodified' => new \external_value(\PARAM_INT, 'Server modification timestamp'),
            'gradeupdated' => new \external_value(\PARAM_BOOL, 'Whether gradebook update reported success'),
        ]);
    }

    private static function assert_valid_json(string $json, string $fieldname): void {
        json_decode($json);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \invalid_parameter_exception($fieldname . ' must be valid JSON.');
        }
    }
}
