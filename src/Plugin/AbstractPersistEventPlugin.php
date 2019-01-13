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
     * @param object $entityManager
     *    The Doctrine Entity Manager from the Console command.
     * @param array $settings
     *    The configuration data from the settings file.
     * @param object $logger
     *    The Monolog logger from the main Console command.
     */
    public function __construct($entityManager, $settings, $logger)
    {
        $this->entityManager = $entityManager;
        $this->settings = $settings;
        $this->logger = $logger;
    }

    /**
     * Gets the reference event from the database.
     *
     * All plugins must implement this method.
     *
     * @param FixityCheckEvent $event
     *    The Event object.
     *
     * @return FixityCheckEvent
     *    The reference Event object.
     */
    abstract public function getReferenceEvent($event);

    /**
     * Persists the fixity event object.
     *
     * All plugins must implement this method.
     *
     * @param FixityCheckEvent $event
     *    The Event object.
     *
     * @return FixityCheckEvent
     *    The persisted Event object.
     */
    abstract public function execute($event);
}
