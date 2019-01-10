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
        var_dump("From fetchdigest plugin");
        var_dump($file_path);        
        $external_digest_program_command = $this->external_program . ' ' . $file_path;
        $external_digest_program_command = escapeshellcmd($external_digest_program_command);
        $external_digest_command_output = exec($external_digest_program_command, $external_digest_program_command_output, $return);
        if ($return == 0) {
            list($digest, $path) = preg_split('/\s/', $external_digest_program_command_output[0]);

            $mtime = exec('stat -c %Y '. escapeshellarg($file_path));
            $mtime_iso8601 = date(\DateTime::ISO8601, $mtime);
            // var_dump("Mtime: " . $mtime_iso8601);

            $digest_value_and_timestamp_array = array(
                'digest_value' => trim($digest),
                'last_modified_timestamp' => $mtime_iso8601
            );
            var_dump($digest_value_and_timestamp_array);                
            $digest_value_and_timestamp = json_encode($digest_value_and_timestamp_array);           
            $output->writeln(trim($digest_value_and_timestamp));
        } else {
            $this->logger->warning("check_fixity cannot retrieve digest from file system.", array(
                'resource_id' => $file_path,
                'status_code' => $return,
            ));
            $output->writeln($return);
        }
    }
}
