<?php

namespace App\Tests\Command;

use App\Command\PluginFetchFromFile;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class PluginFetchFromFileTest extends KernelTestCase
{
    public $params;

    public function testExecute()
    {
        $params = new ParameterBag(array(
            'app.plugins.fetch.from.file.paths' => array('resources/riprap_resource_ids.txt')
        ));
        $this->params = $params;

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new PluginFetchFromFile($params));

        $command = $application->find('app:riprap:plugin:fetch:from:file');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
        ));

        $output = $commandTester->getDisplay();
        $output_array = preg_split("/\r\n|\n|\r/", trim($output));
        $this->assertCount(5, $output_array); 
    }
}