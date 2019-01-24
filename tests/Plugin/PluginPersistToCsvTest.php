<?php

namespace App\Plugin;

use PHPUnit\Framework\TestCase;
use App\Entity\FixityCheckEvent;

class PluginPersistToCsvTest extends TestCase
{
    protected function setUp()
    {
        // $this->output_csv_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'var/unit_tests_output.csv';
        $this->output_csv_path = 'var/unit_tests_output.csv';
        if (file_exists($this->output_csv_path)) {
            unlink($this->output_csv_path);
        }
    }

    public function testPluginPersistToCsv()
    {
        $event = new FixityCheckEvent();
        $event->setEventUuid('c9527bdd-2e42-4816-b7fe-7a02863e48f6');
        $event->setEventType('fix');
        $event->setResourceId('/foo/bar.txt');
        $event->setTimestamp('2019-01-21T19:43:08-0800');
        $event->setDigestAlgorithm('SHA-1');
        $event->setDigestValue('a2e82e6baf7a7612d2bfe81ce74fa8a63b3a3753');
        $event->setEventDetail('Initial fixity check.');
        $event->setEventOutcome('success');
        $event->setEventOutcomeDetailNote('This is a test.');

        $settings = array(
            'fixity_algorithm' => 'SHA-1',
            'plugins.fetchresourcelist' => array('PluginFetchResourceListFromFile'),
            'resource_list_path' => array('resources/csv_file_list.csv'),
            'plugins.fetchdigest' => 'PluginFetchDigestFromShell',
            'digest_command' => '/usr/bin/sha1sum',
            'plugins.persist' => 'PluginPersistToCsv',
            'output_csv_path' => $this->output_csv_path,
            'plugins.postcheck' => array()
        );

        $plugin = new PluginPersistToCsv($settings, null, null);
        $plugin->persistEvent($event);

        $csv_records = file($this->output_csv_path, FILE_IGNORE_NEW_LINES);
        $event_record = explode(',', $csv_records[1]);
        $this->assertEquals(
            'a2e82e6baf7a7612d2bfe81ce74fa8a63b3a3753',
            $event_record[5],
            'Digest value in persisted event did not match expected value.'
        );

        $reference_event = $plugin->getReferenceEvent('/foo/bar.txt');
        $this->assertEquals(
            'a2e82e6baf7a7612d2bfe81ce74fa8a63b3a3753',
            $reference_event->digest_value,
            'Digest value in referernce event did not match expected value.'
        );
    }

    protected function tearDown()
    {
        @unlink($this->output_csv_path);
    }
}
