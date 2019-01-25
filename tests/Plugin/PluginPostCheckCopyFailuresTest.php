<?php

namespace App\Plugin;

use PHPUnit\Framework\TestCase;
use App\Entity\FixityCheckEvent;

class PluginPostCheckCopyFailuresLogTest extends TestCase
{
    protected function setUp()
    {
        $this->failures_log_path = 'var/PluginPostCheckCopyFailuresTest.failureslog.csv';
        if (file_exists($this->failures_log_path)) {
            unlink($this->failures_log_path);
        }
    }

    public function testPluginPostCheckCopyFailures()
    {
        $event = new FixityCheckEvent();
        $event->setEventUuid('c9527bdd-2e42-4816-b7fe-7a02863e48f6');
        $event->setEventType('fix');
        $event->setResourceId('/foo/bar.txt');
        $event->setTimestamp('2019-01-21T19:43:08-0800');
        $event->setDigestAlgorithm('SHA-1');
        $event->setDigestValue('a2e82e6baf7a7612d2bfe81ce74fa8a63b3a3753');
        $event->setEventDetail('Initial fixity check.');
        $event->setEventOutcome('fail');
        $event->setEventOutcomeDetailNote('This is a test.');

        $settings = array(
            'failures_log_path' => $this->failures_log_path,
        );

        $plugin = new PluginPostCheckCopyFailures($settings, null, null);
        $plugin->execute($event);

        $failed_events = file($this->failures_log_path, FILE_IGNORE_NEW_LINES);
        $event_record = explode(',', $failed_events[1]);
        $this->assertEquals(
            'fail',
            $event_record[7],
            'Event outcome was "success" but should have been "fail".'
        );
    }

    protected function tearDown()
    {
        @unlink($this->failures_log_path);
    }
}
