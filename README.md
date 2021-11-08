# Mesamatrix

Mesamatrix is a PHP application that parses information from a text file in the
Mesa Git repository ([features.txt](https://gitlab.freedesktop.org/mesa/mesa/blob/main/docs/features.txt))
and formats it in HTML.

Official website: https://mesamatrix.net/

# Installation

## Prerequisites

Mesamatrix requires the following software:

  * [Composer](https://getcomposer.org/)
  * [Git](https://git-scm.com)
  * [PHP](https://www.php.net/) 7.4 or higher, with these packages:
    * [php-json](https://www.php.net/manual/book.json.php)
    * [php-xml](https://www.php.net/manual/book.simplexml.php)

## Install steps

Clone the Mesamatrix repository:

```sh
git clone git@github.com:MightyCreak/mesamatrix.git
```

Jump into the directory and install the dependencies with Composer:

```sh
cd mesamatrix
composer install
```

## Configuration (optional)

There is a default config file in [`config/config.default.php`](./config/config.default.php).
It provides default values for the application, but is overridden by the
optional `config/config.php`.

For instance, to change the log level: create a `config/config.php` file and
copy this contents:

```php
<?php

use Monolog\Logger as Log;

$CONFIG = array(
    "info" => array(
        "log_level" => Log::DEBUG,
    ),
);
```

## Initial setup

For the initial setup, run the `mesamatrixctl` tool to clone the Mesa Git
repository and generate the XML file:

```sh
./mesamatrixctl setup
```

## Update Mesa information

Once setup is done, you can run the two commands that are needed to get the
latest information from Mesa:

```sh
./mesamatrixctl fetch
./mesamatrixctl parse
```

These commands can be put into a crontab or similar scheduling facility, for
automated operation of your Mesamatrix installation.

## Set up the web server

### For developers

As a developer, an easy way to spawn up a PHP server is by running this
command:

```sh
php -S 0.0.0.0:8080 -t public
```

### For deployment

In order to deploy Mesamatrix on a server, the web server root must point to
the `public` directory. Be aware not to give access to more than just this
directory.

At this point, you are done! Open your site in a web browser, and hopefully you
will see the matrix of Mesa features!

# CLI tool

The `mesamatrixctl` tool can be used to administer your Mesamatrix
installation. It outputs very little by default, but can become more verbose
when passed `-v`, `-vv` or `-vvv` for normal output, verbose output or debug
output respectively.

Run `./mesamatrixctl list` to see the available commands, or
`./mesamatrixctl help` for more detailed help.

# License

Mesamatrix is available under the AGPLv3, a copy of which is available in the
[`LICENSE`](./LICENSE) file.

### Third-party code libraries

* [jQuery](https://jquery.com/) is available under the MIT License.

* [jQuery Tipsy](http://onehackoranother.com/projects/jquery/tipsy/) is
  available under the MIT License.

* PSR Log is available under the MIT License.

* [Symfony](https://symfony.com/) is available under the MIT License.

### Media files

The Mesamatrix banner image was created by Robin McCorkell, and is licensed
under the Creative Commons Attribution-ShareAlike 4.0 International license.
Go tweak it, and send us your improvements!

The RSS feed icon is freely available from the Mozilla Foundation at
http://www.feedicons.com/

The GitHub 'Fork me' ribbon is available under the MIT license.
