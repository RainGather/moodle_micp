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
$selectedgroup = optional_param('group', null, PARAM_INT);
$groupid = $selectedgroup === null ? groups_get_activity_group($cm, true) : max(0, $selectedgroup);

$PAGE->set_url($url);
$PAGE->set_title(get_string('reporttitle', 'mod_micp'));
$PAGE->set_heading(format_string($course->fullname));

$rows = micp_get_participant_report_rows($micp, $cm, $context, (int)$groupid);
$interactioncolumns = micp_get_report_interaction_columns($rows);
$currentgrouplabel = micp_get_report_group_label($cm, (int)$groupid);
$groupoptions = micp_get_report_group_options($course, $cm);
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
    get_string('reviewstatus', 'mod_micp'),
    get_string('lastsubmission', 'mod_micp'),
    get_string('activityscore', 'mod_micp'),
    get_string('grade', 'mod_micp'),
    get_string('grade'),
    get_string('reviewaction', 'mod_micp'),
];
$table->head = array_merge(
    array_slice($table->head, 0, 5),
    array_map(static function(array $column): string {
        return s($column['label'] ?? $column['interactionid'] ?? 'Interaction');
    }, $interactioncolumns),
    array_slice($table->head, 5)
);
$table->data = [];

foreach ($rows as $row) {
    $detailmap = [];
    foreach (($row['interactiondetails'] ?? []) as $detail) {
        $interactionid = (string)($detail['interactionid'] ?? '');
        if ($interactionid !== '') {
            $detailmap[$interactionid] = (string)($detail['scorelabel'] ?? '—');
        }
    }

    $basecells = [
        s($row['fullname']),
        s($row['submissionstatus']),
        s($row['reviewstatus']),
        s($row['lastsubmission']),
        s($row['activityscore']),
    ];
    $interactioncells = array_map(static function(array $column) use ($detailmap): string {
        $interactionid = (string)($column['interactionid'] ?? '');

        return s($detailmap[$interactionid] ?? '—');
    }, $interactioncolumns);
    $tailcells = [
        s($row['grade']),
        s($row['finalgrade']),
        $row['reviewaction'],
    ];

    $table->data[] = array_merge($basecells, $interactioncells, $tailcells);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('reporttitle', 'mod_micp'));

echo html_writer::start_tag('form', [
    'method' => 'get',
    'action' => $url->out(false),
    'class' => 'mb-3',
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'id',
    'value' => $cm->id,
]);
echo html_writer::tag('label', get_string('groupfilterlabel', 'mod_micp'), [
    'for' => 'mod-micp-group-filter',
    'class' => 'form-label mr-2',
]);
echo html_writer::select(
    $groupoptions,
    'group',
    (int)$groupid,
    false,
    [
        'id' => 'mod-micp-group-filter',
        'class' => 'custom-select d-inline-block w-auto mr-2',
        'onchange' => 'this.form.submit()',
    ]
);
echo html_writer::tag('span', get_string('currentgroupdisplay', 'mod_micp', $currentgrouplabel), [
    'class' => 'text-muted ml-2',
]);
echo html_writer::end_tag('form');

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
