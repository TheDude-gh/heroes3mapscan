<?php
header('Content-Type: text/html; charset=utf-8');

require_once 'fun/mi.php';
require_once 'fun/config.php';

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="cz" xml:lang="cz">
<head>
	<title>Heroes III Map Scanner</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8;" />
	<link rel="shortcut icon" href="css/img/heroes.png" type="image/x-icon" />
	<link type="text/css" rel="stylesheet" media="all" href="css/heroesmaps.css	" />
	<script type="application/javascript" src="js/jquery-2.1.3.min.js"></script>
	<script type="application/javascript" src="js/jquery-ui.js"></script>
	<script type="application/javascript" src="js/mapread.js"></script>
<style>
	*, body {background: #ddc; font-family: calibri, arial, sans-serif; }
	table {border-collapse:collapse; margin: 1em; border: solid 1px #000;}
	th { background: #dd1;}
	th, td {border: solid 1px #000; min-width: 1em; padding: 1px 5px;}
	.ar { text-align:right; }
	.ac { text-align:center; }
	.al { text-align:left; }
	.mc { margin: 0px auto; }

	.smalltable {font-size: 14px;}
	.colw100 { width: 100px; }
	.colA { width: 30%; }

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
<a href="mapscan.php">Reload</a> | <a href="mapscan.php?scan=1">Scan</a> | <a href="maplist.php">Map List</a>
| <a href="mapscan.php?nl=1">Reload no list</a>
<br />
<?php

require_once 'fun/h3mapscan.php';
require_once 'fun/h3mapconstants.php';
require_once 'fun/mapsupport.php';



$mapok = false;
$buildmap = true;
$mapfiledb = false;
$mapid = intval(exget('mapid', 0));

$scan = exget('scan');

$mapcode = exget('mapcode');
$disp = '';

if($mapid) {
	if(exget('del')) {
		$sql = "DELETE FROM heroes3_maps WHERE idm = $mapid";
		mq($sql);
	}
	else {
		$sql = "SELECT m.mapfile FROM heroes3_maps AS m WHERE m.idm = $mapid";
		$mapfiledb = mgr($sql);
	}
}
elseif($scan) {
	$scan = new ScanSubDir();
	$scan->SetFilter(['h3m']);
	$scan->scansubdirs(MAPDIR);
	$files = $scan->GetFiles();


	if(!empty($files)) {
		echo 'Maps in folder which are not saved and scanned yet<br /><br />';

		$displayed = 0;
		$maplist = '';
		$maplistjs = [];

		$mapdbs = [];
		$sql = "SELECT m.mapfile, m.idm FROM heroes3_maps AS m";
		$query = mq($sql);
		while($res = mfa($query)) {
			$mapdbs[$res['idm']] = $res['mapfile'];
		}

		foreach($files as $k => $mfile) {
			$mapname = str_replace(MAPDIR, '', $mfile);
			$par = base64_encode($mapname);
			if($mapcode == $par) {
				$disp = $mapname.'<br />';
				//continue;
			}

			$smapname = mes($mapname);

			if(in_array($mapname, $mapdbs)) {
				continue;
			}

			$maplistjs[] = $smapname;

			$maplist .= ($k + 1).' <a href="?mapcode='.$par.'">'.$mapname.'</a><br />';
			$displayed++;
		}

		if($displayed == 0) {
			echo 'There are no maps to proccess in map folder. You can go to <a href="mapindex.php">Map List</a><br /><br />';
		}
		else {
			echo '<a href="saveall" id="mapread" onclick="return false;">Read and save all maps</a><br />';
			echo 'Total unsaved maps: '.count($maplistjs);
			if(!exget('nl', 0)) {
				echo '<p>'.$maplist.'</p>';
			}
			echo '<p id="mapreadstate"></p>';
			echo '<p id="maplist"></p>';
			echo '<script type="text/javascript">'.EOL.'var maplist = ['.EOL.TAB.'"'.implode('",'.EOL.TAB.'"', $maplistjs).'"'.EOL.']'.EOL.'</script>';
		}

	}
}

if($mapfiledb) {
	$mapfile = MAPDIR.$mapfiledb;
	$mapok = true;
}
elseif($mapcode) {
	$mapok = true;
	$mapfile = MAPDIR.base64_decode($mapcode);
}

//read some maps only
if($mapok) {
	echo $disp;
	$tm = new TimeMeasure();

	/*
	H3M_WEBMODE
	H3M_PRINTINFO
	H3M_BUILDMAP
	H3M_SAVEMAPDB
	H3M_EXPORTMAP
	H3M_BASICONLY
	H3M_MAPHTMCACHE
	H3M_SPECIALACCESS
	*/

	$map = new H3MAPSCAN($mapfile, H3M_WEBMODE | H3M_PRINTINFO | H3M_BUILDMAP); // | H3M_BUILDMAP | H3M_SAVEMAPDB | H3M_MAPHTMCACHE
	$map->ReadMap();

	$tm->Measure('End');
	$tm->showTimes();
}

?>
</body>
</html>
