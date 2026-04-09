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

namespace mod_micp\local;

defined('MOODLE_INTERNAL') || die();

class scoring_service {
    public function get_scoring_config(\stdClass $micp, \context_module $context): array {
        $json = $this->load_uploaded_config($context);
        if ($json === null) {
            $json = $this->load_local_config($micp);
        }

        return $json === null ? [] : scoring_config::from_json($json);
    }

    public function evaluate(\stdClass $micp, int $userid, \context_module $context): array {
        global $DB;

        $config = $this->get_scoring_config($micp, $context);
        if (empty($config['interactions'])) {
            $hasinteraction = $DB->record_exists('micp_events', [
                'micpid' => $micp->id,
                'userid' => $userid,
                'eventtype' => 'interaction',
            ]);

            $score = $hasinteraction ? 100 : 0;

            return [
                'score' => $score,
                'rawgrade' => $hasinteraction ? activity_settings::normalize_grade($micp->grade ?? null) : 0,
                'details' => [],
                'configvalid' => false,
            ];
        }

        $events = $DB->get_records('micp_events', [
            'micpid' => $micp->id,
            'userid' => $userid,
            'eventtype' => 'interaction',
        ], 'timecreated ASC, id ASC');

        $latestbyinteraction = [];
        foreach ($events as $event) {
            $payload = json_decode((string)$event->payload, true);
            if (!is_array($payload)) {
                continue;
            }

            $interactionid = trim((string)($payload['interactionid'] ?? ''));
            if ($interactionid === '' || !array_key_exists($interactionid, $config['interactions'])) {
                continue;
            }

            $latestbyinteraction[$interactionid] = $payload;
        }

        $totalearned = 0.0;
        $totalmax = 0.0;
        $details = [];
        $needsmanualreview = false;

        foreach ($config['interactions'] as $interactionid => $item) {
            $payload = $latestbyinteraction[$interactionid] ?? null;
            $weight = (float)$item['weight'];
            $gradingmode = $item['gradingmode'] ?? 'auto';
            $ismanuallygraded = $gradingmode === 'manual';
            $earned = $ismanuallygraded ? 0.0 : $this->score_interaction($item, $payload);

            $totalearned += $earned;
            $totalmax += $weight;

            if ($ismanuallygraded && $payload !== null) {
                $needsmanualreview = true;
            }

            $details[] = [
                'interactionid' => $interactionid,
                'label' => $item['label'],
                'earned' => $earned,
                'max' => $weight,
                'complete' => $payload !== null,
                'gradingmode' => $gradingmode,
                'manualreviewrequired' => $ismanuallygraded && $payload !== null,
            ];
        }

        $normalized = $totalmax > 0 ? ($totalearned / $totalmax) * 100 : 0;
        $score = (int)round($normalized);
        $rawgrade = (int)round((activity_settings::normalize_grade($micp->grade ?? null) * $score) / 100);

        return [
            'score' => max(0, min(100, $score)),
            'rawgrade' => max(0, $rawgrade),
            'details' => $details,
            'configvalid' => true,
            'needsmanualreview' => $needsmanualreview,
        ];
    }

    private function score_interaction(array $item, ?array $payload): float {
        $weight = (float)$item['weight'];
        if ($weight <= 0 || $payload === null) {
            return 0.0;
        }

        $rules = $item['scoring'] ?? [];
        if (!is_array($rules) || $rules === []) {
            return $weight;
        }

        if (!empty($rules['requireNonEmpty'])) {
            $response = $payload['response'] ?? null;
            return trim((string)$response) !== '' ? $weight : 0.0;
        }

        if (array_key_exists('correct', $rules)) {
            return (string)($payload['response'] ?? '') === (string)$rules['correct'] ? $weight : 0.0;
        }

        if (array_key_exists('equals', $rules)) {
            return (string)($payload['outcome'] ?? '') === (string)$rules['equals'] ? $weight : 0.0;
        }

        if (!empty($rules['completed'])) {
            return !empty($payload['completed']) ? $weight : 0.0;
        }

        return $weight;
    }

    private function load_uploaded_config(\context_module $context): ?string {
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_micp', 'launchpackage', 0, 'filepath, filename', false);

        foreach ($files as $file) {
            if ($file->is_directory()) {
                continue;
            }

            if ($file->get_filename() === 'micp-scoring.json') {
                return $file->get_content();
            }
        }

        return null;
    }

    private function load_local_config(\stdClass $micp): ?string {
        global $CFG;

        $launchpath = activity_settings::resolve_launch_path($micp->launchpath ?? '');
        $directory = dirname($launchpath);
        $relative = ($directory === '.' ? '' : $directory . '/') . 'micp-scoring.json';
        $fullpath = $CFG->dirroot . '/mod/micp/' . $relative;

        if (is_readable($fullpath)) {
            return (string)file_get_contents($fullpath);
        }

        return null;
    }
}
