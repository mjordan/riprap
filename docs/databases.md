Riprap uses Doctrine ORM, Symfony's standard database abstraction layer. Below are instructions for creating (and optionally populating with sample data) databases using SQLite, MySQL, and PostgreSQL.

### SQLite

To create the database that Riprap persists fixity event data into, follow these instructions from within the `riprap` directory:

1. Edit .env so that this line is uncommented: `DATABASE_URL=sqlite:///%kernel.project_dir%/var/data.db` and the other lines starting with `DATABASE_URL` are commented out.
1. `rm var/data.db` (might not exist)
1. `rm src/Migrations/*` (might be empty)
1. `php bin/console -n make:migration`
1. `php bin/console -n doctrine:migrations:migrate`
1. Optional: When you run the `check_fixity` command as described below, it will create events based on the fixity checks. If you want to populate the database with some sample fixity events prior to running `check_fixity` (you don't need to), run `php bin/console -n doctrine:fixtures:load`

### MySQL

In `config/packages/doctrine.yaml`, make sure you have:

```
doctrine:
    dbal:
        driver: 'pdo_mysql'
        server_version: '5.7' # Or whatever version you are running.
        charset: utf8mb4
        default_table_options:
            charset: utf8mb4
            collate: utf8mb4_unicode_ci
```
Then follow these instructions from within the `riprap` directory:

1. Create a MySQL user with `create` privileges
1. Edit .env so that this line contains the user, password, and database name you want: `DATABASE_URL=mysql://user:password@127.0.0.1:3306/riprap` and the other lines starting with DATABASE_URL are commented out.
1. `rm src/Migrations/*` (might be empty)
1. `php bin/console doctrine:database:create`
1. `php bin/console -n make:migration`
1. `php bin/console -n doctrine:migrations:migrate`
1. Optional: When you run the `check_fixity` command as described below, it will create events based on the fixity checks. If you want to populate the database with some sample fixity events prior to running `check_fixity` (you don't need to), run `php bin/console -n doctrine:fixtures:load`

###	 PostgreSQL

In `config/packages/doctrine.yaml`, make sure you have:

```
doctrine:
    dbal:
        driver: 'pdo_pgsql'
        charset: utf8
```
Then follow these instructions from within the `riprap` directory:

1. Create a PostgreSQL user with 'createdb' privileges
1. Edit .env so that this line contains the user, password, and database name you want: `DATABASE_URL=pgsql://user:password@127.0.0.1:5432/riprap` and the other lines starting with DATABASE_URL are commented out.
1. `rm src/Migrations/*` (might be empty)
1. `php bin/console doctrine:database:create`
1. `php bin/console -n make:migration`
1. `php bin/console -n doctrine:migrations:migrate`
1. Optional: When you run the `check_fixity` command as described below, it will create events based on the fixity checks. If you want to populate the database with some sample fixity events prior to running `check_fixity` (you don't need to), run `php bin/console -n doctrine:fixtures:load`