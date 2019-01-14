<?php

/**
 * @file
 * Defines the class for the Riprap PluginFetchDigestFromShell plugin.
 */

namespace App\Plugin;

/**
 * Class for the Riprap PluginFetchDigestFromShell plugin.
 */
class PluginFetchDigestFromShell extends AbstractFetchDigestPlugin
{
    /**
     * Gets the resource's digest from some external source.
     *
     * @param string $resource_id
     *    The resource's ID.
     *
     * @return string $digest
     *   The digest value.
     */
    public function execute($resource_id)
    {
        $file_path = $resource_id;
        $external_digest_program_command = $this->settings['digest_command'] . ' ' . $file_path;
        $external_digest_program_command = escapeshellcmd($external_digest_program_command);
        $external_digest_command_output = exec($external_digest_program_command, $external_digest_program_command_output, $return);
        if ($return == 0) {
            list($digest, $path) = preg_split('/\s/', $external_digest_program_command_output[0]);
            return trim($digest);
        } else {
            $this->logger->warning("check_fixity cannot retrieve digest from file system.", array(
                'resource_id' => $file_path,
                'status_code' => $return,
            ));
            return $return;
        }        
    }
}
