<?php
// src/Command/PluginFetchResourceListFromDrupal.php
namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use Psr\Log\LoggerInterface;

use App\Entity\Event;
use App\Service\FixityEventDetailManager;

class PluginFetchResourceListFromDrupal extends ContainerAwareCommand
{
    private $params;

    public function __construct(
        ParameterBagInterface $params = null,
        LoggerInterface $logger = null,
        FixityEventDetailManager $event_detail = null
    ) {
        $this->params = $params;
        $this->input_files = $this->params->get('app.plugins.fetchresourcelist.from.drupal.baseurl');

        $this->logger = $logger;
        $this->event_detail = $event_detail;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:riprap:plugin:fetchresourcelist:from:drupal')
            ->setDescription("A Riprap plugin for reading a list of resource URLs from Drupal's JSON:API. To use this plugin, that contrib module (https://www.drupal.org/project/jsonapi) needs to be installed on the source Drupal instance.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // @todo: See https://github.com/mjordan/riprap/issues/14.

/*
        foreach ($this->input_files as $input_file) {
            $resource_ids = file($input_file, FILE_IGNORE_NEW_LINES);
            foreach ($resource_ids as $resource_id) {
                // This is a string containing one resource ID (URL) per line;
                $output->writeln($resource_id);
            }
        }
*/


        // $this->logger is null while testing.
        if ($this->logger) {
            $this->logger->info("PluginPersistToDatabase executed");
        }
    }
}
