<?php
// src/Command/PersistPluginDatabase.php
namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use Psr\Log\LoggerInterface;

use App\Entity\FixityCheckEvent;
use App\Service\FixityEventDetailManager;

class PluginPersistToDatabase extends ContainerAwareCommand
{
    private $params;

    public function __construct(
        ParameterBagInterface $params = null,
        LoggerInterface $logger = null,
        FixityEventDetailManager $event_detail = null
    ) {
        $this->params = $params;
        $this->event_type = $this->params->get('app.fixity.eventtype.code');

        // Set log output path in config/packages/{environment}/monolog.yaml
        $this->logger = $logger;
        $this->event_detail = $event_detail;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:riprap:plugin:persist:to:database')
            ->setDescription('A Riprap plugin for persisting fixity events to a relational database.');

        // phpcs:disable
        $this
            ->addOption('timestamp', null, InputOption::VALUE_REQUIRED, 'ISO 8601 date when the fixity check event occured.')
            ->addOption('timestamp_start', null, InputOption::VALUE_OPTIONAL, 'ISO8601 date indicating start of date range in queries.', null)
            ->addOption('timestamp_end', null, InputOption::VALUE_OPTIONAL, 'ISO8601 date indicating end of date range in queries.', null)
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Number of items in the result set to return, starting at the value of "offset".', null)
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'The number of items in the result set, starting at the beginning, that are skipped in the result set (i.e., same as standard SQL use of "offset"). Default is 0.', null)
            ->addOption('sort', null, InputOption::VALUE_OPTIONAL, 'Sort events on timestamp. Specify "desc" or "asc" (if not present, will sort "asc").', 'asc')
            ->addOption('resource_id', null, InputOption::VALUE_OPTIONAL, 'Fully qualifid URL of the resource to validate.')
            ->addOption('event_uuid', null, InputOption::VALUE_REQUIRED, 'UUID of the fixity check event.')          
            ->addOption('digest_algorithm', null, InputOption::VALUE_REQUIRED, 'Algorithm used to generate the digest.')
            ->addOption('digest_value', null, InputOption::VALUE_REQUIRED, 'Value of the digest retrieved from the Fedora repository.')
            ->addOption('outcome', null, InputOption::VALUE_OPTIONAL, 'Coded outcome of the event.', null)
            // Persist plugins are special in that they are executed twice, once to get the last digest for the resource
            // and again to persist the event resulting from comparing that digest with a new one.
            ->addOption('operation', null, InputOption::VALUE_REQUIRED, 'One of "get_last_digest", "get_events", or "persist_new_event".');
        // phpcs:enable           
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('operation') == 'get_last_digest') {
            $repository = $this->getContainer()->get('doctrine')->getRepository(FixityCheckEvent::class);
            $event = $repository->findLastFixityCheckEvent(
                $input->getOption('resource_id'),
                $input->getOption('digest_algorithm')
            );
            if (!is_null($event)) {
                $output->write($event->getDigestValue());
                // !!! #26: We also need to add $event->getTimestamp() to $output->write(), maybe as a JSON object? !!!
            }
        }
        // Returns a serialized representation of all fixity check events.
        // @todo: Add  offset and limit parameters.
        if ($input->getOption('operation') == 'get_events') {
            $repository = $this->getContainer()->get('doctrine')->getRepository(FixityCheckEvent::class);

            // If these request query parameters are not present, they are NULL.
            if (!is_null($input->getOption('resource_id')) ||
                !is_null($input->getOption('timestamp_start')) ||
                !is_null($input->getOption('timestamp_end')) ||
                !is_null($input->getOption('outcome')) ||
                !is_null($input->getOption('sort')) ||
                !is_null($input->getOption('offset')) ||
                !is_null($input->getOption('limit'))) {
                $events = $repository->findFixityCheckEventsWithParams(
                    $input->getOption('resource_id'),
                    $input->getOption('timestamp_start'),
                    $input->getOption('timestamp_end'),
                    $input->getOption('outcome'),
                    $input->getOption('offset'),
                    $input->getOption('limit'),
                    $input->getOption('sort')
                );
            } else {
                // No request query parameters are present.
                $events = $repository->findFixityCheckEvents(
                    $input->getOption('resource_id')
                );
            }

            $event_entries = array();
            if (count($events)) {
                foreach ($events as $event) {
                    $event_array = array();
                    $event_array['event_uuid'] = $event->getEventUuid();
                    $event_array['resource_id'] = $event->getResourceId();
                    $event_array['event_type'] = $event->getEventType();
                    $event_array['timestamp'] = $event->getTimestamp();
                    $event_array['digest_algorithm'] = $event->getDigestAlgorithm();
                    $event_array['digest_value'] = $event->getDigestValue();
                    $event_array['event_detail'] = $event->getEventDetail();
                    $event_array['event_outcome'] = $event->getEventOutcome();
                    $event_array['event_outcome_detail_note'] = $event->getEventOutcomeDetailNote();
                    $event_entries[] = $event_array;
                }
            }
            // $output requires a string.
            $output->write(serialize($event_entries));
        }
        if ($input->getOption('operation') == 'persist_fix_event') {
            $entityManager = $this->getContainer()->get('doctrine')->getEntityManager();
            $event = new FixityCheckEvent();
            $event->setEventUuid($input->getOption('event_uuid'));
            $event->setEventType($this->event_type);
            $event->setResourceId($input->getOption('resource_id'));
            $event->setTimestamp($input->getOption('timestamp'));
            $event->setDigestAlgorithm($input->getOption('digest_algorithm'));
            $event->setDigestValue($input->getOption('digest_value'));

            $details = $this->event_detail->getDetails();
            $event_details = $this->event_detail->serialize($details);
            $event->setEventDetail($event_details['event_detail']);
            $event->setEventOutcome($input->getOption('outcome'));
            $event->setEventOutcomeDetailNote($event_details['event_outcome_detail_note']);

            $entityManager->persist($event);
            $entityManager->flush();
        }
    }
}
