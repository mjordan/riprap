<?php
// resources/filesystemexample/src/Command/PluginFetchDigestFromShell.php
namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

use App\Entity\Event;

class PluginFetchDigestFromShell extends ContainerAwareCommand
{
    private $params;

    public function __construct(ParameterBagInterface $params = null, LoggerInterface $logger = null)
    {
        $this->params = $params;
        if ($this->params->has('app.plugins.fetchdigrest.from.shell.command')) {
            $this->external_program = $this->params->get('app.plugins.fetchdigrest.from.shell.command');
        }

        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:riprap:plugin:fetchdigest:from:shell')
            ->setDescription('An example Riprap plugin for getting a digest from a shell command.');

        $this
            ->addOption(
                'resource_id',
                null,
                InputOption::VALUE_REQUIRED,
                'Absolute filesystem path of the resource to validate.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file_path = $input->getOption('resource_id');
        $external_program_command = $this->external_program . ' ' . $file_path;
        $external_program_command = escapeshellcmd($external_program_command);
        $command_output = exec($external_program_command, $external_program_command_output, $return);
        if ($return == 0) {
            list($digest, $path) = preg_split('/\s/', $external_program_command_output[0]);
            $output->writeln(trim($digest));
        } else {
            $this->logger->warning("check_fixity cannot retrieve digest from repository.", array(
                'resource_id' => $file_path,
                'status_code' => $return,
            ));
            $output->writeln($return);
        }
    }
}
