#!/bin/sh
## Setup the mesa repository

#set -x

# Get script directory
DIR=$(cd "$(dirname "$0")" && pwd)

# git parameters
outputdir="$DIR/../http/src"
gitdir="$outputdir/mesa.git"
gitdepth=6000
giturl="git://anongit.freedesktop.org/mesa/mesa"

# Get only the .git (don't need to create a local branch)
mkdir -p $outputdir
git clone --bare --depth $gitdepth $giturl $gitdir
