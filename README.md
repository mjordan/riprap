# Riprap

A fixity-auditing microservice for [Fedora](https://fedora.info/spec/)-based repositories.

## Overview

Addresses https://github.com/Islandora-CLAW/CLAW/issues/847.

![Overview](docs/images/overview.png)

## Requirements

* PHP 7.1.3 or higher
* [composer](https://getcomposer.org/)
* Optionally, SQLite if you want to generate sample fixity event data.

## Installation

1. Clone this git repository
1. `cd riprap`
1. `php composer.phar install` (or equivalent on your system, e.g., `./composer install`)

We will eventually support deployment via Ansible.

## Configuration

Since this is a Symfony application, you need to configure some things:

* In `.env`, set `DATABASE_URL`, e.g., `DATABASE_URL=sqlite:///%kernel.project_dir%/var/data.db`. We use SQLite for now but will support MySQL/MariaDB and PostgreSQL.
* In `config/services.yaml`, set `app.fixity.host`. You probably don't need to set `app.fixity.method`.
* In `config/packages/{environment}/monolog.yaml`, set the path for the main Monolog handler.
* In `config/packages/security.yaml`, configure access to the REST API (see below).

## Usage


`php [path to riprap]/bin/console app:riprap:check_fixity`

e.g.,

`php /home/mark/Documents/hacking/riprap/bin/console app:riprap:check_fixity`

For ongoing fixity checking, riprap should be run from a cronjob.

## Logging

Riprap will provide a variety of ways to log activity, e.g., email someone if a fixity mismatch is detected.

## Plugins

Riprap has a very basic plugin architecture. Some potential uses:

* Provide plugins to assist in migrating fixity data from legacy sources (e.g., Fedora 3.x repositories)
* Provide plugins that fetch a set of Fedora resource URLs to fixity check (e.g., from the Fedora repository's triplestore, from Drupal, from a CSV file).
* Provide plugins that persist data (e.g., to a RDBMS, to the Fedora repository, etc.)
* Provide plugins that react to a fixity check failure (e.g., email an administrator)

## REST API

Preliminary scaffolding is in place for a simple HTTP REST API, which will allow external applications like Drupal to retrieve fixity validation data on particular Fedora resources and to add new and updated fixity validation data. For example, a `GET` request to:

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

HTTP `POST` and `PUT` are also supported, e.g.:

```
curl -v -X PATCH http://localhost:8000/api/resource/iamupdated
*   Trying 127.0.0.1...
* TCP_NODELAY set
* Connected to localhost (127.0.0.1) port 8000 (#0)
> PATCH /api/resource/iamupdated HTTP/1.1
> Host: localhost:8000
> User-Agent: curl/7.58.0
> Accept: */*
> 
< HTTP/1.1 200 OK
< Host: localhost:8000
< Date: Tue, 18 Sep 2018 04:42:38 -0700
< Connection: close
< X-Powered-By: PHP/7.2.7-0ubuntu0.18.04.2
< Cache-Control: no-cache, private
< Date: Tue, 18 Sep 2018 11:42:38 GMT
< Content-Type: application/json
< 
* Closing connection 0
["updated fixity event for resource iamupdated"]
```

Using Symfony's firewall to provide IP-based access to the API should provide sufficient security.

# Sample Fedora API Specification endpoint

To assist in development and testing, Riprap includes an enpoint that simulates the behaviour described in section [7.2](https://fcrepo.github.io/fcrepo-specification/#persistence-fixity) of the spec. If you start Symfony's test server as described above, this endpoint is available via `GET` or `HEAD` requests at `http://localhost:8000/examplerepository/rest/{id}`, where `{id}` is a number from 1-5. Calls to it should include a `Want-Digest` header with the value `SHA-1`, e.g.:

`curl -v -X HEAD -H 'Want-Digest: SHA-1' http://localhost:8000/examplerepository/rest/2`

If the `{id}` is valid, the response will contain the `Digest` header containing the specified SHA-1 hash:

```
*   Trying 127.0.0.1...
* TCP_NODELAY set
* Connected to localhost (127.0.0.1) port 8000 (#0)
> HEAD /examplerepository/rest/2 HTTP/1.1
> Host: localhost:8000
> User-Agent: curl/7.58.0
> Accept: */*
> Want-Digest: SHA-1
> 
< HTTP/1.1 200 OK
< Host: localhost:8000
< Date: Thu, 20 Sep 2018 05:28:57 -0700
< Connection: close
< X-Powered-By: PHP/7.2.7-0ubuntu0.18.04.2
< Cache-Control: no-cache, private
< Date: Thu, 20 Sep 2018 12:28:57 GMT
< Digest: b1d5781111d84f7b3fe45a0852e59758cd7a87e5
< Content-Type: text/html; charset=UTF-8
< 
* Closing connection 0
```

# Message queue listener

Riprap will also be able to listen to an ActiveMQ queue and generate corresponding fixity events for newly added or updated resources. Not implemented yet.

# Generating sample events

As stated above, for now we use SQLite as our database. You do not need to create or populate the database; it is just a placeholder for now until we integrate it into our tests.

If you would like to generate some sample events, follow these instructions from within the `riprap` directory:

In `.env`, open an editor and add the following line in the `doctrine-bundle` section: `DATABASE_URL=sqlite:///%kernel.project_dir%/var/data.db`. Then run the following commands:

1. `rm src/Migrations/*`
1. `php bin/console -n make:migration`
1. `php bin/console -n doctrine:migrations:migrate`
1. `php bin/console -n doctrine:fixtures:load`

At this point you will have five rows in your database's `event` table. If you query the table you will see the following output:

`sqlite3 var/data.db`

```
SQLite version 3.22.0 2018-01-22 18:45:57
Enter ".help" for usage hints.
sqlite> .headers on
sqlite> select * from event;
id|event_uuid|event_type|resource_id|datestamp|hash_algorithm|hash_value|event_outcome
1|2a40d01e-d0fc-49c0-8755-990c90e21f13|verification|http://localhost:8000/examplerepository/rest/1|2018-09-19 05:23:20|sha1|5a5b0f9b7d3f8fc84c3cef8fd8efaaa6c70d75ab|success
2|27099e67-e355-4308-b618-e880900ee16a|verification|http://localhost:8000/examplerepository/rest/2|2018-09-19 05:23:20|sha1|b1d5781111d84f7b3fe45a0852e59758cd7a87e5|success
3|b64d7dac-db2d-4984-b72e-46f6f33d1d0a|verification|http://localhost:8000/examplerepository/rest/3|2018-09-19 05:23:20|sha1|310b86e0b62b828562fc91c7be5380a992b2786a|success
4|f1ff2644-6f6d-4765-84ee-ae2e6ea85b1b|verification|http://localhost:8000/examplerepository/rest/4|2018-09-19 05:23:20|sha1|08a35293e09f508494096c1c1b3819edb9df50db|success
5|59d47475-3c47-412e-a94a-dc5356e9ec14|verification|http://localhost:8000/examplerepository/rest/5|2018-09-19 05:23:20|sha1|450ddec8dd206c2e2ab1aeeaa90e85e51753b8b7|success
sqlite> 
```

## Running tests

`php bin/phpunit` from within the `riprap` directory.

## Maintainer

Mark Jordan (https://github.com/mjordan)

## License

[MIT](https://opensource.org/licenses/MIT)
