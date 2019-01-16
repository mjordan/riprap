<?php
// src/Plugin/PluginPersistToDatabase.php
namespace App\Plugin;

use App\Entity\FixityCheckEvent;

class PluginPersistToDatabase extends AbstractPersistEventPlugin
{
    public function getReferenceEvent($resource_id)
    {
        try {
            $repository = $this->entityManager->getRepository(FixityCheckEvent::class);
            // Note that the reference event has an outcome of 'success'.
            $reference_event = $repository->findReferenceFixityCheckEvent(
                $resource_id,
                $this->settings['fixity_algorithm']
            );
        } catch (Exception $e) {
            $this->logger->error(
                "Persist plugin ran but encountered an error.",
                array(
                    'plugin_name' => 'PluginPersistToDatabase',
                    'resource_id' => $resource_id,
                    'error' => $e->getMessage()
                )
            );
            return null;
        }
        
        if (is_null($reference_event)) {
            return null;
        } else {
            $ret = new \stdClass;
            $ret->digest_value = $reference_event->getDigestValue();
            $ret->timestamp = $reference_event->getTimestamp();
            return $ret;            
        }
    }

    public function persistEvent($event)
    {
        try {
            $this->entityManager->persist($event);
            $this->entityManager->flush();
            return true;
        } catch (Exception $e) {
            $this->logger->error(
                "Persist plugin ran but encountered an error.",
                array(
                    'plugin_name' => 'PluginPersistToDatabase',
                    'resource_id' => $resource_id,
                    'error' => $e->getMessage()
                )
            );
            return false;
        }
    }

    public function getEvents($resource_id, $outcome, $timestamp_start, $timestamp_end, $limit, $offset, $sort)
    {
        $repository = $this->entityManager->getRepository(FixityCheckEvent::class);

       // If these request query parameters are not present, they are NULL.
        if (!is_null($resource_id) ||
            !is_null($timestamp_start) ||
            !is_null($timestamp_end) ||
            !is_null($outcome) ||
            !is_null($sort) ||
            !is_null($offset) ||
            !is_null($limit) {
            $events = $repository->findFixityCheckEventsWithParams(
                $resource_id,
                $timestamp_start,
                $timestamp_end,
                $outcome,
                $offset,
                $limit,
                $sort
            );
        } else {
            // No request query parameters are present.
            $events = $repository->findFixityCheckEvents(
                $resource_id
            );
        }

        $event_entries = array();
        if (count($events)) {
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

        return $event_entries;
    }
}
