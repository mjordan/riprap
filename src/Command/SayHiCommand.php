<?php
// src/Command/SayHiCommand.php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

class SayHiCommand extends Command
{
    private $params;

    public function __construct(ParameterBagInterface $params, LoggerInterface $logger)
    {
        $this->params = $params;

        // Set output in config/packages/{environment}/monolog.yaml
        $this->logger = $logger;

        // Set in the parameters section of config/services.yaml.
        $this->fixityHost = $this->params->get('app.fixity.host');

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
        $output->writeln("Hi, it's $now. Your host is ". $this->fixityHost);

        $this->logger->info("Hi from Riprap");
    }
}
