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
     *    This file's Drupal (filesystem) URI.
     *
     * @return string|bool
     *   The digest value, false on error.
     */
    public function execute($resource_id)
    {
        if (isset($this->settings['drupal_baseurl'])) {
            $this->drupal_base_url = $this->settings['drupal_baseurl'];
        } else {
            $this->drupal_base_url = 'http://localhost:8000';
        }
        if (isset($this->settings['drupal_user'])) {
            $this->drupal_user = $this->settings['drupal_user'];
        } else {
            $this->drupal_user = 'admin';
        }
        if (isset($this->settings['drupal_password'])) {
            $this->drupal_password = $this->settings['drupal_password'];
        } else {
            $this->drupal_password = 'islandora';
        }

        if (isset($this->settings['fixity_algorithm'])) {
            $this->fixity_algorithm = $this->settings['fixity_algorithm'];
        } else {
            $this->fixity_algorithm = 'sha1';
        }

        $this->drupal_file_fieldnames = $this->settings['drupal_file_fieldnames'];

        $client = new \GuzzleHttp\Client();

        if (!strlen($resource_id)) {
            if ($this->logger) {
                $this->logger->info("PluginFetchDigestFromDrupal exited due to empty resource ID.");
            }
            return;
        }

        $get_digest_url = $this->drupal_base_url . '/islandora_riprap/checksum?file_uri=' .
            $resource_id . '&algorithm=' . $this->fixity_algorithm;

        $response = $client->request('GET', $get_digest_url, [
            'http_errors' => false,
            'auth' => [$this->drupal_user, $this->drupal_password]
        ]);
        $status_code = $response->getStatusCode();
        $allowed_codes = array(200);
        if (in_array($status_code, $allowed_codes)) {
            $response_body = json_decode($response->getBody(), true);
            return $response_body[0]['checksum'];
        } else {
            // If the HTTP status code is not in the allowed list, log it.
            $this->logger->warning("check_fixity cannot retrieve digest from Drupal.", array(
                'resource_id' => $url,
                'status_code' => $status_code,
                'algorithm' => $algorithm,
                'resource_id' => $resource_id,
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
