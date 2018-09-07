<?php
// src/Controller/FixityController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class FixityController
{
    public function resource($id)
    {
        $data = array('fixity event 1 for resource ' . $id, 'fixity event 2 for resource ' . $id, 'fixity event 3 for resource ' . $id);
        $response = new JsonResponse($data);
        return $response;
    }

}
