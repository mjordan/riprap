<?php
// src/Controller/ExampleRepositoryEndpoint.php

/**
 * Controller that simmulates persistence fixity responses by a
 * Fedora API Specification-compliant server as described in section
 * 7.2 of the spec. Used in Riprap for testing and development.
 */
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ExampleRepositoryEndpoint
{
    public function read(Request $request, $id)
    {
        // Made up digests for made up resources.
        $digests = array(
            // resource id = > SHA-1 values
            '1' => '5a5b0f9b7d3f8fc84c3cef8fd8efaaa6c70d75ab',
            '2' => 'b1d5781111d84f7b3fe45a0852e59758cd7a87e5',
            '3' => '310b86e0b62b828562fc91c7be5380a992b2786a',
            '4' => '08a35293e09f508494096c1c1b3819edb9df50db',
            '5' => '450ddec8dd206c2e2ab1aeeaa90e85e51753b8b7',
        );

        // Return 404 if the resource identified by $id is not found.
        if (!array_key_exists($id, $digests)) {
            $response = new Response();
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
            return $response;
        }

        // If we've made it this far, look for Want-Digest header, which
        // specifies the digest algorithm. For now, we are only interested
        // in SHA-1 digests.
        $want_digest = $request->headers->get('Want-Digest');
        if (!strlen($want_digest) || $want_digest != 'SHA-1') {
            $response = new Response();
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            return $response;
        }

        // Return the expected hash in the Digest header.
        $response = new Response();
        $response->headers->set('Digest', $digests[$id]);
        return $response;
    }
}