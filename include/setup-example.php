<?php
# webtriggers setup fill in this file and rename it to setup.php

#  changelog
#  2021-11-06 18:31:00

# web server username to be granted access to trigger file
define('USERNAME_HTTP', 'www-data');

# database setup, fill this in
define('DATABASE_HOST', 'localhost');
define('DATABASE_USERNAME', 'www');
define('DATABASE_PASSWORD', 'www');
define('DATABASE_NAME', 'webtriggers');

# not implemented
# define('DATABASE_TABLES_PREFIX', '' /* 'webtriggers_'*/);

# configuration file - must exist
define('CONFIGFILE', '/etc/dptools/webtriggers');

# log file location, set to false to disable
define('LOGFILE', '/var/log/webtriggers');

# trigger file - must exist
define('TRIGGERFILE', '/var/cache/webtriggers.trigger');
?>
