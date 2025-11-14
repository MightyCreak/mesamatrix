# How to contribute

You want to contribute to this project? Well thank you! That's very much
appreciated! 🥰

In this page, we'll see how to setup the project so you can edit it in your IDE.

If you have doubts, questions or suggestions, you can contact us on Matrix
at [#mesamatrix:matrix.org](https://matrix.to/#/%23mesamatrix:matrix.org).

* [Install locally](#install-locally)
  * [Prerequisites](#prerequisites)
  * [Install steps](#install-steps)
  * [Configuration (optional)](#configuration-optional)
  * [Initial setup](#initial-setup)
  * [Update Mesa data](#update-mesa-data)
  * [Run the PHP server](#run-the-php-server)
* [How to use the CLI tool](#how-to-use-the-cli-tool)
  * [`setup` command](#setup-command)
  * [`fetch` command](#fetch-command)
  * [`parse` command](#parse-command)
* [Coding style](#coding-style)
  * [PHP](#php)
  * [Javascript](#javascript)
  * [HTML](#html)
  * [CSS](#css)
* [IDE configuration](#ide-configuration)
  * [VSCode](#vscode)

## Install locally

### Prerequisites

Mesamatrix requires the following software:

* [Composer](https://getcomposer.org/)
* [Git](https://git-scm.com)
* [PHP](https://www.php.net/) 8.3 or higher, with these packages:
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

### Update Mesa data

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

## How to use the CLI tool

The `mesamatrixctl` tool can be used to administer your Mesamatrix
installation. It outputs very little by default, but can become more verbose
when passed `-v`, `-vv` or `-vvv` for normal output, verbose output or debug
output respectively.

Run `./mesamatrixctl list` to see the available commands, or
`./mesamatrixctl help <command_name>` for more detailed help.

### `setup` command

Initializes Mesamatrix: clones the mesa repository. Must be called once.

### `fetch` command

Pulls the latest commits from the Mesa git repository. Call it regularly to
always be up to date.

### `parse` command

Parses the latest commits and generates the XML used by the website.

Options:

* `-f`/`--force`: Force to parse all the commits again
* `-r`/`--regenerate-xml`: Regenerate the XML based on the already parsed commits

## Coding style

Following the coding style of a project is important. It allows to have a
more readable and maintainable code base. It's also useful for code reviews
since there is no preference here, you simply have to follow the guidelines.
And remember, you control your IDE and not the other way around! 😉

### PHP

For PHP the project follows the [PSR-12](https://www.php-fig.org/psr/psr-12/)
coding style (except for the line limit, which was too constraining).

### Javascript

* Always use `var` for your variables
* Use `'` for strings

### HTML

* Use `"` for attributes

### CSS

* Don’t bind your CSS too much to your HTML structure and try to avoid IDs

## IDE configuration

### VSCode

The recommended extensions for this project are in `.vscode/extensions.json`.
You don't have to install them all, it's simply recommended.

List of recommended extensions:

* [EditorConfig for Visual Studio Code](https://marketplace.visualstudio.com/items?itemName=EditorConfig.EditorConfig):
  the base to respect some common standards across the whole project
* Linters:
  * PHP: [phpcs](https://marketplace.visualstudio.com/items?itemName=shevaua.phpcs)
  * Markdown: [markdownlint](https://marketplace.visualstudio.com/items?itemName=DavidAnson.vscode-markdownlint)
  * Shell script: [ShellCheck](https://marketplace.visualstudio.com/items?itemName=timonwong.shellcheck)
* [Code Spell Checker](https://marketplace.visualstudio.com/items?itemName=streetsidesoftware.code-spell-checker):
  spelling checker to check for typos, even in the source code
* [Markdown All in One](https://marketplace.visualstudio.com/items?itemName=yzhang.markdown-all-in-one):
  several extensions for Markdown, especially useful for the table of contents
  generation
