<?php
// src/Command/PersistPluginDatabase.php
namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use Psr\Log\LoggerInterface;

use App\Entity\Event;

class PluginPersistToDatabase extends ContainerAwareCommand
{
    private $params;

    public function __construct(ParameterBagInterface $params, LoggerInterface $logger)
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

        $this
            ->addOption('timestamp', null, InputOption::VALUE_REQUIRED, 'ISO 8601 date when the fixity validation event occured.')
            ->addOption('resource_id', null, InputOption::VALUE_REQUIRED, 'Fully qualifid URL of the resource to validate.')
            ->addOption('event_uuid', null, InputOption::VALUE_REQUIRED, 'UUID of the fixity validation event.')
            ->addOption('digest_value', null, InputOption::VALUE_REQUIRED, 'Value of the digest retrieved from the Fedora repository.')
            ->addOption('outcome', null, InputOption::VALUE_REQUIRED, 'Outcome of the event.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $entityManager = $this->getContainer()->get('doctrine')->getEntityManager();
        $event = new Event();
        $event->setEventUuid($input->getOption('event_uuid'));
        $event->setEventType('verification');
        $event->setResourceId($input->getOption('resource_id'));
        // @todo: Apparently PHP's DateTime class can't do valid ISO8601. The values end up
        // like 2018-09-20 08:44:29, without the ISO8601-specific formatting, even if the date
        // string value is valid 8601 (e.g. produced by date('c'). If we want ISO8601
        // dates for our fixity validation events, we'll need a workaround.
        $event->setDatestamp(\DateTime::createFromFormat(\DateTime::ISO8601, $input->getOption('timestamp')));
        $event->setHashAlgorithm('SHA-1');
        $event->setHashValue($input->getOption('digest_value'));
        $event->setEventOutcome($input->getOption('outcome'));
        $entityManager->persist($event);
        $entityManager->flush();

        $output->writeln("PluginPersistToDatabase executed");

        $this->logger->info("PluginPersistToDatabase executed");
    }
}