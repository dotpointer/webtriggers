<?php

	#	 _   dotpointer       _____
	#	| |__   __ _ ___  ___|___ /
	#	| '_ \ / _` / __|/ _ \ |_ \
	#	| |_) | (_| \__ \  __/___) |
	#	|_.__/ \__,_|___/\___|____/
	#
	#	a collection of useful functions

	# changelog
	# 2016-09-22 17:11:07 - file created, in attempt to break free from mysql and goto mysqli
	# 2016-09-22 22:00:11
	# 2017-02-01 18:57:47 - domain edit
	# 2017-12-29 13:55:03 - adding db query benchmark function callback
	# 2018-06-09 22:16:00 - cleanup

	# --- database functions --------------------------------------------------------------------------------------

	# make sure we have defaults for database constants, in case of not defined
	if (!defined('DATABASE_NAME')) {
		define('DATABASE_NAME', '');
	}

	if (!defined('DATABASE_HOST')) {
		define('DATABASE_HOST', 'localhost');
	}

	if (!defined('DATABASE_USERNAME')) {
		define('DATABASE_USERNAME', 'root');
	}

	if (!defined('DATABASE_PASSWORD')) {
		define('DATABASE_PASSWORD', '');
	} # this is being set by config

	if (!defined('DATABASE_TABLES_PREFIX')) {
		define('DATABASE_TABLES_PREFIX', '');
	}

	# container for error function
	$db_error_function = false;
	$db_query_benchmark_function = !isset($db_query_benchmark_function) ? false : $db_query_benchmark_function;
	$db_query_benchmark_match = !isset($db_query_benchmark_match) ? array() : $db_query_benchmark_match;

	# to connect to db
	function db_connect($host=null, $username=null, $password=null, $dbname=null) {

		# make sure we use default if the values have been false'd
		$host 		= ($host) 		? $host		: DATABASE_HOST;
		$username 	= ($username) 	? $username	: DATABASE_USERNAME;
		$password 	= ($password) 	? $password	: DATABASE_PASSWORD;
		$dbname 	= ($dbname) 	? $dbname : DATABASE_NAME;

		$link = mysqli_connect($host, $username, $password, $dbname);
		if ($link === false) {
			return false;
		}

		# default to UTF-8 communication
		if (mysqli_set_charset($link, 'utf8') === false) {
			return false;
		}

		return $link;
	}

	# to close connection
	function db_close($link) {
		return mysqli_close($link);
	}

	# to get errors
	function db_error($link) {
		return mysqli_error($link);
	}

	# to get last insert id
	function db_insert_id($link) {
		return mysqli_insert_id($link);
	}

	# to run a query, returns an array
	function db_query($link, $query, $die_on_error=true) {
		global $db_error_function, $db_query_benchmark_function, $db_query_benchmark_match;

		# if no database or empty string, get out
		# no link or no query?
		if (!$link || !strlen($query)) {
			# not die on error?
			if (!$die_on_error) {
				# is there an error function?
				if (function_exists($db_error_function)) {
					# then run that function
					return $db_error_function(array(
						'link' => $link,
						'query' => $query,
						'message' => 'No link or query specified.'
					));
				} else {
					return false;
				}
			# or die on error?
			} else {
				if (function_exists($db_error_function)) {
					# then run that function
					$db_error_function(array(
						'link' => $link,
						'query' => $query,
						'message' => 'No link or query specified.'
					));
				}
				die();
			}
		}

		$benchmark = false;
		if (isset($db_query_benchmark_function) && function_exists($db_query_benchmark_function)) {
			foreach ($db_query_benchmark_match as $match) {
				if (strpos($query, $match) !== false || $match === '*') {
					$benchmark = true;
					break;
				}
			}
		}

		if ($benchmark) {
			$benchmark_start = microtime(true);
		}
		$result = mysqli_query($link, $query);
		if ($benchmark) {
			$benchmark_finish = microtime(true) - $benchmark_start;
		}

		# error but not die?
		if ($result === false && !$die_on_error) {
			# is there an error function?
			if (function_exists($db_error_function)) {
				# then run the function
				return $db_error_function(array(
					'link' => $link,
					'query' => $query,
					'result' => $result
				));
			}
			return false;
		}

		# error and die?
		if ($result === false && $die_on_error) {
			# is there an error function?
			if (function_exists($db_error_function)) {
				# then run the function
				$db_error_function(array(
					'link' => $link,
					'query' => $query,
					'result' => $result
				));
				die();
			} else {
				die(db_error($link)."\n");
			}
		}

		if ($benchmark) {
			$db_query_benchmark_function($link, $query, $benchmark_finish);
		}

		$array = array();

		# was the result an object?
		if (is_object($result)) {
			# walk it and convert to arrays
			while ($row = mysqli_fetch_assoc($result)) {
				$array[] = $row;
			}
		}

		# return the result
		return $array;
	}

	# to prepare an insert array
	function db_prepare_insert_array($link, $array) {
		foreach ($array as $key => $value) {
			$array[$key] = '"'.mysqli_real_escape_string($link, $value).'"';
		}
		return $array;
	}

	# to prepare an update array
	function db_prepare_update_array($link, $array) {
		foreach ($array as $key => $value) {
			$array[$key] = $key.'="'.mysqli_real_escape_string($link, $value).'"';
		}
		return $array;
	}

	# to prepare an insert array
	function dbpia ($link, $iu) {
		return db_prepare_insert_array($link, $iu);
	}

	# to prepare an update array
	function dbpua ($link, $iu) {
		return db_prepare_update_array($link, $iu);
	}

	# to ping database
	function db_ping($link) {
		return mysqli_ping($link);
	}

	# to escape string
	function dbres($link, $unescaped_string) {
		return mysqli_real_escape_string($link, $unescaped_string);
	}

	# to escape string
	function db_real_escape_string($link, $unescaped_string) {
		return mysqli_real_escape_string($link, $unescaped_string);
	}

	# to register an error handling function
	# supply a string with the function name,
	# not the function itself
	function db_register_error_function($function) {
		global $db_error_function;
		$db_error_function = $function;
	}

	# to get a db result
	function db_result($result , $row , $field = 0) {
		return $result[$row][$field];
		# try to seek position, returns false on failure
		#if (mysqli_data_seek($result, $row) === false) return false;
		#$row = mysqli_fetch_array($result);
		#if ($row === NULL || !isset($row[$field])) return false;
		# return $row[$field];
	}

	# to set the default character set
	# note that the argument order follows m-ysqli and not m-ysql
	function db_set_charset($link, $charset) {
		return mysqli_set_charset();
	}

	# --- cURL --------------------------------------------------------------------------------------

	# to do curl calls in one function call instead of a mass of lines, introduced 2012-03-08
	function do_curl($curlopts, $opts=array(), &$stats=array()) {

		# prepare our own option defaults
		$opts['die_on_error'] = isset($opts['die_on_error']) ? $opts['die_on_error'] : true;	# we default to dying on error

		# warm up cURL
		$ch = curl_init();

		# make a POST setup
		curl_setopt_array($ch, $curlopts);

		# run it
		$result = curl_exec($ch);

		# put errors into the stats array
		$stats['error'] = curl_error($ch);
		$stats['errno'] = curl_errno($ch);

		# if curl failed then end here
		if (curl_errno($ch) != CURLE_OK) {
			curl_close($ch);
			# should we die on error?
			if ($opts['die_on_error']) {
				die($stats['error'].' ('.$stats['errno'].')');
			} else {
				return $result;
			}
		}

		# close connection
		curl_close($ch);

		return $result;
	}

	# custom filesize function
	function filesize_custom($filepath) {
		# is this 32-bit and linux?
		if ( (PHP_INT_MAX == 2147483647) && strtolower(PHP_OS) === 'linux') {
			# try to get the filesize using ls
			$data = trim(exec("ls -nl ".escapeshellarg($filepath)." | awk '{print $5}'"));
			# make sure it is numeric, otherwise fail
			if (!is_numeric($data)) {
				return false;
			}
			# return the filesize as float, better than string
			return (float)$data;
			# or is this any other platform
		} else {
			return filesize($filepath);
		}
	}

	# calculate eDonkey2000-hash for any given file
	function ed2khash($filename) {
		# calculates eDonkey2000 hash for any given file
		# by: Tom Higginson
		# date: 9th January 2008
		# modified: 11th January 2010
		# license: Public domain

		# modified by dot
		$chunksize = 9728000;
		$md4str = '';

		# make sure file exists
		if(!file_exists($filename)) {
			return false;
		}

		# check if the file size is less than chunk size
		if( filesize_custom($filename) < $chunksize) {
			return strtoupper(hash_file('md4', $filename));
		} else {
			# open file
			$f = fopen($filename, 'r');
			# walk the file
			while(!feof($f)) {
				# calculate more hash
				$md4str .= hash('md4', fread($f, $chunksize), true);
			}

			# close the file
			fclose($f);

			return strtoupper(hash('md4', $md4str));
		}
	}
?>
