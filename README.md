# Riprap

A fixity-checking service for Fedora-based repositories.

## Overview

A microservice that addresses https://github.com/Islandora-CLAW/CLAW/issues/847.

## Requirements

* PHP 7.1.3 or higher
* [composer](https://getcomposer.org/)

## Installation

1. Clone this git repository
1. `cd riprap`
1. `php composer.phar install` (or equivalent on your system, e.g., `./composer install`)

We will eventually support deployment via Ansible.

## Configuration

Since this is a Symfony application, you need to configure some things:

* In `.env`, set `DATABASE_URL`. We use SQLite for now but will support MySQL/MariaDB and PostgreSQL.
* In `config/services.yaml`, set `app.fixity.host`. You probably don't need to set `app.fixity.method`.
* In `config/packages/{environment}/monolog.yaml`, set the path for the main Monolog handler.

## Usage

`php [path to riprap]/bin/console app:riprap:check_fixity`

e.g.,

`php /home/mark/Documents/hacking/riprap/bin/console app:riprap:check_fixity`

## Plugins

We have a very basic plugin architecture. Some ideas:

* Make a set of plugins to assist in migrating fixity data from legacy sources.
* Make a set of plugins that persist data (e.g., to a RDBMS, to the Fedora repository, etc.)
* Make a set of plugins that fetch a set of Fedora resource URLs to fixity check.

## License

[MIT](https://opensource.org/licenses/MIT)
