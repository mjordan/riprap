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
use App\Service\FixityEventDetailManager;

class PluginFetchResourceListFromFile extends ContainerAwareCommand
{
    private $params;

    public function __construct(
        ParameterBagInterface $params = null,
        LoggerInterface $logger = null,
        FixityEventDetailManager $event_detail = null
    ) {
        $this->params = $params;
        if ($this->params->has('app.plugins.fetchresourcelist.from.file.paths')) {
            $this->input_files = $this->params->get('app.plugins.fetchresourcelist.from.file.paths');
        }

        $this->logger = $logger;
        $this->event_detail = $event_detail;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:riprap:plugin:fetchresourcelist:from:file')
            ->setDescription('A Riprap plugin for reading a list of resource URLs from a file, one URL per line.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->input_files as $input_file) {
            $resource_ids = file($input_file, FILE_IGNORE_NEW_LINES);
            foreach ($resource_ids as $resource_id) {
                // This is a string containing one resource ID (URL) per line;
                $output->writeln($resource_id);
            }
        }

        // $this->logger is null while testing.
        if ($this->logger) {
            $this->logger->info("PluginPersistToDatabase executed");
        }
    }
}
