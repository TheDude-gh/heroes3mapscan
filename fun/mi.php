<?php
	function db_link() {
		global $db_link;
		if($db_link) return $db_link;
		global $_cfg;
		$db_link = mysqli_connect($_cfg['mq.host'], $_cfg['mq.user'], $_cfg['mq.pass'], $_cfg['mq.db'], $_cfg['mq.port']) or die('Database not connected');
		mq("SET NAMES 'utf8mb4'");
		return $db_link;
	}

	function db_unlink() {
		global $db_link;
		if($db_link) {
			mysqli_close($db_link);
		}
		$db_link = false;
	}

	function file_write($filename, $data) {
		return file_put_contents($filename, $data);
	}

	function file_append($filename, $data) {
		return file_put_contents($filename, $data, FILE_APPEND);
	}

	function expost($name, $def = null) {
		return $_POST[$name] ?? $def;
	}

	function exget($name, $def = null) {
		return $_GET[$name] ?? $def;
	}

	function excookie($name, $def = null) {
		return $_COOKIE[$name] ?? $def;
	}

	function SetDef($var, $def = null) {
		if(!$var) $var = $def;
	}

	function use_cache($cache_file, $cache_period) {
		if(isSet($_GET['rc'])) {
			return false;
		}
		if(file_exists($cache_file) && (time() < filemtime($cache_file) + $cache_period)) {
			return true; //use cache
		}
		return false; //make new
	}

	function mq($sql) {
		$qres = mysqli_query(db_link(), $sql);
		if(!$qres) {
			mlog($sql);
		}
		return $qres;
	}

	function mfa($query) {
		return mysqli_fetch_array($query, MYSQLI_BOTH);
	}

	function mfr($query) {
		return mysqli_fetch_array($query, MYSQLI_NUM);
	}

	function mfs($query) {
		return mysqli_fetch_array($query, MYSQLI_ASSOC);
	}

	function mgr($sql) { //mysql get result - for one value results
		@$res = mysqli_fetch_array(mq($sql), MYSQLI_BOTH);
		return $res ? $res[0] : null;
	}

	function mgrow($sql, $type = MYSQLI_ASSOC) { //mysql get row - for one row result
		@$res = mysqli_fetch_array(mq($sql), $type);
		return $res;
	}

	function me() {
		$me = mysqli_error(db_link());
		if($me) echo '<p style="border:solid 1px #f00;">'.$me.'</p>';
	}

	function mes($string) {
		return mysqli_real_escape_string(db_link(), $string);
	}

	function mar() {
		return mysqli_affected_rows(db_link());
	}

	function mii() {
		return mysqli_insert_id(db_link());
	}

	function msd($dbname) {
		mysqli_select_db(db_link(), $dbname);
	}

	function mlog($sql) {
		$me = mysqli_error(db_link());
		if(LOCAL) {
			echo $me.'<br /><br />';
			vd($sql);
			return;
		}

		$fmlogdir = 'cache/';
		$fmlog = $fmlogdir.'log_sql.log';
		if(!file_exists($fmlogdir)) {
			return;
		}
		$time = date('Y-m-d H:i:s, ', time());
		$dt = debug_backtrace();
		$dbl = '';
		if(array_key_exists(1, $dt)) {
			$dbl = $dt[1]['file'].', line:'.$dt[1]['line'].', func:'.$dt[1]['function'];
		}
		file_append($fmlog, $time.' '.$_SERVER['REQUEST_URI'].EOL.$dbl.EOL.$sql.EOL.$me.EOL.EOL);
		//vd(error_get_last());
	}

	function sqlog($sql) {
		static $count = 0;
		if($count == 0) file_write('sqlog.sql', '');
		$count++;
		file_append('sqlog.sql', $count."\n".$sql."\n\n");
	}

	function comma($value, $round = 0) {
		return number_format($value, $round, '.', ',');
	}

	function padleft($value, $len = 2, $char = '0') {
		return str_pad($value, $len, $char, STR_PAD_LEFT);
	}

	function vd($var) {
		echo '<pre class="vardump">';
		var_dump($var);
		echo '</pre>';
	}

	function vdc($var) {
		var_dump($var);
	}

	function pre($var) {
		echo '<pre class="vardump">'.$var.'</pre>';
	}

	function CheckAndMakeDir($dir) {
		if(!file_exists($dir)) {
			mkdir($dir);
		}
	}
?>
