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
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

use App\Entity\Event;

class CheckFixityCommand extends ContainerAwareCommand
{
    private $params;

    public function __construct(ParameterBagInterface $params = null, LoggerInterface $logger = null)
    {
        $this->params = $params;
        $this->http_method = $this->params->get('app.fixity.method');
        $this->fixity_algorithm = $this->params->get('app.fixity.algorithm');

        // Set in the parameters section of config/services.yaml.
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
            foreach ($this->fetchPlugins as $fetch_plugin_name) {
                $fetch_plugin_command = $this->getApplication()->find($fetch_plugin_name);
                // This class of plugin doesn't take any command-line options.
                $fetch_plugin_input = new ArrayInput(array());
                $fetch_plugin_output = new BufferedOutput();
                // @todo: Check $returnCode and log+continue if non-0.
                $fetch_plugin_return_code = $fetch_plugin_command->run($fetch_plugin_input, $fetch_plugin_output);
                $ids_from_plugin = $fetch_plugin_output->fetch();
                $this->logger->info("Fetch plugin ran.", array('plugin_name' => $fetch_plugin_name, 'return_code' => $fetch_plugin_return_code));
            }

            // Split $ids_from_plugin on newline to get an array of URLs. Assumes that all
            // fetchPlugins will return a string, which is probably the case since Symfony
            // console commands output strings, not arrays.
            $ids_from_plugin = preg_split("/\r\n|\n|\r/", trim($ids_from_plugin));
            // Combine the output of all fetchPlugins.
            $resource_ids = array_merge($resource_ids, $ids_from_plugin);
        }

        // Loop through the list of resource URLs and perform a fixity validation event on them.
        $resource_id_counter = 0;
        foreach ($resource_ids as $resource_id) {
            $uuid4 = Uuid::uuid4();
            $event_uuid = $uuid4->toString();
            $now_iso8601 = date('c');

            // Print output and log it.
            $resource_id_counter++;
            $this->logger->info("check_fixity ran.", array('event_uuid' => $event_uuid));
            // $output->writeln("Event $event_uuid validated fixity of $resource_id (result: $outcome).");

            // Execute plugins that persist event data. We execute them twice and pass in an 'operation' option,
            // once to get the last digest for the resource and again to persist the event resulting from comparing
            // that digest with a new one.
            if (count($this->persistPlugins) > 0) {
                foreach ($this->persistPlugins as $persist_plugin_name) {
                    // 'get_last_digest' operation.
                    $get_last_digest_plugin_command = $this->getApplication()->find($persist_plugin_name);
                    // Even though some of these options aren't used in the 'get_last_digest'
                    // query, we need to pass them into the plugin.
                    $get_last_digest_plugin_input = new ArrayInput(array(
                        '--resource_id' => $resource_id,
                        '--timestamp' => $now_iso8601,
                        '--digest_algorithm' => $this->fixity_algorithm,
                        '--event_uuid' => '',
                        '--digest_value' => '',
                        '--outcome' => '',
                        '--operation' => 'get_last_digest',
                    ));
                    $get_last_digest_plugin_output = new BufferedOutput();
                    $get_last_digest_plugin_return_code = $get_last_digest_plugin_command->run($get_last_digest_plugin_input, $get_last_digest_plugin_output);
                    // Contains the last recorded digest for this resource. We compare this value with
                    // the digest retrieved during the current fixity validation event.
                    $last_digest_for_resource = $get_last_digest_plugin_output->fetch();
                    $this->logger->info("Persist plugin ran.", array(
                        'plugin_name' => $persist_plugin_name,
                        'return_code' => $get_last_digest_plugin_return_code
                    ));

                    // Query the Fedora repository to get a resource's digest and compare it
                    // to the last known value.
                    if ($current_digest_value = $this->get_resource_digest($resource_id)) {
                        if ($last_digest_for_resource == $current_digest_value) {
                             $outcome = 'suc';
                        } else {
                            $outcome = 'fail';
                        }   
                    } else {
                        // Resource ID and HTTP status code are logged in $this->get_resource_digest().
                        continue;
                    }

                    // 'persist_fix_event' operation.
                    $persist_fix_event_plugin_command = $this->getApplication()->find($persist_plugin_name);
                    $persist_fix_event_plugin_input = new ArrayInput(array(
                        '--resource_id' => $resource_id,
                        '--timestamp' => $now_iso8601,
                        '--digest_algorithm' => $this->fixity_algorithm,
                        '--event_uuid' => $event_uuid,
                        '--digest_value' => $current_digest_value,
                        '--outcome' => $outcome,
                        '--operation' => 'persist_fix_event',
                    ));
                    $persist_fix_event_plugin_output = new BufferedOutput();
                    $persist_fix_event_plugin_return_code = $persist_fix_event_plugin_command->run($persist_fix_event_plugin_input, $persist_fix_event_plugin_output);
                    // Currently not used.
                    $persist_fix_event_plugin_output_string = $persist_fix_event_plugin_output->fetch();
                    $this->logger->info("Persist plugin ran.", array('plugin_name' => $persist_plugin_name, 'return_code' => $persist_fix_event_plugin_return_code));
                }
            }

            // Execute post-validate plugins that react to a fixity validation event (email admin, migrate legacy data, etc.).
            if (count($this->postValidatePlugins) > 0) {
                foreach ($this->postValidatePlugins as $postvalidate_plugin_name) {
                    $postvalidate_plugin_command = $this->getApplication()->find($postvalidate_plugin_name);
                    $postvalidate_plugin_input = new ArrayInput(array(
                        '--resource_id' => $resource_id,
                        '--timestamp' => $now_iso8601,
                        '--digest_algorithm' => $this->fixity_algorithm,
                        '--event_uuid' => $event_uuid,
                        '--digest_value' => $current_digest_value,
                        '--outcome' => $outcome,
                    ));
                    $postvalidate_plugin_output = new BufferedOutput();
                    $postvalidate_plugin_return_code = $postvalidate_plugin_command->run($postvalidate_plugin_input, $postvalidate_plugin_output);
                    // Currently not used.
                    $postvalidate_plugin_output_string = $postvalidate_plugin_output->fetch();                    
                    $this->logger->info("Post validate plugin ran.", array('plugin_name' => $postvalidate_plugin_name, 'return_code' => $postvalidate_plugin_return_code));
                }
            }
        }
        $output->writeln("Riprap validated $resource_id_counter resources.");
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
        $client = new \GuzzleHttp\Client();
        // @todo: Wrap in try/catch.
        $response = $client->request($this->http_method, $url, [
            'http_errors' => false,
            'headers' => ['Want-Digest' => $this->fixity_algorithm],
        ]);
        $status_code = $response->getStatusCode();
        $allowed_codes = array(200);
        if (in_array($status_code, $allowed_codes)) {
            $digest_header_values = $response->getHeader('digest');
            // Assumes there is only one 'digiest' header - is this always the case?
            return $digest_header_values[0];
        } else {
            // If the HTTP status code is not in the allowed list, log it.
            $this->logger->warning("check_fixity cannot retrieve digest from repository.", array(
                'resource_id => $url',
                'status_code' => $status_code,
            ));
            return false;
        }
    }
}
