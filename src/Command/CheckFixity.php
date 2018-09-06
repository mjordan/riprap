<?php
// src/Command/CheckFixity.php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

class CheckFixity extends Command
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
            ->setName('app:riprap:check_fixity')
            ->setDescription('Says Hello world.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = $this->startTimer();
        $now = date(DATE_RFC2822);
        $output->writeln("Hi, it's $now. Your host is ". $this->fixityHost);
        $seconds = $this->startTimer($start);

        $this->logger->info("check_fixity ran, took $seconds seconds.");
    }

    /**
     * Starts the benchmark timer.
     */
    private function startTimer() {
        return microtime(true);
    }

    /**
     * Stops the benchmark timer and reports the results.
     *
     * @param float $time_start
     *   The current time in seconds since the Unix epoch accurate
     *   to the nearest microsecond.
     *
     * @return int
     *   The number of seconds.
     */
    private function stopTimer($time_start) {
        $time_end = microtime(true);
        $time = $time_end - $time_start;
        return $time;
    }

}
