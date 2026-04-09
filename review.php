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
$userid = required_param('userid', PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'micp');
$micp = $DB->get_record('micp', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/micp:viewreports', $context);
$results = new \mod_micp\local\result_service();

$student = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
$submission = $results->get_submission_record($micp, $userid);
$evaluation = $results->evaluate($micp, $userid);
$reviewdata = $results->get_review_data($submission);
$latestactions = $results->get_latest_submission_actions_by_interactionid($submission);
$manualdetails = array_values(array_filter($evaluation['details'] ?? [], static function(array $detail): bool {
    return ($detail['gradingmode'] ?? 'auto') === 'manual';
}));

$url = new moodle_url('/mod/micp/review.php', ['id' => $cm->id, 'userid' => $userid]);
$reporturl = new moodle_url('/mod/micp/report.php', ['id' => $cm->id]);

$PAGE->set_url($url);
$PAGE->set_title(get_string('reviewsubmissiontitle', 'mod_micp'));
$PAGE->set_heading(format_string($course->fullname));

if (optional_param('save', '', PARAM_ALPHA) === 'finalize' && confirm_sesskey()) {
    if (!$submission) {
        throw new moodle_exception('nosubmissionforreview', 'mod_micp');
    }

    $reviewitems = [];
    $manualearned = 0.0;
    foreach ($manualdetails as $detail) {
        $interactionid = (string)($detail['interactionid'] ?? '');
        if ($interactionid === '') {
            continue;
        }

        $scoreparam = 'score_' . $interactionid;
        $commentparam = 'comment_' . $interactionid;
        $score = optional_param($scoreparam, 0, PARAM_FLOAT);
        $score = max(0.0, min((float)($detail['max'] ?? 0), (float)$score));
        $manualearned += $score;

        $reviewitems[$interactionid] = [
            'score' => $score,
            'comment' => optional_param($commentparam, '', PARAM_TEXT),
        ];
    }

    $autoearned = 0.0;
    $totalmax = 0.0;
    foreach ($evaluation['details'] ?? [] as $detail) {
        $totalmax += (float)($detail['max'] ?? 0);
        if (($detail['gradingmode'] ?? 'auto') !== 'manual') {
            $autoearned += (float)($detail['earned'] ?? 0);
        }
    }

    $finalscore = $totalmax > 0 ? (int)round((($autoearned + $manualearned) / $totalmax) * 100) : 0;
    $repository = new \mod_micp\local\submission_repository();
    $repository->save_review(
        $micp->id,
        $userid,
        $finalscore,
        json_encode([
            'items' => $reviewitems,
            'generalcomment' => optional_param('generalcomment', '', PARAM_TEXT),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        (int)$USER->id,
        time()
    );

    $results->update_grades($micp, $userid);
    redirect($url, get_string('reviewsaved', 'mod_micp'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('reviewsubmissiontitle', 'mod_micp'));
echo html_writer::div(html_writer::link($reporturl, get_string('backtoreport', 'mod_micp')), 'mb-3');

if (!$submission) {
    echo $OUTPUT->notification(get_string('nosubmissionforreview', 'mod_micp'), 'warning');
    echo $OUTPUT->footer();
    exit;
}

echo html_writer::tag('p', get_string('reviewingstudent', 'mod_micp', fullname($student)));
echo html_writer::tag('p', get_string('reviewsubmissiontime', 'mod_micp', userdate((int)$submission->timemodified)));
echo html_writer::tag('p', get_string('reviewprovisionalscore', 'mod_micp', (object)[
    'score' => (int)($submission->score ?? 0),
    'grade' => format_float(
        (float)(
            (
                \mod_micp\local\activity_settings::normalize_grade($micp->grade ?? null) *
                (int)($submission->score ?? 0)
            ) / 100
        ),
        2
    ),
]));

if (!$manualdetails) {
    echo $OUTPUT->notification(get_string('nomanualitems', 'mod_micp'), 'info');
    echo $OUTPUT->footer();
    exit;
}

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'save', 'value' => 'finalize']);

foreach ($manualdetails as $detail) {
    $interactionid = (string)($detail['interactionid'] ?? '');
    $response = $latestactions[$interactionid]['response'] ?? '';
    if (!is_scalar($response)) {
        $response = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    $reviewitem = $reviewdata['items'][$interactionid] ?? [];
    $fieldset = [];
    $fieldset[] = html_writer::tag('h3', s((string)($detail['label'] ?? $interactionid)));
    $fieldset[] = html_writer::tag(
        'p',
        get_string('reviewmaxpoints', 'mod_micp', format_float((float)($detail['max'] ?? 0), 2))
    );
    $fieldset[] = html_writer::tag('p', get_string('reviewlearnerresponse', 'mod_micp'));
    $fieldset[] = html_writer::tag(
        'pre',
        s($response !== '' ? (string)$response : get_string('noresponsecaptured', 'mod_micp'))
    );
    $fieldset[] = html_writer::label(get_string('reviewscorelabel', 'mod_micp'), 'score_' . $interactionid);
    $fieldset[] = html_writer::empty_tag('input', [
        'type' => 'number',
        'name' => 'score_' . $interactionid,
        'id' => 'score_' . $interactionid,
        'value' => (string)($reviewitem['score'] ?? 0),
        'min' => 0,
        'max' => (float)($detail['max'] ?? 0),
        'step' => '0.01',
        'class' => 'form-control mb-2',
    ]);
    $fieldset[] = html_writer::label(get_string('reviewcommentlabel', 'mod_micp'), 'comment_' . $interactionid);
    $fieldset[] = html_writer::tag('textarea', s((string)($reviewitem['comment'] ?? '')), [
        'name' => 'comment_' . $interactionid,
        'id' => 'comment_' . $interactionid,
        'rows' => 4,
        'class' => 'form-control',
    ]);
    echo html_writer::div(implode('', $fieldset), 'mb-4 p-3 border rounded');
}

echo html_writer::label(get_string('reviewgeneralcomment', 'mod_micp'), 'generalcomment');
echo html_writer::tag('textarea', s((string)($reviewdata['generalcomment'] ?? '')), [
    'name' => 'generalcomment',
    'id' => 'generalcomment',
    'rows' => 4,
    'class' => 'form-control mb-3',
]);
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'class' => 'btn btn-primary',
    'value' => get_string('finalizereview', 'mod_micp'),
]);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
