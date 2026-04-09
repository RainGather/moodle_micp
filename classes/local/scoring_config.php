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

class scoring_config {
    public static function from_json(string $json): array {
        $config = json_decode($json, true);

        if (!is_array($config)) {
            return [];
        }

        $items = [];
        foreach (($config['interactions'] ?? []) as $interaction) {
            if (!is_array($interaction)) {
                continue;
            }

            $id = trim((string)($interaction['id'] ?? ''));
            if ($id === '') {
                continue;
            }

            $weight = (float)($interaction['weight'] ?? 0);
            $gradingmode = trim((string)($interaction['gradingmode'] ?? 'auto'));
            if ($gradingmode !== 'manual') {
                $gradingmode = 'auto';
            }
            $items[$id] = [
                'id' => $id,
                'label' => trim((string)($interaction['label'] ?? $id)),
                'weight' => $weight > 0 ? $weight : 0,
                'type' => trim((string)($interaction['type'] ?? 'presence')),
                'gradingmode' => $gradingmode,
                'scoring' => is_array($interaction['scoring'] ?? null) ? $interaction['scoring'] : [],
            ];
        }

        $maxscore = (float)($config['aggregation']['maxscore'] ?? 100);

        return [
            'version' => (int)($config['version'] ?? 1),
            'aggregation' => [
                'method' => trim((string)($config['aggregation']['method'] ?? 'sum')),
                'maxscore' => $maxscore > 0 ? $maxscore : 100,
            ],
            'interactions' => $items,
        ];
    }
}
