#!/bin/sh
## Mesamatrix script that update GL3.txt and get the log.

#set -x

gitpath=$HOME/code/c++/mesa
srcpath=$HOME/code/web/mesamatrix/http/src

cd $gitpath

# Sparse checkout
#git init mesa
#cd mesa
#git remote add -f origin git://anongit.freedesktop.org/mesa/mesa
#git config core.sparsecheckout true
#echo "docs/GL3.txt" >> .git/info/sparse-checkout
#git branch --track master origin/master
#git checkout

git pull #origin master
cp docs/GL3.txt $srcpath/gl3.txt
git log -n 10 --pretty=format:"%H%n  timestamp: %ct%n  author: %an%n  subject: %s%n" docs/GL3.txt > $srcpath/gl3_log.txt
