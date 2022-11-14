#!/usr/bin/php
<?php

# changelog
# 2021-11-07 02:48:00

# require cli usage only
if (php_sapi_name() !== 'cli') {
  die();
}

require_once('include/functions.php');

foreach (getopt('hl:r:stv::', array(
  'help', 'list-actions', 'list-orders', 'run:',
  'run-order-file:', 'setup', 'verbose::'
)) as $k => $v) {
  switch ($k) {
    case 'h':
    case 'help':
?>
Usage: <?php echo basename(__FILE__) ?> [parameters]

Parameters:
  -h, --help       Show this information.

  -la,             List actions.
  --list-actions

  -lo,             List orders.
  --list-orders

  -r <action>,     Run action.
  --run <action>

  --run-order-file <action>
                   Run action as an order file placed in order files path.
                   This bypasses the database. Order files path:
<?php
      if (ORDER_FILES_PATH === false) {
?>
                   Disabled.
<?php
      } else {
?>
                   <?php echo ORDER_FILES_PATH ?>.<?php
      }
?>


  -s, --setup      Write configuration to <?php echo CONFIGFILE."\n"?>
                   and trigger file to <?php echo TRIGGERFILE."\n"?>
                   if they do not exist and setup permissions for these
                   files.

  -t               Get trigger file.

  -v[vvv],         Show detailed information, must precede other parameters
  --verbose[vvv]   to take effect as they run in the order they are written,
                   for example to run with -s do -v -s.
<?php
      die();
    case 'l':
    case 'list-actions':
    case 'list-orders':
      if ($k === 'list-actions' || $k === 'l' && $v === 'a') {
        $actions = get_actions();
        echo 'id name action'."\n";
        foreach ($actions as $k => $v) {
          echo $v['id'].' "'.$v['name'].'" "'.$v['action'].'"'."\n";
        }
      }

      if ($k === 'list-orders' || $k === 'l' && $v === 'o') {
        $actions = get_actions();

        $sql = 'SELECT * FROM webtrigger_orders ORDER BY created DESC LIMIT 10';
        $queue = db_query($link, $sql);

        foreach ($queue as $rowindex => $row) {
          # to int
          foreach (array('id', 'id_webtriggers', 'returncode', 'status') as $columnname) {
            $queue[$rowindex][$columnname] = (int)$row[$columnname];
          }
        }

        $queue = array_reverse($queue);
        echo 'id created started ended name status return-code'."\n";
        foreach ($queue as $k => $v) {
          $action_index = false;
          foreach ($actions as $ak => $av) {
            if ((int)$av['id'] === (int)$v['id_webtriggers']) {
              $action_index = $ak;
              break;
            }
          }

          echo $v['id'];
          echo ' '.$v['created'].' '.$v['started'].' '.$v['ended'];
          echo ' "'.($action_index !== false ? $actions[$action_index]['name'] : '').'"';
          echo ' "'.t($statuses[$v['status']]).'"';
          echo ' '.$v['returncode'];
          echo "\n";

          if ((int)$v['status'] < 0) {
            echo ' '.t('Output').': '."\n";
            $outputlines = explode("\n", $v['output']);
            foreach ($outputlines as $outputline) {
              echo '  '.$outputline."\n";
            }
          }
        }
      } # eof list-orders
      die();
    case 'r':
    case 'run':
    case 'run-order-file':

      $actions = get_actions();
      $id_webtriggers = false;
      $action_index = false;
      foreach ($actions as $actionkey => $actiondata) {
        if ((int)$actiondata['id'] === $v || $actiondata['name'] === $v) {
          $id_webtriggers = (int)$actiondata['id'];
          $action_index = $actionkey;
          break;
        }
      }

      if ($id_webtriggers === false) {
        cl('Error, action with id '.$v.' or name '.$v.' not found in configuration file.', VERBOSE_ERROR, false);
        die(1);
      }

      if ($k === 'run-order-file') {
        if (!dir(ORDER_FILES_PATH)) {
          cl('Error, order files path is not a directory: '.ORDER_FILES_PATH, VERBOSE_ERROR, false);
          die(1);
        }

        do {
          $name = ORDER_FILES_PATH.'webtriggers.order.'.$id_webtriggers.'.'.time().'.';
        } while(count(glob($name.'*')));
        $name .= 'queued';
        touch($name);

      } else {
        $iu = dbpia($link, array(
          'id_webtriggers' => $id_webtriggers,
          'created' => date('Y-m-d H:i:s')
        ));
        $sql = 'INSERT INTO webtrigger_orders ('.implode(',', array_keys($iu)).') VALUES('.implode(',', $iu).')';
        cl('SQL: '.$sql, VERBOSE_DEBUG_DEEP, false);
        $r_insert = db_query($link, $sql);
      }
      file_put_contents(TRIGGERFILE, time());

      die();
    case 's':
      cl('Setting upp config file '.CONFIGFILE.' and trigger file '.TRIGGERFILE, VERBOSE_INFO, false);
      # setup trigger file
      touch(TRIGGERFILE);
      chown(TRIGGERFILE, 'root');
      chgrp(TRIGGERFILE, USERNAME_HTTP);
      chmod(TRIGGERFILE, 0666);

      # setup configuration file
      if (!file_exists(CONFIGFILE)) {
        $config_example = array(
          array(
            'id' => 1,
            'name' => 'Write date to /tmp/webtriggers.example',
            'action' => 'date >> /tmp/webtriggers.example'
          ),
          array(
            'id' => 2,
            'name' => 'Write users to /tmp/webtriggers.example',
            'action' => 'users >> /tmp/webtriggers.example'
          )

        );
        $json_indented_by_4 = json_encode($config_example, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
        $json_indented_by_2 = preg_replace('/^(  +?)\\1(?=[^ ])/m', '$1', $json_indented_by_4);

        $output  = '# webtriggers configuration'."\n";
        $output .= '#'."\n";
        $output .= '# id - integer - must be unique for each action'."\n";
        $output .= '# name - string - the action name displayed on the web page'."\n";
        $output .= '# action - string - the program to run'."\n";

        $output = $output.$json_indented_by_2;
        if (!file_exists(dirname(CONFIGFILE))) {
          cl('Creating configuration directory structure '.dirname(CONFIGFILE), VERBOSE_INFO, false);
          if (!mkdir(dirname(CONFIGFILE), 0777, true)) {
            cl('Failed creating configuration file directory '.dirname(CONFIGFILE), VERBOSE_ERROR, false);
            die(1);
          }
        }
        cl('Writing sample configuration to '.CONFIGFILE, VERBOSE_INFO, false);

        if (file_put_contents(CONFIGFILE, $output) === false) {
          cl('Failed creating configuration file at '.CONFIGFILE, VERBOSE_ERROR, false);
          die(1);
        }
        cl('Configuration file example written to '.CONFIGFILE.' please adjust it to your needs', VERBOSE_INFO, false);
      }
      chown(CONFIGFILE, 'root');
      chgrp(CONFIGFILE, 'root');
      chmod(CONFIGFILE, 0644);
      die();
    case 't':
      echo TRIGGERFILE;
      die();
    case 'v': # be verbose
    case 'verbose':
      # determine and set level of verbosity
      switch ($v) {
        default: # error
          if (is_numeric($v)) {
            $verbosity_cli = (int)$v;
          } else {
            $verbosity_cli = VERBOSE_ERROR;
          }
          break;
        case 'v': # error, info
          $verbosity_cli = VERBOSE_INFO;
          break;
        case 'vv': # error, info, debug
          $verbosity_cli = VERBOSE_DEBUG;
          break;
        case 'vvv': # error, info, debug, debug deep
          $verbosity_cli = VERBOSE_DEBUG_DEEP;
          break;
      }
      break;
  }
}

function cmp($a, $b) {
  if ($a['created'] == $b['created']) {
    return 0;
  }
  return ($a['created'] < $b['created']) ? -1 : 1;
}

check_setup_files();

$actions = get_actions();

do {
  cl('Checking for a queued order', VERBOSE_DEBUG);

  # get one order
  $sql = 'SELECT * FROM webtrigger_orders WHERE status='.STATUS_QUEUED.' ORDER BY created LIMIT 1';
  cl('SQL: '.$sql, VERBOSE_DEBUG_DEEP);
  $r = db_query($link, $sql);
  cl('Rows: '.count($r), VERBOSE_DEBUG_DEEP);

  if (ORDER_FILES_PATH !== false && dir(ORDER_FILES_PATH)) {

    $files = scandir(ORDER_FILES_PATH);
    if ($files === false) {
      cl('Error, order files path could not be checked: '.ORDER_FILES_PATH, VERBOSE_ERROR);
      die(1);
    }

    foreach ($files as $file) {

      # webtriggers.order.id_webtriggers.tmpcode[.status]
      if (strpos($file, 'webtriggers.order.') !== 0) continue;

      $parts = explode('.', $file);
      if (!is_numeric($parts[2]) ||
        isset($parts[4]) && $parts[count($parts) - 1] !== 'queued'
      ) {
        continue;
      }

      array_pop($parts);

      $r[] = array(
        'id_webtriggers' => (int)$parts[2],
        'created' => filemtime(ORDER_FILES_PATH.$file),
        'file' => ORDER_FILES_PATH.$file,
        'file_original' => ORDER_FILES_PATH.implode('.', $parts)
      );
    }
    usort($r, "cmp");
  }

  if (count($r)) {

    $file_order = isset($r[0]['file']);

    if ($file_order) {
      $id_orders = 0;
    } else {
      $id_orders = (int)$r[0]['id'];
    }

    $id_webtriggers = (int)$r[0]['id_webtriggers'];

    if ($file_order) {
      cl($r[0]['file_original'].' started', VERBOSE_DEBUG);
    } else {
      cl($id_orders.' started', VERBOSE_DEBUG);
    }

    if ($file_order) {
      $newname = $r[0]['file_original'].'.runs';
      if (!rename($r[0]['file'], $newname)) {
        cl('Failed renaming '.$r[0]['file'].' to '.$newname, VERBOSE_ERROR);
        die(1);
      }
      $r[0]['file'] = $newname;
    }
    # mark order as started
    if (!$file_order) {
      $sql = 'UPDATE webtrigger_orders SET status='.STATUS_STARTED.', started="'.dbres($link, date('Y-m-d H:i:s')).'" WHERE id="'.dbres($link, $id_orders).'"';
      cl($id_orders.' SQL: '.$sql, VERBOSE_DEBUG_DEEP);
      db_query($link, $sql);
    }

    # find action index
    $action_index = false;
    foreach ($actions as $k => $v) {
      if ((int)$v['id'] === $id_webtriggers) {
        $action_index = $k;
        break;
      }
    }

    # check if it does not exist then take next
    if ($action_index === false) {
      if ($file_order) {
        $newname = $r[0]['file_original'].'.error_no_trigger';
        if (!rename($r[0]['file'], $newname)) {
          cl('Failed renaming '.$r[0]['file'].' to '.$newname, VERBOSE_ERROR);
          die(1);
        }
      } else {
        # mark order as nonexistent
        $sql = 'UPDATE webtrigger_orders SET status='.STATUS_ERROR_TRIGGER_NOT_CONFIGURED.' WHERE id="'.dbres($link, $id_orders).'"';
        cl($id_orders.' SQL: '.$sql, VERBOSE_DEBUG_DEEP);
        db_query($link, $sql);
        cl($id_orders.' error, trigger not configured', VERBOSE_ERROR);
      }
      continue;
    }

    # run the action
    if ($file_order) {
      cl($r[0]['file_original'].' run "'.$actions[$action_index]['action'].'"', VERBOSE_INFO);
    } else {
      cl($id_orders.' run "'.$actions[$action_index]['action'].'"', VERBOSE_INFO);
    }

    exec($actions[$action_index]['action'], $o, $returncode);

    if ($file_order) {
      cl($r[0]['file_original'].' end '.$returncode.' "'.implode("\n", $o).'"', VERBOSE_INFO);
    } else {
      cl($id_orders.' end '.$returncode.' "'.implode("\n", $o).'"', VERBOSE_INFO);
    }

    if ($file_order) {
        $newname = $r[0]['file_original'].'.ended';
        if (!rename($r[0]['file'], $newname)) {
          cl('Failed renaming '.$r[0]['file'].' to '.$newname, VERBOSE_ERROR);
          die(1);
        }
        $r[0]['file'] = $newname;

        file_put_contents($r[0]['file'],
          'ended: '.date('Y-m-d H:i:s')."\n".
          'returncode: '.$returncode."\n".
          'status: '. ($returncode === 0 ? STATUS_ENDED : STATUS_ERROR_BAD_EXIT_CODE)."\n".
          'output: '. implode("\n", $o)."\n"
        );
    } else {
      $iu = dbpua($link, array(
        'ended' => date('Y-m-d H:i:s'),
        'output' => implode("\n", $o),
        'returncode' => $returncode,
        'status' => $returncode === 0 ? STATUS_ENDED : STATUS_ERROR_BAD_EXIT_CODE
      ));

      $sql = 'UPDATE webtrigger_orders SET '.implode(',', $iu).' WHERE id="'.dbres($link, $id_orders).'"';
      cl($id_orders.' SQL: '.$sql, VERBOSE_DEBUG_DEEP);
      db_query($link, $sql);
    }
    continue;
  }

  break;

} while(1);

?>
