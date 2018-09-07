<?php
// src/Controller/FixityController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;

class FixityController
{
    public function resource()
    {
        $response = new Response();

        $data = array('fixity event 1', 'fixity event 2', 'fixity event 3');
        $content = json_encode($data);
        $response->setContent($content);

        $response->setStatusCode(Response::HTTP_OK);
        $response->headers->set('Content-Type', 'application/json');

        $response->send();
        return $response;
    }
}
