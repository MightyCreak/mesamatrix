# About

Mesamatrix is a PHP script that parse the text file from the mesa git tree and format it in HTML.

Here is the official website: http://mesamatrix.net/

# Installation

Three steps are required to install Mesamatrix on your server.

## Prerequisites

In order to install this project, you'll also need these:
* git
* cron
* apache or nginx
* php 5

## Copy you config file

There is a default config file in `config/config.default.php`. If you want to have your own configuration, simply copy the file, and then you can edit it:

    $ cp config/config.default.php config/config.php
    
**Protips:** During the set up, you should enable debugging by changing `debug` variable value to `TRUE`, in the `info` section.

## Set up `mesa.git` repository

Because the PHP scripts of Mesamatrix just need to get the content of the repository and they don't need to have a branch of their own, the `tools/setup.php` script will create a *bare* clone (which is basically only the content of the `.git` directory).

To set up Mesamatrix, simply run from the project base directory:

    $ php tools/setup.php

**Protips:** If you don't like the default settings, you can edit your configuration file (`config/config.php`). Everything is in the `git` section. For instance, to change the `mesa.git` directory name, change the `dir` variable value.

## Set up the web interface

Configure your web server to point to the `http` directory (and enable PHP).

Before the final phase, let's test if everything works fine. From the project base directory, run these lines:

    $ php tools/fetch.php       # Fetch the latest commits from mesa.
    $ php tools/parser.php      # Parse GL3.txt, it generates the file `http/gl3.xml`.
                                # It might take some time since it's testing all the URLs.

Now open up Mesamatrix in your browser (something like `http://localhost/mesamatrix/`); it should show the status of the 3D graphics drivers in a nicely formed web page.

## Set up cron

In order to fetch and parse `GL3.txt` file automatically, you'll need to edit your crontab. And for that, you'll need a script that will do both commands sequentially. Create a new file here: `tools/update.sh`, edit it and copy these lines in it:

```sh
#!/bin/sh

php -f /var/www/mesamatrix/tools/fetch.php
php -f /var/www/mesamatrix/tools/parse.php
```

Don't forget to change `/var/www/mesamatrix` to your own base directory.

To edit your crontab, type this command:

    $ crontab -e

And add this line at the end (it will sync the repository every 30 minutes):

    */30 * * * *         /var/www/mesamatrix/tools/update.sh

Once again, don't forget to change `/var/www/mesamatrix` to your own base directory.

# License

Mesamatrix is available under the GPLv3, a copy of which is available in
LICENSE.

jQuery is available under the MIT License.

jQuery Tipsy is available under the MIT License.
