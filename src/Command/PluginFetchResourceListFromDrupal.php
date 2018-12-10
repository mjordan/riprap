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
        $this->drupal_base_url = $this->params->get('app.plugins.fetchresourcelist.from.drupal.baseurl');
        // An array, we need to loop through and add to guzzle request.
        $this->jsonapi_authorization_headers = $this->params->get('app.plugins.fetchresourcelist.from.drupal.authorization_headers');
        // For now we only use the first one, not sure how to handle multiple content types.
        $this->drupal_content_types = $this->params->get('app.plugins.fetchresourcelist.from.drupal.content_types');
        $this->media_tags = $this->params->get('app.plugins.fetchresourcelist.from.drupal.media_tags');

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
        // @todo: See https://github.com/mjordan/riprap/issues/14.

        $client = new \GuzzleHttp\Client();
        $url = $this->drupal_base_url . '/jsonapi/node/' . $this->drupal_content_types[0];
        // First, get JSON:API's first page of nodes, then loop through each one and print it to output.
        // curl -v -H 'Authorization: Basic YWRtaW46aXNsYW5kb3Jh'  "http://localhost:8000/jsonapi/node/islandora_object?page[offset]=2&page[limit]=1"
        $response = $client->request('GET', $url, [
            'http_errors' => false,
            'headers' => [$this->jsonapi_authorization_headers[0]], // @todo: Loop through this array and add each header. 
            'query' => ['page[offset]' => '1', 'page[limit]' => '3']
        ]);
        $status_code = $response->getStatusCode();
        // var_dump($status_code);
        $node_list = (string) $response->getBody();
        $node_list_array = json_decode($node_list, true);
        // var_dump($node_list_array);

        foreach ($node_list_array['data'] as $node) {
            $nid = $node['attributes']['nid'];
            // var_dump($nid);            
            // Get the media associated with this node using the Islandora-supplied Manage Media View.
            $media_url = $this->drupal_base_url . '/node/' . $nid . '/media';
            $media_response = $client->request('GET', $url, [
                'http_errors' => false,
                // @todo: Split this header out into its own config parameter.
                'headers' => [$this->jsonapi_authorization_headers[0]], 
                'query' => ['_format' => 'json']
            ]);
            $status_code = $media_response->getStatusCode();
            // var_dump($status_code);
            $media_list = (string) $response->getBody();
            $media_list = json_decode($media_list, true);

            // Loop through all the media and pick the ones that
            // are tagged with terms in $taxonomy_terms_to_check.
            foreach ($media_list as $media) {
              var_dump($media);
              if (count($media->field_tags)) {
                foreach ($media->field_tags as $term) {
                  if (in_array($term->url, $taxonomy_terms_to_check)) {
                    // @todo: Convert to the equivalent Fedora URL and add to the plugin's output.
                    // @todo: Add option to not convert to Fedora URL if the site doesn't use Fedora.
                    // In that case, we need to figure out how to get Drupal's checksum for the file over HTTP.
                    // var_dump($media->field_media_image[0]->url);
                    $output->writeln($resource_id);
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
}
