<?php
// src/Controller/FixityController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FixityController
{
    public function read(Request $request)
    {
        $resource_id = $request->headers->get('Resource-ID');
        // Dummy data.
        $data = array(
            'fixity event 1 for resource ' . $resource_id,
            'fixity event 2 for resource ' . $resource_id,
            'fixity event 3 for resource ' . $resource_id
        );
        $response = new JsonResponse($data);
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