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
     * @param string $resource_id
     *   The resource's ID.
     * @param string $outcome
     *   Either 'success' or 'fail'.
     * @param string $timestamp_start
     *   ISO8601 (full or partial) date indicating start of date range.
     * @param string $timestamp_start
     *   ISO8601 (full or partial) date indicating end of date range.
     * @param string limit
     *   Number of items in the result set to return, starting at the
     *   value of $offset.
     * @param string $sort
     *   Sort events on timestamp. Either "desc" or "asc"
     *   (default is "asc").
     *
     * @return array
     *    A list of FixityCheckEvents (could be empty) or
     *    false if there is an error.
     */
    abstract public function getEvents(
        $resource_id,
        $outcome,
        $timestamp_start,
        $timestamp_end,
        $limit,
        $offset,
        $sort
    );
}
