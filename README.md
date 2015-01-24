# About

Mesamatrix is a PHP application that parses information from the Mesa Git
repository and formats it in HTML.

Official website: http://mesamatrix.net/

# Installation

## Prerequisites

Mesamatrix requires the following software:

 * Git
 * PHP 5.3.0 or higher

If you are installing from Git, you need to initialise 3rd party code
libraries:

    $ git submodule init
    $ git submodule update

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
    $ ./mesamatrixctl parse

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

# Update Mesa information

To update the information available to Mesamatrix, the following commands need
to be run to fetch new commits and regenerate the XML file:

    $ ./mesamatrixctl fetch
    $ ./mesamatrixctl parse

These commands can be put into a crontab or similar scheduling facility, for
automated operation of your Mesamatrix installation.

# License

Mesamatrix is available under the AGPLv3, a copy of which is available in
LICENSE.

### 3rd Party code libraries

jQuery is available under the MIT License.

jQuery Tipsy is available under the MIT License.

PSR Log is available under the MIT License.

Symfony is available under the MIT License.
