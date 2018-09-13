# Riprap

A fixity-auditing microservice for [Fedora](https://fedora.info/spec/)-based repositories.

## Overview

Addresses https://github.com/Islandora-CLAW/CLAW/issues/847.

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
* In `config/packages/security.yaml`, configure access to the REST API (see below).

## Usage

`php [path to riprap]/bin/console app:riprap:check_fixity --fixity_host=http://foo.com`

e.g.,

`php /home/mark/Documents/hacking/riprap/bin/console app:riprap:check_fixity --fixity_host=http://foo.com`

## Logging

Riprap will provide a variety of ways to log activity, e.g., email someone if a fixity mismatch is detected.

## Plugins

Riprap has a very basic plugin architecture. Some potential uses:

* Provide plugins to assist in migrating fixity data from legacy sources (e.g., Fedora 3.x repositories)
* Provide plugins that fetch a set of Fedora resource URLs to fixity check (e.g., from the repository, from Drupal, from a CSV file).
* Provide plugins that persist data (e.g., to a RDBMS, to the Fedora repository, etc.)

## REST API

Preliminary scaffolding is in place for a read-only HTTP REST API, which will allow external applications like Drupal to retrieve fixity validation data on particular Fedora resources. For example, a `GET` request to:

`http://riprap.example.com/api/resource/96ea3c35-d08e-4812-8c9e-cd0d6d1bd839`

would return a list of all fixity events for the Fedora resource `http://fedorarepo.example.net:8080/fcrepo/rest/96/ea/3c/35/96ea3c35-d08e-4812-8c9e-cd0d6d1bd839`.

To see the API in action,

1. run `php bin/console server:start`
1. run `curl -v "http://localhost:8000/api/resource/123456"`

You should get a response like this:

```
*   Trying 127.0.0.1...
* TCP_NODELAY set
* Connected to localhost (127.0.0.1) port 8000 (#0)
> GET /api/resource HTTP/1.1
> Host: localhost:8000
> User-Agent: curl/7.58.0
> Accept: */*
> 
* HTTP 1.0, assume close after body
< HTTP/1.0 200 OK
< Host: localhost:8000
< Date: Fri, 07 Sep 2018 07:01:01 -0700
< Connection: close
< X-Powered-By: PHP/7.2.7-0ubuntu0.18.04.2
< Cache-Control: no-cache, private
< Date: Fri, 07 Sep 2018 14:01:01 GMT
< Content-Type: application/json
< 
* Closing connection 0
["fixity event 1 for resource 123456","fixity event 2 for resource 123456","fixity event 3 for resource 123456"]
```

Using Symfony's firewall to provide IP-based access to the API should provide sufficient security.

## Running tests

`php bin/phpunit` from within the `riprap` directory.

## Maintainer

Mark Jordan (https://github.com/mjordan)

## License

[MIT](https://opensource.org/licenses/MIT)
