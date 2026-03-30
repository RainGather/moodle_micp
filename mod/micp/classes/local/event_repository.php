<?php

namespace mod_micp\local;

defined('MOODLE_INTERNAL') || die();

class event_repository {
    public function create(int $micpid, int $userid, string $eventtype, string $payload, ?int $clientts): \stdClass {
        global $DB;

        $record = (object) [
            'micpid' => $micpid,
            'userid' => $userid,
            'eventtype' => $eventtype,
            'payload' => $payload,
            'clientts' => $clientts,
            'timecreated' => time(),
        ];

        $record->id = $DB->insert_record('micp_events', $record);

        return $record;
    }
}
