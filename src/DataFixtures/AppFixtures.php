<?php
// src/DataFixtures/AppFixtures.php
namespace App\DataFixtures;

use App\Entity\Event;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

use Ramsey\Uuid\Uuid;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        for ($i = 0; $i < 5; $i++) {
            $event = new Event();

            $uuid4 = Uuid::uuid4();
            $event->setEventUuid($uuid4->toString());
            $event->setEventType('verification');
            // http://localhost:8080/fcrepo/rest/9e/0d/17/00/9e0d1700-2918-4e56-baec-7272262d0fb8
            $event->setResourceId('http://example.net/resource/' . mt_rand(10, 100));
            $event->setDatestamp(\DateTime::createFromFormat("Y-m-d H:i:s", '2018-09-19 05:23:20'));
            $event->setHashAlgorithm('sha1');
            $event->setHashValue(sha1(mt_rand(10, 100)));
            $event->setEventOutcome('success');

            $manager->persist($event);
        }
        $manager->flush();
    }
}
