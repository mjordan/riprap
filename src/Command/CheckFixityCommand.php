<?php
// src/Command/CheckFixity.php
namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

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
        $this->fixityHost = $this->params->get('app.fixity.host'); // Do we need this if we are providing full resource URLs?
        $this->fetchPlugins = $this->params->get('app.plugins.fetch');
        $this->persistPlugins = $this->params->get('app.plugins.persist');
        $this->postValidatePlugins = $this->params->get('app.plugins.postvalidate');

        // Set log output path in config/packages/{environment}/monolog.yaml
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:riprap:check_fixity')
            ->setDescription('Console tool for running batches of fixity validation events against a Fedora repository.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Fire plugins that get a list of resource URLs to validate.
        $resource_ids = array();
        if (count($this->fetchPlugins) > 0) {
            foreach ($this->fetchPlugins as $plugin_name) {
                $plugin_command = $this->getApplication()->find($plugin_name);
                // This class of plugin doesn't take any command-line options.
                $plugin_input = new ArrayInput(array());
                $output = new BufferedOutput();
                // @todo: Check $returnCode and log+continue if non-0.
                $returnCode = $plugin_command->run($plugin_input, $output);
                $ids_from_plugin = $output->fetch();
                $this->logger->info("Fetch plugin ran.", array('plugin_name' => $plugin_name, 'return_code' => $returnCode));
            }

            // Split $ids_from_plugin on newline to get an array of URLs. Assumes that all
            // fetchPlugins will return a string, which is probably the case since Symfony
            // console commands output strings, not arrays.
            $ids_from_plugin = $array = preg_split("/\r\n|\n|\r/", trim($ids_from_plugin));
            // Combine the output of all fetchPlugins.
            $resource_ids = array_merge($resource_ids, $ids_from_plugin);
        }

        // Loop through the list of resource URLs and perform a fixity validation event on them.
        foreach ($resource_ids as $resource_id) {
            $uuid4 = Uuid::uuid4();
            $event_uuid = $uuid4->toString();
            $now_iso8601 = date('c');

            // @todo: Query the Fedora repository and get a resource's digest.
            // if (!$digest_value = $this->get_resource_digest($resource_id)) {
                // @todo: Log failure?
                // continue;
            // }

            // if (compare_digests($digest_value)) {
                $outcome = 'success'; // test data
            // } else {
                // $outcome = 'failure';
            // }

            // Print output and log it.
            $this->logger->info("check_fixity ran.", array('event_uuid' => $event_uuid));
            $output->writeln("Event $event_uuid validated fixity of $resource_id (result: $outcome).");

            // Execute plugins that persist event data.
            if (count($this->persistPlugins) > 0) {
                foreach ($this->persistPlugins as $plugin_name) {
                    $plugin_command = $this->getApplication()->find($plugin_name);
                    $plugin_input = new ArrayInput(array(
                        '--resource_id' => $resource_id,
                        '--timestamp' => $now_iso8601,
                        '--event_uuid' => $event_uuid,
                        '--digest_value' => 'somehashvaluefromCheckFixityCommand', // test data
                        '--outcome' => $outcome,
                    ));
                    $returnCode = $plugin_command->run($plugin_input, $output);
                    $this->logger->info("Persist plugin ran.", array('plugin_name' => $plugin_name, 'return_code' => $returnCode));
                }
            }

            // Execute post-validate plugins that react to a fixity validation event (email admin, migrate legacy data, etc.).
            if (count($this->postValidatePlugins) > 0) {
                foreach ($this->postValidatePlugins as $plugin_name) {
                    $plugin_command = $this->getApplication()->find($plugin_name);
                    $plugin_input = new ArrayInput(array(
                        '--resource_id' => $resource_id,
                        '--timestamp' => $now_iso8601,
                        '--event_uuid' => $event_uuid,
                        '--digest_value' => 'somehashvaluefromCheckFixityCommand', // test data
                        '--outcome' => $outcome,
                    ));
                    $returnCode = $plugin_command->run($plugin_input, $output);
                    $this->logger->info("Post validate plugin ran.", array('plugin_name' => $plugin_name, 'return_code' => $returnCode));
                }
            }


        }
    }

    /**
     * Queries a Fedora repository to get the digest of the resource.
     *
    * @param string $url
    *   The resource's URL.
    *
    * @return string
    *   The digest retrieved from the repository or false on failure.
    */
    protected function get_resource_digest($url)
    {


    }

    /**
     * Compares the newly retrieved digest with the last recorded digest value.
     *
    * @param string $digest
    *   The digest value.
    *
    * @return bool
    *   True if the digests match, false if they do not.
    */
    protected function compare_digests($url)
    {
         return true; //test data
    }
}
