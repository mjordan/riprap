<?php
// src/Command/PluginFetchResourceListFromDrupal.php
namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Entity\Event;
use App\Service\FixityEventDetailManager;

class PluginFetchResourceListFromDrupal extends ContainerAwareCommand
{
    private $params;

    public function __construct(
        ParameterBagInterface $params = null,
        LoggerInterface $logger = null,
        FixityEventDetailManager $event_detail = null
    ) {
        $this->params = $params;
        if ($this->params->has('app.plugins.fetchresourcelist.from.drupal.baseurl')) {
            $this->drupal_base_url = $this->params->get('app.plugins.fetchresourcelist.from.drupal.baseurl');
        } else {
            $this->drupal_base_url = 'http://localhost:8000';
        }
        // An array, we need to loop through and add to guzzle request.
        if ($this->params->has('app.plugins.fetchresourcelist.from.drupal.json_authorization_headers')) {
            $this->jsonapi_authorization_headers = $this->params->get('app.plugins.fetchresourcelist.from.drupal.json_authorization_headers');
        } else {
            $this->jsonapi_authorization_headers = array();
        }
        if ($this->params->has('app.plugins.fetchresourcelist.from.drupal.media_auth')) {
            $this->media_auth = $this->params->get('app.plugins.fetchresourcelist.from.drupal.media_auth');
        } else {
            $this->media_auth = '';
        }
        // For now we only use the first one, not sure how to handle multiple content types.
        if ($this->params->has('app.plugins.fetchresourcelist.from.drupal.content_types')) {
            $this->drupal_content_types = $this->params->get('app.plugins.fetchresourcelist.from.drupal.content_types');
        } else {
            $this->drupal_content_types = array();
        }
        if ($this->params->has('app.plugins.fetchresourcelist.from.drupal.media_tags')) {
            $this->media_tags = $this->params->get('app.plugins.fetchresourcelist.from.drupal.media_tags');
        } else {
            $this->media_tags = array();
        }
        if ($this->params->has('app.plugins.fetchresourcelist.from.drupal.use_fedora_urls')) {
            $this->use_fedora_urls = $this->params->get('app.plugins.fetchresourcelist.from.drupal.use_fedora_urls');
        } else {
            $this->use_fedora_urls = true;
        }
        if ($this->params->has('app.plugins.fetchresourcelist.from.drupal.gemini_endpoint')) {
            $this->gemini_endpoint = $this->params->get('app.plugins.fetchresourcelist.from.drupal.gemini_endpoint');
        } else {
            $this->gemini_endpoint = '';
        }
        if ($this->params->has('app.plugins.fetchresourcelist.from.drupal.gemini_auth_header')) {
            $this->gemini_auth_header = $this->params->get('app.plugins.fetchresourcelist.from.drupal.gemini_auth_header');
        } else {
            $this->gemini_auth_header = '';
        }
        if ($this->params->has('app.plugins.fetchresourcelist.from.drupal.page_size')) {
            $this->page_size = $this->params->get('app.plugins.fetchresourcelist.from.drupal.page_size');
        } else {
            $this->page_size = 50;
        }
        if ($this->params->has('app.plugins.fetchresourcelist.from.drupal.pager_data')) {
            $this->page_data_file = $this->params->get('app.plugins.fetchresourcelist.from.drupal.pager_data');
        } else {
            $this->page_data_file = '';
        }

        $this->logger = $logger;
        $this->event_detail = $event_detail;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:riprap:plugin:fetchresourcelist:from:drupal')
            ->setDescription("A Riprap plugin for reading a list of resource URLs from Drupal's JSON:API. To use this plugin, that contrib module (https://www.drupal.org/project/jsonapi) needs to be installed on the source Drupal instance.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if (file_exists($this->page_data_file)) {
            $page_offset = (int) trim(file_get_contents($this->page_data_file));
        }
        else {
            $page_offset = 0;
            file_put_contents($this->page_data_file, $page_offset);
        }

        $client = new \GuzzleHttp\Client();
        $url = $this->drupal_base_url . '/jsonapi/node/' . $this->drupal_content_types[0];
        $response = $client->request('GET', $url, [
            'http_errors' => false,
            // @todo: Loop through this array and add each header.
            'headers' => [$this->jsonapi_authorization_headers[0]],
            // Sort descending by 'changed' so new and updated nodes
            // get checked immediately after they are added/updated.
            'query' => ['page[offset]' => $page_offset, 'page[limit]' => $this->page_size, 'sort' => '-changed']
        ]);

        $status_code = $response->getStatusCode();
        $node_list = (string) $response->getBody();
        $node_list_array = json_decode($node_list, true);

        if ($status_code === 200) {
            $this->setPageOffset($page_offset, $node_list_array['links']);
        }

        if (count($node_list_array['data']) == 0) {
            if ($this->logger) {
                $this->logger->info("PluginFetchResourceListFromDrupal retrieved an empty node list from Drupal",
                    array(
                        'HTTP response code' => $status_code
                    )
                );
            }
        }

        foreach ($node_list_array['data'] as $node) {
            $nid = $node['attributes']['nid'];
            // Get the media associated with this node using the Islandora-supplied Manage Media View.
            $media_client = new \GuzzleHttp\Client();
            $media_url = $this->drupal_base_url . '/node/' . $nid . '/media';
            $media_response = $media_client->request('GET', $media_url, [
                'http_errors' => false,
                'auth' => $this->media_auth, 
                'query' => ['_format' => 'json']
            ]);
            $media_status_code = $media_response->getStatusCode();
            $media_list = (string) $media_response->getBody();
            $media_list = json_decode($media_list, true);

            if (count($media_list) === 0) {
                if ($this->logger) {
                    $this->logger->info("PluginFetchResourceListFromDrupal is skipping node with an empty media list.",
                        array(
                            'Node ID' => $nid
                        )
                    );
                }
                continue;
            }

            // Loop through all the media and pick the ones that are tagged with terms in $taxonomy_terms_to_check.
            foreach ($media_list as $media) {
                if (count($media['field_media_use'])) {
                    foreach ($media['field_media_use'] as $term) {
                        if (in_array($term['url'], $this->media_tags)) {
                            // Get the timestamp of the current revision.
                            // Will be in ISO8601 format.
                            $revised = $media['revision_created'][0]['value'];                  
                            if ($this->use_fedora_urls) {
                                // @todo: getFedoraUrl() returns false on failure, so build in logic here to log that
                                // the resource ID / URL cannot be found. (But, http responses are already logged in
                                // getFedoraUrl() so maybe we don't need to log here?)
                                if (isset($media['field_media_image'])) {
                                    $fedora_url = $this->getFedoraUrl($media['field_media_image'][0]['target_uuid']);
                                    if (strlen($fedora_url)) {
                                        $media_data = array(
                                            'resource_id' => $fedora_url,
                                            'last_modified_timestamp' => $revised
                                        );
                                        $media_data = json_encode($media_data);
                                        // This is a string containing one JSON object per line.
                                        $output->writeln($media_data);
                                    }
                                } else {
                                    $fedora_url = $this->getFedoraUrl($media['field_media_file'][0]['target_uuid']);
                                    if (strlen($fedora_url)) {
                                        $media_data = array(
                                            'resource_id' => $fedora_url,
                                            'last_modified_timestamp' => $revised
                                        );
                                        $media_data = json_encode($media_data);
                                        // This is a string containing one JSON object per line.                                     
                                        $output->writeln($media_data);
                                    }                             
                                }
                            } else {
                                if (isset($media['field_media_image'])) {
                                    if (strlen($media['field_media_image'][0]['url'])) {
                                        $media_data = array(
                                            'resource_id' => $media['field_media_image'][0]['url'],
                                            'last_modified_timestamp' => $revised
                                        );
                                        $media_data = json_encode($media_data);
                                        // This is a string containing one JSON object per line.                            
                                        $output->writeln($media_data);
                                    }
                                } else {
                                    if (strlen($media['field_media_file'][0]['url'])) {
                                        $media_data = array(
                                            'resource_id' => $media['field_media_file'][0]['url'],
                                            'last_modified_timestamp' => $revised
                                        );
                                        $media_data = json_encode($media_data);
                                        // This is a string containing one JSON object per line.
                                        $output->writeln($media_data);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // $this->logger is null while testing.
        if ($this->logger) {
            $this->logger->info("PluginFetchResourceListFromDrupal executed");
        }
    }

   /**
    * Get a Fedora URL for a File entity from Gemini.
    *
    * @param string $uuid
    *   The File entity's UUID.
    *
    * @return string
    *    The Fedora URL corresponding to the UUID, or false.
    */
    private function getFedoraUrl($uuid)
    {
        try {
            $client = new \GuzzleHttp\Client();
            $options = [
                'http_errors' => false,
                'headers' => ['Authorization' => $this->gemini_auth_header],
            ];
            $url = $this->gemini_endpoint . '/' . $uuid;
            $response = $client->request('GET', $url, $options);
            $code = $response->getStatusCode();
            if ($code == 200) {
                $body = $response->getBody()->getContents();
                $body_array = json_decode($body, true);
                return $body_array['fedora'];
            }
            elseif ($code == 404) {
                return false;
            }
            else {
                if ($this->logger) {
                    $this->logger->error("PluginFetchResourceListFromDrupal could not get Fedora URL from Gemini",
                        array(
                            'HTTP response code' => $code
                        )
                    );
                }
                return false;
            }
        }
        catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error("PluginFetchResourceListFromDrupal could not get Fedora URL from Gemini",
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
    * Sets the page offset to use in the next JSON:API request.
    *
    * @param int $page_offset
    *   The page offset used in the current JSON:API request.
    * @param string $links
    *   The 'links' array member from the JSON:API response.
    */
    private function setPageOffset($page_offset, $links)
    {
        // We are not on the last page, so increment the page offset counter.
        // See https://www.drupal.org/docs/8/modules/jsonapi/pagination for
        // info on the JSON API paging logic.
        if (array_key_exists('next', $links)) {
            $next_url = $links['next'];
            $query_string = parse_url(urldecode($next_url), PHP_URL_QUERY);
            parse_str($query_string, $query_array);
            $next_offset = $query_array['page']['offset'];
            file_put_contents($this->page_data_file, trim($next_offset));
        }
        // We are on the last page, so reset the offset value to start the
        // verification cycle from the beginning.
        else {
            if (array_key_exists('first', $links)) {
                $first_url = $links['first'];
                $query_string = parse_url(urldecode($first_url), PHP_URL_QUERY);
                parse_str($query_string, $query_array);
                $first_offset = $query_array['page']['offset'];
                file_put_contents($this->page_data_file, trim($first_offset));

                if ($this->logger) {
                    $this->logger->info("PluginFetchResourceListFromDrupal has reset Drupal's JSON:API page offset to the first page.",
                        array(
                            'Pager self URL' => $links['self']
                        )
                    );
                }                
            }
        }
    }
}
