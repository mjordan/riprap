<?php
// src/Controller/FixityController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;

class FixityController
{
    public function resource($id)
    {
        $response = new Response();

        $data = array('fixity event 1 for resource ' . $id, 'fixity event 2 for resource ' . $id, 'fixity event 3 for resource ' . $id);
        $content = json_encode($data);
        $response->setContent($content);

        $response->setStatusCode(Response::HTTP_OK);
        $response->headers->set('Content-Type', 'application/json');

        $response->send();
        return $response;
    }
}
