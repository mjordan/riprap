<?php
// src/Plugin/PluginFetchResourceListFromDrupalView.php

namespace App\Plugin;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\Output;

class PluginFetchResourceListFromDrupalView extends AbstractFetchResourceListPlugin
{
    public function execute()
    {
        $output = new ConsoleOutput();

        if (isset($this->settings['fixity_algorithm'])) {
            $this->fixity_algorithm = $this->settings['fixity_algorithm'];
        } else {
            $this->fixity_algorithm = 'sha1';
        }
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

        $this->drupal_file_fieldnames = $this->settings['drupal_file_fieldnames'];

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
            $page_number = 0;
            file_put_contents($this->page_data_file, $page_number);
        }

        // Query the View to get list of media.
        $client = new \GuzzleHttp\Client();
        $url = $this->drupal_base_url . '/riprap_resource_list?page=' . $page_number;
        $page_response = $client->request('GET', $url, [
            'http_errors' => false,
            'auth' => [$this->drupal_user, $this->drupal_password]
        ]);

        if ($page_response->getStatusCode() == 200) {
            $media_list = (string) $page_response->getBody();
            $media_list = json_decode($media_list, true);
        }

        $empty_media_list_message = "PluginFetchResourceListFromDrupalView retrieved an empty media list. " .
            "This probably means Riprap has finished checking all your media. However, if Riprap " .
            "shows this message the next time it runs, you should check to make sure " .
            "your \"Riprap resource list\" Drupal View is working properly.";

        // Loop through all the media perform fixity event check.
        $num_media = count($media_list);
        if ($num_media == 0) {
            $this->setPageNumber($page_number, $num_media);
            if ($this->logger) {
                $this->logger->error($empty_media_list_message);
            }
            $output->writeln($empty_media_list_message);
            exit(1);
        }

        $output_resource_records = [];
        foreach ($media_list as $media) {
            if ($this->settings['plugins.fetchdigest'] == 'PluginFetchDigestFromFedoraAPI') {
                // @todo: getFileUrlFromFedora() returns false on failure, so build in logic here to log that
                // the resource ID / URL cannot be found. (But, http responses are already logged in
                // getFileUrlFromFedora() so maybe we don't need to log here?)
                $file_fedora_url = $this->getFileUrlFromFedora($media['mid']);
                if (strlen($file_fedora_url)) {
                    $resource_record_object = new \stdClass;
                    $resource_record_object->resource_id = $file_fedora_url;
                    $resource_record_object->last_modified_timestamp = $media['changed'];
                    $output_resource_records[] = $resource_record_object;
                }
            }
            if ($this->settings['plugins.fetchdigest'] == 'PluginFetchDigestFromDrupal') {
                // @todo: getFileUrlFromDrupal() returns false on failure, so build in logic here to log that
                // the resource ID / URL cannot be found. (But, http responses are already logged in
                // getFileUrlFromFedora() so maybe we don't need to log here?)
                $file_drupal_uri = $this->getFileUriFromDrupal($media['mid']);
                if (strlen($file_drupal_uri)) {
                    $resource_record_object = new \stdClass;
                    $resource_record_object->resource_id = $file_drupal_uri;
                    $resource_record_object->last_modified_timestamp = $media['changed'];
                    $output_resource_records[] = $resource_record_object;
                }
            }
        }
        $this->setPageNumber($page_number, $num_media);

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
    *    The Fedora URL corresponding to the media ID, or false.
    */
    private function getFileUrlFromFedora($mid)
    {
        // First, retrieve the media entity from Drupal.
        $media_url = $this->drupal_base_url . '/media/' . $mid . '?_format=json';
        $media_client = new \GuzzleHttp\Client();
        $media_response = $media_client->request('GET', $media_url, [
            'http_errors' => false,
            'auth' => [$this->drupal_user, $this->drupal_password]
        ]);
        $media_response_body = $media_response->getBody()->getContents();
        $media_response_body = json_decode($media_response_body, true);

        foreach ($this->drupal_file_fieldnames as $file_fieldname) {
            if (isset($media_response_body[$file_fieldname])) {
                $file_field = $file_fieldname;
                break;
            }
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
            $response = $client->request('GET', $url, $options);
            $code = $response->getStatusCode();
            if ($code == 200) {
                $body = $response->getBody()->getContents();
                $body_array = json_decode($body, true);
                return $body_array['fedora'];
            } elseif ($code == 404) {
                return false;
            } else {
                if ($this->logger) {
                    $this->logger->error(
                        "PluginFetchResourceListFromDrupal could not get Fedora File URL from Gemini.",
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
                    "PluginFetchResourceListFromDrupal could not get Fedora File URL from Gemini.",
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
    * Get a URI for a File entity from Drupal.
    *
    * @param string $mid
    *   The media ID.
    *
    * @return string
    *    The Drupal URI of the file, or false. We use the URI to get the
    *    digest from the /islandora_riprap/checksum endpoint.
    */
    private function getFileUriFromDrupal($mid)
    {
        // Retrieve the media entity from Drupal.
        try {
            $url = $this->drupal_base_url . '/media/' . $mid . '?_format=json';
            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', $url, [
                'http_errors' => false,
                'auth' => [$this->drupal_user, $this->drupal_password]
            ]);
            $code = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            $body = json_decode($body, true);

            foreach ($this->drupal_file_fieldnames as $file_fieldname) {
                if (isset($body[$file_fieldname])) {
                    $file_field = $file_fieldname;
                    break;
                }
            }
            // Then the file entity to get its URI.
            if ($code == 200) {
                if (isset($body[$file_field][0]['target_id'])) {
                    $file_id = $body[$file_field][0]['target_id'];
                    $file_url = $this->drupal_base_url . '/entity/file/' . $file_id . '?_format=json';
                    $file_response = $client->request('GET', $file_url, [
                        'http_errors' => false,
                        'auth' => [$this->drupal_user, $this->drupal_password]
                    ]);
                    $file_code = $file_response->getStatusCode();
                    $file_body = $file_response->getBody()->getContents();
                    $file_body = json_decode($file_body, true);
                    return $file_body['uri'][0]['value'];
                }
            } elseif ($code == 404) {
                return false;
            } else {
                if ($this->logger) {
                    $this->logger->error(
                        "PluginFetchResourceListFromDrupal could not get File URI from Drupal.",
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
                    "PluginFetchResourceListFromDrupal could not get File URI from Drupal.",
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
        // exceeds the number of pages. In the meantime, we show
        // the user the $empty_media_list_message, above. Riprap
        // issue https://github.com/mjordan/riprap/issues/69.
        if ($num_media == 0) {
            $next_page_number = 0;
        } else {
            $next_page_number = $page_number + 1;
        }
        file_put_contents($this->page_data_file, trim($next_page_number));
    }
}
