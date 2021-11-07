# Webtriggers

A interface to start system programs from the web as another user.

Made by dotpointer in PHP, MySQL, HTML and CSS.

## How it works

The user visits a web page (index.php), clicks on the desired action
to trigger. The web page inserts an order to the database (webtriggers)
with the action and updates the trigger file (var/cache/webtriggers.trigger).

A file system watcher (onchange.sh) notes that the trigger file has changed
and starts the runner file (runner.php). The runner file retrieves one order
at a time and runs the action for that order.

## List of files

/etc/dptools/webtriggers - Configuration file with actions
/var/cache/webtriggers.trigger - Trigger file to to be updated by web page
/var/log/webtriggers - Log file
include/base3.php, functions.php - Helper functionality
include/database.sql - Database schema
include/onchange.sh - File system watcher
include/setup-example.php - Example file of database setup
include/setup.php - Example file of database setup
index.php - Web page to be visited by a web browser.
runner.php - Order fetcher and action runner
watch.trigger.sh - File trigger starter

## Getting Started

These instructions will get you a copy of the project up and running on your
local machine for development and testing purposes. See deployment for notes on
how to deploy the project on a live system.

### Prerequisites

What things you need to install the software and how to install them

```
- Debian Linux 9 or similar system
- nginx
- MariaDB (or MySQL)
- PHP
- PHP-FPM
- PHP-MySQLi
```

Setup the nginx web server with PHP-FPM support and MariaDB/MySQL.

In short: apt-get install nginx mariadb-server php-fpm php-mysqli
and then configure nginx, PHP and setup a user in MariaDB.

### Installing

Head to the nginx document root and clone the repository:

```
cd /var/www/html
git clone https://gitlab.com/dotpointer/webtriggers.git
cd webtriggers/
```

Import database structure, located in include/database.sql

Standing in the project root directory login to the database:

```
mariadb/mysql -u <username> -p

```

If you do not have a user for the web server, then login as root and do this to
create the user named www with password www:

```
CREATE USER 'www'@'localhost' IDENTIFIED BY 'www';
```

Then import the database structure and assign a user to it, replace
www with the web server user in the database system:
```
SOURCE include/database.sql
GRANT ALL PRIVILEGES ON webtriggers.* TO 'www'@'localhost';
FLUSH PRIVILEGES;
```

Fill in the database configuration in:

```
include/setup.php.
```

Run this to write a configuration file to /etc/dptools/webtriggers and
trigger file to /var/cache/webtriggers.trigger:

```
php runner.php -vs
```

Open the configuration file at /etc/dptools/webtriggers and setup your actions
in this file.

Add the following to /etc/rc.local or similar file that runs on system start to
start the watcher for the trigger file that is updated by the web interface:

```
/bin/bash /<path-to-project-root-directory>/watch.trigger.sh
```

The watcher calls runner.php which checks the database for orders to run when
the trigger file is updated.

## Usage

Visit the web page at https://<server>/webtriggers/, and click on the buttons
on the desired action to run it.

## Authors

* **Robert Klebe** - *Development* - [dotpointer](https://gitlab.com/dotpointer)

See also the list of
[contributors](https://gitlab.com/dotpointer/webtriggers/contributors)
who participated in this project.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md)
file for details.

Contains dependency files that may be licensed under their own respective
licenses.
