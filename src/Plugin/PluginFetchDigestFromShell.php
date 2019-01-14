<?php

/**
 * @file
 * Defines the abstract class for Riprap fetchdigest plugins.
 */

namespace App\Plugin;

/**
 * Abstract class for Riprap plugins.
 */
class PluginFetchDigestFromShell extends AbstractFetchDigestPlugin
{
    /**
     * Gets the resource's digest from some external source.
     *
     * @param string $resource_id
     *    The resource's ID.
     *
     * @return FixityCheckEvent $event
     *   The modified fixity check event object.
     */
    public function execute($resource_id)
    {
        $file_path = $resource_id;
        $external_digest_program_command = $this->settings['digest_command'] . ' ' . $file_path;
        $external_digest_program_command = escapeshellcmd($external_digest_program_command);
        $external_digest_command_output = exec($external_digest_program_command, $external_digest_program_command_output, $return);
        if ($return == 0) {
            list($digest, $path) = preg_split('/\s/', $external_digest_program_command_output[0]);

            $mtime = exec('stat -c %Y '. escapeshellarg($file_path));
            $mtime_iso8601 = date(\DateTime::ISO8601, $mtime);

            $digest_value_and_timestamp = new \stdClass;
            $digest_value_and_timestamp->digest_value = trim($digest);
            $digest_value_and_timestamp->last_modified_timestamp = $mtime_iso8601;

            return $digest_value_and_timestamp;
        } else {
            $this->logger->warning("check_fixity cannot retrieve digest from file system.", array(
                'resource_id' => $file_path,
                'status_code' => $return,
            ));
            return $return;
        }        
    }
}
