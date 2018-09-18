<?php
// src/Controller/FixityController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class FixityController
{
    public function read($id)
    {
        // Dummy data.
        $data = array(
            'fixity event 1 for resource ' . $id,
            'fixity event 2 for resource ' . $id,
            'fixity event 3 for resource ' . $id
        );
        $response = new JsonResponse($data);
        return $response;
    }

    public function add($id)
    {
        // Dummy data.
        $data = array(
            'new fixity event for resource ' . $id,
        );
        $response = new JsonResponse($data);
        return $response;
    }

    public function update($id)
    {
        // Dummy data.
        $data = array(
            'updated fixity event for resource ' . $id,
        );
        $response = new JsonResponse($data);
        return $response;
    }
}
