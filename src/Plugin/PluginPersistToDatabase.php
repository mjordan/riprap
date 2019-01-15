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
            $reference_event = $repository->findLastFixityCheckEvent(
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
        $this->entityManager->persist($event);
        $this->entityManager->flush();
    }

    public function getEvents()
    {
        // See PluginPersistToCsv.
    }
}
