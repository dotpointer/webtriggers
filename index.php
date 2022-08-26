<?php

# changelog
# 2021-11-07 02:48:00

require_once('include/functions.php');

start_translations(dirname(__FILE__).'/include/locales/');

check_setup_files();

$actions = get_actions();

$a = isset($_REQUEST['a']) ? $_REQUEST['a'] : false;
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : $a;
$format = isset($_REQUEST['format']) ? $_REQUEST['format'] : false;
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : false;

switch ($action) {
  case 'list':

    header('Content-Type: application/json');
    $sql = 'SELECT * FROM webtrigger_orders ORDER BY created DESC LIMIT 10';
    $queue = db_query($link, $sql);

    foreach ($queue as $rowindex => $row) {
      # to int
      foreach (array('id', 'id_webtriggers', 'returncode', 'status') as $columnname) {
        $queue[$rowindex][$columnname] = (int)$row[$columnname];
      }
    }

    $queue = array_reverse($queue);

    die(json_encode(array(
      'status' => true,
      'data' => $queue
    )));

    break;
  case 'trigger':

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
      die(json_encode(array(
        'status' => true
      )));
    }
    break;
}

$sql = 'SELECT * FROM webtrigger_orders ORDER BY created DESC LIMIT 10';
$queue = db_query($link, $sql);

?><!DOCTYPE html>
<html>
  <head>
    <title>Webtriggers</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="stylesheet" href="include/style.css" type="text/css" media="screen"/>
    <script src="include/jquery-3.6.1.min.js"></script>
    <script>
      window.wt = window.wt == null ? {} : window.wt;
      window.wt.actions = <?php echo json_encode($actions); ?>;
      window.wt.msg = <?php echo json_encode(get_translation_texts(), true); ?>;
      window.wt.statuses = <?php echo json_encode($statuses); ?>;
      window.wt.timeouts = {
        list: null
      };
    </script>
    <script src="include/load.js"></script>
  </head>
  <body>
    <h1><a href="?">Webtriggers</a></h1>
<?php
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
      <caption><?php echo t('Queue - latest').' '.count($queue) ?></caption>
      <thead>
        <tr class="header">
            <th><?php echo t('Name')?></th>
            <th><?php echo t('Status')?></th>
            <th class="extra"><?php echo t('Created')?></th>
            <th class="extra"><?php echo t('Start')?></th>
            <th class="extra"><?php echo t('End')?></th>
        </tr>
      </thead>
      <tbody>
<?php
foreach ($queue as $k => $v) {
  $action_index = false;
  foreach ($actions as $ak => $av) {
    if ((int)$av['id'] === (int)$v['id_webtriggers']) {
      $action_index = $ak;
      break;
    }
  }
?>
        <tr id="queuerow<?php echo $v['id']?>">
          <td class="actionname"><?php echo $action_index !== false ? $actions[$action_index]['name'] : ''; ?></td>
          <td class="status"><?php
            echo t($statuses[$v['status']]);
            if ((int)$v['status'] < 0) {
              ?><br><?php
              echo t('Return code').': '.$v['returncode'];
              ?><br><?php
              echo t('Output').': '.$v['output'];
            }
        ?></td>
          <td class="created extra"><?php echo $v['created']; ?></td>
          <td class="started extra"><?php echo $v['started']; ?></td>
          <td class="ended extra"><?php echo $v['ended']; ?></td>
        </tr>
<?php
}
?>
      </tbody>
    </table>
  </body>
</html>
