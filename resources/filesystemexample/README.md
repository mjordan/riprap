# "Filesystem" examples for Riprap plugin development.

The plugins in this directory are samples intended for developers who want to write new plugins. They illustrate how the three requrired types of plugins ("fetchresourcelist", "fetchdigest", and "persist" plugins) and the optional "postcheck" type of plugin function.

## Overview

Each of the plugins is covered in more detail below, but in summary, these plugins check the fixity of files within the `resources/filesystemexample/resourcefiles` directory (`file1.bin`, 'file2.bin`, `file3.bin') by calling the Linux command `sha1sum` on them and then persist the outcome of the fixity check events to a CSV file at `/tmp/riprap_sample_persist_plugin.csv`.

## Example plugins

### fetchresourcelist

Every time we run Riprap, the `app:riprap:plugin:fetchresourcelist:from:glob` plugin lists all files in `resources/filesystemexample/resourcefiles` by performing a simple PHP `glob()` to get all the files ending in `.bin`. If you add more files to that directory, they will have their fixity checked.

### fetchdigest

For each resource (file) in the list produced by the `app:riprap:plugin:fetchresourcelist:from:glob` plugin, the `app:riprap:plugin:fetchdigest:from:shell` plugin calls the shell command `sha1sum` resource files we want checked.

### persist

For each resource Riprap checks, the `app:riprap:plugin:persist:to:csv` plugin saves the results of the fixity check to a CSV file located at `/tmp/riprap_filesystemexample.csv`.

### postcheck

For each resource Riprap checks, the `app:riprap:plugin:postcheck:sayhi` says "Hello". Not very exiting but friendly!

## Writing plugins

Riprap plugins are Symfony console commmands, and work exactly the same as an ordinary Symfony console command. Unlike most console commands, they are registered in `services.yaml` as plugins and are invoked from within the main `check_fixity` command. Their input is via command options passed in from `check_fixity`, and their output is typically to a log file, but need not be. They can pass data back to `check_fixity` as well, via `$output->writeln()`.

## Running the plugins

[instructions to follow]

