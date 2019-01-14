<?php

/**
 * @file
 * Defines the abstract class for Riprap fetchdigest plugins.
 */

namespace App\Plugin;

/**
 * Abstract class for Riprap fetchdigest plugins.
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
     * @param string $resource_id
     *   The resource's ID.
     *
     * @return string
     *   The digest value.
     */
    abstract public function execute($resource_id);
}
