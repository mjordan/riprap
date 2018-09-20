<?php
// src/Command/CheckFixity.php
namespace App\Command;

// use Symfony\Component\Console\Command\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
// use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Console\Input\ArrayInput;

use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

use App\Entity\Event;

class CheckFixityCommand extends ContainerAwareCommand
{
    private $params;

    public function __construct(ParameterBagInterface $params = null, LoggerInterface $logger = null)
    {
        $this->params = $params;

        // Set in the parameters section of config/services.yaml.
        $this->fixityHost = $this->params->get('app.fixity.host');
        $this->persistPlugins = $this->params->get('app.plugins.persist');

        // Set log output path in config/packages/{environment}/monolog.yaml
        $this->logger = $logger;

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
        // @todo: Fire plugins that get a list of resource URIs to validate.

        // @todo: Loop through the list and validate them.
        $resource_uris = array('http:foo.com');
        foreach ($resource_uris as $resource_id) {
            $uuid4 = Uuid::uuid4();
            $event_uuid = $uuid4->toString();

            $now_iso8601 = date('c');

            // Print output and log it.
            $this->logger->info("check_fixity ran.", array('event_uuid' => $event_uuid));
            // test data
            $result = 'success';
            $output->writeln("Event $event_uuid validated fixity of $resource_id (result: $result).");

            // @todo: Execute plugins that react to a fixity validation event (email admin, etc.).

            // @todo: Execute plugins that persist event data.
            if (count($this->persistPlugins) > 0) {
                foreach ($this->persistPlugins as $plugin_name) {
                    $plugin_command = $this->getApplication()->find($plugin_name);
                    $plugin_input = new ArrayInput(array(
                        '--resource_id' => $resource_id,
                        '--timestamp' => $now_iso8601,
                        '--event_uuid' => $event_uuid,
                        '--digest_value' => 'somehashvaluefromCheckFixityCommand',
                        '--outcome' => 'success',

                    ));
                    $returnCode = $plugin_command->run($plugin_input, $output);
                    $this->logger->info("Persist plugin ran.", array('plugin_name' => $plugin_name, 'return_code' => $returnCode));
                }
            }

            /*
            // Execute plugins. @todo: Figure out how to provide configuration parameters for plugins, e.g., in services.yaml.
            if (count($this->plugins) > 0) {
                foreach ($this->plugins as $plugin_name) {
                    $plugin_command = $this->getApplication()->find($plugin_name);
                    $plugin_input = new ArrayInput(array());
                    $returnCode = $plugin_command->run($plugin_input, $output);
                    $this->logger->info("Plugin ran.", array('plugin_name' => $plugin_name, 'return_code' => $returnCode));
                }
            }
            */
        }
    }
}