<?php

namespace App\Plugin;

use PHPUnit\Framework\TestCase;

class PluginFetchResourceListFromFileTest extends TestCase
{
    public function testPluginFetchResourceListFromFile()
    {
        $settings = array('resource_list_path' => array('resources/csv_file_list.csv'));
        $plugin = new PluginFetchResourceListFromFile($settings, null);
        $records = $plugin->execute();
        $this->assertCount(3, $records);
    }
}
