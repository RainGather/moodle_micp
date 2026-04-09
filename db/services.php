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

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_micp_report_event' => [
        'component' => 'mod_micp',
        'classname' => 'mod_micp\\external\\report_event',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Record a learner interaction event for the current MICP activity.',
        'type' => 'write',
        'ajax' => true,
    ],
    'mod_micp_submit_attempt' => [
        'component' => 'mod_micp',
        'classname' => 'mod_micp\\external\\submit_attempt',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Store the latest learner submission for the current MICP activity.',
        'type' => 'write',
        'ajax' => true,
    ],
];

$services = [];
