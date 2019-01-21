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
     * Does something after the event has been persisted to the database.
     *
     * All plugins must implement this method.
     *
     * @param FixityCheckEvent $event
     *    The Event object.
     *
     * @return bool
     *    No return value.
     */
    abstract public function execute($event);
}
