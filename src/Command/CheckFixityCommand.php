<?php
// src/Command/CheckFixity.php
namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Stopwatch\Stopwatch;

use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

use App\Entity\Event;

class CheckFixityCommand extends ContainerAwareCommand
{
    private $params;

    public function __construct(ParameterBagInterface $params = null, LoggerInterface $logger = null)
    {
        // Set in the parameters section of config/services.yaml.
        $this->params = $params;
        $this->http_method = $this->params->get('app.fixity.method');
        $this->fixity_algorithm = $this->params->get('app.fixity.algorithm');
        $this->fetchResourceListPlugins = $this->params->get('app.plugins.fetchresourcelist');
        $this->fetchDigestPlugin = $this->params->get('app.plugins.fetchdigest');
        $this->persistPlugins = $this->params->get('app.plugins.persist');
        $this->postCheckPlugins = $this->params->get('app.plugins.postcheck');

        // Set log output path in config/packages/{environment}/monolog.yaml
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:riprap:check_fixity')
            ->setDescription('Console tool for running batches of fixity check events against ' .
                'a Fedora (or other) repository.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('fixity_check');

        // Execute plugins that get a list of resource IDs to check.
        $resource_ids = array();
        $num_resource_ids = 0;
        if (count($this->fetchResourceListPlugins) > 0) {
            foreach ($this->fetchResourceListPlugins as $fetchresourcelist_plugin_name) {
                $fetchresourcelist_plugin_command = $this->getApplication()->find($fetchresourcelist_plugin_name);
                // This class of plugin doesn't take any command-line options.
                $fetchresourcelist_plugin_input = new ArrayInput(array());
                $fetchresourcelist_plugin_output = new BufferedOutput();
                // @todo: Check $returnCode and log+continue if non-0.
                $fetchresourcelist_plugin_return_code = $fetchresourcelist_plugin_command->run(
                    $fetchresourcelist_plugin_input,
                    $fetchresourcelist_plugin_output
                );
                $ids_from_plugin = $fetchresourcelist_plugin_output->fetch();
                $this->logger->info(
                    "Fetchresourcelist plugin ran.",
                    array(
                        'plugin_name' => $fetchresourcelist_plugin_name,
                        'return_code' => $fetchresourcelist_plugin_return_code
                    )
                );
            }

            // Split $ids_from_plugin on newline to get an array of URLs. Assumes that all
            // fetchresourcelistPlugins will return a string, which is probably the case
            // since Symfony console commands output strings, not arrays.
            $ids_from_plugin = preg_split("/\r\n|\n|\r/", trim($ids_from_plugin));
            // Combine the output of all fetchPlugins.
            $resource_ids = array_merge($resource_ids, $ids_from_plugin);
            $num_resource_ids = count($resource_ids);
        }

        // Workaround for making tests pass.
        $env = getenv('APP_ENV');
        if ($num_resource_ids == 0 && $env =! 'test') {
            $this->logger->info("There are no resources to check. Exiting.");
            exit;
        }

        // Loop through the list of resource IDs and perform a fixity check event on each of them.
        $num_successful_events = 0;
        $num_failed_events = 0;
        foreach ($resource_ids as $resource_id) {
            $uuid4 = Uuid::uuid4();
            $event_uuid = $uuid4->toString();
            $now_iso8601 = date(\DateTime::ISO8601);

            $event_detail = '';

            // Print output and log it.
            $this->logger->info("check_fixity ran.", array('event_uuid' => $event_uuid));

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
                        '--event_detail' => $event_detail,
                        '--digest_value' => '',
                        '--outcome' => '',
                        '--operation' => 'get_last_digest',
                    ));
                    $get_last_digest_plugin_output = new BufferedOutput();
                    $get_last_digest_plugin_return_code = $get_last_digest_plugin_command->run(
                        $get_last_digest_plugin_input,
                        $get_last_digest_plugin_output
                    );
                    // $last_digest_for_resource contains the last recorded digest for this resource.
                    // We compare this value with the digest retrieved during the current fixity
                    // check event.

                    // @todo: If we allow multiple persist plugins, the last one called determines
                    // the value of $last_digest_for_resource. Is that OK? Is there a real use case
                    // for persisting to multiple places? If so, can we persist to additional places
                    // using a postcheck plugin instead of multiple persist plugins?
                    $last_digest_for_resource = $get_last_digest_plugin_output->fetch();
                    $this->logger->info("Persist plugin ran.", array(
                        'plugin_name' => $persist_plugin_name,
                        'return_code' => $get_last_digest_plugin_return_code
                    ));

                    // Get the resource's digest and compare it to the last known value. Currently we
                    // only allow one fetchdigest plugin pre resource_id.
                    $get_current_digest_plugin_command = $this->getApplication()->find($this->fetchDigestPlugin);
                    $get_current_digest_plugin_input = new ArrayInput(array(
                        '--resource_id' => $resource_id
                    ));
                    $get_current_digest_plugin_output = new BufferedOutput();
                    $get_current_digest_plugin_return_code = $get_current_digest_plugin_command->run(
                        $get_current_digest_plugin_input,
                        $get_current_digest_plugin_output
                    );
                    $current_digest_plugin_return_value = trim($get_current_digest_plugin_output->fetch());
                    $this->logger->info("Fetchdigest plugin ran.", array(
                        'plugin_name' => $this->fetchDigestPlugin,
                        'return_code' => $get_current_digest_plugin_return_code,
                        // Assumes that the plugin use http... but our filesystemexmaple one doesn't.
                        'http_response_code' => $current_digest_plugin_return_value,
                    ));

                    // If there was a problem, the fetchdigest plugin will return an HTTP response code, so
                    // we check the length of the plugin's output to see if its's longer than 3 charaters.
                    $outcome = 'fail';
                    if (strlen($current_digest_plugin_return_value) > 3) {
                        if ($last_digest_for_resource == $current_digest_plugin_return_value) {
                            $outcome = 'suc';
                            $num_successful_events++;
                            $current_digest_value = $current_digest_plugin_return_value;
                        // Riprap has no entries in its db for this resource; this is OK, since this will
                        // be the case for new resources detected by the fetchresourcelist plugins.
                        } elseif (strlen($last_digest_for_resource) == 0) {
                            $outcome = 'suc';
                            $event_detail = 'Initial fixity check.';
                            $num_successful_events++;
                            $current_digest_value = $current_digest_plugin_return_value;
                        } else {
                            $num_failed_events++;
                            $current_digest_value = $current_digest_plugin_return_value;
                        }
                    } else {
                        $this->logger->error("Fetchdigest plugin ran.", array(
                            'plugin_name' => $this->fetchDigestPlugin,
                            'return_code' => $get_current_digest_plugin_return_code,
                            'http_response_code' => $current_digest_plugin_return_value,
                        ));
                        $num_failed_events++;
                        continue;
                    }

                    // 'persist_fix_event' operation.
                    $persist_fix_event_plugin_command = $this->getApplication()->find($persist_plugin_name);
                    $persist_fix_event_plugin_input = new ArrayInput(array(
                        '--resource_id' => $resource_id,
                        '--timestamp' => $now_iso8601,
                        '--digest_algorithm' => $this->fixity_algorithm,
                        '--event_uuid' => $event_uuid,
                        '--event_detail' => $event_detail,
                        '--digest_value' => $current_digest_value,
                        '--outcome' => $outcome,
                        '--operation' => 'persist_fix_event',
                    ));
                    $persist_fix_event_plugin_output = new BufferedOutput();
                    $persist_fix_event_plugin_return_code = $persist_fix_event_plugin_command->run(
                        $persist_fix_event_plugin_input,
                        $persist_fix_event_plugin_output
                    );
                    // Currently not used.
                    $persist_fix_event_plugin_output_string = $persist_fix_event_plugin_output->fetch();
                    $this->logger->info(
                        "Persist plugin ran.",
                        array(
                            'plugin_name' => $persist_plugin_name,
                            'return_code' => $persist_fix_event_plugin_return_code
                        )
                    );
                }
            }

            // Execute post-check plugins that react to a fixity check event
            // (email admin, migrate legacy data, etc.).
            if (count($this->postCheckPlugins) > 0) {
                foreach ($this->postCheckPlugins as $postcheck_plugin_name) {
                    $postcheck_plugin_command = $this->getApplication()->find($postcheck_plugin_name);
                    $postcheck_plugin_input = new ArrayInput(array(
                        '--resource_id' => $resource_id,
                        '--timestamp' => $now_iso8601,
                        '--digest_algorithm' => $this->fixity_algorithm,
                        '--event_uuid' => $event_uuid,
                        '--digest_value' => $current_digest_plugin_return_value,
                        '--outcome' => $outcome,
                    ));
                    $postcheck_plugin_output = new BufferedOutput();
                    $postcheck_plugin_return_code = $postcheck_plugin_command->run(
                        $postcheck_plugin_input,
                        $postcheck_plugin_output
                    );
                    // Currently not used.
                    $postcheck_plugin_output_string = $postcheck_plugin_output->fetch();
                    $this->logger->info(
                        "Post check plugin ran.",
                        array(
                            'plugin_name' => $postcheck_plugin_name,
                            'return_code' => $postcheck_plugin_return_code
                        )
                    );
                }
            }
        }
        $fixity_check = $stopwatch->stop('fixity_check');
        $duration = $fixity_check->getDuration(); // milliseconds
        $duration = $duration / 1000; // seconds
        $output->writeln("Riprap checked $num_resource_ids resources ($num_successful_events successful events, " .
            "$num_failed_events failed events) in $duration seconds.");
    }
}
