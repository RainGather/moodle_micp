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

namespace mod_micp\privacy;

use context;
use context_module;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\plugin\provider as plugin_provider;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for mod_micp.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    plugin_provider,
    core_userlist_provider {

    /**
     * Describe stored personal data.
     *
     * @param collection $collection Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection) {
        $collection->add_database_table('micp_events', [
            'userid' => 'privacy:metadata:micp_events:userid',
            'eventtype' => 'privacy:metadata:micp_events:eventtype',
            'payload' => 'privacy:metadata:micp_events:payload',
            'clientts' => 'privacy:metadata:micp_events:clientts',
            'timecreated' => 'privacy:metadata:micp_events:timecreated',
        ], 'privacy:metadata:micp_events');

        $collection->add_database_table('micp_submissions', [
            'userid' => 'privacy:metadata:micp_submissions:userid',
            'rawjson' => 'privacy:metadata:micp_submissions:rawjson',
            'clientmeta' => 'privacy:metadata:micp_submissions:clientmeta',
            'score' => 'privacy:metadata:micp_submissions:score',
            'reviewstatus' => 'privacy:metadata:micp_submissions:reviewstatus',
            'finalscore' => 'privacy:metadata:micp_submissions:finalscore',
            'reviewjson' => 'privacy:metadata:micp_submissions:reviewjson',
            'reviewedby' => 'privacy:metadata:micp_submissions:reviewedby',
            'reviewedat' => 'privacy:metadata:micp_submissions:reviewedat',
            'timecreated' => 'privacy:metadata:micp_submissions:timecreated',
            'timemodified' => 'privacy:metadata:micp_submissions:timemodified',
        ], 'privacy:metadata:micp_submissions');

        return $collection;
    }

    /**
     * Get contexts containing user data.
     *
     * @param int $userid User ID.
     * @return contextlist
     */
    public static function get_contexts_for_userid($userid) {
        $contextlist = new contextlist();
        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'micp',
            'userid' => $userid,
            'reviewerid' => $userid,
        ];
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm
                    ON cm.id = ctx.instanceid
                   AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m
                    ON m.id = cm.module
                   AND m.name = :modname
                  JOIN {micp} micp
                    ON micp.id = cm.instance
             LEFT JOIN {micp_events} me
                    ON me.micpid = micp.id
                   AND me.userid = :userid
             LEFT JOIN {micp_submissions} ms
                    ON ms.micpid = micp.id
                   AND (ms.userid = :userid OR ms.reviewedby = :reviewerid)
                 WHERE me.id IS NOT NULL
                    OR ms.id IS NOT NULL";
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export user data for approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = (int)$contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            $micpid = self::get_micpid_from_context($context);
            if ($micpid === null) {
                continue;
            }

            $events = $DB->get_records('micp_events', [
                'micpid' => $micpid,
                'userid' => $userid,
            ], 'timecreated ASC');

            if ($events) {
                $payload = (object)[
                    'events' => array_values(array_map(static function(\stdClass $event): \stdClass {
                        unset($event->id, $event->micpid);
                        return $event;
                    }, $events)),
                ];

                writer::with_context($context)->export_data(
                    [get_string('privacy:subcontext:events', 'mod_micp')],
                    $payload
                );
            }

            $submission = $DB->get_record('micp_submissions', [
                'micpid' => $micpid,
                'userid' => $userid,
            ]);

            if ($submission) {
                unset($submission->id, $submission->micpid);

                writer::with_context($context)->export_data(
                    [get_string('privacy:subcontext:submission', 'mod_micp')],
                    $submission
                );
            }

            $reviews = $DB->get_records('micp_submissions', [
                'micpid' => $micpid,
                'reviewedby' => $userid,
            ], 'reviewedat ASC, id ASC');

            if ($reviews) {
                $payload = (object)[
                    'reviews' => array_values(array_map(static function(\stdClass $review): \stdClass {
                        return (object)[
                            'finalscore' => $review->finalscore,
                            'reviewjson' => $review->reviewjson,
                            'reviewedat' => $review->reviewedat,
                            'timemodified' => $review->timemodified,
                        ];
                    }, $reviews)),
                ];

                writer::with_context($context)->export_data(
                    [get_string('privacy:subcontext:reviews', 'mod_micp')],
                    $payload
                );
            }
        }
    }

    /**
     * Delete all user data in a context.
     *
     * @param context $context The context to delete data from.
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        global $DB;

        $micpid = self::get_micpid_from_context($context);
        if ($micpid === null) {
            return;
        }

        $DB->delete_records('micp_events', ['micpid' => $micpid]);
        $DB->delete_records('micp_submissions', ['micpid' => $micpid]);
    }

    /**
     * Delete a user's data from approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = (int)$contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            $micpid = self::get_micpid_from_context($context);
            if ($micpid === null) {
                continue;
            }

            $DB->delete_records('micp_events', ['micpid' => $micpid, 'userid' => $userid]);
            $DB->delete_records('micp_submissions', ['micpid' => $micpid, 'userid' => $userid]);
            self::anonymise_reviews_for_users_in_activity($micpid, [$userid]);
        }
    }

    /**
     * Get the list of users who have data in the given context.
     *
     * @param userlist $userlist User list for the context.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        $micpid = self::get_micpid_from_context($context);

        if ($micpid === null) {
            return;
        }

        $params = ['micpid' => $micpid];
        $userlist->add_from_sql(
            'userid',
            'SELECT userid FROM {micp_events} WHERE micpid = :micpid',
            $params
        );
        $userlist->add_from_sql(
            'userid',
            'SELECT userid FROM {micp_submissions} WHERE micpid = :micpid',
            $params
        );
        $userlist->add_from_sql(
            'userid',
            'SELECT reviewedby AS userid FROM {micp_submissions} WHERE micpid = :micpid AND reviewedby IS NOT NULL',
            $params
        );
    }

    /**
     * Delete data for multiple users in a single context.
     *
     * @param approved_userlist $userlist Approved user list.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $micpid = self::get_micpid_from_context($userlist->get_context());
        if ($micpid === null || !$userlist->get_userids()) {
            return;
        }

        [$userinsql, $userparams] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params = ['micpid' => $micpid] + $userparams;
        $where = "micpid = :micpid AND userid {$userinsql}";

        $DB->delete_records_select('micp_events', $where, $params);
        $DB->delete_records_select('micp_submissions', $where, $params);
        self::anonymise_reviews_for_users_in_activity($micpid, $userlist->get_userids());
    }

    /**
     * Resolve the module instance id from a module context.
     *
     * @param context $context Moodle context.
     * @return int|null
     */
    protected static function get_micpid_from_context(context $context): ?int {
        global $DB;

        if (!$context instanceof context_module) {
            return null;
        }

        $sql = "SELECT cm.instance
                  FROM {course_modules} cm
                  JOIN {modules} m
                    ON m.id = cm.module
                 WHERE cm.id = :cmid
                   AND m.name = :modname";

        $instanceid = $DB->get_field_sql($sql, [
            'cmid' => $context->instanceid,
            'modname' => 'micp',
        ]);

        return $instanceid ? (int)$instanceid : null;
    }

    /**
     * Remove reviewer-identifying data while preserving the graded submission outcome.
     *
     * @param int $micpid Activity instance ID.
     * @param int[] $userids User IDs to anonymise as reviewers.
     */
    protected static function anonymise_reviews_for_users_in_activity(int $micpid, array $userids): void {
        global $DB;

        $userids = array_values(array_filter(array_map('intval', $userids)));
        if (!$userids) {
            return;
        }

        [$userinsql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params = ['micpid' => $micpid] + $userparams;
        $records = $DB->get_records_select('micp_submissions', "micpid = :micpid AND reviewedby {$userinsql}", $params);

        foreach ($records as $record) {
            $record->reviewedby = null;
            $record->reviewedat = 0;
            $record->reviewjson = self::sanitise_review_json($record->reviewjson);
            $DB->update_record('micp_submissions', $record);
        }
    }

    /**
     * Strip reviewer-authored comments from persisted review payloads.
     *
     * @param string|null $reviewjson Stored review payload.
     * @return string|null
     */
    protected static function sanitise_review_json(?string $reviewjson): ?string {
        if ($reviewjson === null || $reviewjson === '') {
            return $reviewjson;
        }

        $data = json_decode($reviewjson, true);
        if (!is_array($data)) {
            return null;
        }

        unset($data['generalcomment']);

        if (!empty($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $interactionid => $item) {
                if (!is_array($item)) {
                    unset($data['items'][$interactionid]);
                    continue;
                }

                unset($item['comment']);
                $data['items'][$interactionid] = $item;
            }
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
