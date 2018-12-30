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

class PluginPostCheckMailFailures extends ContainerAwareCommand
{
    private $params;

    public function __construct(ParameterBagInterface $params = null, LoggerInterface $logger = null)
    {
        $this->params = $params;
        $this->email_from = $this->params->get('app.plugins.postcheck.mailfailures.from');
        $this->email_to = $this->params->get('app.plugins.postcheck.mailfailures.to');

        // Set log output path in config/packages/{environment}/monolog.yaml
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:riprap:plugin:postcheck:mailfailures')
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
        if ($input->getOption('outcome') == 'fail') {
            $resource_id = $input->getOption('resource_id');
            $timestamp = $input->getOption('timestamp');
            $mail_command = $this->getApplication()->find('swiftmailer:email:send');
            $input = new ArrayInput(array(
                '--from' => $this->email_from,
                '--to' => $this->email_to,
                '--subject' => "Fixity validation failure on " . $resource_id,
                '--body' => "Riprap has detected a fixity validation failure on " .
                    $resource_id .
                    " (event UUID " .
                    $input->getOption('event_uuid') .
                    "), which occured at " .
                    $timestamp,
            ));
            $returnCode = $mail_command->run($input, $output);
            $this->logger->info("Mail Failure plugin generated a message", array(
                'recipient' => $this->email_to,
                'resource ID' => $resource_id,
                'timestamp' => $timestamp,
            ));
        }
    }
}
