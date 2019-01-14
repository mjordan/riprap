<?php
// src/Plugin/PluginPostCheckSayHello.php
namespace App\Plugin;

class PluginPostCheckSayHello extends AbstractPostCheckPlugin
{
    public function execute($event)
    {
        if ($event->getEventOutcome() == 'success') {
            $uuid = $event->getEventUuid();
            $resource_id = $event->getResourceId();
            $timestamp = $event->getTimestamp();
            if ($this->logger) {
                $this->logger->info("Resource $resource_id says Hello from fixity event $uuid.");
            }
        }
    }
}
