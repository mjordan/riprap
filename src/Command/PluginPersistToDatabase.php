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

class PluginPersistToDatabase extends ContainerAwareCommand
{
    private $params;

    public function __construct(ParameterBagInterface $params = null, LoggerInterface $logger = null)
    {
        $this->params = $params;

        // Set log output path in config/packages/{environment}/monolog.yaml
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:riprap:plugin:persist:to:database')
            ->setDescription('A Riprap plugin for persisting fixity events to a relational database.');

        // phpcs:disable
        $this
            ->addOption('timestamp', null, InputOption::VALUE_REQUIRED, 'ISO 8601 date when the fixity validation event occured.')
            ->addOption('resource_id', null, InputOption::VALUE_REQUIRED, 'Fully qualifid URL of the resource to validate.')
            ->addOption('event_uuid', null, InputOption::VALUE_REQUIRED, 'UUID of the fixity validation event.')
            ->addOption('digest_algorithm', null, InputOption::VALUE_REQUIRED, 'Algorithm used to generate the digest.')
            ->addOption('digest_value', null, InputOption::VALUE_REQUIRED, 'Value of the digest retrieved from the Fedora repository.')
            ->addOption('outcome', null, InputOption::VALUE_REQUIRED, 'Outcome of the event.')
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
                $output->write($event->getHashValue());
            }
        }
        // Returns a serialized representation of all fixity check events.
        // @todo: Add  offset and limit parameters.
        if ($input->getOption('operation') == 'get_events') {
            $repository = $this->getContainer()->get('doctrine')->getRepository(FixityCheckEvent::class);
            $events = $repository->findFixityCheckEvents(
                $input->getOption('resource_id')
            );
            if (count($events)) {
                $event_entries = array();
                foreach ($events as $event) {
                    $event_array = array();
                    $event_array['event_uuid'] = $event->getEventUuid();
                    $event_array['resource_id'] = $event->getResourceId();
                    $event_array['event_type'] = $event->getEventType();
                    $event_array['datestamp'] = $event->getDatestamp();
                    $event_array['hash_algorithm'] = $event->getHashAlgorithm();
                    $event_array['hash_value'] = $event->getHashValue();
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
            $event->setEventType('fix');
            $event->setResourceId($input->getOption('resource_id'));
            // @todo: Apparently PHP's DateTime class can't do valid ISO8601. The values end up
            // like 2018-09-20 08:44:29, without the ISO8601-specific formatting, even if the date
            // string value is valid 8601 (e.g. produced by date('c'). If we want ISO8601
            // dates for our fixity validation events, we'll need a workaround.
            $event->setDatestamp(\DateTime::createFromFormat(\DateTime::ISO8601, $input->getOption('timestamp')));
            $event->setHashAlgorithm($input->getOption('digest_algorithm'));
            $event->setHashValue($input->getOption('digest_value'));
            $event->setEventDetail('');
            $event->setEventOutcome($input->getOption('outcome'));
            $event->setEventOutcomeDetailNote('');
            $entityManager->persist($event);
            $entityManager->flush();
        }
    }
}
