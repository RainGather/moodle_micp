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
require_capability('mod/micp:view', $context);

$iframeid = 'mod-micp-iframe-' . $cm->id;
$iframetitle = format_string($micp->name);

$micpcontext = [
    'userid' => (int)$USER->id,
    'cmid' => (int)$cm->id,
    'courseid' => (int)$course->id,
    'sesskey' => sesskey(),
    'username' => (string)($USER->username ?? fullname($USER)),
];

$amdarguments = [
    'activitySelector' => '[data-region="mod-micp-activity"]',
    'iframeId' => $iframeid,
    'iframeSrc' => '',
    'launchPath' => '',
];

$resultsummary = micp_get_user_result_summary($micp, (int)$USER->id);
$launch = micp_get_launch_url($micp, $context);
$iframeurl = $launch['url'];
$launchpath = $launch['path'];
$amdarguments['iframeSrc'] = $iframeurl;
$amdarguments['launchPath'] = $launchpath;
$canviewreports = has_capability('mod/micp:viewreports', $context);

$PAGE->set_url('/mod/micp/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($micp->name));
$PAGE->set_heading(format_string($course->fullname));

$PAGE->requires->js_init_code('window.MICP_CONTEXT = ' . json_encode(
    $micpcontext,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) . ';');
$PAGE->requires->js_call_amd('mod_micp/view', 'init', [$amdarguments]);

$rendererdata = [
    'name' => $iframetitle,
    'intro' => format_module_intro('micp', $micp, $cm->id),
    'launchpath' => $launchpath,
    'uploadedbacked' => !empty($launch['uploaded']),
    'iframesrc' => $iframeurl,
    'iframeid' => $iframeid,
    'iframetitle' => $iframetitle,
    'resultsummary' => $resultsummary,
    'canviewreports' => $canviewreports,
    'reporturl' => (new moodle_url('/mod/micp/report.php', ['id' => $cm->id]))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_micp/activity', $rendererdata);
echo $OUTPUT->footer();
