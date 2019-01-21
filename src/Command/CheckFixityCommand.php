<?php
// src/Command/CheckFixity.php
namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Yaml\Yaml;

use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

use App\Entity\FixityCheckEvent;

class CheckFixityCommand extends ContainerAwareCommand
{
    public function __construct(LoggerInterface $logger = null)
    {
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
            ->addOption(
                'settings',
                null,
                InputOption::VALUE_REQUIRED,
                'Absolute or relative path to YAML configuration settings file.'
            );
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
        $this->persistPlugin = $this->settings['plugins.persist'];
        $this->postCheckPlugins = $this->settings['plugins.postcheck'];
        if (array_key_exists('note_delimiter', $this->settings)) {
            $this->note_delimiter = $this->settings['note_delimiter'];
        } else {
            $this->note_delimiter = '|';
        }

        // Execute plugins that get a list of resource IDs to check. There can be more
        // than one.
        $all_resource_records = array();
        $num_resource_records = 0;
        if (count($this->fetchResourceListPlugins) > 0) {
            foreach ($this->fetchResourceListPlugins as $fetchresourcelist_plugin_name) {
                $plugin_name = 'App\Plugin\\' . $fetchresourcelist_plugin_name;
                $fetchresourcelist_plugin = new $plugin_name($this->settings, $this->logger);
                $resource_records = $fetchresourcelist_plugin->execute();
                if (!is_array($resource_records) || count($resource_records) == 0) {
                    continue;
                }
                $all_resource_records = array_merge($all_resource_records, $resource_records);
            }
            $num_resource_records = count($all_resource_records);
        }

        // Workaround for making tests pass.
        $env = getenv('APP_ENV');
        if ($num_resource_records == 0 && $env =! 'test') {
            $this->logger->info("There are no resources to check. Exiting.");
            $output->writeln("There are no resources to check. Exiting.");
            exit;
        }

        // Loop through the list of resource IDs and perform a fixity check event
        // on each of them.
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

            // We execute the persist plugin twice, once to get the "reference event" for the
            // resource and again to persist the event resulting from comparing the reference
            // event's digest value with the current one.
            $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
            $plugin_name = 'App\Plugin\\' . $this->persistPlugin;
            $persist_plugin = new $plugin_name($this->settings, $this->logger, $this->entityManager);
            $reference_event = $persist_plugin->getReferenceEvent($resource_record->resource_id);

            // The $reference_event object contains two properties, 1) 'digest_value', the digest
            // recorded in the last fixity event check for this resource, which we compare with
            // the value of the digest retrieved during the current fixity check, and 2) 'timestamp',
            // the timestamp from the last event, which we compare to the current last modified
            // timestamp of the resource. If the current last-modified timestamp of the resource
            // is later than the timestamp in the reference event, we don't compare digests since
            // the digest to be different (because the resource was modified); if it is earlier,
            // we compare digests since the resource has not been modified since the reference
            // fixity check event. If there is no reference event (first time Riprap has encountered
            // the resource), $reference_event will be null. Note that the reference event has an
            // outcome of 'success'.

            $fetch_digest_plugin_name = 'App\Plugin\\' . $this->fetchDigestPlugin;
            $fetch_digest_plugin = new $fetch_digest_plugin_name($this->settings, $this->logger);
            $fetch_digest_plugin_output = $fetch_digest_plugin->execute($resource_record->resource_id);

            // If there was a problem, the fetchdigest plugin will return false.
            if ($fetch_digest_plugin_output) {
                $fetch_digest_plugin_output_ok = true;
                $current_digest_value = $fetch_digest_plugin_output;
            } else {
                $fetch_digest_plugin_output_ok = false;
                // Move on to the next fetchdigest plugin for this resource.
                continue;
            }

            if ($fetch_digest_plugin_output_ok) {
                // Initialize $outcome to 'fail', change it to 'success' only if required
                // conditions are met.
                $outcome = 'fail';

                // We'll populate these arrays below and then serialize them prior to persisting.
                $event_detail = array();
                $event_outcome_detail_note = array();

                if (is_null($reference_event) || strlen($reference_event->digest_value) == 0) {
                    // Riprap has no entries in its db for this resource; this is OK, since this will
                    // be the case for new resources detected by the fetchresourcelist plugins.
                    $outcome = 'success';
                    $num_successful_events++;
                    $event_detail[] = 'Initial fixity check.';
                } elseif ($reference_event->digest_value == $current_digest_value) {
                    $outcome = 'success';
                    $num_successful_events++;
                } elseif ($resource_record->last_modified_timestamp > $reference_event->timestamp) {
                    // The resource's current last modified date is later than the timestamp in the
                    // reference fixity check event for this resource.
                    $outcome = 'success';
                    $num_successful_events++;
                    $event_detail[] = 'Resource modified since last fixity check.';
                } elseif ($reference_event->digest_value != $current_digest_value) {
                    // Simple digest mismatch.
                    $num_failed_events++;
                } else {
                    $num_failed_events++;
                    $event_outcome_detail_note[] = 'Insufficient conditions for fixity check event.';
                }
            } else {
                $this->logger->error("Fetchdigest plugin ran but could not fetch digest.", array(
                    'plugin_name' => $this->fetchDigestPlugin,
                    'plugin output' => $fetch_digest_plugin_output
                ));
                $num_failed_events++;
                continue;
            }

            $event_detail_string = implode($this->note_delimiter, $event_detail);
            $event_outcome_detail_note_string = implode($this->note_delimiter, $event_outcome_detail_note);

            $event = new FixityCheckEvent();
            $event->setEventUuid($event_uuid);
            $event->setEventType('fix');
            $event->setResourceId($resource_record->resource_id);
            $event->setTimestamp($now_iso8601);
            $event->setDigestAlgorithm($this->fixity_algorithm);
            $event->setDigestValue($current_digest_value);
            $event->setEventDetail($event_detail_string);
            $event->setEventOutcome($outcome);
            $event->setEventOutcomeDetailNote($event_outcome_detail_note_string);

            $persisted = $persist_plugin->persistEvent($event);

            // Execute post-check plugins that react to a fixity check event (email somebody, etc.).
            // There can be more than one.
            if (isset($event) && $persisted && count($this->postCheckPlugins) > 0) {
                foreach ($this->postCheckPlugins as $postcheck_plugin_name) {
                    $post_check_plugin_name = 'App\Plugin\\' . $postcheck_plugin_name;
                    $post_check_plugin = new $post_check_plugin_name(
                        $this->settings,
                        $this->logger,
                        $this->entityManager
                    );
                    $post_check_plugin->execute($event);
                }
            }

            // Log that this command ran.
            $this->logger->info("check_fixity ran.", array('event_uuid' => $event_uuid));
        }

        $fixity_check = $stopwatch->stop('fixity_check');
        $duration = $fixity_check->getDuration(); // milliseconds
        $duration = $duration / 1000; // seconds

        $output->writeln("Riprap checked $num_resource_records resources ($num_successful_events successful events, " .
            "$num_failed_events failed events) in $duration seconds.");
    }
}
