<?php

/**
 * @file
 * Defines the abstract class for Riprap plugins.
 */

namespace App\Plugin;

use App\Entity\FixityCheckEvent;

/**
 * Class for Riprap plugin that persists to a db via Doctrine.
 */
class TestPlugin 
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
     * Persists a fixity event.
     *
     * All plugins must implement this method.
     *
     * @param object $event
     *    The Event object.
     *
     * @return The modified Event object.
     */
    public function execute($event) {
         $this->logger->info("Hello from TestPlugin.");

            // $entityManager = $this->getDoctrine()->getManager();
            $event = new FixityCheckEvent();
            $event->setEventUuid('foo');
            $event->setEventType('fax');
            $event->setResourceId('http://foo.com');
            $event->setTimestamp(time());
            $event->setDigestAlgorithm('FOO-1');
            $event->setDigestValue('123456');

            $event->setEventDetail('Quite the event');
            $event->setEventOutcome('fail');
            $event->setEventOutcomeDetailNote('');

            $this->entityManager->persist($event);
            $this->entityManager->flush();
    }

}
