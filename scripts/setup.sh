#!/bin/sh
## Setup the mesa repository

#set -x

# Get script directory
DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)

# git parameters
gitdepth=6000
giturl="git://anongit.freedesktop.org/mesa/mesa"
gitdir="$DIR/../http/src/mesa.git"

# Get only the .git (don't need to create a local branch)
git clone --bare --depth $gitdepth $giturl $gitdir
