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
            'resultsummary' => self::normalise_result_summary(\micp_get_user_result_summary($resolved['micp'], (int) $USER->id)),
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
            'resultsummary' => new \external_single_structure([
                'submitted' => new \external_value(\PARAM_BOOL, 'Whether the attempt has been submitted'),
                'statuslabel' => new \external_value(\PARAM_TEXT, 'Submission status label'),
                'statusclass' => new \external_value(\PARAM_ALPHANUMEXT, 'Submission status class'),
                'hasinteraction' => new \external_value(\PARAM_BOOL, 'Whether any interaction has been recorded'),
                'interactionslabel' => new \external_value(\PARAM_TEXT, 'Interaction state label'),
                'scorelabel' => new \external_value(\PARAM_TEXT, 'Visible score label'),
                'rawgradelabel' => new \external_value(\PARAM_TEXT, 'Visible raw grade label'),
                'grademaxlabel' => new \external_value(\PARAM_TEXT, 'Visible grade max label'),
                'showsubmittedat' => new \external_value(\PARAM_BOOL, 'Whether submitted-at should be shown'),
                'submittedatlabel' => new \external_value(\PARAM_TEXT, 'Submitted-at label'),
                'details' => new \external_multiple_structure(new \external_single_structure([
                    'label' => new \external_value(\PARAM_TEXT, 'Detail label'),
                    'scorelabel' => new \external_value(\PARAM_TEXT, 'Detail score label'),
                    'complete' => new \external_value(\PARAM_BOOL, 'Whether the detail is complete'),
                ]), 'Interaction breakdown items'),
                'showdetails' => new \external_value(\PARAM_BOOL, 'Whether details should be shown'),
            ]),
        ]);
    }

    private static function normalise_result_summary(array $summary): array {
        return [
            'submitted' => !empty($summary['submitted']),
            'statuslabel' => (string) ($summary['statuslabel'] ?? ''),
            'statusclass' => (string) ($summary['statusclass'] ?? ''),
            'hasinteraction' => !empty($summary['hasinteraction']),
            'interactionslabel' => (string) ($summary['interactionslabel'] ?? ''),
            'scorelabel' => (string) ($summary['scorelabel'] ?? ''),
            'rawgradelabel' => (string) ($summary['rawgradelabel'] ?? ''),
            'grademaxlabel' => (string) ($summary['grademaxlabel'] ?? ''),
            'showsubmittedat' => !empty($summary['showsubmittedat']),
            'submittedatlabel' => (string) ($summary['submittedatlabel'] ?? ''),
            'details' => array_map(static function(array $detail): array {
                return [
                    'label' => (string) ($detail['label'] ?? ''),
                    'scorelabel' => (string) ($detail['scorelabel'] ?? ''),
                    'complete' => !empty($detail['complete']),
                ];
            }, $summary['details'] ?? []),
            'showdetails' => !empty($summary['showdetails']),
        ];
    }

    private static function assert_valid_json(string $json, string $fieldname): void {
        json_decode($json);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \invalid_parameter_exception($fieldname . ' must be valid JSON.');
        }
    }
}
