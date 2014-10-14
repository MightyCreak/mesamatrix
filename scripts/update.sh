#!/bin/sh
## Mesamatrix script that update GL3.txt and get the log.

#set -x

# Get script directory
DIR=$(cd "$(dirname "$0")" && pwd)

# git parameters
outputdir="$DIR/../http/src"
gitdir="$outputdir/mesa.git"
gitgl3path="docs/GL3.txt"
gitlog_depth=10
gitlog_format="%H%n  timestamp: %ct%n  author: %an%n  subject: %s%n"

cd $gitdir

git fetch origin master:master
git cat-file blob HEAD:$gitgl3path > $outputdir/gl3.txt
git log -n $gitlog_depth --pretty=format:"$gitlog_format" -- $gitgl3path > $outputdir/gl3_log.txt
