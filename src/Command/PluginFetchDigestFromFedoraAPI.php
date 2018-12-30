<?php
// src/Command/PersistPluginDatabase.php
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
use App\Service\FixityEventDetailManager;

class PluginFetchDigestFromFedoraAPI extends ContainerAwareCommand
{
    private $params;

    public function __construct(
        ParameterBagInterface $params = null,
        LoggerInterface $logger = null,
        FixityEventDetailManager $event_detail = null
    ) {
        $this->params = $params;
        $this->http_method = $this->params->get('app.fixity.fetchdigest.from.fedoraapi.method');
        $this->fixity_algorithm = $this->params->get('app.fixity.fetchdigest.from.fedoraapi.algorithm');
        $this->hash_leader_pattern = $this->params->get('app.fixity.fetchdigest.from.fedoraapi.digest_header.leader_pattern');

        $this->logger = $logger;
        $this->event_detail = $event_detail;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:riprap:plugin:fetchdigest:from:fedoraapi')
            ->setDescription('A Riprap plugin for querying a Fedora API Specification compliant ' .
                'repository for a resource\'s digest.');

        $this
            ->addOption(
                'resource_id',
                null,
                InputOption::VALUE_REQUIRED,
                'Fully qualifid URL of the resource to validate.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = new \GuzzleHttp\Client();
        // @todo: Wrap in try/catch.
        $url = $input->getOption('resource_id');
        if (!strlen($url)) {
            if ($this->logger) {
                $this->logger->info("PluginFetchDigestFromFedoraAPI exited due to empty resource ID.");
            }
            return;
        }

        $response = $client->request($this->http_method, $url, [
            'http_errors' => false,
            'headers' => ['Want-Digest' => $this->fixity_algorithm],
        ]);
        $status_code = $response->getStatusCode();
        $allowed_codes = array(200);
        if (in_array($status_code, $allowed_codes)) {
            $digest_header_values = $response->getHeader('digest');
            // Digest header looks like Digest: sha-256=cef971b6697fa92c7125a329437b69f9161c2472cce873a229a329d1424a4ff1,
            // so we need to remove the 'sha-256=' leader.
            $digest_header_value = preg_replace('/' . $this->hash_leader_pattern . '/', '', $digest_header_values[0]);
            // Assumes there is only one 'digest' header - is this always the case?
            $output->writeln($digest_header_value);
        } else {
            // If the HTTP status code is not in the allowed list, log it.
            $this->logger->warning("check_fixity cannot retrieve digest from repository.", array(
                'resource_id' => $url,
                'status_code' => $status_code,
            ));
            $output->writeln($status_code);
        }

        if ($this->event_detail) {
            $this->event_detail->add('event_outcome_detail_note', '');
        }

        // $this->logger is null while testing.
        if ($this->logger) {
            $this->logger->info("PluginFetchDigestFromFedoraAPI executed");
        }
    }
}
