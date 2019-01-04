# Plugins

Riprap is designed so that it can be used to audit the fixity of data stored in a variety of platforms, and so that the results of its fixity checks can be managed in a variety of ways. To meet these goals, Riprap uses plugins to process most of its input and output.

## Types of plugins

Plugins perform the specific work of:

1. getting a list of resources to perform fixity checks on (these are called "fetchresourcelist" plugins)
1. calculating the digest on the resource using a specific digest digest_algorithm (these are called "fetchdigest" plugins)
1. persisting the results of the fixity check to a database or other datastore ("persist" plugins; these also retrieve the digest value from the last fixity check event, and produce lists of events that are passed to the REST interface in response to `GET` requests)
1. performing tasks after the fixity check has been performed and persisted (these are called "postcheck" plugins)

These four classes of plugins are executed, in that order, within the main `app:riprap:check_fixity` console command.

## Registering plugins to work together

One or more of each of these types of plugins are registered in Riprap's configuration file located at `config/services.yaml`, in the following options, e.g.:

```
app.plugins.fetchresourcelist: ['app:riprap:plugin:fetchresourcelist:from:file']
app.plugins.fetchdigest: 'app:riprap:plugin:fetchdigest:from:fedoraapi'
app.plugins.persist: ['app:riprap:plugin:persist:to:database']
app.plugins.postcheck: ['app:riprap:plugin:postcheck:mailfailures', 'app:riprap:plugin:postcheck:migratefedora3auditlog']
```

Additional configuration parameters that apply to registered plugins, and general configuration options, are also registered in `config/services.yaml`, as described below.

## Types of plugin input and output

### Configuration parameters

All plugins can take configuration parameters. These are stored in `config/services.yaml` in sections named after each of the plugins. For example, if the plugin '' is registered in the `app.plugins.fetchresourcelist` configuration parameter:

```
app.plugins.fetchresourcelist: ['app:riprap:plugin:fetchresourcelist:from:drupal']
```
parameters it uses can use the same base name:

```
app.plugins.fetchresourcelist.from.drupal.baseurl: 'http://localhost:8000'
app.plugins.fetchresourcelist.from.drupal.json_authorization_headers: ['Authorization: Basic YWRtaW46aXNsYW5kb3Jh'] # admin:islandora
app.plugins.fetchresourcelist.from.drupal.use_fedora_urls: true
app.plugins.fetchresourcelist.from.drupal.media_auth: ['admin', 'islandora']
app.plugins.fetchresourcelist.from.drupal.content_types: ['islandora_object']
app.plugins.fetchresourcelist.from.drupal.media_tags: ['/taxonomy/term/15']
app.plugins.fetchresourcelist.from.drupal.gemini_endpoint: 'http://localhost:8000/gemini'
```
### InputInterface options

Since Riprap plugins are Symfony console commands, their `execute()` methods take instances of Symfony's `Symfony\Component\Console\Input\InputInterface` (along with instances of `Symfony\Component\Console\Output\OutputInterface`):

```php
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

protected function execute(InputInterface $input, OutputInterface $output)
    {
      // code
    }
```

Specific input options are defined in each plugin's `configure()` method, e.g.:

```php
$this
  ->addOption('timestamp', null, InputOption::VALUE_REQUIRED, 'ISO 8601 date when the fixity check event occured.')
  ->addOption('timestamp_start', null, InputOption::VALUE_OPTIONAL, 'ISO8601 date indicating start of date range in queries.', null)
```

### Output

As illustrated in the code example above, plugins pass values to other plugins, or to controllers, via Symfony's `Symfony\Component\Console\Output\OutputInterface`. Plugins can also write to files or to a database (for example, persist plugins do both, depending on what operation they are performing).

Output is writen from within a plugin's `execute()` method like this:

```php
 $output->writeln($string);
 ```

 Plugins can only output strings using `writeln()`.

## Input options and output variables used within app:riprap:check_fixity

 Within the main `app:riprap:check_fixity` command, the following input options and output variables are used.

### fetchresource plugins
* input
  * none
* output
  * a resource ID

### fetchdigest plugins
* input
  * '--resource_id' => $resource_id
* output
    * a digest (string) or an HTTP response code or a shell exit code (both are integers)

### persist plugins
* input
    * '--operation'
      * "get_last_digest" operation
        * '--resource_id'
        * '--digest_algorithm'
      * "persist_fix_event" operation
        * '--resource_id'
        * '--digest_algorithm'
        * '--event_uuid'
        * '--digest_value'
        * '--outcome'
      * "get_events" operation
        * '--resource_id'
        * '--timestamp_start'
        * '--timestamp_end'           
        * '--offset'
        * '--limit'
        * '--sort'
        * '--outcome'
* output
    * "get_last_digest" operation
      * a digest (string)
    * "get_events" operation
      * a serialized array of events, with keys (string)
    * "persist_fix_event" operation
      * no output other than to file, database

### postcheck plugins
* input
      '--resource_id'
      '--digest_algorithm'
      '--event_uuid'
      '--digest_value'
      '--outcome'
* output
  * none

 Note that plugins may take optional input parameters, but if they do so, they must use preemptive checks to determine whether those options are populated. For example, persist plugins may take optional `--timestamp` or `--timestamp_start`/`--timestamp_end` parameters.
