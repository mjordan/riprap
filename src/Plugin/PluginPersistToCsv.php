<?php
// src/Plugin/PluginPersistToCsv.php
namespace App\Plugin;

use App\Entity\FixityCheckEvent;

class PluginPersistToCsv extends AbstractPersistEventPlugin
{
    public function getReferenceEvent($resource_id)
    {
        // This is the first time we've tried to get the reference event,
        // so there is no CSV file yet.
        if (!file_exists($this->settings['output_csv_path'])) {
            return null;
        }

        $rows = file($this->settings['output_csv_path'], FILE_IGNORE_NEW_LINES);
        // Accumulate all the rows that apply to the current resource ID that have
        // the same fixity algorithm and that have an outcome of 'success'.
        $event_records = array();
        foreach ($rows as $row) {
            $fields = explode(',', $row);
            if ($fields[2] == $resource_id &&
                $fields[4] == $this->settings['fixity_algorithm'] &&
                $fields[7] == 'success' &&
                strlen($fields[5])
            ) {
                $event_records[] = $fields;
            }
        }

        // Riprap hasn't seen this resource before.
        if (count($event_records) == 0) {
            return null;
        }

        // Get the most recent record.
        $last_record = end($event_records);

        $reference_event = new \stdClass;
        $reference_event->digest_value = $last_record[5];
        $reference_event->timestamp = $last_record[3];
        return $reference_event;
    }

    public function persistEvent($event)
    {
        if (!file_exists($this->settings['output_csv_path'])) {
            $csv_headers = array(
                'event_uuid',
                'event_type',
                'resource_id',
                'timestamp',
                'digest_algorithm',
                'digest_value',
                'event_detail',
                'event_outcome',
                'event_outcome_detail_note'
            );
            $header_row = implode(',', $csv_headers);
            file_put_contents($this->settings['output_csv_path'], $header_row . "\n");
        }

        $record = array(
            $event->getEventUuid(),
            'fix',
            $event->getResourceId(),
            $event->getTimestamp(),
            $event->getDigestAlgorithm(),
            $event->getDigestValue(),
            $event->getEventDetail(),
            $event->getEventOutcome(),
            $event->getEventOutcomeDetailNote()
        );
        $record = implode(',', $record);
        if (@file_put_contents($this->settings['output_csv_path'], $record . "\n", FILE_APPEND)) {
            return true;
        } else {
            $this->logger->error(
                "Persist plugin ran but encountered an error.",
                array(
                    'plugin_name' => 'PluginPersistToCsv',
                    'resource_id' => $event->getResourceId(),
                    'error' => $this->settings['output_csv_path'] . " could not be written to."
                )
            );
            return false;
        }
    }

    public function getEvents($resource_id, $outcome, $timestamp_start, $timestamp_end, $limit, $offset, $sort)
    {
        // For now, only PluginPersisttoDatabase supports this method.
        return array();
    }
}
