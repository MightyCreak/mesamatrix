# About

Mesamatrix is a PHP application that parses information from the Mesa Git
repository and formats it in HTML.

Official website: http://mesamatrix.net/

# Installation

## Prerequisites

Mesamatrix requires the following software:

 * Git
 * PHP 5.3.0 or higher

If you are installing from Git, you need to initialise third-party code
libraries with Composer:

    $ php composer.phar install

## Configuration (optional)

There is a default config file in `config/config.default.php`. It provides
default values for the application, but is overridden by `config/config.php`
or any files matching `config/*.config.php`. It is advised to copy the default
configuration to `config/config.php` and perform modifications on that.

**Protip:** You can enable debugging by changing `debug` variable in the `info`
section to `TRUE`.

## Initial setup

For the initial setup, run the `mesamatrixctl` tool to clone the Mesa Git
repository and generate the XML file:

    $ ./mesamatrixctl setup

## Update Mesa information

Once setup is done, you can run the two commands that are needed to get
the latest informations from Mesa:

    $ ./mesamatrixctl fetch
    $ ./mesamatrixctl parse

These commands can be put into a crontab or similar scheduling facility, for
automated operation of your Mesamatrix installation.

## Set up the web interface

Configure your web server to point to the `http` directory. Be aware that if
you give your webserver access to the whole root directory, there are no access
controls preventing anyone from downloading the Mesa Git repository or other
files!

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

Mesamatrix is available under the AGPLv3, a copy of which is available in
LICENSE.

### Third-party code libraries

jQuery is available under the MIT License.

jQuery Tipsy is available under the MIT License.

PSR Log is available under the MIT License.

Symfony is available under the MIT License.

### Media files

The Mesamatrix banner image was created by Robin McCorkell, and is licensed
under the Creative Commons Attribution-ShareAlike 4.0 International license.
Go tweak it, and send us your improvements!

The RSS feed icon is freely available from the Mozilla Foundation at
http://www.feedicons.com/

The GitHub 'Fork me' ribbon is available under the MIT license.
