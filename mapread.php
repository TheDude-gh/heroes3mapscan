<?php
	header('Content-Type: text/html; charset=utf-8');

	require_once './fun/mi.php';
	require_once './fun/config.php';
	require_once './fun/mapscanf.php';
	require_once './fun/mapsupport.php';
	require_once './fun/mapconstants.php';

	$mapname = expost('map', '');

	echo $mapname;

	$mapfile = MAPDIR.$mapname;
	if(!file_exists($mapfile)) {
		exit;
	}

	$tm = new TimeMeasure();
	$map = new H3MAPSCAN($mapfile, false);
	$map->PrintStateSet(false, true); //dont print, build map
	$map->SetSaveMap(1);
	$map->ReadMap();


	echo ', Duration: ';
	$tm->Measure();
	$tm->showTime();
?>