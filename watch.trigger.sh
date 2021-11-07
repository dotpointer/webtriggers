#!/bin/bash

# watch the trigger file for changes and if so start the runner
# add this file to /etc/rc.local

# changelog
# 2021-11-07 02:53:00

# start the watcher
include/onchange.sh /var/cache/webtriggers.trigger "/usr/bin/php /develop/webtriggers/runner.php" &
