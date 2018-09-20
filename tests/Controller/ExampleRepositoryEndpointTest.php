<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ExampleRepositoryEndpointTest extends WebTestCase
{
    public function testResponse()
    {
        $client = static::createClient();
        // Symfony requires "nonstandard headers" to be prefixed with 'HTTP_'
        $client->request('GET', '/examplerepository/rest/2', array(), array(), array('HTTP_Want-Digest' => 'SHA-1'));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
		$this->assertTrue($client->getResponse()->headers->contains('Digest', 'b1d5781111d84f7b3fe45a0852e59758cd7a87e5')); // resource 2

        $client->request('GET', '/examplerepository/rest/12345');
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }
}
