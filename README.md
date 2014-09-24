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
* php

## Setup mesa repository

Follow these steps to create a repository that only gets `docs/GL3.txt`:

    ## Initialize the local mesa repository.
    $ git init mesa
    $ cd mesa
    
    ## Connect to the mesa repository.
    $ git remote add -f origin git://anongit.freedesktop.org/mesa/mesa
    
    ## Activate sparse checkout only for "docs/GL3.txt".
    $ git config core.sparsecheckout true
    $ echo "docs/GL3.txt" >> .git/info/sparse-checkout
    
    ## Select the remote branch and checkout.
    $ git branch --track master origin/master
    $ git checkout

## Setup the web interface

First, make a config file and create a `src` directory:

    $ cp example.config.inc.php config.inc.php
    $ mkdir src

Then, edit the cron script `mesamatrix_update.sh` to verify that `gitpath` and `srcpath` points respectively to the mesa repository and `src` directory we just created above.

## Setup cron

Last step, edit your crontab. Type this command:

    $ crontab -e

And add this line at the end (it will sync the repository every 30 minutes):

    */30 * * * *         /path/to/mesamatrix_update.sh
