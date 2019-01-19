<?php
// src/Plugin/PluginPostCheckMailFailures.php
namespace App\Plugin;


class PluginPostCheckMailFailures extends AbstractPostCheckPlugin
{
    public function execute($event)
    {
        if ($event->getEventOutcome() == 'fail') {
            $mail_command = $this->getApplication()->find('swiftmailer:email:send');
            $input = new ArrayInput(array(
                '--from' => $this->settings['email_from'],
                '--to' => $this->settings['email_to'],
                '--subject' => "Fixity validation failure on " . $event->getResourceId(),
                '--body' => "Riprap has detected a fixity validation failure on " .
                    $event->getResourceId() .
                    " (event UUID " .
                    $event->getEventUuid() .
                    "), which occured at " .
                    $event->getTimestamp(),
            ));
            $returnCode = $mail_command->run($input, $output);
            $this->logger->info("Mail Failure plugin generated a message", array(
                'recipient' => $this->settings['email_to'],
                'resource ID' => $event->getResourceId(),
                'timestamp' => $event->getTimestamp(),
            ));
        }
    }
}
