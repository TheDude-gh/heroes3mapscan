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


class StringConvert {

	private $map;

	// map based on:
	// http://konfiguracja.c0.pl/iso02vscp1250en.html
	// http://konfiguracja.c0.pl/webpl/index_en.html#examp
	// http://www.htmlentities.com/html/entities/
	/*public function __construct() {
		$this->map = array(
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
			chr(0xA8) => 'Ë',
			chr(0xA9) => '&copy;',
			chr(0xAB) => '&laquo;',
			chr(0xAE) => '&reg;',
			chr(0xB1) => '&plusmn;',
			chr(0xB5) => '&micro;',
			chr(0xB6) => '&para;',
			chr(0xB7) => '&middot;',
			chr(0xB8) => 'ë',
			chr(0xBB) => '&raquo;',
			chr(0xc0) => 'А',
			chr(0xc1) => 'Б',
			chr(0xc2) => 'В',
			chr(0xc3) => 'Г',
			chr(0xc4) => 'Д',
			chr(0xc5) => 'Е',
			chr(0xc6) => 'Ж',
			chr(0xc7) => 'З',
			chr(0xc8) => 'И',
			chr(0xc9) => 'Й',
			chr(0xca) => 'К',
			chr(0xcb) => 'Л',
			chr(0xcc) => 'М',
			chr(0xcd) => 'Н',
			chr(0xce) => 'О',
			chr(0xcf) => 'П',
			chr(0xd0) => 'Р',
			chr(0xd1) => 'С',
			chr(0xd2) => 'Т',
			chr(0xd3) => 'У',
			chr(0xd4) => 'Ф',
			chr(0xd5) => 'Х',
			chr(0xd6) => 'Ц',
			chr(0xd7) => 'Ч',
			chr(0xd8) => 'Ш',
			chr(0xd9) => 'Щ',
			chr(0xda) => 'Ъ',
			chr(0xdb) => 'Ы',
			chr(0xdc) => 'Ь',
			chr(0xdd) => 'Э',
			chr(0xde) => 'Ю',
			chr(0xdf) => 'Я',
			chr(0xe0) => 'а',
			chr(0xe1) => 'б',
			chr(0xe2) => 'в',
			chr(0xe3) => 'г',
			chr(0xe4) => 'д',
			chr(0xe5) => 'е',
			chr(0xe6) => 'ж',
			chr(0xe7) => 'з',
			chr(0xe8) => 'и',
			chr(0xe9) => 'й',
			chr(0xea) => 'к',
			chr(0xeb) => 'л',
			chr(0xec) => 'м',
			chr(0xed) => 'н',
			chr(0xee) => 'о',
			chr(0xef) => 'п',
			chr(0xf0) => 'р',
			chr(0xf1) => 'с',
			chr(0xf2) => 'т',
			chr(0xf3) => 'у',
			chr(0xf4) => 'ф',
			chr(0xf5) => 'х',
			chr(0xf6) => 'ц',
			chr(0xf7) => 'ч',
			chr(0xf8) => 'ш',
			chr(0xf9) => 'щ',
			chr(0xfa) => 'ъ',
			chr(0xfb) => 'ы',
			chr(0xfc) => 'ь',
			chr(0xfd) => 'э',
			chr(0xfe) => 'ю',
			chr(0xff) => 'я',
		);
	}*/

	//proprietary conversion to standard ascii and w1251 for russian and check and conversion for chinese
	public function Convert($text) {
		//there is no really easy and realiable way to detect correct language. So it's left as that for now
		//russian is selected here, because majority of non english maps is in russian

		//try to detect chenese
		//$chincheck = preg_match('/\p{Han}+/u', $text);
		//$chincheck = preg_match('/[\x{4e00}-\x{9fa5}]+/u', $text);
		/*if($chincheck === false) {
			return @iconv('GB2312', 'UTF-8', $text); //chinese
		}*/
		//return @iconv('WINDOWS-1250', 'UTF-8', $text); //middle/eastern europe
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
			$echo = sprintf('%3.3f', ($this->now - $this->start)).' s'.ENVE;
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
