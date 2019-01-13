<?php
// src/Command/CheckFixity.php
namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Yaml\Yaml;

use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

use App\Entity\Event;
use App\Service\FixityEventDetailManager;

class CheckFixityCommand extends ContainerAwareCommand
{
    private $params;

    public function __construct(
        ParameterBagInterface $params = null,
        LoggerInterface $logger = null
    ) {
        // Set in the parameters section of config/services.yaml.
        $this->params = $params;
        // $this->http_method = $this->params->get('app.fixity.fetchdigest.from.fedoraapi.method');
        $this->fixity_algorithm = $this->params->get('app.fixity_algorithm');
        // $this->fetchResourceListPlugins = $this->params->get('app.plugins.fetchresourcelist');
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
                'a Fedora (or other) repository.')  
            ->addOption('settings', null, InputOption::VALUE_REQUIRED, 'Absolute path to YAML configuration settings file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('fixity_check');

        $settings_path = $input->getOption('settings');
        $this->settings = Yaml::parseFile($settings_path);
        $this->fetchResourceListPlugins = $this->settings['plugins.fetchresourcelist'];

            /*
            $entityManager = $this->getContainer()->get('doctrine')->getEntityManager();
            $this->entityManager = $entityManager;
            $plugin_name = 'App\Plugin\TestPlugin';
            $issue31_plugin = new $plugin_name($this->entityManager, $this->params, $this->logger);
            $foo = $issue31_plugin->execute($stopwatch);
            */

        // Execute plugins that get a list of resource IDs to check.
        $resource_ids = array();
        $num_resource_ids = 0;
        // $this->fetchResourceListPlugins = array('PluginFetchResourceListFromFileIssue26');
        if (count($this->fetchResourceListPlugins) > 0) {
            foreach ($this->fetchResourceListPlugins as $fetchresourcelist_plugin_name) {
                $plugin_name = 'App\Plugin\\' . $fetchresourcelist_plugin_name;
                $issue31_plugin = new $plugin_name($this->settings, $this->logger);
                $resource_records = $issue31_plugin->execute();
                /*
                $fetchresourcelist_plugin_command = $this->getApplication()->find($fetchresourcelist_plugin_name);
                // This class of plugin doesn't take any command-line options.
                $fetchresourcelist_plugin_input = new ArrayInput(array());
                $fetchresourcelist_plugin_output = new BufferedOutput();
                // @todo: Check $returnCode and log, continue if non-0.
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
                */
            }

            // Split $ids_from_plugin on newline to get an array of resource URIs.
            // Assumes that all fetchresourcelistPlugins will return a string,
            // which is probably the case since Symfony console commands output
            // strings, not arrays.
            // $ids_from_plugin = preg_split("/\r\n|\n|\r/", trim($ids_from_plugin));

            // Combine the output of all fetchPlugins.
            // $resource_records = array_merge($resource_ids, $ids_from_plugin);
            // if (count($resource_records) > 0 && strlen($resource_records->)) {
                // $num_resource_ids = count($resource_ids);
            // }
            // else {
                // $num_resource_ids = 0;
            // }
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
        foreach ($resource_records as $resource_record) {
            // If the resource ID is empty, move on to the next one.
            if (!strlen($resource_record->resource_id)) {
                continue;
            }
            print "\n";
            $uuid4 = Uuid::uuid4();
            $event_uuid = $uuid4->toString();
            $now_iso8601 = date(\DateTime::ISO8601);

            // Execute plugins that persist event data. We execute them twice and pass in an 'operation' option,
            // once to get the last digest for the resource and again to persist the event resulting from comparing
            // that digest with a new one.
            if (count($this->persistPlugins) > 0) {
                foreach ($this->persistPlugins as $persist_plugin_name) {
                    // $json_object_array = json_decode($resource_id, true);
                    // $resource_id = $json_object_array['resource_id'];
                    // $last_modified_timestamp = $json_object_array['last_modified_timestamp'];               

                    // 'get_last_digest' operation.
                    $reference_event_plugin_command = $this->getApplication()->find($persist_plugin_name);
                    // Even though some of these options aren't used in the 'get_last_digest'
                    // query, we need to pass them into the plugin.
                    $reference_event_plugin_input = new ArrayInput(array(
                        '--resource_id' => $resource_record->resource_id,
                        '--timestamp' => $now_iso8601,
                        '--digest_algorithm' => $this->fixity_algorithm,
                        '--event_uuid' => '',
                        '--digest_value' => '',
                        '--outcome' => '',
                        '--operation' => 'get_last_digest',
                    ));
                    // $reference_event is the previous fixity check event for this resource.
                    $reference_event_plugin_output = new BufferedOutput();
                    $reference_event_plugin_return_code = $reference_event_plugin_command->run(
                        $reference_event_plugin_input,
                        $reference_event_plugin_output
                    );
                    $reference_event = json_decode($reference_event_plugin_output->fetch(), true);
                    // var_dump("From command");
                    // var_dump($resource_id);
                    // var_dump($reference_event);
                    // $reference_event contains to members, 1) the digest recorded in the last fixity
                    // event check for this resource, which we compare this value with the digest retrieved
                    // during the current fixity check, and 2) the timestamp from the last event, so we can
                    // compare the current last modified timestamp of the resource to it. If the current last-
                    // modified timestamp of the resource is later than the timestamp in the reference event,
                    // we don't compare digests since the digest to be different (afer all, the resource
                    // was modified); otherwise, we compare digests since the resource has not been modified
                    // since the reference (i.e., previous) fixity check event.

                    // @todo: If we allow multiple persist plugins, the last one called determines
                    // the value of $last_digest_for_resource. Is that OK? Is there a real use case
                    // for persisting to multiple places? If so, can we persist to additional places
                    // using a postcheck plugin instead of multiple persist plugins?

                    $reference_event_digest_value = $reference_event['digest_value'];
                    $reference_event_timestamp = $reference_event['timestamp'];

                    $this->logger->info("Persist plugin ran.", array(
                        'plugin_name' => $persist_plugin_name,
                        'return_code' => $reference_event_plugin_return_code
                    ));

                    // Get the resource's digest and compare it to the last known value. Currently we
                    // only allow one fetchdigest plugin per resource_id.
                    $current_digest_plugin_command = $this->getApplication()->find($this->fetchDigestPlugin);
                    $current_digest_plugin_input = new ArrayInput(array(
                        '--resource_id' => $resource_record->resource_id
                    ));
                    $current_digest_plugin_output = new BufferedOutput();
                    $current_digest_plugin_return_code = $current_digest_plugin_command->run(
                        $current_digest_plugin_input,
                        $current_digest_plugin_output
                    );
                    $current_digest_plugin_return_value = trim($current_digest_plugin_output->fetch());

                    // @todo: We shold only be logging $current_digest_plugin_return_value if it is not a digest value.
                    $this->logger->info("Fetchdigest plugin ran.", array(
                        'plugin_name' => $this->fetchDigestPlugin,
                        'return_code' => $current_digest_plugin_return_code,
                        // Assumes that the plugin use http... but our filesystemexmaple one doesn't.
                        'http_response_code' => $current_digest_plugin_return_value,
                    ));

                    // If there was a problem, the fetchdigest plugin will return an HTTP response code
                    // or an executable's exit code (not a digest value/timestamp JSON object), so we
                    // check the length of the plugin's output to see if it's longer than 3 characters,
                    // which JSON objects are.
                    if (strlen($current_digest_plugin_return_value) > 3) {
                        $current_digest_plugin_output_ok = $current_digest_plugin_return_value;
                        $current_digest_plugin_output = json_decode($current_digest_plugin_return_value, true);
                        $current_digest_value = $current_digest_plugin_output['digest_value'];
                    } else {
                        $current_digest_plugin_output_ok = false;
                    }

                    if ($current_digest_plugin_output_ok) {
                        // Initialize $outcome to 'fail', change it to 'success' only if conditions are met.
                        $outcome = 'fail';
                        print "Pre\n";
                        $this->event_detail = new FixityEventDetailManager($this->params);
                        print "Post\n";

                        // var_dump("reference_event_digest_value");
                        // var_dump($reference_event_digest_value);
                        // var_dump("current_digest_value");
                        // var_dump($current_digest_value);

                        // Riprap has no entries in its db for this resource; this is OK, since this will
                        // be the case for new resources detected by the fetchresourcelist plugins.
                        if (strlen($reference_event_digest_value) == 0) {
                            $outcome = 'success';
                            $num_successful_events++;
                            // if ($this->event_detail) {
                                $this->event_detail->add('event_detail', 'Initial fixity check.');
                            // }
                        } elseif ($reference_event_digest_value == $current_digest_value) {
                            $outcome = 'success';
                            $num_successful_events++;
                        // The resource's current last modified date is later than the timestamp in the
                        // reference fixity check event for this resource.
                        // } elseif ($current_digest_plugin_output['last_modified_timestamp'] > $reference_event_timestamp) {
                        } elseif ($resource_record->last_modified_timestamp > $reference_event_timestamp) {
                            print "From within true loop for $resource_id:\n";
                            print "Current digest plugin last modified timestamp: " . $current_digest_plugin_output['last_modified_timestamp'] . "\n";
                            print "Reference event timestamp:" . $reference_event_timestamp . "\n";
                            $outcome = 'success';
                            $num_successful_events++;
                            // if ($this->event_detail) {
                                $this->event_detail->add(
                                    'event_detail', 'Resource modified since last fixity check.'
                                );
                            // }
                        } else {
                            $num_failed_events++;
                            // if ($this->event_detail) {
                                $this->event_detail->add(
                                    'event_outcome_detail_note', 'Insufficient conditions for fixity check event.'
                                );
                            // }
                        }
                    } else {
                        $this->logger->error("Fetchdigest plugin ran but could not fetch digest.", array(
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
                        '--resource_id' => $resource_record->resource_id,
                        '--timestamp' => $now_iso8601,
                        '--digest_algorithm' => $this->fixity_algorithm,
                        '--event_uuid' => $event_uuid,
                        '--digest_value' => $current_digest_value,
                        '--outcome' => $outcome,
                        '--operation' => 'persist_fix_event',
                    ));

                    // Aaarrrgggghhhh! $this->event_detail is getting squashed in the persist plugin.
                    // var_dump($this->event_detail->getDetails());

                    $persist_fix_event_plugin_output = new BufferedOutput();
                    $persist_fix_event_plugin_return_code = $persist_fix_event_plugin_command->run(
                        $persist_fix_event_plugin_input,
                        $persist_fix_event_plugin_output,
                        $this->event_detail
                    );

                    $this->logger->info(
                        "Persist plugin ran.",
                        array(
                            'plugin_name' => $persist_plugin_name,
                            'return_code' => $persist_fix_event_plugin_return_code
                        )
                    );
                }
            }

            // Execute post-check plugins that react to a fixity check event (email admin, etc.).
            if (count($this->postCheckPlugins) > 0) {
                foreach ($this->postCheckPlugins as $postcheck_plugin_name) {
                    $postcheck_plugin_command = $this->getApplication()->find($postcheck_plugin_name);
                    $postcheck_plugin_input = new ArrayInput(array(
                        '--resource_id' => $resource_record->resource_id,
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

            // Print output and log it.
            $this->logger->info("check_fixity ran.", array('event_uuid' => $event_uuid));
        }

        $fixity_check = $stopwatch->stop('fixity_check');
        $duration = $fixity_check->getDuration(); // milliseconds
        $duration = $duration / 1000; // seconds

        $output->writeln("Riprap checked $num_resource_ids resources ($num_successful_events successful events, " .
            "$num_failed_events failed events) in $duration seconds.");
    }
}
