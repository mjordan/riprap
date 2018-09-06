<?php
// src/Command/PluginFoo.php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use Psr\Log\LoggerInterface;

class PluginFoo extends Command
{
    private $params;

    public function __construct(ParameterBagInterface $params, LoggerInterface $logger)
    {
        $this->params = $params;

        // Set log output path in config/packages/{environment}/monolog.yaml
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:riprap:plugin_foo')
            ->setDescription('A sample plugin.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("plugin foo says: Hi!");

        $this->logger->info("plugin foo can log too!.");
    }

}
