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
	public $files = [];
	private $filter = [];
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

	private $lat;
	private $cyr;

	private $badchar;
	private $goodchar;

	public function __construct() {
	  //cyrilic table
		$this->cyr = [
			'щ','ё','ж','ц','ч','ш','ю','я','а','б','в','г','д','е','з','и','й',
			'к','л','м','н','о','п','р','с','т','у','ф','х','ъ','ы','ь','э',
			'Щ','Ё','Ж','Ц','Ч','Ш','Ю','Я','А','Б','В','Г','Д','Е','З','И','Й',
			'К','Л','М','Н','О','П','Р','С','Т','У','Ф','Х','Ъ','Ы','Ь','Э',
		];

		//latin table
		$this->lat = [
			'sht','io','zh','ts','ch','sh','yu','ya','a','b','v','g','d','e','z','i','y',
			'k','l','m','n','o','p','r','s','t','u','f','h','a','i','y','e',
			'Sht','Io','Zh','Ts','Ch','Sh','Yu','Ya','A','B','V','G','D','E','Z','I','Y',
			'K','L','M','N','O','P','R','S','T','U','F','H','A','I','Y','e',
		];

		//conversion tables of unwanted and wanted chars
		$this->badchar  = ['\'', '{', '}', '~', '¬', 'Ú', 'ß',  'á', 'ä', 'é', 'í', 'ó', 'ö', 'ú', 'ü', 'ý', 'ą', 'ć', 'č', 'ę', 'ě', 'ł', 'ř', 'Ś', 'ś', 'š', 'Ţ', 'ů', 'Ż', 'ż', 'ž', 'Č', 'ź', 'ń'];
		$this->goodchar = ['',   '',   '',  '',  '', 'U', 'ss', 'a', 'a', 'e', 'i', 'o', 'o', 'u', 'u', 'y', 'a', 'c', 'c', 'e', 'e', 'l', 'r', 'S', 's', 's', 'T', 'u', 'Z', 'z', 'z', 'C', 'z', 'n'];
	}


	public function CyrLat($string, $type) { // LC= L => C,  CL= C => L
		if($type == 'LC') { //cyrilic to latin
			return str_replace($this->lat, $this->cyr, $string);
		}
		elseif($type == 'CL') { //latin to cyrilic
			return str_replace($this->cyr, $this->lat, $string);
		}
		return $string;
	}

	//convert string to ascii only
	public function AsciiConvert($string) {
		return str_replace($this->badchar, $this->goodchar, $string);
	}

	public function SanityString($string) {
		//convert from cyrilic to latin
		$string = $this->CyrLat($string, 'CL');
		//convert non asci chars to asci. Table is of course incomplete, just most common letters
		$string = $this->AsciiConvert($string);
		//sanity, remove all non asci chars and convert spaces to underscores
		return $string;
		return sanity_string($string);
	}

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
	private $times = [];

	public function __construct () {
		$this->start = tmc();
		$this->times[] = ['start', $this->start];
	}

	public function Measure($desc = ''){
		$this->prev = $this->prev == 0 ? $this->start : $this->now;
		$this->now = tmc();
		$this->times[] = [$desc, $this->now];
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
			[$desc, $time] = $timed;
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
		elseif(($char < 'a' || $char > 'z') && ($char < '0' || $char > '9') && $char != '_' && $char != '-' && $char != '.') {
			continue;
		}
		$newstring .= $string[$i];
	}
	return $newstring == '' ? $string : $newstring;
}

function h3mmakeFP($mapid, $terrate, $return = false) {
	$bytefp = '';

	foreach($terrate as $z => $rate) {
		foreach($rate as $k => $ter) {
			$bytefp .= chr(($ter & 0xff00) >> 8) . chr($ter & 0xff);
		}
	}
	
	if($bytefp == '') {
		return;
	}
	
	if($return) {
		return $bytefp;
	}

	$bytefp = mes($bytefp);
	$sql = "INSERT INTO heroes_mapfp (idm, fp) VALUES ($mapid, '$bytefp')";
	mq($sql);
}

function GetCampaignLayout($layout) {
	switch($layout) {
		case 1:  return 'Long Live the Queen';
		case 2:  return 'Liberation';
		case 3:  return 'Song for the Father';
		case 4:  return 'Dungeons and Devils';
		case 5:  return 'Long Live the King';
		case 6:  return 'Spoils of War';
		case 7:  return 'Seeds of Discontent';
		case 8:  return 'Dragon Slayer';
		case 9:  return 'Foolhardy Waywardness';
		case 10: return 'Festival of life';
		case 11: return 'Dragon\'s Blood';
		case 12: return 'Playing with Fire';
		case 13: return 'Armageddon\'s Blade';
		case 14: return 'Hack and Slash';
		case 15: return 'Birth of a Barbarian';
		case 16: return 'New Beginning';
		case 17: return 'Elixir of Life';
		case 18: return 'Rise of the Necromancer';
		case 19: return 'Unholy Alliance';
		case 20: return 'Specter of Power';
		case 21: return 'Under rhe Jolly Roger';
		case 22: return 'Terror of the Seas';
		case 23: return 'Horn of the Abbys';
		case 24: return 'Forged in Fire';
		case 25: return 'Antagarich';
		default: return 'Unknown';
	}
}

?>
