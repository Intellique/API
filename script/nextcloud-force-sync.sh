#! /bin/dash

COMMAND=/var/www/nextcloud/occ
DIRECTORY="$1"

if [ -x "$COMMAND" -a -x "$DIRECTORY" ]; then
	su -s /bin/bash -c "$COMMAND files:scan --path=$DIRECTORY" -- www-data
fi

