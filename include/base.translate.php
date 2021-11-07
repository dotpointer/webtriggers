<?php

# Base translate - common translate functions
# by Robert Klebe, dotpointer

# changelog
# 2018-12-20 17:41:00 - first version

# to get a matching locale translation index, send in locale and get a working translation index in return
function get_working_locale($langs_available, $try_lang = false) {

  $accept_langs = array();

  # no language to try provided?
  if (!$try_lang) {
    # try with header - or if not there, go en
    $try_lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : false;
  }

  # any language to try now?
  if ($try_lang) {
    preg_match_all(
      '/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.\d+))?/i',
      $try_lang,
      $lang_parse
    );

    if (isset($lang_parse[1]) && count($lang_parse[1])) {

      # create a list like 'en-US' => 0.7
      $accept_langs = array_combine($lang_parse[1], $lang_parse[4]);

      # set default to 1 for any without q factor
      foreach ($accept_langs as $k => $v) {
        if ($v === '') {
          $accept_langs[$k] = 1;
        }
      }

      arsort($accept_langs, SORT_NUMERIC);
    }# if match
  } # if-trylang

  # walk the languages - en, sv, es etc...
  foreach (array_keys($accept_langs) as $current_acceptlang) {
    # walk the available languages provided
    foreach ($langs_available as $k => $v) {
      # walk the locales in this provided language
      foreach ($v['locales'] as $k2 => $v2) {
        # compare the language, file xx-XX <-> browser xx-XX
        if (strtolower($v2) === strtolower($current_acceptlang)) {
          return $k;
        }
      }
    }

    $acceptlang_intro = stristr($current_acceptlang, '-') ? substr($current_acceptlang, 0, strpos($current_acceptlang, '-')) : $current_acceptlang;

    foreach ($langs_available as $k => $v) {
      foreach ($v['locales'] as $k2 => $v2) {
        # compare the language, file xx <-> browser xx-XX
        if (strtolower($v2) === strtolower($acceptlang_intro)) {
          return $k;
        }
      }
    }

    foreach ($langs_available as $k => $v) {
      foreach ($v['locales'] as $availlang) {
        # compare the language, file xx <-> browser xx
        if (strtolower($acceptlang_intro) === strtolower(stristr($availlang, '-') ? substr($availlang, 0, strpos($availlang, '-')) : $availlang)) {
          return $k;
        }
      }
    }
  }

  return 0;
}

# to translate string
function t($s) {
  # get translation data and translations
  global $translations;

  # make sure we have the index
  $tindex = isset($translations['current']['index']) ? $translations['current']['index'] : 0;

  # is this language not present
  if (!isset($translations['languages'][$tindex])) {
    # then get out
    return $s;
  }

  foreach ($translations['languages'][$tindex]['content'] as $sentence) {
    if (
      # are all parts there
      isset($sentence[0], $sentence[1]) &&
      # is the sentence the one we are looking for
      $s === $sentence[0] &&
      # and there is an replacement sentence
      $sentence[1] !== false
    ) {
        # then return it
      return $sentence[1];
    }
  }

  if (isset($translations['languages'][$tindex]['content_logged_in'])) {
    foreach ($translations['languages'][$tindex]['content_logged_in'] as $sentence) {
      if (
        # are all parts there
        isset($sentence[0], $sentence[1]) &&
        # is the sentence the one we are looking for
        $s === $sentence[0] &&
        # and there is an replacement sentence
        $sentence[1] !== false
      ) {
        # then return it
      return $sentence[1];
      }
    }
  }
  return $s;
}

# to translate string
function get_translation_texts() {
  # get translation data and translations
  global $translations;

  # make sure we have the index
  $tindex = isset($translations['current']['index']) ? $translations['current']['index'] : 0;

  # is this language not present
  if (!isset($translations['languages'][$tindex])) {
    # then get out
    return array();
  }

  return function_exists('is_logged_in') && is_logged_in() ? array_merge($translations['languages'][$tindex]['content'], $translations['languages'][$tindex]['content_logged_in']) : $translations['languages'][$tindex]['content'];
}

# base structure for translations
$translations = array(
  'current' => array(
    'index' => 0,
    'locale' => 'en-US'
  ),
  'languages' => array(
    array(
      # content for the locale
      'content' => array(),
      'content_logged_in' => array(),
      'locales' => array(
        'en-US'
      )
    )
  )
);

function start_translations($locale_basepath) {
  global $translations;

  # directory where the translations are located
  $locale_basepath = $locale_basepath ? $locale_basepath : substr(__FILE__, 0, strrpos(__FILE__, '/') + 1 ).'locales/';

  # scan the directory
  $dircontents = scandir($locale_basepath);

  # walk contents of directory
  foreach ($dircontents as $item) {
    # does this item end with the desired ending?
    if (substr($item, -9) === '.lang.php') {
      require_once($locale_basepath.$item);
    }
  }

  # get the parameters
  $translations['current']['index'] = isset($_REQUEST['translationindex']) ? $_REQUEST['translationindex'] : false;
  $translations['current']['index'] = !isset($_SESSION['translation_index']) ? get_working_locale($translations['languages']) : $_SESSION['translation_index'];
  $translations['current']['locale'] = reset($translations['languages'][$translations['current']['index']]['locales']);
  $_SESSION['translation_index'] = $translations['current']['index'];
}

?>
