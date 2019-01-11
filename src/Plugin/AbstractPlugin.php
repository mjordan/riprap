<?php

/**
 * @file
 * Defines the abstract class for Riprap plugins.
 */

namespace App\Plugin;

/**
 * Abstract class for Riprap plugins.
 */
abstract class AbstractPlugin
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
     * Modifies or persists the fixity event object.
     *
     * All plugins must implement this method.
     *
     * @param object $event
     *    The Event object.
     *
     * @return The modified Event object.
     */
    abstract public function execute($event);
}
