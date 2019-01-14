<?php

/**
 * @file
 * Defines the abstract class for Riprap fetchresourcelist plugins.
 */

namespace App\Plugin;

/**
 * Abstract class for Riprap plugins.
 */
abstract class AbstractFetchResourceListPlugin
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
     * Gets a list of resource records.
     *
     * @return array|bool
     *   An array of resource records, each of which is
     *   simple object with two properties, 'resource_id'
     *   and 'last_modified_timestamp'. Could be an empty
     *   array. Return false if there was an error.
     */
    abstract public function execute();
}
