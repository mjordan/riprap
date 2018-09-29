<?php
// src/DataFixtures/AppFixtures.php
namespace App\DataFixtures;

use App\Entity\FixityCheckEvent;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // // phpcs:disable
        $data = array(
            '1' => array('uuid' => '2a40d01e-d0fc-49c0-8755-990c90e21f13', 'SHA-1' => '5a5b0f9b7d3f8fc84c3cef8fd8efaaa6c70d75ab'),
            '2' => array('uuid' => '27099e67-e355-4308-b618-e880900ee16a', 'SHA-1' => 'b1d5781111d84f7b3fe45a0852e59758cd7a87e5'),
            '3' => array('uuid' => 'b64d7dac-db2d-4984-b72e-46f6f33d1d0a', 'SHA-1' => '310b86e0b62b828562fc91c7be5380a992b2786a'),
            '4' => array('uuid' => 'f1ff2644-6f6d-4765-84ee-ae2e6ea85b1b', 'SHA-1' => '08a35293e09f508494096c1c1b3819edb9df50db'),
            '5' => array('uuid' => '59d47475-3c47-412e-a94a-dc5356e9ec14', 'SHA-1' => '450ddec8dd206c2e2ab1aeeaa90e85e51753b8b7'),
            '6' => array('uuid' => 'a8417047-da33-444b-8e3d-653547ab838c', 'SHA-1' => 'fc1200c7a7aa52109d762a9f005b149abef01479'),
            '7' => array('uuid' => 'bf8c1961-a7a2-41d2-a425-4e298e179df9', 'SHA-1' => '2c9a62c3748f484690d547c0d707aededf04fbd2'),
            '8' => array('uuid' => '79ba3699-661d-4609-9a2b-1053464e8f85', 'SHA-1' => 'f8c024c4ad95bf78baaf9d88334722b84f8a930b'),
            '9' => array('uuid' => '5e27d1c8-2bf2-403f-b11e-dfa81e07529c', 'SHA-1' => '63843e04b0f7a32d94539cf328ed335d39085a56'),
            '10' => array('uuid' => '350c8922-27c8-4a31-ab53-2d1eb4eda76e', 'SHA-1' => 'c28097ad29ab61bfec58d9b4de53bcdec687872e'),
            '11' => array('uuid' => 'af60c8d5-7504-4be0-a355-06177855b8b8', 'SHA-1' => '339e2ebc99d2a81e7786a466b5cbb9f8b3b81377'),
            '12' => array('uuid' => '0b048721-b34a-45a3-beca-1c8798787804', 'SHA-1' => '0bad865a02d82f4970687ffe1b80822b76cc0626'),
            '13' => array('uuid' => 'b5ea416e-51f1-4e86-97d7-c8e475bc9289', 'SHA-1' => '667be543b02294b7624119adc3a725473df39885'),
            '14' => array('uuid' => 'c47a8b1b-6ed9-4224-9d8d-77589ada627e', 'SHA-1' => '86cf294a07a8aa25f6a2d82a8938f707a2d80ac3'),
            '15' => array('uuid' => '3b16ac09-db36-407d-b88c-5c1a22161e91', 'SHA-1' => '2019219149608a3f188cafaabd3808aace3e3309'),
            '16' => array('uuid' => '2508cade-289c-4d8c-bee3-f4aad63af82e', 'SHA-1' => '12b15c8db6c703fe4c7f4f8b71ca4ead06cca8b5'),
            '17' => array('uuid' => 'aa888a6b-db14-447f-adbd-948493b154af', 'SHA-1' => '2d0c8af807ef45ac17cafb2973d866ba8f38caa9'),
            '18' => array('uuid' => 'ee3f6eef-630b-4ed6-ac6d-880bf5ad94bd', 'SHA-1' => '7331dfb7fe13c8c4d5e68c8ee419edf1a1884911'),
            '19' => array('uuid' => 'e47dd696-f60d-40c8-8544-07843af86688', 'SHA-1' => '6216f8a75fd5bb3d5f22b6f9958cdede3fc086c2'),
            '20' => array('uuid' => '39fef3f0-9ac7-4c2a-9aa8-44aeb1175d51', 'SHA-1' => '9e6a55b6b4563e652a23be9d623ca5055c356940'),
        );
        // phpcs:enable

        for ($i = 1; $i <= 20; $i++) {
            $event = new FixityCheckEvent();
            $event->setEventUuid($data[$i]['uuid']);
            $event->setEventType('ing');
            $event->setResourceId('http://localhost:8000/mockrepository/rest/' . $i);
            $event->setDatestamp(\DateTime::createFromFormat("Y-m-d H:i:s", '2018-09-19 05:23:20'));
            $event->setHashAlgorithm('SHA-1');
            $event->setHashValue($data[$i]['SHA-1']);
            $event->setEventOutcome('suc');
            $event->setEventDetail('');
            $event->setEventOutcomeDetailNote('');

            $manager->persist($event);
        }
        $manager->flush();
    }
}
