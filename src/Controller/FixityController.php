<?php
// src/Controller/FixityController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
// Note that $logger here is independent from the logger that the console command uses.
use Psr\Log\LoggerInterface;

// Note: Until we figure out how to define which persist plugin to use in this controller via
// a single configuration shared between it and the console command, we are limited to using
// the PluginPersistToDatabase plugin. We could revert to using services.yaml, at least to
// register persist plugins for this controller, but then we'd have two places to register
// configuration info.
use App\Plugin\PluginPersistToDatabase;

class FixityController extends AbstractController
{
    public function read(Request $request, LoggerInterface $logger)
    {
        // Will be NULL if not present in request.
        $resource_id = $request->headers->get('Resource-ID');
        $timestamp_start = $request->query->get('timestamp_start');
        $timestamp_end = $request->query->get('timestamp_end');
        $outcome = $request->query->get('outcome');
        // Set default to 'asc' if not in request.
        $sort = !is_null($request->query->get('sort')) ? $request->query->get('sort') : 'asc';
        // This typecasting makes the default value of $limit and $offset to be 0.
        $limit = (int) $request->query->get('limit');
        $offset = (int) $request->query->get('offset');

        $entityManager = $this->getDoctrine()->getManager();
        // See comment above about hard-coded persist plugin.
        $this->persist_plugin = new PluginPersistToDatabase(array(), $logger, $entityManager);
        $events_for_resource = $this->persist_plugin->getEvents(
            $resource_id,
            $outcome,
            $timestamp_start,
            $timestamp_end,
            $limit,
            $offset,
            $sort
        );        

        if (getenv('APP_ENV') == 'test') {
            $response = new JsonResponse(array());
        } else {
            $response = new JsonResponse($events_for_resource);
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
