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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->libdir . '/filelib.php');

class mod_micp_mod_form extends moodleform_mod {
    public function definition(): void {
        $mform = $this->_form;
        $launchmanager = new \mod_micp\local\launch_manager();

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements();

        $mform->addElement('text', 'launchpath', get_string('launchpath', 'mod_micp'), ['size' => 64]);
        $mform->setType('launchpath', PARAM_PATH);
        $mform->setDefault('launchpath', \mod_micp\local\activity_settings::default_launch_path());

        $mform->addElement(
            'filemanager',
            'launchfile',
            get_string('launchfile', 'mod_micp'),
            null,
            $launchmanager->get_file_manager_options()
        );

        $this->standard_grading_coursemodule_elements();
        $mform->setDefault('grade', \mod_micp\local\activity_settings::normalize_grade(100));

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $launchpath = trim($data['launchpath'] ?? '');
        if ($launchpath === '') {
            return $errors;
        }

        if (str_starts_with($launchpath, 'http://') || str_starts_with($launchpath, 'https://')) {
            $errors['launchpath'] = get_string('launchpathinvalidurl', 'mod_micp');
            return $errors;
        }

        if (str_starts_with($launchpath, '/')) {
            $errors['launchpath'] = get_string('launchpathinvalidabsolute', 'mod_micp');
            return $errors;
        }

        if (strpos($launchpath, '..') !== false) {
            $errors['launchpath'] = get_string('launchpathinvalidtraversal', 'mod_micp');
            return $errors;
        }

        if (!preg_match('/\.html\z/i', $launchpath)) {
            $errors['launchpath'] = get_string('launchpathinvalidextension', 'mod_micp');
        }

        return $errors;
    }

    public function data_preprocessing(&$defaultvalues): void {
        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('launchfile');
            file_prepare_draft_area($draftitemid, $this->context->id, 'mod_micp', 'launchpackagezip', 0, [
                'subdirs' => 0,
                'maxfiles' => 1,
            ]);
            $defaultvalues['launchfile'] = $draftitemid;
        }
    }
}
