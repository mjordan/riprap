<?php
// src/Controller/FixityController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FixityController
{
    public function read(Request $request, ParameterBagInterface $params, KernelInterface $kernel)
    {
        $resource_id = $request->headers->get('Resource-ID');

        // Will be NULL if not present in request.
        $timestamp_start = $request->query->get('timestamp_start');
        $timestamp_end = $request->query->get('timestamp_end');
        $outcome = $request->query->get('outcome');

        // phpcs:disable
        // Initial implementation of calling plugin from controller. If you run
        // curl -v -H 'Resource-ID:http://localhost:8000/mockrepository/rest/10' http://localhost:8000/api/fixity
        // your request will return the fixity event entries in the database for resource 10.
        // phpcs:ensable
        $this->params = $params;
        $this->persistPlugins = $this->params->get('app.plugins.persist');

        // @todo: If we allow multiple persist plugins, the last one called determines
        // the value of $last_digest_for_resource.
        $this->persistPlugin = $this->persistPlugins[0];

        $get_events_plugin_command = new Application($kernel);
        $get_events_plugin_command->setAutoExit(false);

        $get_events_plugin_input = new ArrayInput(array(
           'command' => $this->persistPlugin,
            '--resource_id' => $resource_id,
            '--timestamp' => '',
            '--digest_algorithm' => '',
            '--event_uuid' => '',
            '--digest_value' => '',
            '--outcome' => $outcome,
            '--operation' => 'get_events',
            '--timestamp_start' => $timestamp_start,
            '--timestamp_end' => $timestamp_end,
        ));
        $get_events_plugin_output = new BufferedOutput();
        $get_events_plugin_return_code = $get_events_plugin_command->run($get_events_plugin_input, $get_events_plugin_output);
        $events_for_resource = $get_events_plugin_output->fetch();

        // $events_for_resource is a serialized PHP array.
        if (getenv('APP_ENV') == 'test') {
            $response = new JsonResponse(array());
        } else {
            $response = new JsonResponse(unserialize($events_for_resource));
        }
        return $response;
    }

    public function add(Request $request)
    {
        $resource_id = $request->headers->get('Resource-ID');
        // Dummy data.
        $data = array(
            'new fixity event for resource ' . $resource_id,
        );
        $response = new JsonResponse($data);
        return $response;
    }

    public function update(Request $request)
    {
        $resource_id = $request->headers->get('Resource-ID');
        // Dummy data.
        $data = array(
            'updated fixity event for resource ' . $resource_id,
        );
        $response = new JsonResponse($data);
        return $response;
    }
}
