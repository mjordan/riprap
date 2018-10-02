<?php
// resources/filesystemexample/src/Command/PluginFetchResourceListFromGlob.php
namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use Psr\Log\LoggerInterface;

use App\Entity\Event;

class PluginFetchResourceListFromGlob extends ContainerAwareCommand
{
    private $params;

    public function __construct(ParameterBagInterface $params = null, LoggerInterface $logger = null)
    {
        $this->params = $params;
        if ($this->params->has('app.plugins.fetchresourcelist.from.glob.file_directory')) {
            $this->file_directory = $this->params->get('app.plugins.fetchresourcelist.from.glob.file_directory');
        }

        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:riprap:plugin:fetchresourcelist:from:glob')
            ->setDescription('An example Riprap plugin for reading the contents of a directory.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file_paths = glob($this->file_directory . '/*.bin');
        foreach ($file_paths as $resource_id) {
            $output->writeln($resource_id);
        }
    }
}
