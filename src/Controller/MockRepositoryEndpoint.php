<?php
// src/Controller/MockRepositoryEndpoint.php

/**
 * Controller that simmulates persistence fixity responses by a
 * Fedora API Specification-compliant server as described in section
 * 7.2 of the spec. Used in Riprap for testing and development.
 */
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class MockRepositoryEndpoint
{
    public function read(Request $request, $id)
    {
        // Digests corresponding to the resource IDs in the 'fixity_check_event' table.
        $digests = array(
            '1' => '5a5b0f9b7d3f8fc84c3cef8fd8efaaa6c70d75ab',
            '2' => 'b1d5781111d84f7b3fe45a0852e59758cd7a87e5',
            '3' => '310b86e0b62b828562fc91c7be5380a992b2786a',
            '4' => '08a35293e09f508494096c1c1b3819edb9df50db',
            '5' => '450ddec8dd206c2e2ab1aeeaa90e85e51753b8b7',
            '6' => 'fc1200c7a7aa52109d762a9f005b149abef01479',
            '7' => '2c9a62c3748f484690d547c0d707aededf04fbd2',
            '8' => 'f8c024c4ad95bf78baaf9d88334722b84f8a930b',
            '9' => '63843e04b0f7a32d94539cf328ed335d39085a56',
            '10' => 'c28097ad29ab61bfec58d9b4de53bcdec687872e',
            '11' => '339e2ebc99d2a81e7786a466b5cbb9f8b3b81377',
            '12' => '0bad865a02d82f4970687ffe1b80822b76cc0626',
            '13' => '667be543b02294b7624119adc3a725473df39885',
            '14' => '86cf294a07a8aa25f6a2d82a8938f707a2d80ac3',
            '15' => '2019219149608a3f188cafaabd3808aace3e3309',
            '16' => '12b15c8db6c703fe4c7f4f8b71ca4ead06cca8b5',
            '17' => '2d0c8af807ef45ac17cafb2973d866ba8f38caa9',
            '18' => '7331dfb7fe13c8c4d5e68c8ee419edf1a1884911',
            '19' => '6216f8a75fd5bb3d5f22b6f9958cdede3fc086c2',
            '20' => '9e6a55b6b4563e652a23be9d623ca5055c356940',
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
