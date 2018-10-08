<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

use App\Service\FixityEventDetailManager;

class FixityEventDetailManagerTest extends TestCase
{
    public function testFixityEventManagerMethods()
    {
        $params = new ParameterBag(array('app.service.detailmanager.delimiter' => ';'));

        $event_manager = new FixityEventDetailManager($params);
        $event_manager->add('event_detail', 'I am an event detail');
        $event_manager->add('event_detail', 'I am the second event detail');
        $event_manager->add('event_detail', 'I am the third event detail');

        $event_manager->add('event_outcome_detail_note', 'First event outcome detail note');
        $event_manager->add('event_outcome_detail_note', 'Second event outcome detail note');

        $this->assertCount(3, $event_manager->event_details['event_detail']);
        $this->assertCount(2, $event_manager->event_details['event_outcome_detail_note']);

        $details = $event_manager->getDetails();
        $this->assertCount(3, $details['event_detail']);
        $this->assertCount(2, $details['event_outcome_detail_note']);

        $serialized_details = $event_manager->serialize($details);
        $this->assertEquals(
            $serialized_details['event_detail'],
            'I am an event detail;I am the second event detail;I am the third event detail'
        );
        $this->assertEquals(
            $serialized_details['event_outcome_detail_note'],
            'First event outcome detail note;Second event outcome detail note'
        );
    }

    public function testFixityEventManageConfig()
    {
        $params = new ParameterBag(array('app.service.detailmanager.delimiter' => '|'));

        $event_manager = new FixityEventDetailManager($params);
        $event_manager->add('event_detail', 'One');
        $event_manager->add('event_detail', 'Two');
        $event_manager->add('event_detail', 'Three');

        $event_manager->add('event_outcome_detail_note', 'Eight');
        $event_manager->add('event_outcome_detail_note', 'Nine');
        $event_manager->add('event_outcome_detail_note', 'Ten');

        $details = $event_manager->getDetails();
        $serialized_details = $event_manager->serialize($details);
        $this->assertEquals($serialized_details['event_detail'], 'One|Two|Three');
        $this->assertEquals($serialized_details['event_outcome_detail_note'], 'Eight|Nine|Ten');
    }
}
