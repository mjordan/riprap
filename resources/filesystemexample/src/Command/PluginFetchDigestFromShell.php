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
        $this->external_program = $this->params->get('app.plugins.fetchdigrest.from.shell.command'); // /usr/bin/sha1sum

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
        /*
        $client = new \GuzzleHttp\Client();
        $url = $input->getOption('resource_id');

        $response = $client->request($this->http_method, $url, [
            'http_errors' => false,
            'headers' => ['Want-Digest' => $this->fixity_algorithm],
        ]);
        $status_code = $response->getStatusCode();
        $allowed_codes = array(200);
        if (in_array($status_code, $allowed_codes)) {
            $digest_header_values = $response->getHeader('digest');
            // Assumes there is only one 'digiest' header - is this always the case?
            $output->writeln($digest_header_values[0]);
        } else {
            // If the HTTP status code is not in the allowed list, log it.
            $this->logger->warning("check_fixity cannot retrieve digest from repository.", array(
                'resource_id => $url',
                'status_code' => $status_code,
            ));
            $output->writeln($status_code);
        }
        */
    }
}
