<?php

namespace App\Tests\Command;

use App\Command\CheckFixityCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CheckFixityCommandTest extends KernelTestCase
{
    public function testExecute()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new CheckFixityCommand());

        // We need to access configuration parameters, e.g. app.fixity.host.
        // How? config/services_test.yaml and config/packages/test/services.yaml
        // don't seem to be providing parameters, since in both cases, $this->params
        // in the CheckFixity object is null.

        $command = $application->find('app:riprap:check_fixity');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
        ));

        // The output of the command in the console.
        $output = $commandTester->getDisplay();
        $this->assertContains('Your fixity host is set to', $output);

    }
}
