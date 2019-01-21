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

One or more of each of these types of plugins are registered in a Riprap configuration file, the location of which is passed to the `check_fixity` command in its `--settings` option.. Specifically, a configuration may contain more than one "fetchresourcelist" and more than one "postcheck" plugin; in contrast, only one "fetchdigest" and one "persist" plugin can be registered. Multiple plugins are registered in a YAML list value (e.g., `['foo', 'bar']`) and single plugins are registered in a YAML string value (`baz`).

The ability to use multiple "fetchresourcelist" plugins means that Riprap can simultaneously audit the fixity of resources managed by multiple platforms. Running multiple "postcheck" plugins means that Riprap can react in various ways to event failures, e.g., log the failure and also email an administrator.

If your fixity auditing strategy includes workflows that would require executing multiple "persist" or "fetchdigest" plugins, it might be possible to achieve your goals using custom "postcheck" plugins (which can in fact persit events since they have access to the current Doctrine `entityManager`), or to run Riprap multiple times as part of the same scheduled job, each with a different configuration file.

Additional configuration parameters that apply to registered plugins, and general configuration options, are also indicated in a configuration file. Refer to the walkthrough in "The sample CSV configuration" section of Riprap's README file for more information on how configuration files work.

## Plugins API

The abstract classes in `src/Plugin` document the base classes for each of the four types of plugins. This is a summary of that documentation.

All plugins have access, through their parent class's constructors, to `$this->settings`, which is a flat associative array of all the  values from the configuration file, and to `$this->logger`, which is the Monolog logger object instantiated in the `check_fixity` command. "persist" and "postcheck" plugins also have access to `$this->entityManager`, the Doctrine entity manager instantiated in `check_fixity`.

### Required methods

Plugins are called from several places within the `check_fixity` console command, and are loaded dynamically based on their presence in the configuration file. Each plugin class file must contain an `execute()` method, but the parameters this method takes vary from plugin to plugin. The only plugin type that does not require an `execute()` method is "persist" plugins, which contain three different methods, `getReferenceEvent()`, `persistEvent()`, and `getEvents()`.

### Return values

Return values for each of the required methods are documented in the abstract classes. Plugins should return `false` if they encountered an error or exception, and logging of those errors should happen prior to returning `false`. 

## Writing and deploying your own plugins

To create your own plugin, all you need to do is extend the relevant abstract class in your own class file. Putting the file in the `src/Plugin` directory will place it within the `App\Plugin` namespace. To register your plugin in the configuration file, simply add it to the relevant `plugins.` option. If your plugin requires static configuration settings, you can call its settings whatever you want, as long as you don't reuse an existing YAML key as the setting name.