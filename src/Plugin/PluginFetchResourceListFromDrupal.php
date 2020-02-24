<?php
// src/Plugin/PluginFetchResourceListFromDrupal.php

namespace App\Plugin;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\Output;

class PluginFetchResourceListFromDrupal extends AbstractFetchResourceListPlugin
{
    public function execute()
    {
        $output = new ConsoleOutput();

        if (isset($this->settings['drupal_baseurl'])) {
            $this->drupal_base_url = $this->settings['drupal_baseurl'];
        } else {
            $this->drupal_base_url = 'http://localhost:8000';
        }
        if (isset($this->settings['view_authorization_user'])) {
            $this->view_authorization_user = $this->settings['view_authorization_user'];
        } else {
            $this->view_authorization_user = 'admin';
        }
        if (isset($this->settings['view_authorization_password'])) {
            $this->view_authorization_password = $this->settings['view_authorization_password'];
        } else {
            $this->view_authorization_password = 'islandora';
        }
        if (isset($this->settings['use_fedora_urls'])) {
            $this->use_fedora_urls = $this->settings['use_fedora_urls'];
        } else {
            $this->use_fedora_urls = true;
        }
        if (isset($this->settings['gemini_endpoint'])) {
            $this->gemini_endpoint = $this->settings['gemini_endpoint'];
        } else {
            $this->gemini_endpoint = '';
        }
        if (isset($this->settings['gemini_auth_header'])) {
            $this->gemini_auth_header = $this->settings['gemini_auth_header'];
        } else {
            $this->gemini_auth_header = '';
        }
        if (isset($this->settings['views_pager_data_file_path'])) {
            $this->page_data_file = $this->settings['views_pager_data_file_path'];
        } else {
            $this->page_data_file = '';
        }
        if (file_exists($this->page_data_file)) {
            $page_number = (int) trim(file_get_contents($this->page_data_file));
        } else {
            $page_number = 1;
            file_put_contents($this->page_data_file, $page_number);
        }

        // Make an initial ping request to Drupal.
        $ping_url = $this->drupal_base_url . '/riprap_resource_list?page=1';
        $ping_client = new \GuzzleHttp\Client();
        $ping_response = $ping_client->request('GET', $ping_url, [
            'http_errors' => false,
            'auth' => [$this->view_authorization_user, $this->view_authorization_password]
        ]);

        if ($ping_response->getStatusCode() != 200) {
            if ($this->logger) {
                $this->logger->error(
                    "PluginFetchResourceListFromDrupal request returned a non-200 response",
                    array(
                        'HTTP response code' => $ping_response->getStatusCode()
                    )
                );
            }
            $output->writeln("Ping request to Drupal returned a non-200 stataus code. Please " .
                "see the Riprap log for more detail.");
            exit(1);
        }

        $ping_media_list = (string) $ping_response->getBody();
        $ping_media_list = json_decode($ping_media_list, true);
        if (count($ping_media_list) == 0) {
            if ($this->logger) {
                $this->logger->error(
                    "PluginFetchResourceListFromDrupal retrieved an empty media list"
                );
            }
            $output->writeln("PluginFetchResourceListFromDrupal retrieved an empty media list");
            exit(1);
        }

        // If we've made it this far, query the View to get list of media.
        $client = new \GuzzleHttp\Client();
        $url = $this->drupal_base_url . '/riprap_resource_list?page=' . $page_number;
        $page_response = $client->request('GET', $url, [
            'http_errors' => false,
            'auth' => [$this->view_authorization_user, $this->view_authorization_password]
        ]);

        if ($page_response->getStatusCode() == 200) {
            $media_list = (string) $page_response->getBody();
            $media_list = json_decode($media_list, true);
        }

        var_dump($media_list);
        // Loop through all the media perform fixity event check.
        $num_media = count($media_list);
        $output_resource_records = [];
        if ($num_media > 0) {
            foreach ($media_list as $media) {
                if ($this->use_fedora_urls) {
                    // @todo: getFedoraUrl() returns false on failure, so build in logic here to log that
                    // the resource ID / URL cannot be found. (But, http responses are already logged in
                    // getFedoraUrl() so maybe we don't need to log here?)
                    $file_fedora_url = $this->getFedoraUrl($media['mid']);
                    if (strlen($file_fedora_url)) {
                        $resource_record_object = new \stdClass;
                        $resource_record_object->resource_id = $file_fedora_url;
                        $resource_record_object->last_modified_timestamp = $media['changed'];
                        $output_resource_records[] = $resource_record_object;
                    }
                }
            }
            $this->setPageNumber($page_number, $num_media);
        } else {
            $this->setPageNumber($page_number, $num_media);
            if ($this->logger) {
                $this->logger->error(
                    "PluginFetchResourceListFromDrupal retrieved an empty media list"
                );
            }
            $output->writeln("PluginFetchResourceListFromDrupal retrieved an empty media list");
            exit(1);
        }

        // $this->logger is null while testing.
        if ($this->logger) {
            $this->logger->info("PluginFetchResourceListFromDrupal executed");
        }

        return $output_resource_records;
    }

   /**
    * Get a Fedora URL for a File entity from Gemini.
    *
    * @param string $mid
    *   The media ID.
    *
    * @return string
    *    The Fedora URL corresponding to the UUID, or false.
    */
    private function getFedoraUrl($mid)
    {
        // First, retrieve the media entity from Drupal.
        $media_url = $this->drupal_base_url . '/media/' . $mid . '?_format=json';
        $media_client = new \GuzzleHttp\Client();
        $media_response = $media_client->request('GET', $media_url, [
            'http_errors' => false,
            'auth' => [$this->view_authorization_user, $this->view_authorization_password]
        ]);
        $media_response_body = $media_response->getBody()->getContents();
        $media_response_body = json_decode($media_response_body, true);
        if (isset($media_response_body['field_media_image'])) {
            $file_field = 'field_media_image';
        }
        if (isset($media_response_body['field_media_file'])) {
            $file_field = 'field_media_file';
        }
        $target_file_uuid = $media_response_body[$file_field][0]['target_uuid'];

        // Then query Gemini to get the target file's Fedora URL.
        try {
            $client = new \GuzzleHttp\Client();
            $options = [
                'http_errors' => false,
                'headers' => ['Authorization' => $this->gemini_auth_header],
            ];
            $url = $this->gemini_endpoint . '/' . $target_file_uuid;
            var_dump($url);
            $response = $client->request('GET', $url, $options);
            $code = $response->getStatusCode();
            var_dump($code);
            if ($code == 200) {
                $body = $response->getBody()->getContents();
                $body_array = json_decode($body, true);
                return $body_array['fedora'];
            } elseif ($code == 404) {
                return false;
            } else {
                if ($this->logger) {
                    $this->logger->error(
                        "PluginFetchResourceListFromDrupal could not get Fedora URL from Gemini",
                        array(
                            'HTTP response code' => $code
                        )
                    );
                }
                return false;
            }
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error(
                    "PluginFetchResourceListFromDrupal could not get Fedora URL from Gemini",
                    array(
                        'HTTP response code' => $code,
                        'Exception message' => $e->getMessage()
                    )
                );
            }
            return false;
        }
    }

   /**
    * Sets the page offset to use in the next REST request to the Drupal View.
    *
    * @param int $page_number
    *   The page number used in the current request.
    */
    private function setPageNumber($page_number, $num_media)
    {
        // Views REST responses don't include pagination info. See
        // https://www.drupal.org/project/drupal/issues/2982729.
        // For now, we use Views REST serializer's behavior of
        // returning an empty response when the provided page number
        // exceeds the number of pages.
        if ($num_media == 0) {
            $next_page_number = 1;
        } else {
            $next_page_number = $page_number + 1;
        }
        file_put_contents($this->page_data_file, trim($next_page_number));
    }
}
