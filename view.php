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

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'micp');
$micp = $DB->get_record('micp', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/micp:view', $context);
$results = new \mod_micp\local\result_service();
$launchmanager = new \mod_micp\local\launch_manager();

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
    'strings' => [
        'submiterror' => get_string('submiterror', 'mod_micp'),
        'restoreinline' => get_string('restoreinline', 'mod_micp'),
        'interactionfallbacklabel' => get_string('interactionfallbacklabel', 'mod_micp'),
    ],
];

$resultsummary = $results->get_user_result_summary($micp, (int)$USER->id);
$launch = $launchmanager->get_launch_url($micp, $context);
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
    'maximizeiframelabel' => get_string('maximizeiframe', 'mod_micp'),
    'restoreinlinelabel' => get_string('restoreinline', 'mod_micp'),
    'resultsummarytitle' => get_string('resultsummarytitle', 'mod_micp'),
    'submissionstatuslabel' => get_string('submissionstatus', 'mod_micp'),
    'activityscorelabel' => get_string('activityscore', 'mod_micp'),
    'gradelabel' => get_string('grade', 'mod_micp'),
    'interactionstatelabel' => get_string('interactionstate', 'mod_micp'),
    'lastsubmissionlabel' => get_string('lastsubmission', 'mod_micp'),
    'interactionbreakdownlabel' => get_string('interactionbreakdown', 'mod_micp'),
    'viewresultslabel' => get_string('viewresults', 'mod_micp'),
    'resultsummary' => $resultsummary,
    'canviewreports' => $canviewreports,
    'reporturl' => (new moodle_url('/mod/micp/report.php', ['id' => $cm->id]))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_micp/activity', $rendererdata);
echo $OUTPUT->footer();
