<?php
# webtriggers setup fill in this file and rename it to setup.php

#  changelog
#  2021-11-06 18:31:00

# web server username to be granted access to trigger file
define('USERNAME_HTTP', 'www-data');

# database setup, fill this in for web based orders:
define('DATABASE_HOST', 'localhost');
define('DATABASE_USERNAME', 'www');
define('DATABASE_PASSWORD', 'www');

# database setup, fill in database name or set to false to 
# disable web based orders:
define('DATABASE_NAME', 'webtriggers');

# not implemented
# define('DATABASE_TABLES_PREFIX', '' /* 'webtriggers_'*/);

# configuration file - must exist
define('CONFIGFILE', '/etc/dptools/webtriggers');

# log file location, set to false to disable
define('LOGFILE', '/var/log/webtriggers');

# order files path, set to false to disable
define('ORDER_FILES_PATH', false);
#define('ORDER_FILES_PATH', '/tmp/');

# trigger file - must exist
define('TRIGGERFILE', '/var/cache/webtriggers.trigger');

# enable web interface, set to false to disable
define('WEB_ENABLED', true);
?>
