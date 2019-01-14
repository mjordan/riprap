<?php
// src/Plugin/PluginPersistToCsv.php
namespace App\Plugin;

use App\Entity\FixityCheckEvent;

class PluginPersistToCsv extends AbstractPersistEventPlugin
{
    public function getReferenceEvent($resource_id) {
        if (!file_exists($this->settings['output_csv_path'])) {
            return false;
        }

        $rows = file($this->settings['output_csv_path'], FILE_IGNORE_NEW_LINES);
        // Get all the rows that apply to the current resource ID.
        $event_records = array();
        foreach ($rows as $row) {
            $fields = explode(',', $row);
            if ($fields[2] == $resource_id) {
                $event_records[] = $fields;
            }
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
        file_put_contents($this->settings['output_csv_path'], $record . "\n", FILE_APPEND);        
    }

    public function getEvents()
    {
        /*

        Code below is copied from old console command version of this plugin.
        
        // Returns a serialized representation of all fixity check events.
        // @todo: Add  offset and limit parameters.
        if ($input->getOption('operation') == 'get_events') {
            $repository = $this->getContainer()->get('doctrine')->getRepository(FixityCheckEvent::class);
            $events = $repository->findFixityCheckEvents(
                $input->getOption('resource_id')
            );
            if (count($events)) {
                $event_entries = array();
                foreach ($events as $event) {
                    $event_array = array();
                    $event_array['event_uuid'] = $event->getEventUuid();
                    $event_array['resource_id'] = $event->getResourceId();
                    $event_array['event_type'] = $event->getEventType();
                    $event_array['timestamp'] = $event->getTimestamp();
                    $event_array['digest_algorithm'] = $event->getDigestAlgorithm();
                    $event_array['digest_value'] = $event->getDigestValue();
                    $event_array['event_detail'] = $event->getEventDetail();
                    $event_array['event_outcome'] = $event->getEventOutcome();
                    $event_array['event_outcome_detail_note'] = $event->getEventOutcomeDetailNote();
                    $event_entries[] = $event_array;
                }
            }
            // $output requires a string.
            $output->write(serialize($event_entries));
        }
        */        

    }
}
