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

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$resolvelaunchpath = static function(?string $launchpath): string {
    $launchpath = trim($launchpath ?? '');
    if ($launchpath === '' ||
            str_starts_with($launchpath, 'http://') ||
            str_starts_with($launchpath, 'https://') ||
            str_starts_with($launchpath, '/') ||
            strpos($launchpath, '..') !== false ||
            !preg_match('/\.html\z/i', $launchpath)) {
        return micp_default_launch_path();
    }

    return $launchpath;
};

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_course_login($course);

$PAGE->set_url('/mod/micp/index.php', ['id' => $course->id]);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('pluginnameplural', 'mod_micp'));
$PAGE->set_heading(format_string($course->fullname));

$instances = get_all_instances_in_course('micp', $course);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginnameplural', 'mod_micp'));

if (empty($instances)) {
    echo $OUTPUT->notification(get_string('noinstances', 'mod_micp'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('name'),
    get_string('launchpath', 'mod_micp'),
];

foreach ($instances as $instance) {
    $link = new moodle_url('/mod/micp/view.php', ['id' => $instance->coursemodule]);
    $name = format_string($instance->name, true, ['context' => context_module::instance($instance->coursemodule)]);
    $launchpath = $resolvelaunchpath($instance->launchpath ?? '');

    $table->data[] = [
        html_writer::link($link, $name),
        s($launchpath),
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
