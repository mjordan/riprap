<?php
// src/Command/PluginPostCheckMailFailures.php
namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Console\Input\ArrayInput;

use Psr\Log\LoggerInterface;

use App\Entity\Event;

class PluginPostCheckSayHello extends ContainerAwareCommand
{
    private $params;

    public function __construct(ParameterBagInterface $params = null, LoggerInterface $logger = null)
    {
        $this->params = $params;

        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:riprap:plugin:postcheck:sayhi')
            ->setDescription('A Riprap plugin that emails notifications of fixity check failures.');

        // phpcs:disable
        $this
            ->addOption('timestamp', null, InputOption::VALUE_REQUIRED, 'ISO 8601 date when the fixity validation event occured.')
            ->addOption('resource_id', null, InputOption::VALUE_REQUIRED, 'Fully qualifid URL of the resource to check.')
            ->addOption('event_uuid', null, InputOption::VALUE_REQUIRED, 'UUID of the fixity validation event.')
            ->addOption('digest_algorithm', null, InputOption::VALUE_REQUIRED, 'Algorithm used to generate the digest.')
            ->addOption('digest_value', null, InputOption::VALUE_REQUIRED, 'Value of the digest retrieved from the Fedora repository.')
            ->addOption('outcome', null, InputOption::VALUE_REQUIRED, 'Outcome of the event.');
        // phpcs:enable
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('outcome') == 'success') {
            $resource_id = $input->getOption('resource_id');
            $timestamp = $input->getOption('timestamp');
            // Postcheck plugins typically don't write output to STDOUT, since `check_fixity` is not expecting
            // any ouput. This category of plugin probably should log data instead.
            if ($this->logger) {
                $this->logger->info("Resource $resource_id says Hello!");
            }
        }
    }
}
