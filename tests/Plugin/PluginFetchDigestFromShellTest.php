<?php

namespace App\Plugin;

use PHPUnit\Framework\TestCase;

class PluginFetchDigestFromShellTest extends TestCase
{
    protected function setUp()
    {
        $this->sample_resource_id = 'var/PlugiFetchDigestFromShellTest.test.data';
        if (file_exists($this->sample_resource_id)) {
            unlink($this->sample_resource_id);
        }
        file_put_contents($this->sample_resource_id, "ijsdflksdijsdljwwuw999wqk");
    }

    public function testPluginFetchDigestFromShell()
    {
        $settings = array('digest_command' => '/usr/bin/sha1sum');
        $plugin = new PluginFetchDigestFromShell($settings, null);
        $digest = $plugin->execute($this->sample_resource_id);
        $this->assertEquals(
            'a06be9164a8dc334aafe97b04c36f66773a04734',
            $digest,
            'Digest value should have been a06be9164a8dc334aafe97b04c36f66773a04734.'
        );
    }

    protected function tearDown()
    {
        @unlink($this->sample_resource_id);
    }
}
