<?php
	DEFINE('TAB', "\t");
	DEFINE('EOL', "\n");
	DEFINE('ELD', "\r\n");
	DEFINE('ENVE', '<br />');

	//folder with heroes 3 maps, maps are originally compressed by GZIP
	DEFINE('MAPDIR', './maps/');

	//folder with uncompressed maps
	DEFINE('MAPDIREXP', './mapsexp/');

	//folder with map images
	DEFINE('MAPDIRIMG', './mapsimg/');

	//folder with campaigns
	DEFINE('MAPDIRCAM', './mapscam/');

	//folder with campaigns maps
	DEFINE('MAPDIRCAMEXP', './mapscam/exp/');

	//folder with cached map info
	DEFINE('MAPDIRINFO', './mapsinfo/');

	require_once './fun/access.php';

?>
