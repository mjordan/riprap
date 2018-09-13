<?php
// src/Command/CheckFixity.php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
// use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

class CheckFixity extends Command
{
    private $params;

    public function __construct(ParameterBagInterface $params = null, LoggerInterface $logger = null)
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

        // $this
            // ->addArgument('name', InputArgument::OPTIONAL, 'Who do you want to greet?');

        $this
            // ->addOption('fixity_host', 'f', InputOption::VALUE_NONE, 'Fully qualifid URL of the repository host', false);
            ->addOption('fixity', 'f', InputOption::VALUE_NONE, 'Fully qualifid URL of the repository host');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $uuid4 = Uuid::uuid4();
        $uuid4_string = $uuid4->toString();

        $now = date(DATE_RFC2822);
        $output->writeln("Hi, it's $now, and your UUID is " . $uuid4_string . ".");
        $output->writeln("Your fixity host is set to ". $this->fixityHost . ".");

        if ($input->getOption('fixity')) {
            // $output->writeln("You indicated that your preferred host is " . $input->getOption('fixity_host'));
            $output->writeln("You indicated that you like fixity hosts");
        }

        $this->logger->info("check_fixity ran.", array('uuid' => $uuid4_string));

        // Execute plugins using https://symfony.com/doc/current/console/calling_commands.html?
        // @todo: Figure out how to provide configuration parameters for plugins, e.g., in services.yaml.
        if (count($this->plugins) > 0) {
            foreach ($this->plugins as $plugin_name) {
                $plugin_command = $this->getApplication()->find($plugin_name);
                $returnCode = $plugin_command->run($input, $output);
                $this->logger->info("Plugin ran.", array('plugin_name' => $plugin_name, 'return_code' => $returnCode));
            }
        }
    }

}