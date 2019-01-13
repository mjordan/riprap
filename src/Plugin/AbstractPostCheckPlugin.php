<?php

/**
 * @file
 * Defines the abstract class for Riprap postcheck plugins.
 */

namespace App\Plugin;

/**
 * Abstract class for Riprap plugins.
 */
abstract class AbstractPostCheckPlugin
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
     * Does something after the event has been persisted to the database.
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
