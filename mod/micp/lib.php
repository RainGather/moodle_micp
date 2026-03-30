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

require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/mod/micp/classes/local/scoring_service.php');

function micp_default_launch_path(): string {
    return 'sample_content/demo.html';
}

function micp_resolve_launch_path(?string $launchpath): string {
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
}

function micp_normalize_grade($grade): int {
    $grade = (int)($grade ?? 0);

    return $grade > 0 ? $grade : 100;
}

function micp_get_launch_file_manager_options(): array {
    return [
        'subdirs' => 0,
        'maxfiles' => 1,
        'accepted_types' => ['.zip', '.html'],
    ];
}

function micp_is_zip_file(stored_file $file): bool {
    $filename = strtolower($file->get_filename());

    return str_ends_with($filename, '.zip') || $file->get_mimetype() === 'application/zip';
}

function micp_extract_launch_package(context_module $context): void {
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_micp', 'launchpackage', 0);

    $zipfiles = $fs->get_area_files($context->id, 'mod_micp', 'launchpackagezip', 0, 'filename', false);
    if (!$zipfiles) {
        return;
    }

    $zipfile = reset($zipfiles);
    if (!$zipfile || $zipfile->is_directory()) {
        return;
    }

    if (!micp_is_zip_file($zipfile)) {
        // Non-ZIP HTML file: copy to launchpackage as index.html at filepath='/0/'.
        // pluginfile.php URL reconstruction for itemid=0 gives filepath='/0/',
        // so we must store at filepath='/0/' for the lookup to match.
        $fs->create_file_from_storedfile([
            'contextid' => $context->id,
            'component' => 'mod_micp',
            'filearea' => 'launchpackage',
            'itemid' => 0,
            'filepath' => '/0/',
            'filename' => 'index.html',
        ], $zipfile);

        return;
    }

    $packer = get_file_packer('application/zip');
    $extracted = $zipfile->extract_to_storage($packer, $context->id, 'mod_micp', 'launchpackage', 0, '/');
    if ($extracted === 0) {
        debugging("micp: extract_to_storage produced 0 files for ZIP: " . $zipfile->get_filename(), DEBUG_ALL);
        return;
    }

    // Fix filepath mismatch: extract_to_storage stores at filepath='/'.
    // pluginfile.php URL reconstruction for itemid=0 gives filepath='/0/'.
    // Move root files to filepath='/0/' so the lookup matches.
    $allfiles = $fs->get_area_files($context->id, 'mod_micp', 'launchpackage', 0, 'filepath, filename', false);
    foreach ($allfiles as $file) {
        if ($file->is_directory()) {
            continue;
        }
        if ($file->get_filepath() === '/') {
            $fs->create_file_from_storedfile([
                'contextid' => $context->id,
                'component' => 'mod_micp',
                'filearea' => 'launchpackage',
                'itemid' => 0,
                'filepath' => '/0/',
                'filename' => $file->get_filename(),
            ], $file);
            $file->delete();
        }
    }
}

function micp_save_launch_file(stdClass $data): void {
    if (!isset($data->coursemodule)) {
        return;
    }

    $context = context_module::instance((int)$data->coursemodule);
    file_save_draft_area_files(
        (int)($data->launchfile ?? 0),
        $context->id,
        'mod_micp',
        'launchpackagezip',
        0,
        micp_get_launch_file_manager_options()
    );

    micp_extract_launch_package($context);
}

function micp_get_uploaded_launch_file(context_module $context): ?stored_file {
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_micp', 'launchpackage', 0, 'filepath, filename', false);
    $fallback = null;

    foreach ($files as $file) {
        if ($file->is_directory()) {
            continue;
        }

        if ($file->get_filename() === 'index.html') {
            return $file;
        }

        if ($fallback === null && preg_match('/\.html\z/i', $file->get_filename())) {
            $fallback = $file;
        }
    }

    return $fallback;
}

function micp_get_public_launch_path(stored_file $storedfile): string {
    $filepath = $storedfile->get_filepath();
    if ($filepath === '/0/') {
        $filepath = '/';
    }

    return ltrim($filepath, '/') . $storedfile->get_filename();
}

function micp_get_launch_url(stdClass $micp, context_module $context): array {
    $storedfile = micp_get_uploaded_launch_file($context);
    if ($storedfile) {
        $launchpath = micp_get_public_launch_path($storedfile);
        $segments = array_map('rawurlencode', explode('/', $launchpath));
        $fileurl = $GLOBALS['CFG']->wwwroot . '/mod/micp/file.php/' . $context->instanceid . '/' . implode('/', $segments);

        return [
            'url' => $fileurl,
            'path' => $launchpath,
            'uploaded' => true,
        ];
    }

    $launchpath = micp_resolve_launch_path($micp->launchpath ?? '');

    return [
        'url' => $GLOBALS['CFG']->wwwroot . '/mod/micp/' . $launchpath,
        'path' => $launchpath,
        'uploaded' => false,
    ];
}

function micp_add_instance(stdClass $data, $mform = null): int {
    global $DB;

    $data->launchpath = micp_resolve_launch_path($data->launchpath ?? '');
    $data->grade = micp_normalize_grade($data->grade ?? null);
    $data->timemodified = time();

    $data->id = $DB->insert_record('micp', $data);
    micp_save_launch_file($data);

    return $data->id;
}

function micp_update_instance(stdClass $data, $mform = null): bool {
    global $DB;

    $data->id = $data->instance;
    $data->launchpath = micp_resolve_launch_path($data->launchpath ?? '');
    $data->grade = micp_normalize_grade($data->grade ?? null);
    $data->timemodified = time();

    $updated = $DB->update_record('micp', $data);
    micp_save_launch_file($data);

    return $updated;
}

function micp_delete_instance(int $id): bool {
    global $DB;

    if (!$micp = $DB->get_record('micp', ['id' => $id])) {
        return false;
    }

    $DB->delete_records('micp_events', ['micpid' => $micp->id]);
    $DB->delete_records('micp_submissions', ['micpid' => $micp->id]);
    $cm = get_coursemodule_from_instance('micp', $micp->id, $micp->course, false, IGNORE_MISSING);
    if ($cm) {
        $context = context_module::instance((int)$cm->id);
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_micp', 'launchpackagezip', 0);
        $fs->delete_area_files($context->id, 'mod_micp', 'launchpackage', 0);
    }
    $DB->delete_records('micp', ['id' => $micp->id]);

    return true;
}

function micp_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []): bool {
    if ($context->contextlevel !== CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);
    if (!has_capability('mod/micp:view', $context)) {
        return false;
    }

    if ($filearea !== 'launchpackage') {
        return false;
    }

    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_micp', 'launchpackage', 0, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, null, 0, false, $options);
}

function micp_supports(string $feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_INTERACTIVECONTENT;
        default:
            return null;
    }
}

function micp_evaluate(stdClass $micp, int $userid): array {
    $cm = get_coursemodule_from_instance('micp', $micp->id, $micp->course, false, MUST_EXIST);
    $context = context_module::instance((int)$cm->id);
    $service = new \mod_micp\local\scoring_service();

    return $service->evaluate($micp, $userid, $context);
}

function micp_build_grade_entry(stdClass $micp, int $userid): stdClass {
    $evaluation = micp_evaluate($micp, $userid);

    return (object) [
        'userid' => $userid,
        'rawgrade' => $evaluation['rawgrade'],
    ];
}

function micp_get_graded_userids(int $micpid): array {
    global $DB;

    $sql = 'SELECT userid
              FROM {micp_events}
             WHERE micpid = :eventmicpid
          GROUP BY userid
            UNION
            SELECT userid
              FROM {micp_submissions}
             WHERE micpid = :submissionmicpid';

    return array_map('intval', array_keys($DB->get_records_sql($sql, [
        'eventmicpid' => $micpid,
        'submissionmicpid' => $micpid,
    ])));
}

function micp_grade_item_update(stdClass $micp, ?array $grades = null): int {
    $grademax = micp_normalize_grade($micp->grade ?? null);

    $item = [
        'itemname' => clean_param($micp->name, PARAM_NOTAGS),
        'gradetype' => GRADE_TYPE_VALUE,
        'grademax' => $grademax,
        'grademin' => 0,
    ];

    return grade_update('mod/micp', $micp->course, 'mod', 'micp', $micp->id, 0, $grades, $item);
}

function micp_update_grades(stdClass $micp, int $userid = 0): int {
    if (!empty($userid)) {
        return micp_grade_item_update($micp, [
            $userid => micp_build_grade_entry($micp, $userid),
        ]);
    }

    $grades = [];
    foreach (micp_get_graded_userids($micp->id) as $gradeduserid) {
        $grades[$gradeduserid] = micp_build_grade_entry($micp, $gradeduserid);
    }

    return micp_grade_item_update($micp, $grades ?: null);
}

function micp_get_user_result_summary(stdClass $micp, int $userid): array {
    global $DB;

    $submission = $DB->get_record('micp_submissions', [
        'micpid' => $micp->id,
        'userid' => $userid,
    ]);

    $hasinteraction = $DB->record_exists('micp_events', [
        'micpid' => $micp->id,
        'userid' => $userid,
        'eventtype' => 'interaction',
    ]);

    $evaluation = micp_evaluate($micp, $userid);
    $grademax = micp_normalize_grade($micp->grade ?? null);
    $submitted = !empty($submission);

    return [
        'submitted' => $submitted,
        'statuslabel' => $submitted ? 'Submitted' : 'Not submitted yet',
        'statusclass' => $submitted ? 'submitted' : 'pending',
        'hasinteraction' => $hasinteraction,
        'interactionslabel' => $hasinteraction ? 'Interaction recorded' : 'No interaction recorded yet',
        'scorelabel' => (string)($evaluation['score'] ?? 0),
        'rawgradelabel' => (string)($evaluation['rawgrade'] ?? 0),
        'grademaxlabel' => (string)$grademax,
        'showsubmittedat' => $submitted && !empty($submission->timemodified),
        'submittedatlabel' => $submitted && !empty($submission->timemodified) ? userdate((int)$submission->timemodified) : '',
        'details' => array_map(static function(array $detail): array {
            return [
                'label' => (string)($detail['label'] ?? $detail['interactionid'] ?? 'Interaction'),
                'scorelabel' => format_float((float)($detail['earned'] ?? 0), 2) . ' / ' . format_float((float)($detail['max'] ?? 0), 2),
                'complete' => !empty($detail['complete']),
            ];
        }, $evaluation['details'] ?? []),
        'showdetails' => !empty($evaluation['details']),
    ];
}

function micp_get_user_grade_record(stdClass $micp, int $userid): ?stdClass {
    global $DB;

    $gradeitem = $DB->get_record('grade_items', [
        'itemmodule' => 'micp',
        'iteminstance' => $micp->id,
        'itemnumber' => 0,
    ]);

    if (!$gradeitem) {
        return null;
    }

    return $DB->get_record('grade_grades', [
        'itemid' => $gradeitem->id,
        'userid' => $userid,
    ]) ?: null;
}

function micp_get_participant_report_rows(stdClass $micp, $cm, context_module $context, int $groupid = 0): array {
    $users = get_enrolled_users(
        $context,
        '',
        $groupid,
        'u.id,u.firstname,u.lastname,u.username,u.email',
        'u.lastname ASC, u.firstname ASC'
    );

    $rows = [];
    foreach ($users as $user) {
        if (has_capability('mod/micp:addinstance', $context, $user) || has_capability('mod/micp:viewreports', $context, $user)) {
            continue;
        }

        $summary = micp_get_user_result_summary($micp, (int)$user->id);
        $grade = micp_get_user_grade_record($micp, (int)$user->id);
        $rawgrade = $grade && $grade->rawgrade !== null ? format_float((float)$grade->rawgrade, 2) : get_string('nograderecord', 'mod_micp');
        $finalgrade = $grade && $grade->finalgrade !== null ? format_float((float)$grade->finalgrade, 2) : get_string('nograderecord', 'mod_micp');

        $rows[] = [
            'fullname' => fullname($user),
            'submissionstatus' => $summary['submitted'] ? get_string('submitted', 'mod_micp') : get_string('notsubmitted', 'mod_micp'),
            'lastsubmission' => $summary['showsubmittedat'] ? $summary['submittedatlabel'] : get_string('never', 'mod_micp'),
            'activityscore' => $summary['scorelabel'] . '%',
            'grade' => $rawgrade . ' / ' . $summary['grademaxlabel'],
            'finalgrade' => $finalgrade,
            'interactionbreakdown' => implode(', ', array_map(static function(array $detail): string {
                return ($detail['label'] ?? 'Interaction') . ': ' . ($detail['scorelabel'] ?? '0 / 0');
            }, $summary['details'] ?? [])),
        ];
    }

    return $rows;
}
