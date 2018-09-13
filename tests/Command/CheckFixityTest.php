<?php

namespace App\Tests\Command;

use App\Command\CheckFixity;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CheckFixityTest extends KernelTestCase
{
    public function testExecute()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new CheckFixity());


        // We need to use configuration parameters, e.g. app.fixity.host. How?

        $command = $application->find('app:riprap:check_fixity');

        $commandTester = new CommandTester($command);
        // $commandTester->execute(array(
            // 'command'  => $command->getName(),
        // ));
        $commandTester->execute();

        // The output of the command in the console.
        $output = $commandTester->getDisplay();
        $this->assertContains('plugin foo says: Hi!', $output);

    }
}
