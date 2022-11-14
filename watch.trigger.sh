#!/bin/bash

# watch the trigger file for changes and if so start the webtriggers
# add this file to /etc/rc.local

# changelog
# 2021-11-07 02:53:00

THIS_SCRIPT_DIR=$(cd $(dirname "${BASH_SOURCE[0]}") && pwd);

TRIGGERFILE=$(${THIS_SCRIPT_DIR}/webtriggers.php -t);

# start the watcher
$THIS_SCRIPT_DIR/include/onchange.sh "$TRIGGERFILE" "/usr/bin/php $THIS_SCRIPT_DIR/webtriggers.php" &
