<?php
// src/Command/SayHiCommand.php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;

class SayHiCommand extends Command
{

    public function __construct(LoggerInterface $logger)
    {
        // Set output in config/packages/{environment}/monolog.yaml
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:riprap:say_hi')
            ->setDescription('Says Hi.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $now = date(DATE_RFC2822);
        $output->writeln("Hi, it's $now.");

        $this->logger->info("Hi from Riprap");
    }
}
