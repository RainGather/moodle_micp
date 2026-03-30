<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->libdir . '/filelib.php');
require_once(__DIR__ . '/lib.php');

class mod_micp_mod_form extends moodleform_mod {
    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements();

        $mform->addElement('text', 'launchpath', get_string('launchpath', 'mod_micp'), ['size' => 64]);
        $mform->setType('launchpath', PARAM_PATH);
        $mform->setDefault('launchpath', micp_default_launch_path());

        $mform->addElement('filemanager', 'launchfile', get_string('launchfile', 'mod_micp'), null, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['.zip', '.html'],
        ]);

        $this->standard_grading_coursemodule_elements();
        $mform->setDefault('grade', micp_normalize_grade(100));

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
            $errors['launchpath'] = 'Launch path must be a plugin-local relative HTML file.';
            return $errors;
        }

        if (str_starts_with($launchpath, '/')) {
            $errors['launchpath'] = 'Launch path must not start with /.';
            return $errors;
        }

        if (strpos($launchpath, '..') !== false) {
            $errors['launchpath'] = 'Launch path must not contain path traversal segments.';
            return $errors;
        }

        if (!preg_match('/\.html\z/i', $launchpath)) {
            $errors['launchpath'] = 'Launch path must end in .html.';
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
