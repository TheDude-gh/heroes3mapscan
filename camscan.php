<?php
header('Content-Type: text/html; charset=utf-8');

require_once 'fun/mi.php';
require_once 'fun/config.php';

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="cz" xml:lang="cz">
<head>
	<title>Cam Scanner</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8;" />
<style>
	* {background: #ddc; font-family: calibri, arial, sans-serif; }
	table {border-collapse:collapse; margin: 1em; border: solid 1px #000;}
	th { background: #dd1;}
	th, td {border: solid 1px #000; min-width: 1em; padding: 1px 5px;}
	.ar { text-align:right; }
	.ac { text-align:center; }
	.al { text-align:left; }
	.mc { margin: 0px auto; }

	.smalltable {font-size: 14px;}

	.color1 { background: #ff0000; padding: 0px 6px; border-radius:5px; } /* red */
	.color2 { background: #3152ff; padding: 0px 6px; border-radius:5px; } /* blue */
	.color3 { background: #9c7352; padding: 0px 6px; border-radius:5px; } /* tan */
	.color4 { background: #429429; padding: 0px 6px; border-radius:5px; } /* green */
	.color5 { background: #ff8400; padding: 0px 6px; border-radius:5px; } /* orange */
	.color6 { background: #8c29a5; padding: 0px 6px; border-radius:5px; } /* purple */
	.color7 { background: #089ca5; padding: 0px 6px; border-radius:5px; } /* teal */
	.color8 { background: #c67b8c; padding: 0px 6px; border-radius:5px; } /* pink */
	.color256 { background: #848484; padding: 0px 6px; border-radius:5px; } /* neutral */

</style>
</head>
<body>
<a href="mapscan.php">Reload</a> | <a href="mapindex.php">Map List</a>
| <a href="mapscan.php?nl=1">Reload no list</a>
<br />
<?php

//http://heroescommunity.com/viewthread.php3?TID=44079
//http://heroescommunity.com/viewthread.php3?TID=42097&pagenumber=1

require_once 'fun/h3camscan.php';
require_once 'fun/camconstants.php';
require_once 'fun/mapscanf.php';
require_once 'fun/mapsupport.php';
require_once 'fun/mapconstants.php';

/*$scan = new ScanSubDir();
$scan->SetFilter(array('h3c'));
$scan->scansubdirs(MAPDIR);
$files = $scan->GetFiles();*/

$num = 16;

$n = exget('n');
if($n) {
	$num = $n;
}

$cam = [
	0 => 'Enderasian Crusades.h3c',
	1 => 'Birth of Hero.h3c',
	2 => 'Roland.h3c',
	3 => 'Archibald.h3c',
	4 => 'Final.h3c',
	5 => 'Yog.h3c',
	6 => 'Trilogy Capital of Death - Part 3 - Fire From Sky.h3c',
	7 => 'the_coming_home_1463.h3c',
	8 => 'ab.h3c',
	9 => 'fool.h3c',
	10 => 'Leki-cam.h3c',
	11 => 'Honza.h3c',
	12 => 'Honza2.h3c',
	13 => 'Honza3.h3c',
	14 => 'Rebellion.h3c',
	15 => 'Avatar.h3c',
	16 => 'zc1.h3c',
	17 => 'zc2.h3c',
	18 => 'zc3.h3c',
	19 => 'zc4.h3c',
	20 => 'H1Roger.h3c',
	21 => 'H2Terror.h3c',
	22 => 'H3Horn.h3c',
];

$camfile = $cam[$num];
/*
H3C_PRINTINFO   
H3C_SAVECAMDB   
H3C_EXPORTMAPS  
H3C_CAMHTMCACHE 
*/

$mapfile = MAPDIRCAM.$camfile;
$map = new H3CAMSCAN($mapfile, H3C_PRINTINFO | H3C_SAVECAMDB | H3C_EXPORTMAPS | H3C_CAMHTMCACHE);
$map->ReadCAM();
$map->ReadMaps();

/*foreach($files as $mapfile) {

	//$mapfile = MAPDIR.$cam;

	$map = new H3CAMSCAN($mapfile, true);
	$map->ReadCAM();
}*/





?>
</body>
</html>
