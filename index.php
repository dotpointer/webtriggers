<?php

# changelog
# 2021-11-07 02:48:00

require_once('include/functions.php');

start_translations(dirname(__FILE__).'/include/locales/');

check_setup_files();

$actions = get_actions();

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : false;
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : false;

switch ($action) {
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

    # insert packlist relation
    $iu = dbpia($link, array(
      'id_webtriggers' => $id_webtriggers,
      'created' => date('Y-m-d H:i:s')
    ));
    $sql = 'INSERT INTO webtrigger_orders ('.implode(',', array_keys($iu)).') VALUES('.implode(',', $iu).')';
    cl('SQL: '.$sql, VERBOSE_DEBUG_DEEP, false);
    $r_insert = db_query($link, $sql);
    file_put_contents(TRIGGERFILE, time());
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
    <table>
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
        <tr>
          <td><?php echo $action_index !== false ? $actions[$action_index]['name'] : ''; ?></td>
          <td><?php echo t($statuses[$v['status']]); ?></td>
          <td class="extra"><?php echo $v['created']; ?></td>
          <td class="extra"><?php echo $v['started']; ?></td>
          <td class="extra"><?php echo $v['ended']; ?></td>
        </tr>
      </tbody>
<?php
}
?>
    </table>
  </body>
</html>
