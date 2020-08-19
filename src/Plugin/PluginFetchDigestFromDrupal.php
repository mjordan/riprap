<?php

/**
 * @file
 * Defines the class for the Riprap PluginFetchDigestFromDrupal plugin.
 */

namespace App\Plugin;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Class for the Riprap PluginFetchDigestFromDrupal plugin.
 */
class PluginFetchDigestFromDrupal extends AbstractFetchDigestPlugin
{
    /**
     * Gets the resource's digest from the REST endpoint provided by Islandora Riprap.
     *
     * @param string $resource_id
     *    The file's UUID.
     *
     * @return string $digest
     *   The digest value.
     */
    public function execute($resource_id)
    {
        if (isset($this->settings['fixity_algorithm'])) {
            $this->fixity_algorithm = $this->settings['fixity_algorithm'];
        } else {
            $this->fixity_algorithm = 'sha1';
        }

        $client = new \GuzzleHttp\Client();
        // @todo: Wrap in try/catch.

        // @todo: Request is to /islandora_riprap/checksum/{file_uuid}/{algorithm}, not to the resource ID as with Fedora.
        $url = $resource_id;
        if (!strlen($url)) {
            if ($this->logger) {
                $this->logger->info("PluginFetchDigestFromDrupal exited due to empty resource ID.");
            }
            return;
        }

        $response = $client->request('GET', $url, [
            'http_errors' => false,
        ]);
        $status_code = $response->getStatusCode();
        $allowed_codes = array(200);
        if (in_array($status_code, $allowed_codes)) {
            $response_body = json_decode($response->getBody(), true);
            return $response_body[0]->checksum;
        } else {
            // If the HTTP status code is not in the allowed list, log it.
            $this->logger->warning("check_fixity cannot retrieve digest from Drupal.", array(
                'resource_id' => $url,
                'status_code' => $status_code,
            ));
            return false;
        }

        if ($this->event_detail) {
            $this->event_detail->add('event_outcome_detail_note', '');
        }

        // $this->logger is null while testing.
        if ($this->logger) {
            $this->logger->info("PluginFetchDigestFromDrupal executed");
        }
    }
}
