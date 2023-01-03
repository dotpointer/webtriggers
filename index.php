<?php

# changelog
# 2021-11-07 02:48:00

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'functions.php');

start_translations(dirname(__FILE__).DIRECTORY_SEPARATOR.'include'.DIRECTORY_SEPARATOR.'locales'.DIRECTORY_SEPARATOR);

check_setup_files();

$actions = get_actions();

$a = isset($_REQUEST['a']) ? $_REQUEST['a'] : false;
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : $a;
$format = isset($_REQUEST['format']) ? $_REQUEST['format'] : false;
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : false;

switch ($action) {
  case 'list':

    $queue = array();
    header('Content-Type: application/json');

    if (strlen(DATABASE_NAME)) {
      $sql = 'SELECT * FROM webtrigger_orders ORDER BY created DESC LIMIT 10';
      $queue = db_query($link, $sql);
    }

    if (ORDER_FILES_PATH !== false && dir(ORDER_FILES_PATH)) {

      $files = scandir(ORDER_FILES_PATH);
      if ($files === false) {
        die(json_encode_formatted(array(
          'status' => false,
          'error' => t('Error, order files path could not be checked:').' '.ORDER_FILES_PATH
        )));
        die(1);
      }

      foreach ($files as $file) {

        # webtriggers.order.id_webtriggers.tmpcode[.status]
        if (strpos($file, 'webtriggers.order.') !== 0) continue;

        $parts = explode('.', $file);
        if (!is_numeric($parts[2]) ) {
          continue;
        }
        $status = STATUS_ERROR_UNKNOWN;
        if (isset($parts[4]) && isset($parts[count($parts) - 1])) {
          $statuspart = $parts[count($parts) - 1];
          foreach ($statuses_files as $sfk => $sfv) {
            if ($sfv === $statuspart) {
              $status = $sfk;
              break;
            }
          }
        }

        array_pop($parts);

        $queue[] = array(
          'id' => -filemtime(ORDER_FILES_PATH.$file),
          'id_webtriggers' => (int)$parts[2],
          'created' => date('Y-m-d H:i:s', filemtime(ORDER_FILES_PATH.$file)),
          'file' => ORDER_FILES_PATH.$file,
          'file_original' => ORDER_FILES_PATH.implode('.', $parts),
          'status' => $status
        );
      }
      usort($queue, 'compare_orders');
    }

    foreach ($queue as $rowindex => $row) {
      # to int
      foreach (array('id', 'id_webtriggers', 'returncode', 'status') as $columnname) {
        if (isset($row[$columnname])) {
          $queue[$rowindex][$columnname] = (int)$row[$columnname];
        }
      }
    }
    $queue = array_reverse($queue);
    $queue = array_slice($queue, 0, 10);
    $queue = array_reverse($queue);


    die(json_encode_formatted(array(
      'status' => true,
      'data' => $queue
    )));

    break;
  case 'abort':
    if (!strlen(DATABASE_NAME)) {
      if ($format === 'json') {
        die(json_encode_formatted(array(
          'status' => false
        )));
      }
      die();
    }

    $id_webtriggers = false;
    $action_index = false;
    foreach ($actions as $k => $v) {
      if ((int)$v['id'] === $id) {
        $id_webtriggers = $id;
        $action_index = $k;
        break;
      }
    }

    if ($id_webtriggers === false) {
      header('Content-Type: text/plain');
      cl('Error, action with id '.$id.' not found in configuration file.', VERBOSE_ERROR, false);
      die(1);
    }

    $iu = dbpua($link, array(
      'status' => STATUS_ABORTED,
      'started' => date('Y-m-d H:i:s'),
      'ended' => date('Y-m-d H:i:s')
    ));
    $sql = '
      UPDATE webtrigger_orders
      SET '.implode($iu, ', ').'
      WHERE id="'.dbres($link, $id_webtriggers).' AND status="'.dbres($link, STATUS_QUEUED).'"
    ';
    cl('SQL: '.$sql, VERBOSE_DEBUG_DEEP, false);
    $r_insert = db_query($link, $sql);
    file_put_contents(TRIGGERFILE, time());

    if ($format === 'json') {
      die(json_encode_formatted(array(
        'status' => true
      )));
    }
    break;
  case 'trigger':

    if (!strlen(DATABASE_NAME)) {
      if ($format === 'json') {
        die(json_encode_formatted(array(
          'status' => false
        )));
      }
      die();
    }

    $id_webtriggers = false;
    $action_index = false;
    foreach ($actions as $k => $v) {
      if ((int)$v['id'] === $id) {
        $id_webtriggers = $id;
        $action_index = $k;
        break;
      }
    }

    if ($id_webtriggers === false) {
      header('Content-Type: text/plain');
      cl('Error, action with id '.$id.' not found in configuration file.', VERBOSE_ERROR, false);
      die(1);
    }

    $iu = dbpia($link, array(
      'id_webtriggers' => $id_webtriggers,
      'created' => date('Y-m-d H:i:s')
    ));
    $sql = 'INSERT INTO webtrigger_orders ('.implode(',', array_keys($iu)).') VALUES('.implode(',', $iu).')';
    cl('SQL: '.$sql, VERBOSE_DEBUG_DEEP, false);
    $r_insert = db_query($link, $sql);
    file_put_contents(TRIGGERFILE, time());

    if ($format === 'json') {
      die(json_encode_formatted(array(
        'status' => true
      )));
    }
    break;
}

?><!DOCTYPE html>
<html>
  <head>
    <title>Webtriggers</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="stylesheet" href="include/style.css" type="text/css" media="screen"/>
    <script src="include/jquery-3.6.1.min.js"></script>
    <script>
      window.wt = window.wt == null ? {} : window.wt;
      window.wt.actions = <?php echo json_encode_formatted($actions); ?>;
      window.wt.msg = <?php echo json_encode_formatted(get_translation_texts(), true); ?>;
      window.wt.statuses = <?php echo json_encode_formatted($statuses); ?>;
      window.wt.timeouts = {
        list: null
      };
    </script>
    <script src="include/load.js"></script>
  </head>
  <body>
    <h1><a href="?">Webtriggers</a></h1>
<?php

if (WEB_ENABLED) {

  switch ($action) {
    case 'trigger':
?>
  <p><?php echo t('Queued action').' '.$actions[$action_index]['name'] ?>.</p>
<?php
      break;
  }
?>
    <table>
      <caption><?php echo t('Actions') ?></caption>
      <thead>
        <tr class="header">
            <th><?php echo t('Name') ?></th>
            <th><?php echo t('Manage') ?></th>
        </tr>
      </thead>
      <tbody>
<?php
  foreach ($actions as $k => $action) {
?>
        <tr>
          <td><?php echo $action['name'] ?></td>
          <td>
            <form action="?" method="get">
              <input type="hidden" name="action" value="trigger">
              <input type="hidden" name="id" value="<?php echo $action['id']?>">
              <button><?php echo t('Run') ?></button>
            </form>
          </td>
        </tr>
      </tbody>
<?php
  }
?>
    </table>
    <br>
    <table id="queue">
      <caption><?php echo t('Queue - latest') ?></caption>
      <thead>
        <tr class="header">
            <th><?php echo t('Name')?></th>
            <th><?php echo t('Status')?></th>
            <th class="extra"><?php echo t('Created')?></th>
            <th class="extra"><?php echo t('Start')?></th>
            <th class="extra"><?php echo t('End')?></th>
            <th><?php echo t('Actions')?></th>
        </tr>
      </thead>
      <tbody>
      </tbody>
    </table>
<?php
} else {
?>
    <p>Web interface is disabled.</p>
<?php
}
?>
  </body>
</html>
