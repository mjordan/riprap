<?php

/**
 * @file
 * Defines the abstract class for Riprap fetchdigest plugins.
 */

namespace App\Plugin;

/**
 * Abstract class for Riprap plugins.
 */
abstract class AbstractFetchDigestPlugin
{
    /**
     * Constructor.
     *
     * @param array $settings
     *    The configuration data from the settings file.
     * @param object $logger
     *    The Monolog logger from the main Console command.
     */
    public function __construct($settings, $logger)
    {
        $this->settings = $settings;
        $this->logger = $logger;
    }

    /**
     * Gets the resource's digest from some external source.
     *
     * All plugins must implement this method.
     *
     * @param FixityCheckEvent $event
     *   The fixity check event object.
     *
     * @return FixityCheckEvent $event
     *   The modified fixity check event object.
     */
    abstract public function execute($event);
}
