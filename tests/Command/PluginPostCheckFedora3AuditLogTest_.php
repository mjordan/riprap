<?php

namespace App\Tests\Command;

use App\Command\PluginPostCheckMigrateFedora3AuditLog;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class PluginPostCheckMigrateFedora3AuditLogTest_ extends KernelTestCase
{
    public $params;

    public function testExecute()
    {
        $params = new ParameterBag(array());
        $this->params = $params;

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new PluginPostCheckMigrateFedora3AuditLog());

        $command = $application->find('app:riprap:plugin:postcheck:migratefedora3auditlog');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
        ));

        // Note that in production, we would be using only the AUDIT datastream,
        // not the entire FOXML. See https://github.com/Islandora-CLAW/CLAW/issues/917.
        $xml = simplexml_load_file('resources/foxml.xml');
        $xml->registerXPathNamespace('audit', 'info:fedora/fedora-system:def/audit#');
        $records = $xml->xpath('//audit:record');

        $fixity_events = array();
        foreach ($records as $record) {
            $timestamp = $record->xpath('./audit:date')[0];
            $details = $record->xpath('./audit:justification')[0];
            if (strlen($details)) {
                $event_parts = explode(';', $details);
                if (isset($event_parts[1]) &&
                    isset($event_parts[2]) &&
                    trim($event_parts[1]) == 'PREMIS:eventType=fixity check') {
                    $timestamp = (string) $timestamp;
                    $fixity_events[$timestamp] = array(trim($event_parts[0]), trim($event_parts[2]));
                }
            }
        }
        $fixity_events_string = json_encode($fixity_events);

        // phpcs:disable 
        $expected = '{"2018-09-15T14:29:03.684Z":["PREMIS:file=islandora:3+OBJ+OBJ.0","PREMIS:eventOutcome=SHA-1 checksum validated."],"2018-09-18T03:05:41.148Z":["PREMIS:file=islandora:3+OBJ+OBJ.0","PREMIS:eventOutcome=SHA-1 checksum validated."],"2018-09-19T01:38:02.136Z":["PREMIS:file=islandora:3+OBJ+OBJ.0","PREMIS:eventOutcome=SHA-1 checksum validated."],"2018-09-27T12:09:21.033Z":["PREMIS:file=islandora:3+OBJ+OBJ.0","PREMIS:eventOutcome=SHA-1 checksum validated."]}';
        // phpcs:enable

        $this->assertJsonStringEqualsJsonString($expected, $fixity_events_string);
    }
}
