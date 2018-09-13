<?php
// src/Command/CheckFixity.php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
// use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Console\Input\ArrayInput;

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

        $this
            ->addOption('fixity_host', 'f', InputOption::VALUE_REQUIRED, 'Fully qualifid URL of the repository host', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $uuid4 = Uuid::uuid4();
        $uuid4_string = $uuid4->toString();

        $now = date(DATE_RFC2822);
        $output->writeln("Hi, it's $now, and your UUID is " . $uuid4_string . ".");
        $output->writeln("Your fixity host is set to ". $this->fixityHost . ".");

        if ($input->getOption('fixity_host')) {
            $output->writeln("You indicated that your preferred host is " . $input->getOption('fixity_host'));
        }

        $this->logger->info("check_fixity ran.", array('uuid' => $uuid4_string));

        // Execute plugins. @todo: Figure out how to provide configuration parameters for plugins, e.g., in services.yaml.
        if (count($this->plugins) > 0) {
            foreach ($this->plugins as $plugin_name) {
                $plugin_command = $this->getApplication()->find($plugin_name);
                // @todo: It would be great to be able to provide plugins with options passed to riprap.
                // $input->getOptions() gets the list, but plugins complain that "The "x" argument does not exist."
                // Until we figure this out, we pass in an empty array to prevent those errors.
                $plugin_input = new ArrayInput(array());
                $returnCode = $plugin_command->run($plugin_input, $output);
                $this->logger->info("Plugin ran.", array('plugin_name' => $plugin_name, 'return_code' => $returnCode));
            }
        }

    }

}