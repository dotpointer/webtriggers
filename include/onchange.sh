#!/bin/bash
#
# Watch current directory (recursively) for file changes, and execute
# a command when a file or directory is created, modified or deleted.
#
# Written by: Senko Rasic <senko.rasic@dobarkod.hr>
#
# Requires Linux, bash and inotifywait (from inotify-tools package).
#
# To avoid executing the command multiple times when a sequence of
# events happen, the script waits one second after the change - if
# more changes happen, the timeout is extended by a second again.
#
# Installation:
#     chmod a+rx onchange.sh
#     sudo cp onchange.sh /usr/local/bin
#
# Example use - rsync local changes to the remote server:
#
#    onchange.sh rsync -avt . host:/remote/dir
#
# Released to Public Domain. Use it as you like.
#

# Modified by Robert Klebe
#
# changelog
# 2014-10-04
# 2016-06-06 10:54:00
# 2021-11-07 02:56:00

# act on these events
EVENTS="CREATE,CLOSE_WRITE,DELETE,MODIFY,MOVED_FROM,MOVED_TO"

# no directory?
if [ -z "$1" ]; then
    echo "Usage: $0 directory command";
    exit -1;
fi

# no command?
if [ -z "$2" ]; then
   echo "Usage: $0 directory command";
    exit -2;
fi

EXCLUDE=""
# exclude?
if [ ! -z "$3" ]; then
	EXCLUDE="--exclude $3"
fi

inotifywait -e "$EVENTS" -m -r --format '%:e %f' $EXCLUDE $1 | (
    WAITING="";
    while true; do
        LINE="";
        read -t 1 LINE;
        if test -z "$LINE"; then
            if test ! -z "$WAITING"; then
                    echo "CHANGE";
                    WAITING="";
            fi;
        else
            WAITING=1;
        fi;
    done) | (
    while true; do
        read TMP;
        # echo $@
        # $@
	# echo $2
	$2
    done
)
