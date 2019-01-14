<?php

/**
 * @file
 * Defines the abstract class for Riprap persist plugins.
 */

namespace App\Plugin;

/**
 * Abstract class for Riprap plugins.
 */
abstract class AbstractPersistEventPlugin
{
    /**
     * Constructor.
     *
     * @param array $settings
     *    The configuration data from the settings file.
     * @param object $logger
     *    The Monolog logger from the main Console command.
     * @param object $entityManager
     *    The Doctrine Entity Manager from the Console command.     
     */
    public function __construct($settings, $logger, $entityManager)
    {
        $this->settings = $settings;
        $this->logger = $logger;
        $this->entityManager = $entityManager;        
    }

    /**
     * Gets the reference event from the database.
     *
     * @param string $resource_id
     *    The resource's ID.
     *
     * @return object|bool
     *    The reference Event object, which has the two properties
     *    'digest_value' and 'timestamp'. Returns null if there
     *    is no reference event (i.e., it is the first time Riprap
     *    knows about the resource) or there false if there is an error.
     */
    abstract public function getReferenceEvent($resource_id);

    /**
     * Persists the fixity event object.
     *
     * @param FixityCheckEvent $event
     *    The Event object.
     *
     * @return bool
     *    True if the event was persisted or false if there is an error.
     */
    abstract public function persistEvent($event);

    /**
     * Retrieves events from the database.
     *
     * @return array
     *    A list of FixityCheckEvents (could be empty) or
     *    false if there is an error.
     */
    abstract public function getEvents();
}
