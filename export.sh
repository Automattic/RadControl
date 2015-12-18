#!/usr/bin/env bash

usage="Usage: ./export.sh <dest dir>"
if [ ! -f "adcontrol.php" ]; then
    echo "Could not find adcontrol.php, are you sure this is the right source directory?"
    exit
fi

dest="$1"
if [ $# -eq 0 ]; then
    echo "No arguments supplied"
    echo $usage
    exit
fi

if [ ! -d "$dest" ]; then
    echo "$dest is not a valid directory"
    echo $usage
    exit
fi

cp adcontrol.php $dest
cp readme.txt $dest
cp -R css $dest
cp -R js $dest
cp -R languages $dest
cp -R php $dest

echo "Successfully exported to $dest"
