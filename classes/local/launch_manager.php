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
 * Launch package management for mod_micp.
 *
 * @package     mod_micp
 * @copyright   2026 RainGather
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_micp\local;

use context_module;
use stdClass;
use stored_file;

defined('MOODLE_INTERNAL') || die();

/**
 * Handles uploaded lesson package storage and launch URLs.
 */
class launch_manager {
    /**
     * Filemanager configuration for launch packages.
     *
     * @return array
     */
    public function get_file_manager_options(): array {
        return [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['.zip', '.html'],
        ];
    }

    /**
     * Persist the uploaded draft file and expand it when needed.
     *
     * @param stdClass $data
     * @return void
     */
    public function save_launch_file(stdClass $data): void {
        if (!isset($data->coursemodule)) {
            return;
        }

        $context = context_module::instance((int)$data->coursemodule);
        \file_save_draft_area_files(
            (int)($data->launchfile ?? 0),
            $context->id,
            'mod_micp',
            'launchpackagezip',
            0,
            $this->get_file_manager_options()
        );

        $this->extract_launch_package($context);
    }

    /**
     * Extract or copy the uploaded launch package into the served file area.
     *
     * @param context_module $context
     * @return void
     */
    public function extract_launch_package(context_module $context): void {
        $fs = \get_file_storage();
        $fs->delete_area_files($context->id, 'mod_micp', 'launchpackage', 0);

        $zipfiles = $fs->get_area_files($context->id, 'mod_micp', 'launchpackagezip', 0, 'filename', false);
        if (!$zipfiles) {
            return;
        }

        $zipfile = reset($zipfiles);
        if (!$zipfile || $zipfile->is_directory()) {
            return;
        }

        if (!$this->is_zip_file($zipfile)) {
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

        $packer = \get_file_packer('application/zip');
        $extracted = $zipfile->extract_to_storage($packer, $context->id, 'mod_micp', 'launchpackage', 0, '/');
        if ($extracted === 0) {
            \debugging('micp: extract_to_storage produced 0 files for ZIP: ' . $zipfile->get_filename(), \DEBUG_ALL);
            return;
        }

        $allfiles = $fs->get_area_files($context->id, 'mod_micp', 'launchpackage', 0, 'filepath, filename', false);
        foreach ($allfiles as $file) {
            if ($file->is_directory() || $file->get_filepath() !== '/') {
                continue;
            }

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

    /**
     * Delete stored launch package files for an activity.
     *
     * @param context_module $context
     * @return void
     */
    public function delete_activity_files(context_module $context): void {
        $fs = \get_file_storage();
        $fs->delete_area_files($context->id, 'mod_micp', 'launchpackagezip', 0);
        $fs->delete_area_files($context->id, 'mod_micp', 'launchpackage', 0);
    }

    /**
     * Return the main uploaded launch file when one exists.
     *
     * @param context_module $context
     * @return stored_file|null
     */
    public function get_uploaded_launch_file(context_module $context): ?stored_file {
        $fs = \get_file_storage();
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

    /**
     * Convert a stored file into a public relative path.
     *
     * @param stored_file $storedfile
     * @return string
     */
    public function get_public_launch_path(stored_file $storedfile): string {
        $filepath = $storedfile->get_filepath();
        if ($filepath === '/0/') {
            $filepath = '/';
        }

        return ltrim($filepath, '/') . $storedfile->get_filename();
    }

    /**
     * Resolve the effective launch URL and path.
     *
     * @param stdClass $micp
     * @param context_module $context
     * @return array
     */
    public function get_launch_url(stdClass $micp, context_module $context): array {
        global $CFG;

        $storedfile = $this->get_uploaded_launch_file($context);
        if ($storedfile) {
            $launchpath = $this->get_public_launch_path($storedfile);
            $segments = array_map('rawurlencode', explode('/', $launchpath));
            $fileurl = $CFG->wwwroot . '/mod/micp/file.php/' . $context->instanceid . '/' . implode('/', $segments);

            return [
                'url' => $fileurl,
                'path' => $launchpath,
                'uploaded' => true,
            ];
        }

        $launchpath = activity_settings::resolve_launch_path($micp->launchpath ?? '');

        return [
            'url' => $CFG->wwwroot . '/mod/micp/' . $launchpath,
            'path' => $launchpath,
            'uploaded' => false,
        ];
    }

    /**
     * Detect whether a stored file should be treated as a ZIP package.
     *
     * @param stored_file $file
     * @return bool
     */
    private function is_zip_file(stored_file $file): bool {
        $filename = strtolower($file->get_filename());

        return str_ends_with($filename, '.zip') || $file->get_mimetype() === 'application/zip';
    }
}
