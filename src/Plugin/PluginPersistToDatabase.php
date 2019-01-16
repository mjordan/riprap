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

    public function getEvents()
    {
        $repository = $this->entityManager->getRepository(FixityCheckEvent::class);
        // @todo: We'll need to figure out how to pass in all the possible options
        // from FixityController.php. Maybe just a long list of arguements like
        // getEvents($resource_id, $outcome, $timestamp_start, $timestamp_end, $limit, $offset, $sort)?
    }
}
