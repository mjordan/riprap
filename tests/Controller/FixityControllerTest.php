<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FixityControllerTest extends WebTestCase
{
    public function testGetResponseCode()
    {
        $client = static::createClient();
        $client->request('GET', '/api/resource/foo');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
