#!/bin/sh
## Cron script to update Mesamatrix.

set -e

base_dir=$(dirname "$0")

cd "$base_dir/.."
./mesamatrixctl fetch
./mesamatrixctl parse
