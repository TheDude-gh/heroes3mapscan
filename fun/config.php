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

	global $_cfg;
		$_cfg = array(
		'mq.host' => 'localhost',
		'mq.user' => '',
		'mq.pass' => '',
		'mq.db'   => '',
		'mq.port' => '3306',
	);
	
?>