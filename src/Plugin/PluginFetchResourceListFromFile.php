<?php
// src/Plugin/PluginFetchResourceListFromFile.php

namespace App\Plugin;

class PluginFetchResourceListFromFile extends AbstractFetchResourceListPlugin
{
    public function execute()
    {
        $output_resource_records = array();
        foreach ($this->settings['resource_list_path'] as $input_file) {
            $input_resource_records = file($input_file, FILE_IGNORE_NEW_LINES);
            foreach ($input_resource_records as $resource_record) {
                if (strlen($resource_record)) {
                    list($uri, $last_modified_timestamp) = explode(',', $resource_record);
                    // This is an array of objects with the properties 'resource_id' and 'last_modified_timestamp'.
                    $resource_record_object = new \stdClass;
                    $resource_record_object->resource_id = $uri;
                    $resource_record_object->last_modified_timestamp = $last_modified_timestamp;
                    $output_resource_records[] = $resource_record_object;
                }
            }
        }

        // $this->logger is null while testing.
        if ($this->logger) {
            $this->logger->info("PluginFetchResourceListFromFile executed");
        }

        return $output_resource_records;
    }
}
