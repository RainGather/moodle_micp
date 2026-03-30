<?php

namespace mod_micp\local;

use context_module;

defined('MOODLE_INTERNAL') || die();

class activity_locator {
    public function resolve_from_cmid(int $cmid): array {
        global $DB;

        $cm = \get_coursemodule_from_id('micp', $cmid, 0, false, \MUST_EXIST);
        $micp = $DB->get_record('micp', ['id' => $cm->instance], '*', \MUST_EXIST);
        $context = context_module::instance($cm->id);

        return [
            'cm' => $cm,
            'micp' => $micp,
            'context' => $context,
        ];
    }
}
