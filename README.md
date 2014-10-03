# About

Mesamatrix is a PHP script that parse the text file from the mesa git tree and format it in HTML.

Here is the official website: http://mesamatrix.net/

# Installation

Three steps are required to install mesamatrix on your server.

## Prerequisites

In order to install this project, you'll also need these:
* git
* cron
* apache or nginx
* php 5

## Setup mesa repository

Because the scripts just need to get the content of the repository and they don't need to have a branch of their own, the `setup.sh` script will create a *bare* clone (which is basically only the content of the `.git` directory).

If you don't want the repository directory to be in `/http/src/mesa.git`, edit the `outputdir` and `gitdir` variables in both `setup.sh` and `update.sh`.

And to set up mesamatrix, simply run from the project base directory:

    $ ./scripts/setup.sh

## Setup the web interface

Create your config file:

    $ cp example.config.inc.php config.inc.php

If you've changed the output directory in the scripts, change it also in your `conifg.inc.php` for the `gl3_file` and `log_file` entries in the `info` section.

## Setup cron

Last step, edit your crontab. Type this command:

    $ crontab -e

And add this line at the end (it will sync the repository every 30 minutes):

    */30 * * * *         /path/to/scripts/update.sh

## License

MesaMatrix is available under the GPLv3, a copy of which is available in
LICENSE.

jQuery is available under the MIT License.

jQuery Tipsy is available under the MIT License.
