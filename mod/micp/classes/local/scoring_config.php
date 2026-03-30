<?php

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
            $items[$id] = [
                'id' => $id,
                'label' => trim((string)($interaction['label'] ?? $id)),
                'weight' => $weight > 0 ? $weight : 0,
                'type' => trim((string)($interaction['type'] ?? 'presence')),
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
