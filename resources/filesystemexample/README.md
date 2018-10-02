# "Filesystem" examples for Riprap plugin development.

The plugins `src/Command/PluginFetchResourceListFromGlob.php`, `src/Command/PluginFetchDigestFromShell.php`, `src/Command/PluginPersistToCsv.php`, and `src/Command/PluginPostCheckSayHello.php` are samples for developers who want to write new plugins. They illustrate how the three requrired types of plugins ("fetchresourcelist", "fetchdigest", and "persist" plugins) and the optional "postcheck" type of plugin function.

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

## Running the plugins

If you want to run Riprap using these plugins, you need to modify `config/services.yaml`:

1. Comment out the following lines by prefixing them with a `#`:

* `app.plugins.fetchresourcelist: ['app:riprap:plugin:fetchresourcelist:from:file']`
* `app.plugins.fetchdigest: 'app:riprap:plugin:fetchdigest:from:fedoraapi'`
* `app.plugins.persist: ['app:riprap:plugin:persist:to:database']`
* `app.plugins.postcheck: ['app:riprap:plugin:postcheck:mailfailures', 'app:riprap:plugin:postcheck:migratefedora3auditlog']`

2. Uncomment all of the lines in the ` ### Sample "filesystem" plugins` section.

When you run the `app:riprap:check_fixity` command, the sample plugins will be used instead of the default plugins.

## Writing plugins

Riprap plugins are Symfony console commmands, and work exactly the same as an ordinary Symfony console command. Unlike most console commands, they are registered in `services.yaml` as plugins and are invoked from within the main `check_fixity` command. Their input is via command options passed in from `check_fixity`, and their output is typically to a log file, but need not be. They can pass data back to `check_fixity` as well, via `$output->writeln()`.