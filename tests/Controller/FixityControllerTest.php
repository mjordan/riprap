<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FixityControllerTest extends WebTestCase
{
    public function testGetResponseCode()
    {
        $client = static::createClient();
        $client->request('GET', '/api/resource', array(), array(), array('Resource' => 'foo'));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $client->request('POST', '/api/resource', array(), array(), array('Resource' => 'foo'));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $client->request('PATCH', '/api/resource', array(), array(), array('Resource' => 'foo'));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
