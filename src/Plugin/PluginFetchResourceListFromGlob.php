<?php
// src/Plugin/PluginFetchResourceListFromGlob.php
namespace App\Plugin;

class PluginFetchResourceListFromGlob extends AbstractFetchResourceListPlugin
{
    public function execute()
    {
        $output_resource_records = array();
        if (count($this->settings['resource_dir_paths']) > 0) {
            foreach ($this->settings['resource_dir_paths'] as $dir_path) {
                if (file_exists($dir_path)) {
                    $file_paths = glob($dir_path . '/*.bin');
                    foreach ($file_paths as $file_path) {
                        // This is an array of objects with the properties 'resource_id' and 'last_modified_timestamp'.
                        $resource_record_object = new \stdClass;
                        $resource_record_object->resource_id = $file_path;
                        try {
                            $mtime = exec('stat -c %Y '. escapeshellarg($file_path));
                            $mtime_iso8601 = date(\DateTime::ISO8601, $mtime);
                        } catch (Exception $e) {
                            $this->logger->error(
                                "Fetchresourcelist plugin ran but encountered an error.",
                                array(
                                    'plugin_name' => 'PluginFetchResourceListFromGlob',
                                    'error' => $e->getMessage()  
                                )
                            );
                            return false;
                        }
                        $resource_record_object->last_modified_timestamp = $mtime_iso8601;
                        $output_resource_records[] = $resource_record_object;
                    }
                } else {
                    $this->logger->warning(
                        "Fetchresourcelist plugin ran but encountered an error.",
                        array(
                            'plugin_name' => 'PluginFetchResourceListFromGlob',
                            'error' => "Input path $dir_path doesn't exist."  
                        )
                    );
                    return false;
                }
            }
        } else {
            $this->logger->warning(
                "Fetchresourcelist plugin ran but returned no resources.",
                array(
                    'plugin_name' => 'PluginFetchResourceListFromGlob',
                    'number_of_input_directories' => count($this->settings['resource_dir_paths'])
                )
            );
            return false;
        }

        return $output_resource_records;
    }
}
