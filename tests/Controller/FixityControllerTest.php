<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FixityControllerTest_ extends WebTestCase
{
    public function testGetResponseCode()
    {
        $client = static::createClient();
        $client->request('GET', '/api/fixity', array(), array(), array('Resource-ID' => 'foo'));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $client->request('POST', '/api/fixity', array(), array(), array('Resource-ID' => 'foo'));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $client->request('PATCH', '/api/fixity', array(), array(), array('Resource-ID' => 'foo'));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
