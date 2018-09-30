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
        $this->file_directory = $this->params->get('app.plugins.fetchresourcelist.from.glob.file_directory');

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
        // @todo: glob() the directory at $this->file_directory and return the resulting array.
        // $filepaths = glob($this->file_directory . '/*.bin');

        /*
        foreach ($this->input_files as $input_file) {
            $resource_ids = file($input_file, FILE_IGNORE_NEW_LINES);
            foreach ($resource_ids as $resource_id) {
                // This is a string containing one resource ID (URL) per line;
                $output->writeln($resource_id);
            }
        }
        */
    }
}
