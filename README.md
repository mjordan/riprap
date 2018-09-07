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

## REST API

Not started yet, but we plan to expose an HTTP REST API that will allow external clients to retrieve fixity validation data on particular resources. For example, a `GET` request to:

`http://riprap.example.com/api/resource/96ea3c35-d08e-4812-8c9e-cd0d6d1bd839`

would return a list of all fixity events for the Fedora resource `http://fedorarepo.example.net:8080/fcrepo/rest/96/ea/3c/35/96ea3c35-d08e-4812-8c9e-cd0d6d1bd839`.

If you want to see the API in action,

1. run `php bin/console server:start`
1. run `curl -v "http://localhost:8000/api/resource"`

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
["fixity event 1","fixity event 2","fixity event 3"]["fixity event 1","fixity event 2","fixity event 3"]
```

## License

[MIT](https://opensource.org/licenses/MIT)
