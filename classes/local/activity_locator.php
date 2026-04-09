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
