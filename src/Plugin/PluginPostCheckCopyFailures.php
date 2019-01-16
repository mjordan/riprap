<?php
// src/Plugin/PluginPostCheckCopyFailures.php
namespace App\Plugin;

class PluginPostCheckCopyFailures extends AbstractPostCheckPlugin
{
    public function execute($event)
    {
        if ($event->getEventOutcome() == 'fail') {
            if (!file_exists($this->settings['failures_log_path'])) {
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
                file_put_contents($this->settings['failures_log_path'], $header_row . "\n");
            }

            $record = array(
                $event->getEventUuid(),
                $event->getEventType(),
                $event->getResourceId(),
                $event->getTimestamp(),
                $event->getDigestAlgorithm(),
                $event->getDigestValue(),
                $event->getEventDetail(),
                $event->getEventOutcome(),
                $event->getEventOutcomeDetailNote()
            );
            $record = implode(',', $record);
            if (@file_put_contents($this->settings['failures_log_path'], $record . "\n", FILE_APPEND)) {
                return true;
            } else {
                $this->logger->error(
                    "Postcheck plugin ran but encountered an error.",
                    array(
                        'plugin_name' => 'PluginPostCheckCopyFailures',
                        'resource_id' => $event->getResourceId(),
                        'error' => $this->settings['failures_log_path'] . " could not be written to."
                    )
                );
                return false;
            }
        }
    }
}
