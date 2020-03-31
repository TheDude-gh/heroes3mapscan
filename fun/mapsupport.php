<?php

function FromArray($key, $array, $def = '?') {
	if(!is_array($array)) {
		return $def;
	}
	if(array_key_exists($key, $array)) {
		return $array[$key];
	}
	return $def.' ['.$key.']';
}

class ScanSubDir {
	public $files = array();
	private $filter = array();
	private $scansubdirs = true;

	public function SetFilter($filter) {
		$this->filter = $filter;
	}
	
	public function scansubdirs_set($value) {
		$this->scansubdirs = $value;
	}

	public function scansubdirs($dir) {
		$sc = scandir($dir);
		foreach($sc as $fd) {
			if($fd == '.' || $fd == '..') continue;
			if(is_dir($dir.$fd)) {
				if($this->scansubdirs) {
					$this->scansubdirs($dir.$fd.'/');
				}
			}
			else {
				$pi = pathinfo($dir.$fd);
				$ext = strtolower($pi['extension']);
				if(empty($this->filter) || in_array($ext, $this->filter)){
					$this->files[] = $dir.$fd;
				}
			}
		}
	}

	public function GetFiles() {
		return $this->files;
	}
}


class StringConvert {

	//proprietary conversion to standard ascii and w1251 for russian and check and conversion for chinese
	public function Convert($text) {
		//there is no really easy and realiable way to detect correct language. So it's left as that for now
		//russian is selected here, because majority of non english maps is in russian

		//try to detect chinese
		//$chincheck = preg_match('/\p{Han}+/u', $text);
		//$chincheck = preg_match('/[\x{4e00}-\x{9fa5}]+/u', $text);
		/*if($chincheck === false) {
			return @iconv('GB2312', 'UTF-8', $text); //chinese
		}*/
		return @iconv('WINDOWS-1250', 'UTF-8', $text); //middle/eastern europe
		return @iconv('WINDOWS-1251', 'UTF-8', $text); //russian
		//return @iconv('ISO-8859-2', 'UTF-8', $text); //middle/eastern europe
		//return strtr($text, $this->map);  //more or less russian
		//return $text;
	}
}


function dbglog(){
	static $justone = 0;
	if($justone > 0) return;
	$justone++;
	$time = date('Y-m-d H:i:s, ', time());
	$dt = debug_backtrace();
	$dbl = '';
	foreach($dt as $dbg){
		$dbl .= $dbg['file'].', line:'.$dbg['line'].', func:'.$dbg['function'].ENVE;
	}
	echo $dbl;
}

function showbytes($str){
	$len = strlen($str);
	echo $len.': ';
	for($i = 0; $i < $len; $i++){
		echo dechex(ord($str[$i])).' ';
	}
	echo ENVE;
}


function tmc(){
	return microtime(true);
}


function ByteBin($byte) {
	return sprintf('%08b', $byte);
}


class TimeMeasure {
	private $start;
	private $prev = 0;
	private $now;
	private $times = array();

	public function __construct () {
		$this->start = tmc();
		$this->times[] = array('start', $this->start);
	}

	public function Measure($desc = ''){
		$this->prev = $this->prev == 0 ? $this->start : $this->now;
		$this->now = tmc();
		$this->times[] = array($desc, $this->now);
	}

	public function ShowTime($print = 1, $pos = -1, $text = '') {
		if($pos == -1) {
			$echo = sprintf('%3.3f', ($this->now - $this->start)).' s';
		}
		else {
			$echo = sprintf('%5d	%4.3fs	%4.3fs	', $pos, ($this->now - $this->start), ($this->now - $this->prev)).' '.$text.ENVE;
		}
		if($print) {
			echo $echo;
		}
		else {
			return $echo;
		}
	}

	public function ShowTimes() {
		$prev = $this->start;
		echo '<table>';
		foreach($this->times as $timed) {
			list($desc, $time) = $timed;
			echo '<tr><td>'.$desc.'</td><td>'.sprintf('%4.3f s</td><td>%4.3f s', ($time - $prev), ($time - $this->start)).'</td></tr>';
			$prev = $time;
		}
		echo '</table>';
	}

}

function sanity_string($string) {
	$newstring = '';
	for($i = 0, $len = strlen($string); $i < $len; $i++) {
		$char = strtolower($string[$i]);
		if($char == ' '){
			$newstring .= '_';
			continue;
		}
		if(($char < 'a' || $char > 'z') && ($char < '0' || $char > '9') && $char != '_' && $char != '-') {
			continue;
		}
		$newstring .= $string[$i];
	}
	return $newstring == '' ? $string : $newstring;
}

?>
