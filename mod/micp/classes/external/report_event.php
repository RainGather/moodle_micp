<?php

namespace mod_micp\external;

use mod_micp\local\activity_locator;
use mod_micp\local\event_repository;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

class report_event extends \external_api {
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'cmid' => new \external_value(\PARAM_INT, 'Course module id'),
            'eventtype' => new \external_value(\PARAM_ALPHANUMEXT, 'MICP event type'),
            'payload' => new \external_value(\PARAM_RAW, 'JSON encoded event payload'),
            'clientts' => new \external_value(\PARAM_INT, 'Optional client timestamp', \VALUE_DEFAULT, 0),
        ]);
    }

    public static function execute(int $cmid, string $eventtype, string $payload, int $clientts = 0): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'eventtype' => $eventtype,
            'payload' => $payload,
            'clientts' => $clientts,
        ]);

        \require_sesskey();
        $payloaddata = self::assert_valid_json($params['payload'], 'payload');

        if ($params['eventtype'] === 'interaction') {
            $interactionid = trim((string)($payloaddata['interactionid'] ?? ''));
            if ($interactionid === '') {
                throw new \invalid_parameter_exception('interaction payload must include interactionid.');
            }
        }

        $locator = new activity_locator();
        $resolved = $locator->resolve_from_cmid((int) $params['cmid']);
        self::validate_context($resolved['context']);
        \require_capability('mod/micp:submit', $resolved['context']);

        $repository = new event_repository();
        $event = $repository->create(
            (int) $resolved['micp']->id,
            (int) $USER->id,
            $params['eventtype'],
            $params['payload'],
            !empty($params['clientts']) ? (int) $params['clientts'] : null
        );

        return [
            'status' => true,
            'eventid' => (int) $event->id,
            'timecreated' => (int) $event->timecreated,
        ];
    }

    public static function execute_returns(): \external_single_structure {
        return new \external_single_structure([
            'status' => new \external_value(\PARAM_BOOL, 'Whether the event was stored'),
            'eventid' => new \external_value(\PARAM_INT, 'Stored event row id'),
            'timecreated' => new \external_value(\PARAM_INT, 'Server creation timestamp'),
        ]);
    }

    private static function assert_valid_json(string $json, string $fieldname): array {
        $decoded = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \invalid_parameter_exception($fieldname . ' must be valid JSON.');
        }

        if (!is_array($decoded)) {
            throw new \invalid_parameter_exception($fieldname . ' must decode to a JSON object.');
        }

        return $decoded;
    }
}
