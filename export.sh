#!/usr/bin/env bash

usage="Usage: ./export.sh <dest dir>"
if [ ! -f "adcontrol.php" ]; then
    echo "Could not find adcontrol.php, are you sure this is the right source directory?"
    exit
fi

stable=$(sed -n 's/Stable tag: \(.*\)$/\1/p' readme.txt)
version=$(sed -n 's/Version: \(.*\)$/\1/p' adcontrol.php)
const=$(sed -n "s/define( 'ADCONTROL_VERSION', '\(.*\)' );/\1/p" adcontrol.php)

if [ "$stable" != "$version" ]; then
	echo "Stable/Version mismatch"
	echo "$stable : $version"
	exit
fi

if [ "$stable" != "$const" ]; then
	echo "Stable/Const mismatch"
	echo "$stable : $const"
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
cp uninstall.php $dest
cp readme.txt $dest
cp -R css $dest
cp -R js $dest
cp -R languages $dest
cp -R php $dest

echo "Successfully exported to $dest"
