<?php
// src/DataFixtures/AppFixtures.php
namespace App\DataFixtures;

use App\Entity\Event;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $data = array(
            '1' => array('uuid' => '2a40d01e-d0fc-49c0-8755-990c90e21f13', 'SHA-1' => '5a5b0f9b7d3f8fc84c3cef8fd8efaaa6c70d75ab'),
            '2' => array('uuid' => '27099e67-e355-4308-b618-e880900ee16a', 'SHA-1' => 'b1d5781111d84f7b3fe45a0852e59758cd7a87e5'),
            '3' => array('uuid' => 'b64d7dac-db2d-4984-b72e-46f6f33d1d0a', 'SHA-1' => '310b86e0b62b828562fc91c7be5380a992b2786a'),
            '4' => array('uuid' => 'f1ff2644-6f6d-4765-84ee-ae2e6ea85b1b', 'SHA-1' => '08a35293e09f508494096c1c1b3819edb9df50db'),
            '5' => array('uuid' => '59d47475-3c47-412e-a94a-dc5356e9ec14', 'SHA-1' => '450ddec8dd206c2e2ab1aeeaa90e85e51753b8b7'),
        );

        for ($i = 1; $i <= 5; $i++) {
            $event = new Event();
            $event->setEventUuid($data[$i]['uuid']);
            $event->setEventType('verification');
            $event->setResourceId('http://localhost:8000/resource/' . $i);
            $event->setDatestamp(\DateTime::createFromFormat("Y-m-d H:i:s", '2018-09-19 05:23:20'));
            $event->setHashAlgorithm('sha1');
            $event->setHashValue($data[$i]['SHA-1']);
            $event->setEventOutcome('success');

            $manager->persist($event);
        }
        $manager->flush();
    }
}
