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
 * Core callbacks and thin wrappers for mod_micp.
 *
 * @package     mod_micp
 * @copyright   2026 RainGather
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/filelib.php');

function micp_default_launch_path(): string {
    return \mod_micp\local\activity_settings::default_launch_path();
}

function micp_resolve_launch_path(?string $launchpath): string {
    return \mod_micp\local\activity_settings::resolve_launch_path($launchpath);
}

function micp_normalize_grade($grade): int {
    return \mod_micp\local\activity_settings::normalize_grade($grade);
}

function micp_get_launch_file_manager_options(): array {
    return (new \mod_micp\local\launch_manager())->get_file_manager_options();
}

function micp_is_zip_file(stored_file $file): bool {
    $filename = strtolower($file->get_filename());

    return str_ends_with($filename, '.zip') || $file->get_mimetype() === 'application/zip';
}

function micp_extract_launch_package(context_module $context): void {
    (new \mod_micp\local\launch_manager())->extract_launch_package($context);
}

function micp_save_launch_file(stdClass $data): void {
    (new \mod_micp\local\launch_manager())->save_launch_file($data);
}

function micp_get_uploaded_launch_file(context_module $context): ?stored_file {
    return (new \mod_micp\local\launch_manager())->get_uploaded_launch_file($context);
}

function micp_get_public_launch_path(stored_file $storedfile): string {
    return (new \mod_micp\local\launch_manager())->get_public_launch_path($storedfile);
}

function micp_get_launch_url(stdClass $micp, context_module $context): array {
    return (new \mod_micp\local\launch_manager())->get_launch_url($micp, $context);
}

function micp_add_instance(stdClass $data, $mform = null): int {
    global $DB;

    $data->launchpath = \mod_micp\local\activity_settings::resolve_launch_path($data->launchpath ?? '');
    $data->grade = \mod_micp\local\activity_settings::normalize_grade($data->grade ?? null);
    $data->timemodified = time();

    $data->id = $DB->insert_record('micp', $data);
    (new \mod_micp\local\launch_manager())->save_launch_file($data);

    return $data->id;
}

function micp_update_instance(stdClass $data, $mform = null): bool {
    global $DB;

    $data->id = $data->instance;
    $data->launchpath = \mod_micp\local\activity_settings::resolve_launch_path($data->launchpath ?? '');
    $data->grade = \mod_micp\local\activity_settings::normalize_grade($data->grade ?? null);
    $data->timemodified = time();

    $updated = $DB->update_record('micp', $data);
    (new \mod_micp\local\launch_manager())->save_launch_file($data);

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
        (new \mod_micp\local\launch_manager())->delete_activity_files($context);
    }

    $DB->delete_records('micp', ['id' => $micp->id]);

    return true;
}

function micp_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []): bool {
    if ($context->contextlevel !== CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);
    if (!has_capability('mod/micp:view', $context) || $filearea !== 'launchpackage') {
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

    return true;
}

function micp_supports(string $feature) {
    switch ($feature) {
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_INTERACTIVECONTENT;
        default:
            return null;
    }
}

function micp_evaluate(stdClass $micp, int $userid): array {
    return (new \mod_micp\local\result_service())->evaluate($micp, $userid);
}

function micp_build_grade_entry(stdClass $micp, int $userid): stdClass {
    return (new \mod_micp\local\result_service())->build_grade_entry($micp, $userid);
}

function micp_get_submission_record(stdClass $micp, int $userid): ?stdClass {
    return (new \mod_micp\local\result_service())->get_submission_record($micp, $userid);
}

function micp_get_review_data(?stdClass $submission): array {
    return (new \mod_micp\local\result_service())->get_review_data($submission);
}

function micp_get_effective_score_percent(?stdClass $submission, array $evaluation): ?int {
    return (new \mod_micp\local\result_service())->get_effective_score_percent($submission, $evaluation);
}

function micp_get_effective_raw_grade(stdClass $micp, ?stdClass $submission, array $evaluation): ?float {
    return (new \mod_micp\local\result_service())->get_effective_raw_grade($micp, $submission, $evaluation);
}

function micp_extract_submission_actions(?stdClass $submission): array {
    return (new \mod_micp\local\result_service())->extract_submission_actions($submission);
}

function micp_get_latest_submission_actions_by_interactionid(?stdClass $submission): array {
    return (new \mod_micp\local\result_service())->get_latest_submission_actions_by_interactionid($submission);
}

function micp_get_graded_userids(int $micpid): array {
    return (new \mod_micp\local\result_service())->get_graded_userids($micpid);
}

function micp_grade_item_update(stdClass $micp, ?array $grades = null): int {
    return (new \mod_micp\local\result_service())->grade_item_update($micp, $grades);
}

function micp_update_grades(stdClass $micp, int $userid = 0): int {
    return (new \mod_micp\local\result_service())->update_grades($micp, $userid);
}

function micp_get_user_result_summary(stdClass $micp, int $userid): array {
    return (new \mod_micp\local\result_service())->get_user_result_summary($micp, $userid);
}

function micp_get_user_grade_record(stdClass $micp, int $userid): ?stdClass {
    return (new \mod_micp\local\result_service())->get_user_grade_record($micp, $userid);
}

function micp_get_participant_report_rows(stdClass $micp, $cm, context_module $context, int $groupid = 0): array {
    return (new \mod_micp\local\report_builder())->get_participant_rows($micp, $cm, $context, $groupid);
}

function micp_get_report_interaction_columns(array $rows): array {
    return (new \mod_micp\local\report_builder())->get_interaction_columns($rows);
}

function micp_get_report_group_label($cm, int $groupid): string {
    return (new \mod_micp\local\report_builder())->get_group_label($cm, $groupid);
}

function micp_get_report_group_options(stdClass $course, $cm): array {
    return (new \mod_micp\local\report_builder())->get_group_options($course, $cm);
}
