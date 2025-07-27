# Mesamatrix

Mesamatrix is a PHP application that parses information from a text file in the
Mesa Git repository ([features.txt](https://gitlab.freedesktop.org/mesa/mesa/blob/main/docs/features.txt))
and formats it in HTML.

Official website: <https://mesamatrix.net/>

## Installation

### Prerequisites

Mesamatrix requires the following software:

* [Composer](https://getcomposer.org/)
* [Git](https://git-scm.com)
* [PHP](https://www.php.net/) 8.2 or higher, with these packages:
  * [php-json](https://www.php.net/manual/book.json.php)
  * [php-xml](https://www.php.net/manual/book.simplexml.php)

### Install steps

Clone the Mesamatrix repository:

```sh
git clone git@github.com:MightyCreak/mesamatrix.git
```

Jump into the directory and install all the dependencies with Composer:

```sh
cd mesamatrix
composer install
```

### Configuration (optional)

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

### Initial setup

For the initial setup, run the `mesamatrixctl` tool to clone the Mesa Git
repository and generate the XML file:

```sh
./mesamatrixctl setup
```

### Update Mesa information

Once setup is done, you can run the two commands that are needed to get the
latest information from Mesa:

```sh
./mesamatrixctl fetch
./mesamatrixctl parse
```

These commands can be put into a crontab or similar scheduling facility, for
automated operation of your Mesamatrix installation.

### Run the PHP server

As a developer, an easy way to spawn up a PHP server is by running this
command:

```sh
php -S 0.0.0.0:8080 -t public
```

## CLI tool

The `mesamatrixctl` tool can be used to administer your Mesamatrix
installation. It outputs very little by default, but can become more verbose
when passed `-v`, `-vv` or `-vvv` for normal output, verbose output or debug
output respectively.

Run `./mesamatrixctl list` to see the available commands, or
`./mesamatrixctl help` for more detailed help.

## Deploy using a Docker image

Here are the steps to deploy Mesamatrix using a Docker image:

1. Build the image
2. Run the image
3. Initialize Mesamatrix
4. Setup Cron

Note: all these commands also work with `podman`.

### Build the image

To build the Docker image, run this command:

```sh
docker build -t mesamatrix .
```

### Run the image

The following command will run the image in the background (`-d`), expose the
internal port 80 to the port 8080 (`-p`), mount a host directory to the
`private` directory (`-v`), and give it a name (`--name`):

```sh
docker run -d \
  -p 8080:80 \
  -v "/path/to/host/folder:/var/www/html/private/" \
  --name mesamatrix \
  mesamatrix:latest
```

### Initialize Mesamatrix

Now that Mesamatrix is running in the background, run this command line to
initialize the application:

```sh
docker exec -t mesamatrix sh -c "./mesamatrixctl setup && ./mesamatrixctl parse"
```

### Setup cron

Now that the container runs, you probably want it to automatically fetch the
`mesa` repository and parse its commits in order to update the data in your
Mesamatrix website.

There is a script that you can run within the container, like so:

```sh
docker exec -t mesamatrix scripts/cron.sh
```

Now what's left to do is to set up a cron job that will run this script
regularly. On your server, run `sudo crontab -e` and add these lines at the end
of the file:

```cron
# Update Mesamatrix
*/15  *  * * *  docker exec -t mesamatrix scripts/cron.sh > /dev/null
```

## License

Mesamatrix is available under the AGPLv3, a copy of which is available in the
[`LICENSE`](./LICENSE) file.

### Third-party code libraries

* [jQuery](https://jquery.com/) is available under the MIT License.
* [jQuery Tipsy](http://onehackoranother.com/projects/jquery/tipsy/) is
  available under the MIT License.
* PSR Log is available under the MIT License.
* [Fork me on GitHub](https://simonwhitaker.github.io/github-fork-ribbon-css/)
  is available under the MIT License.
* [Symfony](https://symfony.com/) is available under the MIT License.

### Media files

The Mesamatrix banner image was created by Robin McCorkell, and is licensed
under the Creative Commons Attribution-ShareAlike 4.0 International license.
Go tweak it, and send us your improvements!

The RSS feed icon is freely available from the Mozilla Foundation at
<http://www.feedicons.com/>.
