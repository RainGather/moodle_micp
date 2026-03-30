<?php

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
