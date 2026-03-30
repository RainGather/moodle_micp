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

$id = required_param('id', PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'micp');
$micp = $DB->get_record('micp', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/micp:viewreports', $context);

$url = new moodle_url('/mod/micp/report.php', ['id' => $cm->id]);
$groupid = groups_get_activity_group($cm, true);

$PAGE->set_url($url);
$PAGE->set_title(get_string('reporttitle', 'mod_micp'));
$PAGE->set_heading(format_string($course->fullname));

$rows = micp_get_participant_report_rows($micp, $cm, $context, (int)$groupid);
$submittedcount = 0;
$gradedcount = 0;
foreach ($rows as $row) {
    if ($row['submissionstatus'] === get_string('submitted', 'mod_micp')) {
        $submittedcount++;
    }
    if ($row['finalgrade'] !== get_string('nograderecord', 'mod_micp')) {
        $gradedcount++;
    }
}

$table = new html_table();
$table->head = [
    get_string('participant', 'mod_micp'),
    get_string('submissionstatus', 'mod_micp'),
    get_string('lastsubmission', 'mod_micp'),
    get_string('activityscore', 'mod_micp'),
    get_string('interactionbreakdown', 'mod_micp'),
    get_string('grade', 'mod_micp'),
    get_string('grade'),
];
$table->data = [];

foreach ($rows as $row) {
    $table->data[] = [
        s($row['fullname']),
        s($row['submissionstatus']),
        s($row['lastsubmission']),
        s($row['activityscore']),
        s($row['interactionbreakdown']),
        s($row['grade']),
        s($row['finalgrade']),
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('reporttitle', 'mod_micp'));

if (groups_get_activity_groupmode($cm)) {
    groups_print_activity_menu($cm, $url);
}

echo html_writer::div(get_string('reportsummary', 'mod_micp', (object) [
    'participants' => count($rows),
    'submitted' => $submittedcount,
    'graded' => $gradedcount,
]), 'alert alert-info');

echo html_writer::div(
    html_writer::link(new moodle_url('/mod/micp/view.php', ['id' => $cm->id]), format_string($micp->name)),
    'mb-3'
);

echo html_writer::table($table);
echo $OUTPUT->footer();
