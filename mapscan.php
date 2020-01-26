<?php
header('Content-Type: text/html; charset=utf-8');

//error_reporting(-1);
//ini_set('display_errors', 'On');

require_once 'fun/config.php';
require_once 'fun/mi.php';

//<link type="text/css" rel="stylesheet" href="css/jquery-ui.css">
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="cz" xml:lang="cz">
<head>
	<title>Heroes III Map Scanner</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8;" />
	<script type="application/javascript" src="js/jquery-2.1.3.min.js"></script>
	<script type="application/javascript" src="js/jquery-ui.js"></script>
  <script type="application/javascript" src="js/mapread.js"></script>
<style>
	* {background: #ddc; font-family: calibri, arial, sans-serif; }
	table {border-collapse:collapse; margin: 1em; border: solid 1px #000;}
	th { background: #dd1;}
	th, td {border: solid 1px #000; min-width: 1em; padding: 1px 5px;}
	ar { text-align:right; }

	.smalltable {font-size: 14px;}
</style>
</head>
<body>
<a href="mapscan.php">Reload</a> | <a href="mapindex.php">Map List</a> <br />
<?php


require_once 'fun/mapscanf.php';
require_once 'fun/mapsupport.php';
require_once 'fun/mapconstants.php';

$mapok = false;
$buildmap = true;
$mapfiledb = false;
$mapid = intval(exget('mapid', 0));

$mapcode = exget('mapcode');

if($mapid) {
	$sql = "SELECT m.mapfile FROM heroes_maps AS m WHERE m.idm = $mapid";
	$mapfiledb = mgr($sql);
}


$scan = new ScanSubDir();
$scan->SetFilter(array('h3m'));
$scan->scansubdirs(MAPDIR);
$files = $scan->GetFiles();


if(!empty($files)) {
	echo 'Maps in folder which are not saved and scanned yet<br /><br />';

	$displayed = 0;
	$maplist = '';
	$maplistjs = array();

	foreach($files as $k => $mfile) {
		$mapname = str_replace(MAPDIR, '', $mfile);
		$par = base64_encode($mapname);
		if($mapcode == $par) {
			continue;
		}

		$smapname = mes($mapname);
		$sql = "SELECT m.mapfile FROM heroes_maps AS m WHERE m.mapfile='$smapname'";
		$mapdb = mgr($sql);
		if($mapdb) {
			continue;
		}

		$maplistjs[] = $smapname;

		$maplist .= ($k + 1).' <a href="?mapcode='.$par.'">'.$mapname.'</a><br />';
		$displayed++;
	}

	if($displayed == 0) {
		echo 'There are no maps to proccess in map folder. You can go to <a href="mapindex.php">Map List</a>';
	}
	else {
		echo '<a href="saveall" id="mapread" onclick="return false;">Read and save all maps</a><br />';
		echo '<p>'.$maplist.'</p>';
		echo '<p id="maplist"></p>';
		echo '<script type="text/javascript">'.EOL.'var maplist = ['.EOL.TAB.'"'.implode($maplistjs, '",'.EOL.TAB.'"').'"'.EOL.']'.EOL.'</script>';
	}

}


if($mapfiledb) {
	//$mapfile = 'maps/'.str_ireplace('.h3m', '_ugz.h3m', $mapfiledb);
	$mapfile = MAPDIR.$mapfiledb;
	$mapok = true;
	//$buildmap = false; //for debug cancel
}
elseif($mapcode) {
  $mapok = true;
	$mapfile = MAPDIR.base64_decode($mapcode);
}
//read some maps only
if($mapok) {
	//mq("TRUNCATE heroes_maps");
	global $tm;
	$tm = new TimeMeasure();
	$map = new H3MAPSCAN($mapfile, true);
	//$buildmap = true;
	$map->PrintStateSet(true, $buildmap);
	$map->SetSaveMap(1);
	$map->ReadMap();
	
	$tm->Measure('End');
	$tm->showTimes();

	//$map->ObjectsShow();
}

//read all maps
if(false) {
	$scan = new ScanSubDir();
	$scan->SetFilter(array('h3m'));
	$scan->scansubdirs(MAPDIR);
	$files = $scan->GetFiles();

	echo 'FILES: '.count($files).ENVE;

	//vd($files);

	$tm = new TimeMeasure();

	//mq("TRUNCATE heroes_maps");
	foreach($files as $k => $mapfile) {
		$map = new H3MAPSCAN($mapfile, true);
		$map->SetSaveMap(1);
		$map->ReadMap();

		$tm->Measure();
		$tm->ShowTime(true, $k, $mapfile);
		//if($k == 1) break;
	}
}


?>
</body>
</html>