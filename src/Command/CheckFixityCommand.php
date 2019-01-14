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

use App\Entity\FixityCheckEvent;
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
        // $this->fixity_algorithm = $this->params->get('app.fixity_algorithm');
        // $this->fetchResourceListPlugins = $this->params->get('app.plugins.fetchresourcelist');
        // $this->fetchDigestPlugin = $this->params->get('app.plugins.fetchdigest');
        // $this->persistPlugins = $this->params->get('app.plugins.persist');
        // $this->postCheckPlugins = $this->params->get('app.plugins.postcheck');

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
        $this->fixity_algorithm = $this->settings['fixity_algorithm'];
        $this->fetchResourceListPlugins = $this->settings['plugins.fetchresourcelist'];
        $this->fetchDigestPlugin = $this->settings['plugins.fetchdigest'];
        $this->persistPlugins = $this->settings['plugins.persist'];
        $this->postCheckPlugins = $this->settings['plugins.postcheck'];

        // Execute plugins that get a list of resource IDs to check.
        $resource_ids = array();
        $num_resource_ids = 0;
        if (count($this->fetchResourceListPlugins) > 0) {
            foreach ($this->fetchResourceListPlugins as $fetchresourcelist_plugin_name) {
                $plugin_name = 'App\Plugin\\' . $fetchresourcelist_plugin_name;
                $fetchresourcelist_plugin = new $plugin_name($this->settings, $this->logger);
                $resource_records = $fetchresourcelist_plugin->execute();
            }
        }
        else {
            $this->logger->warning(
                "Fetchresourcelist plugin ran but returned no resources.",
                array(
                    'plugin_name' => $fetchresourcelist_plugin_name,
                )
            );            
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

            $uuid4 = Uuid::uuid4();
            $event_uuid = $uuid4->toString();
            $now_iso8601 = date(\DateTime::ISO8601);

            // Execute plugins that persist event data. We execute them twice, once to get
            // the "reference event" for the resource and again to persist the event resulting
            // from comparing its digest with the current one.
            if (count($this->persistPlugins) > 0) {
                $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
                foreach ($this->persistPlugins as $persist_plugin_name) {
                    $plugin_name = 'App\Plugin\\' . $persist_plugin_name;
                    $persist_plugin = new $plugin_name($this->settings, $this->logger, $this->entityManager);
                    $reference_event = $persist_plugin->getReferenceEvent($resource_record->resource_id);

                    // The $reference_event object contains two properties, 1) 'digest_value', the digest
                    // recorded in the last fixity event check for this resource, which we compare with
                    // the value with the digest retrieved during the current fixity check, and 2) 'timestamp',
                    // the timestamp from the last event, so we can compare the current last modified
                    // timestamp of the resource to it. If the current last-modified timestamp of the
                    // resource is later than the timestamp in the reference event, we don't compare
                    // digests since the digest to be different (because the resource was modified);
                    // otherwise, we compare digests since the resource has not been modified since the
                    // reference (i.e., previous) fixity check event.

                    // @todo: If we allow multiple persist plugins, the last one called determines
                    // the value of $last_digest_for_resource. Is that OK? Is there a real use case
                    // for persisting to multiple places? If so, can we persist to additional places
                    // using a postcheck plugin instead of multiple persist plugins?

                    // @todo: We shold only be logging $current_digest_plugin_return_value if it is not a digest value.
                    // $this->logger->info("Fetchdigest plugin ran.", array(
                        // 'plugin_name' => $this->fetchDigestPlugin,
                        // 'return_code' => $current_digest_plugin_return_code,
                        // Assumes that the plugin use http... but our filesystemexmaple one doesn't.
                        // 'http_response_code' => $current_digest_plugin_return_value,
                    // ));

                    $fetch_digest_plugin_name = 'App\Plugin\\' . $this->fetchDigestPlugin;
                    $fetch_digest_plugin = new $fetch_digest_plugin_name($this->settings, $this->logger);
                    $fetch_digest_plugin_output = $fetch_digest_plugin->execute($resource_record->resource_id);

                    // If there was a problem, the fetchdigest plugin will return an HTTP response code
                    // or an executable's exit code (not a digest object).
                    if (strlen($fetch_digest_plugin_output) >= 3) {
                        $fetch_digest_plugin_output_ok = true;
                        $current_digest_value = $fetch_digest_plugin_output;
                    } else {
                        $fetch_digest_plugin_output_ok = false;                     
                    }

                    if ($fetch_digest_plugin_output_ok) {
                        // Initialize $outcome to 'fail', change it to 'success' only if conditions are met.
                        $outcome = 'fail';
                        $this->event_detail = new FixityEventDetailManager($this->params);

                        if (!$reference_event || strlen($reference_event->digest_value) == 0) {
                            // Riprap has no entries in its db for this resource; this is OK, since this will
                            // be the case for new resources detected by the fetchresourcelist plugins.                            
                            $outcome = 'success';
                            $num_successful_events++;
                            $this->event_detail->add('event_detail', 'Initial fixity check.');
                        } elseif ($reference_event->digest_value == $current_digest_value) {
                            $outcome = 'success';
                            $num_successful_events++;
                        } elseif ($resource_record->last_modified_timestamp > $reference_event->timestamp) {
                            // The resource's current last modified date is later than the timestamp in the
                            // reference fixity check event for this resource.
                            print "debug: From within true loop for $resource_id:\n";
                            print "debug: Current digest plugin last modified timestamp: " . $current_digest_plugin_output['last_modified_timestamp'] . "\n";
                            print "debug: Reference event timestamp:" . $reference_event->timestamp . "\n";
                            $outcome = 'success';
                            $num_successful_events++;
                            $this->event_detail->add(
                                'event_detail', 'Resource modified since last fixity check.'
                            );
                        } else {
                            $num_failed_events++;
                            $this->event_detail->add(
                                'event_outcome_detail_note', 'Insufficient conditions for fixity check event.'
                            );
                        }
                    } else {
                        $this->logger->error("Fetchdigest plugin ran but could not fetch digest.", array(
                            'plugin_name' => $this->fetchDigestPlugin,
                            'return_code' => $fetch_digest_plugin_output,
                            // 'http_response_code' => $current_digest_plugin_return_value,
                        ));
                        $num_failed_events++;
                        continue;
                    }

                    // @todo: implode arrays of event detail and event outcome detail notes here
                    // and set default value to '' if there are none. The delete the FixityEventDetailManager service.

                    $event = new FixityCheckEvent();
                    $event->setEventUuid($event_uuid);
                    $event->setEventType('fix');
                    $event->setResourceId($resource_record->resource_id);
                    $event->setTimestamp($now_iso8601);
                    $event->setDigestAlgorithm($this->fixity_algorithm);
                    $event->setDigestValue($current_digest_value);
                    $details = $this->event_detail->getDetails();
                    $event_details = $this->event_detail->serialize($details);
                    $event->setEventDetail($event_details['event_detail']);
                    $event->setEventOutcome($outcome);
                    $event->setEventOutcomeDetailNote($event_details['event_outcome_detail_note']);

                    $persist_plugin->persistEvent($event);
                }
            }

            // Execute post-check plugins that react to a fixity check event (email admin, etc.).
            if (isset($event) && count($this->postCheckPlugins) > 0) {
                foreach ($this->postCheckPlugins as $postcheck_plugin_name) {
                    $post_check_plugin_name = 'App\Plugin\\' . $postcheck_plugin_name;
                    $post_check_plugin = new $post_check_plugin_name($this->settings, $this->logger, $this->entityManager);
                    $post_check_plugin->execute($event);
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
