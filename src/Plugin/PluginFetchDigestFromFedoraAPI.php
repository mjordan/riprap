<?php

/**
 * @file
 * Defines the class for the Riprap PluginFetchDigestFromFedoraAPI plugin.
 */

namespace App\Plugin;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Class for the Riprap PluginFetchDigestFromFedoraAPI plugin.
 */
class PluginFetchDigestFromFedoraAPI extends AbstractFetchDigestPlugin
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
        if (isset($this->settings['fedoraapi_method'])) {
            $this->http_method = $this->settings['fedoraapi_method'];
        } else {
            $this->http_method = 'HEAD';
        }
        if (isset($this->settings['fixity_algorithm'])) {
            $this->fixity_algorithm = $this->settings['fixity_algorithm'];
        } else {
            $this->fixity_algorithm = 'sha256';
        }
        if (isset($this->settings['fedoraapi_digest_header_leader_pattern'])) {
            $this->hash_leader_pattern = $this->settings['fedoraapi_digest_header_leader_pattern'];
        } else {
            $this->hash_leader_pattern = "^.+=";
        }

        $client = new \GuzzleHttp\Client();
        // @todo: Wrap in try/catch.
        $url = $resource_id;
        if (!strlen($url)) {
            if ($this->logger) {
                $this->logger->info("PluginFetchDigestFromFedoraAPI exited due to empty resource ID.");
            }
            return;
        }

        $response = $client->request($this->http_method, $url, [
            'http_errors' => false,
            'headers' => ['Want-Digest' => $this->fixity_algorithm],
        ]);
        $status_code = $response->getStatusCode();
        $allowed_codes = array(200);
        if (in_array($status_code, $allowed_codes)) {
            $digest_header_values = $response->getHeader('digest');
            // Digest header looks like
            // Digest: sha-256=cef971b6697fa92c7125a329437b69f9161c2472cce873a229a329d1424a4ff1,
            // so we need to remove the 'sha-256=' leader.
            $digest_header_value = preg_replace('/' . $this->hash_leader_pattern . '/', '', $digest_header_values[0]);
            // Assumes there is only one 'digest' header - is this always the case?
            return $digest_header_value;
        } else {
            // If the HTTP status code is not in the allowed list, log it.
            $this->logger->warning("check_fixity cannot retrieve digest from Fedora repository.", array(
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
            $this->logger->info("PluginFetchDigestFromFedoraAPI executed");
        }
    }
}
