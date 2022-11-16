<?php

# changelog
# 2021-11-07 02:48:00

session_start();

define('SITE_SHORTNAME', 'webtriggers');

define('STATUS_ERROR_UNKNOWN', -3);
define('STATUS_ERROR_BAD_EXIT_CODE', -2);
define('STATUS_ERROR_TRIGGER_NOT_CONFIGURED', -1);
define('STATUS_QUEUED', 0);
define('STATUS_STARTED', 1);
define('STATUS_ENDED', 2);
define('STATUS_ABORTED', 3);

$statuses = array(
  STATUS_ERROR_UNKNOWN => 'Error, unknown',
  STATUS_ERROR_TRIGGER_NOT_CONFIGURED => 'Error, not found in configuration file',
  STATUS_ERROR_BAD_EXIT_CODE => 'Error, bad exit code',
  STATUS_QUEUED => 'Queued',
  STATUS_STARTED => 'Started',
  STATUS_ENDED => 'Ended',
  STATUS_ABORTED => 'Aborted'
);

$statuses_files = array(
  STATUS_ERROR_UNKNOWN => 'error_unknown',
  STATUS_ERROR_TRIGGER_NOT_CONFIGURED => 'error_no_trigger',
  STATUS_ERROR_BAD_EXIT_CODE => 'error_bad_exit_code',
  STATUS_QUEUED => 'queued',
  STATUS_STARTED => 'started',
  STATUS_ENDED => 'ended',
  STATUS_ABORTED => 'aborted'
);

# verbosity
define('VERBOSE_OFF', 0);               # no info at all
define('VERBOSE_ERROR', 1);             # only errors
define('VERBOSE_INFO', 2);              # above and things that changes
define('VERBOSE_DEBUG', 3);             # above and verbose info
define('VERBOSE_DEBUG_DEEP', 4);                # above and exec outputs

if (!file_exists(dirname(__FILE__).DIRECTORY_SEPARATOR.'setup.php')) {
  header('Content-Type: text/plain');
?>
  Welcome. It appears that the setup file in include/setup.php is not present. Please go to the include
  directory and copy the example file setup-example.php to setup.php. Then edit the setup.php to fit your
  system setup. Thanks for using this software.
<?php
    die();
}

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'setup.php');
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'base3.php');
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'base.translate.php');

$verbosity_cli = VERBOSITY_CLI;

$link = strlen(DATABASE_NAME) ? db_connect() : false;
# mysql_set_charset('utf8', $link);

$errors = array();

if ($link) {
  if (!function_exists('shutdown_function')) {
    # a function to run when the script shutdown
    function shutdown_function($link) {
      if ($link) {
        db_close($link);
      }
    }
    # register a shutdown function
  }
  register_shutdown_function('shutdown_function', $link);
}

function check_setup_files() {

  # check configuration file existence
  if (!file_exists(CONFIGFILE)) {
    if (php_sapi_name() != "cli") {
      header('Content-Type: text/plain');
    }
    cl('Error, configuration file '.CONFIGFILE.' does not exist, please run webtriggers.php -s.', VERBOSE_ERROR);
    die(1);
  }

  # check configuration file ownership and permissions
  if (!file_exists(CONFIGFILE) || fileowner(CONFIGFILE) !== 0 || substr(sprintf('%o', fileperms(CONFIGFILE)), -4) !== '0644') {
    if (php_sapi_name() != "cli") {
      header('Content-Type: text/plain');
    }
    cl('Error, configuration file '.CONFIGFILE.' must be owned by root and set to 644 (rw--r--r--).', VERBOSE_ERROR);
    die(1);
  }

  if (!file_exists(TRIGGERFILE) || !is_writeable(TRIGGERFILE)) {
    if (php_sapi_name() != "cli") {
      header('Content-Type: text/plain');
    }
    cl('Error, trigger file '.TRIGGERFILE.' does not exist or is not writable, please run webtriggers with the -s parameter.', VERBOSE_ERROR);
    die(1);
  }
}

# debug printing
function cl($s, $level=VERBOSE_ERROR, $log_to_logfile=true) {

  global $verbosity_cli;

  # do not log passwords from mountcifs
  $s = preg_replace('/password=\".*\" \"\/\//', 'password="*****" "//', $s);

  # do not log passwords from lftpd
  $s = preg_replace('/\-u .* \-e/', '-u *****,***** -e', $s);

  # find out level of verbosity
  switch ($level) {
    case VERBOSE_ERROR:
      $l = 'E';
      break;
    case VERBOSE_INFO:
      $l = 'I';
      break;
    case VERBOSE_DEBUG:
    case VERBOSE_DEBUG_DEEP:
      $l = 'D';
      break;
  }
  $s = ''.date('Y-m-d H:i:s').' '.$l.' '.$s."\n";

  # is verbosity on and level is enough?
  if ($verbosity_cli && $verbosity_cli >= $level) {
    echo $s;
  }

  # is log level on and level is enough - the try to append to log
  if ($log_to_logfile && VERBOSITY_LOGFILE && VERBOSITY_LOGFILE >= $level && $f = fopen(LOGFILE, 'a')) {
    fwrite($f, $s);
    fclose($f);
  }

  return true;
}

function compare_orders($a, $b) {
  if ($a['created'] == $b['created']) {
    return 0;
  }
  return ($a['created'] < $b['created']) ? -1 : 1;
}

function get_actions() {
  $actions = file_get_contents(CONFIGFILE);

  # remove comments beginning with # in the file
  $actions = explode("\n", $actions);
  foreach ($actions as $k => $v) {
    $actions[$k] = preg_replace('/^\s*\#+.*$/', '',$v);
  }
  $actions = implode("\n", $actions);

  # decode the json
  $actions = json_decode($actions, true);
  if (json_last_error() !== JSON_ERROR_NONE) {
    if (php_sapi_name() != "cli") {
      header('Content-Type: text/plain');
    }
    cl('Error decoding JSON data in settings file '.CONFIGFILE.': '.json_last_error_msg(), VERBOSE_ERROR);
    die(1);
  }
  return $actions;
}

function json_encode_formatted($output) {
  $json_indented_by_4 = json_encode($output, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
  $json_indented_by_2 = preg_replace('/^(  +?)\\1(?=[^ ])/m', '$1', $json_indented_by_4);
  return $json_indented_by_2;
}

function require_root($text) {
  $currentuser = posix_getpwuid(posix_geteuid());
  $currentuser = $currentuser['name'];
  if ($currentuser !== 'root') {
    cl('Root is required to '.$text.'.', VERBOSE_ERROR, false);
    die(1);
  }
}

?>
