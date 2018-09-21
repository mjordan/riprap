<?php
// src/Command/PluginPostValidateMailFailures.php
namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use Psr\Log\LoggerInterface;

use App\Entity\Event;

class PluginPostValidateMailFailures extends ContainerAwareCommand
{
    private $params;

    public function __construct(ParameterBagInterface $params, LoggerInterface $logger)
    {
        $this->params = $params;
        $this->email_from = $this->params->get('app.plugins.postvalidate.mailfailures.from');
        $this->email_to = $this->params->get('app.plugins.postvalidate.mailfailures.to');

        // Set log output path in config/packages/{environment}/monolog.yaml
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:riprap:plugin:postvalidate:mailfailures')
            ->setDescription('A Riprap plugin that emails notifications of fixity validation failures.');

        $this
            ->addOption('timestamp', null, InputOption::VALUE_REQUIRED, 'ISO 8601 date when the fixity validation event occured.')
            ->addOption('resource_id', null, InputOption::VALUE_REQUIRED, 'Fully qualifid URL of the resource to validate.')
            ->addOption('event_uuid', null, InputOption::VALUE_REQUIRED, 'UUID of the fixity validation event.')
            ->addOption('digest_value', null, InputOption::VALUE_REQUIRED, 'Value of the digest retrieved from the Fedora repository.')
            ->addOption('outcome', null, InputOption::VALUE_REQUIRED, 'Outcome of the event.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('outcome') == 'failure') {
            $mail_command = $this->getApplication()->find('swiftmailer:email:send');
            $input = new ArrayInput(array(
                '--from' => $this->email_from,
                '--to' => $this->email_to,
                '--subject' => "Fixity validation failure on " . $input->getOption('resource_id'),
                '--body' => "Riprap has detected a fixity validation failure on " . 
                    $input->getOption('resource_id') .
                    " (event UUID " .
                    $input->getOption('event_uuid') .
                    "), which occured at " . 
                    $input->getOption('timestamp'),
            ));
            $returnCode = $plugin_command->run($input, $output);
            $this->logger->info("Mail Failure plugin generated a message", array(
                'recipient' => $this->email_to,
                'resource ID' => $input->getOption('resource_id'),
                'timestamp' => $input->getOption('timestamp')
            ));
        }
    }
}