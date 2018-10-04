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
        // I expected parameters defined in config/services_test.yaml and
        // config/packages/test/services.yaml to be available in Command tests,
        // but they are not (in both cases, $this->params in the CheckFixity
        // object is null). To work around this, we need to define configuration
        // parameters locally within the test.
        $params = new ParameterBag(
            array(
            'app.fixity.fetchdigest.from.fedoraapi.method' => 'HEAD',
            'app.fixity.fetchdigest.from.fedoraapi.algorithm' => 'SHA-1',
            'app.plugins.fetchresourcelist' => array(),
            'app.plugins.fetchdigest' => array(),
            'app.plugins.persist' => array(),
            'app.plugins.postcheck' => array())
        );

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new CheckFixityCommand($params));

        $command = $application->find('app:riprap:check_fixity');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
        ));

        // The output of the command in the console.
        $output = $commandTester->getDisplay();
        $this->assertContains('Riprap checked', $output);
    }
}
