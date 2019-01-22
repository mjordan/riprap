<?php

namespace App\Tests\Command;

use App\Command\CheckFixityCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class CheckFixityCommandTest extends KernelTestCase
{
    public function testExecute()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new CheckFixityCommand());

        $command = $application->find('app:riprap:check_fixity');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--settings' => 'resources/sample_csv_config.yml'
        ));

        // The output of the command in the console.
        $output = $commandTester->getDisplay();
        $this->assertContains('Riprap checked', $output);

        // Riprap has no entries in its db for this resource; this is OK, since this will
        // be the case for new resources detected by the fetchresourcelist plugins.
        $resource_record = new \stdClass;
        $result = $command->checkFixity(null, $resource_record, '');
        $this->assertTrue($result, "Fixity check event was not successful.");

        // Digest value from reference event is a zero-lenght string.
        $reference_event = new \stdClass;
        $reference_event->digest_value = '';
        $resource_record = new \stdClass;
        $result = $command->checkFixity($reference_event, $resource_record, '85e9d4014da20770b685e9c980b4db64147f1f6c');
        $this->assertTrue($result, "Fixity check event was not successful.");

        // Digest value from reference event and from current fixity check are the same.
        $reference_event = new \stdClass;
        $reference_event->digest_value = '7bb2e6023344e35e72150af91c8c1a8896f4af4d';
        $resource_record = new \stdClass;
        $resource_record->resource_id = '/foo/bar/baz';
        $result = $command->checkFixity($reference_event, $resource_record, '7bb2e6023344e35e72150af91c8c1a8896f4af4d');
        $this->assertTrue($result, "Fixity check event was not successful.");

        // The resource's current last modified date is later than the timestamp in the
        // reference fixity check event for this resource.
        $reference_event = new \stdClass;
        $reference_event->digest_value = '';
        $reference_event->timestamp = '2019-01-21T19:32:38-0800';
        $resource_record = new \stdClass;
        $resource_record->last_modified_timestamp = '2019-02-21T19:32:38-0800';
        $result = $command->checkFixity($reference_event, $resource_record, '');
        $this->assertTrue($result, "Fixity check event was not successful.");

        // Digest mismatch.
        $reference_event = new \stdClass;
        $reference_event->digest_value = '12345';
        $reference_event->timestamp = '2019-01-21T19:32:38-0800';
        $resource_record = new \stdClass;
        $resource_record->last_modified_timestamp = '2019-01-21T19:32:38-0800';
        $result = $command->checkFixity($reference_event, $resource_record, '67890');
        $this->assertFalse($result, "Fixity check event was not successful.");
    }
}
