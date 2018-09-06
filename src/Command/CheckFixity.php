<?php
// src/Command/CheckFixity.php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

class CheckFixity extends Command
{
    private $params;

    public function __construct(ParameterBagInterface $params, LoggerInterface $logger)
    {
        $this->params = $params;

        // Set log output path in config/packages/{environment}/monolog.yaml
        $this->logger = $logger;

        // Set in the parameters section of config/services.yaml.
        $this->fixityHost = $this->params->get('app.fixity.host');
        $this->plugins = $this->params->get('app.plugins');

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
        $uuid4 = Uuid::uuid4();
        $uuid4_string = $uuid4->toString();

        $now = date(DATE_RFC2822);
        $output->writeln("Hi, it's $now, and your UUID is " . $uuid4_string . ".");
        $output->writeln("Your fixity host is set to ". $this->fixityHost . ".");

        $this->logger->info("check_fixity ran.", array('uuid' => $uuid4_string));

        // Fire plugins using https://symfony.com/doc/current/console/calling_commands.html?
        // @todo: Figure out how to pass in configuration parameters to plugins in services.yaml?
        if (count($this->plugins) > 0) {
            foreach ($this->plugins as $plugin_name) {
                $command = $this->getApplication()->find($plugin_name);
                $returnCode = $command->run($input, $output);
                $this->logger->info("Plugin ran.", array('plugin' => $plugin_name, 'return_code' => $returnCode));
            }
        }
    }

}
