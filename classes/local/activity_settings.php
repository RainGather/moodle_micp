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
 * Shared activity settings helpers for mod_micp.
 *
 * @package     mod_micp
 * @copyright   2026 RainGather
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_micp\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Centralises small activity-level defaults and validation rules.
 */
class activity_settings {
    /**
     * Return the default launch file for repository-local content.
     *
     * @return string
     */
    public static function default_launch_path(): string {
        return 'index.html';
    }

    /**
     * Normalise a repository-local launch path.
     *
     * @param string|null $launchpath
     * @return string
     */
    public static function resolve_launch_path(?string $launchpath): string {
        $launchpath = trim($launchpath ?? '');

        if ($launchpath === '' ||
                str_starts_with($launchpath, 'http://') ||
                str_starts_with($launchpath, 'https://') ||
                str_starts_with($launchpath, '/') ||
                strpos($launchpath, '..') !== false ||
                !preg_match('/\.html\z/i', $launchpath)) {
            return self::default_launch_path();
        }

        return $launchpath;
    }

    /**
     * Guarantee a positive grade maximum.
     *
     * @param mixed $grade
     * @return int
     */
    public static function normalize_grade($grade): int {
        $grade = (int)($grade ?? 0);

        return $grade > 0 ? $grade : 100;
    }
}
