<?php

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

function w1250_to_utf8($text) {
		// map based on:
		// http://konfiguracja.c0.pl/iso02vscp1250en.html
		// http://konfiguracja.c0.pl/webpl/index_en.html#examp
		// http://www.htmlentities.com/html/entities/
		$map = array(
				chr(0x8A) => chr(0xA9),
				chr(0x8C) => chr(0xA6),
				chr(0x8D) => chr(0xAB),
				chr(0x8E) => chr(0xAE),
				chr(0x8F) => chr(0xAC),
				chr(0x9C) => chr(0xB6),
				chr(0x9D) => chr(0xBB),
				chr(0xA1) => chr(0xB7),
				chr(0xA5) => chr(0xA1),
				chr(0xBC) => chr(0xA5),
				chr(0x9F) => chr(0xBC),
				chr(0xB9) => chr(0xB1),
				chr(0x9A) => chr(0xB9),
				chr(0xBE) => chr(0xB5),
				chr(0x9E) => chr(0xBE),
				chr(0x80) => '&euro;',
				chr(0x82) => '&sbquo;',
				chr(0x84) => '&bdquo;',
				chr(0x85) => '&hellip;',
				chr(0x86) => '&dagger;',
				chr(0x87) => '&Dagger;',
				chr(0x89) => '&permil;',
				chr(0x8B) => '&lsaquo;',
				chr(0x91) => '&lsquo;',
				chr(0x92) => '&rsquo;',
				chr(0x93) => '&ldquo;',
				chr(0x94) => '&rdquo;',
				chr(0x95) => '&bull;',
				chr(0x96) => '&ndash;',
				chr(0x97) => '&mdash;',
				chr(0x99) => '&trade;',
				chr(0x9B) => '&rsquo;',
				chr(0xA6) => '&brvbar;',
				chr(0xA9) => '&copy;',
				chr(0xAB) => '&laquo;',
				chr(0xAE) => '&reg;',
				chr(0xB1) => '&plusmn;',
				chr(0xB5) => '&micro;',
				chr(0xB6) => '&para;',
				chr(0xB7) => '&middot;',
				chr(0xBB) => '&raquo;',
		);
		return html_entity_decode(mb_convert_encoding(strtr($text, $map), 'UTF-8', 'ISO-8859-2'), ENT_QUOTES, 'UTF-8');
}

class ScanSubDir {
	public $files = array();
	private $filter = array();

	public function SetFilter($filter) {
		$this->filter = $filter;
	}

	public function scansubdirs($dir) {
		$sc = scandir($dir);
		foreach($sc as $fd) {
			if($fd == '.' || $fd == '..') continue;
			if(is_dir($dir.$fd)) {
				$this->scansubdirs($dir.$fd.'/');
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

function tmc(){
	return microtime(true);
}

function FromArray($key, $array, $def = '?') {
	if(!is_array($array)) return $def;
	if(array_key_exists($key, $array)) return $array[$key];
	return $def;
}

function ByteBin($byte) {
	return sprintf('%08b', $byte);
}


class TimeMeasure {
	private $start;
	private $prev = 0;
	private $now;
	
	public function __construct () {
		$this->start = tmc();
	}
	
	public function Measure(){
		$this->prev = $this->prev == 0 ? $this->start : $this->now;
		$this->now = tmc();
	}
	
	public function ShowTime($print = 1, $pos = -1, $text = '') {
		if($pos == -1) $echo = sprintf('%3.3f', ($this->now - $this->start)).' s'.ENVE;
		else $echo = sprintf('%5d	%4.3fs	%4.3fs	', $pos, ($this->now - $this->start), ($this->now - $this->prev)).' '.$text.ENVE;
		if($print) echo $echo;
		else return $echo;
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
		if(($char < 'a' || $char > 'z') && ($char < '0' || $char > '9')) continue;
		$newstring .= $string[$i];
	}
	return $newstring == '' ? $string : $newstring;
}

?>
