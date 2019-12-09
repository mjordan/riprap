<?php
// src/Command/GetEventsCommand.php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

use Psr\Log\LoggerInterface;
use \League\Csv\Writer;

// Note: Until we figure out how to define which persist plugin to use in this controller via
// a single configuration shared between it and the console command, we are limited to using
// the PluginPersistToDatabase plugin. We could revert to using services.yaml, at least to
// register persist plugins for this controller, but then we'd have two places to register
// configuration info.
use App\Plugin\PluginPersistToDatabase;

class GetEventsCommand extends Command
{
    public function __construct(EntityManagerInterface $entityManager = null, LoggerInterface $logger = null)
    {
        // Set log output path in config/packages/{environment}/monolog.yaml
        $this->logger = $logger;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:riprap:get_events')
            ->setDescription('Console tool for getting fixity events from Riprap.')
            ->addOption(
                'resource_id',
                null,
                InputOption::VALUE_REQUIRED,
                'Resource identifier.'
            )
            ->addOption(
                'output_format',
                'json',
                InputOption::VALUE_REQUIRED,
                'Output format. Defaults to "json". Must be either "csv" or "json".'
            )
            ->addOption(
                'timestamp_start',
                null,
                InputOption::VALUE_REQUIRED,
                'ISO8601 (full or partial) date indicating start of date range in query.'
            )
            ->addOption(
                'timestamp_end',
                null,
                InputOption::VALUE_REQUIRED,
                'ISO8601 (full or partial) date indicating end of date range in query.'
            )
            ->addOption(
                'timestamp_start',
                null,
                InputOption::VALUE_REQUIRED,
                'ISO8601 (full or partial) date indicating start of date range in query.'
            )
            ->addOption(
                'outcome',
                null,
                InputOption::VALUE_REQUIRED,
                'Coded outcome of the event, either "success" or "fail". If no outcome is ' .
                    'specified, all events are returned in the response.'
            )
            ->addOption(
                'offset',
                0,
                InputOption::VALUE_REQUIRED,
                'The number of items in the result set, starting at the beginning, ' .
                    'that are skipped in the result set (i.e., same as standard SQL ' .
                    'use of "offset"). Default is 0.'
            )
            ->addOption(
                'limit',
                0,
                InputOption::VALUE_REQUIRED,
                'Number of items in the result set to return, starting at the value of "offset".'
            )
            ->addOption(
                'sort',
                'asc',
                InputOption::VALUE_REQUIRED,
                'Sort events on timestamp. Specify "desc" or "asc". Default is "asc".'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if (is_null($input->getOption('resource_id'))) {
            $output->writeln("You must specify a resource ID using the --resource_id option.");
            exit(1);
        }

        $resource_id = $input->getOption('resource_id');
        $timestamp_start = null;
        $timestamp_end = null;
        $outcome = null;
        // Set default to 'asc' if not in request.
        $sort = null;
        // This typecasting makes the default value of $limit and $offset to be 0.
        $limit = 5;
        $offset = 0;

        // See comment above about hard-coded persist plugin.
        $this->persist_plugin = new PluginPersistToDatabase(array(), $this->logger, $this->entityManager);
        $events_for_resource = $this->persist_plugin->getEvents(
            $resource_id,
            $outcome,
            $timestamp_start,
            $timestamp_end,
            $limit,
            $offset,
            $sort
        );

        $output_format = $input->getOption('output_format');
        if (!in_array($output_format, ['json', 'csv'])) {
            $output_format = 'json';
        }
        if ($output_format == 'json') {
            if (count($events_for_resource) > 0) {
                $output->writeln(json_encode($events_for_resource));
            } else {
                // Output an empty array.
                $output->writeln(json_encode([]));
            }
        }
        if ($output_format == 'csv') {
            if (count($events_for_resource) > 0) {
                $header = array_keys($events_for_resource[0]);
                $csv = Writer::createFromString('');
                $csv->insertOne($header);
                foreach ($events_for_resource as $record) {
                    $csv->insertOne($record);
                }
                $csv_string = $csv->getContent();
                $output->writeln($csv_string);
            } else {
                // Output a string.
                $output->writeln('"No events found."');
            }
        }
    }
}
