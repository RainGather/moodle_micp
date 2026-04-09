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

$string['pluginname'] = 'MICP';
$string['pluginnameplural'] = 'MICP activities';
$string['modulename'] = 'MICP';
$string['modulenameplural'] = 'MICP activities';
$string['pluginadministration'] = 'MICP activity administration';
$string['modulename_help'] = 'MICP delivers uploaded HTML lesson packages inside Moodle, records learner interactions, '
    . 'applies server-side scoring rules, and supports optional manual review.';
$string['micp:addinstance'] = 'Add a new MICP activity';
$string['micp:view'] = 'View MICP activity';
$string['micp:submit'] = 'Submit MICP activity responses';
$string['micp:viewreports'] = 'View MICP activity reports';
$string['activityplaceholder'] = 'MICP lesson content will be rendered here.';
$string['maximizeiframe'] = 'Fullscreen';
$string['restoreinline'] = 'Exit fullscreen';
$string['defaultlaunchpath'] = 'index.html';
$string['launchpath'] = 'Launch path';
$string['launchpath_help'] = 'Relative plugin-local HTML file to load for the embedded activity.';
$string['launchfile'] = 'Uploaded HTML package or file';
$string['launchfile_help'] = 'Upload either a ZIP package containing index.html and any required CSS, JavaScript, or '
    . 'image assets, or upload a single standalone HTML file. If present, it overrides the launch path setting.';
$string['launchpathinvalidurl'] = 'Launch path must be a plugin-local relative HTML file.';
$string['launchpathinvalidabsolute'] = 'Launch path must not start with /.';
$string['launchpathinvalidtraversal'] = 'Launch path must not contain path traversal segments.';
$string['launchpathinvalidextension'] = 'Launch path must end in .html.';
$string['noinstances'] = 'No MICP activities have been added to this course yet.';
$string['privacy:metadata'] = 'The MICP activity scaffold stores interaction and submission data.';
$string['privacy:metadata:micp_events'] = 'Each learner interaction event recorded by the activity.';
$string['privacy:metadata:micp_events:userid'] = 'The ID of the learner who triggered the event.';
$string['privacy:metadata:micp_events:eventtype'] = 'The event type reported by the lesson runtime.';
$string['privacy:metadata:micp_events:payload'] = 'The JSON payload submitted for the event.';
$string['privacy:metadata:micp_events:clientts'] = 'The client-side timestamp supplied by the browser for the event.';
$string['privacy:metadata:micp_events:timecreated'] = 'The server-side timestamp recording when the event was stored.';
$string['privacy:metadata:micp_submissions'] = 'The latest submission snapshot stored for a learner in the activity.';
$string['privacy:metadata:micp_submissions:userid'] = 'The ID of the learner who owns the submission.';
$string['privacy:metadata:micp_submissions:rawjson'] = 'The raw submission payload received from the lesson runtime.';
$string['privacy:metadata:micp_submissions:clientmeta'] = 'Additional client metadata supplied with the submission.';
$string['privacy:metadata:micp_submissions:score'] = 'The automatically calculated percentage score before manual '
    . 'review.';
$string['privacy:metadata:micp_submissions:reviewstatus'] = 'Whether the submission requires manual review.';
$string['privacy:metadata:micp_submissions:finalscore'] = 'The final percentage score after manual review, when '
    . 'applicable.';
$string['privacy:metadata:micp_submissions:reviewjson'] = 'The teacher review payload, including manual scores and '
    . 'comments.';
$string['privacy:metadata:micp_submissions:reviewedby'] = 'The ID of the teacher who finalised manual review.';
$string['privacy:metadata:micp_submissions:reviewedat'] = 'The time when manual review was completed.';
$string['privacy:metadata:micp_submissions:timecreated'] = 'The time when the submission record was first created.';
$string['privacy:metadata:micp_submissions:timemodified'] = 'The time when the submission record was last updated.';
$string['privacy:subcontext:events'] = 'Interaction events';
$string['privacy:subcontext:reviews'] = 'Reviews performed';
$string['privacy:subcontext:submission'] = 'Submission';
$string['resultsummarytitle'] = 'Your activity result';
$string['reporttitle'] = 'MICP results';
$string['viewresults'] = 'View results';
$string['participant'] = 'Participant';
$string['submissionstatus'] = 'Submission status';
$string['reviewstatus'] = 'Review status';
$string['lastsubmission'] = 'Last submission';
$string['activityscore'] = 'Activity score';
$string['interactionstate'] = 'Interaction state';
$string['interactionbreakdown'] = 'Interaction breakdown';
$string['grade'] = 'Grade';
$string['reviewaction'] = 'Review';
$string['reportsummary'] = 'Participants: {$a->participants}, submitted: {$a->submitted}, graded: {$a->graded}';
$string['nograderecord'] = 'No grade yet';
$string['notsubmitted'] = 'Not submitted';
$string['submitted'] = 'Submitted';
$string['pendingteacherreview'] = 'Pending teacher review';
$string['reviewedbyteacher'] = 'Reviewed by teacher';
$string['reviewstatuspending'] = 'Pending review';
$string['reviewstatusreviewed'] = 'Reviewed';
$string['reviewstatusnotrequired'] = 'Not required';
$string['reviewstatusnotapplicable'] = 'Not applicable';
$string['pendingreviewshort'] = 'Pending review';
$string['pendingreviewdetail'] = 'Pending review';
$string['interactionfallbacklabel'] = 'Interaction';
$string['interactionrecorded'] = 'Interaction recorded';
$string['nointeractionrecorded'] = 'No interaction recorded yet';
$string['reviewsubmission'] = 'Review submission';
$string['reviewsubmissiontitle'] = 'Manual review';
$string['reviewsaved'] = 'Review saved and final grade published.';
$string['backtoreport'] = 'Back to report';
$string['reviewingstudent'] = 'Reviewing: {$a}';
$string['reviewsubmissiontime'] = 'Last submission: {$a}';
$string['reviewprovisionalscore'] = 'Provisional auto score: {$a->score}% ({$a->grade})';
$string['reviewmaxpoints'] = 'Max points for this item: {$a}';
$string['reviewlearnerresponse'] = 'Learner response';
$string['reviewscorelabel'] = 'Teacher-assigned points';
$string['reviewcommentlabel'] = 'Teacher comment';
$string['reviewgeneralcomment'] = 'Overall teacher comment';
$string['finalizereview'] = 'Finalize review';
$string['nomanualitems'] = 'This submission has no manual-review items.';
$string['nosubmissionforreview'] = 'There is no submission to review for this learner.';
$string['noresponsecaptured'] = 'No response captured.';
$string['currentgroupdisplay'] = 'Current group: {$a}';
$string['allparticipantsgroup'] = 'All participants';
$string['groupmodedisabled'] = 'Group mode disabled';
$string['never'] = 'Never';
$string['groupfilterlabel'] = 'Group filter';
$string['submiterror'] = 'The MICP submission could not be processed.';
$string['viewerror'] = 'The MICP activity could not be displayed.';
