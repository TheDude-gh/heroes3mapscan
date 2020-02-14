<?php
	class VICTORY {
		const ARTIFACT = 0;
		const ACCCREATURES = 1;
		const ACCRESOURCES = 2;
		const UPGRADETOWN = 3;
		const BUILDGRAIL = 4;
		const DEFEATHERO = 5;
		const CAPTURETOWN = 6;
		const KILLMONSTER = 7;
		const FLAGWELLINGS = 8;
		const FLAGMINES = 9;
		const TRANSPORTART = 10;
		const ELIMINATEMONSTERS = 11;
		const SURVIVETIME = 12;
		const NONE = 0xff;
	}

	class LOSS {
		const TOWN = 0;
		const HERO = 1;
		const TIME = 2;
		const NONE = 0xff;
	}

	$VICTORY = [
		VICTORY::NONE => 'Standard',
		VICTORY::ARTIFACT => 'Acquire a specific artifact',
		VICTORY::ACCCREATURES => 'Accumulate creatures',
		VICTORY::ACCRESOURCES => 'Accumulate resources',
		VICTORY::UPGRADETOWN => 'Upgrade a specific town',
		VICTORY::BUILDGRAIL => 'Build the grail structure',
		VICTORY::DEFEATHERO => 'Defeat a specific Hero',
		VICTORY::CAPTURETOWN => 'Capture a specific town',
		VICTORY::KILLMONSTER => 'Defeat a specific monster',
		VICTORY::FLAGWELLINGS => 'Flag all creature dwelling',
		VICTORY::FLAGMINES => 'Flag all mines',
		VICTORY::TRANSPORTART => 'Transport a specific artifact',
		VICTORY::ELIMINATEMONSTERS => 'Eliminate all monsters',
		VICTORY::SURVIVETIME => 'Survive for certain time',
	];

	$LOSS = [
		LOSS::NONE => 'None',
		LOSS::TOWN => 'Lose a specific town',
		LOSS::HERO => 'Lose a specific hero',
		LOSS::TIME => 'Time expires',
	];


?>
