<?php

	//victory and loss class constants for maplist and form, so we dont have to load whole H3 constats file

	class VICTORYi {
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

	class LOSSi {
		const TOWN = 0;
		const HERO = 1;
		const TIME = 2;
		const NONE = 0xff;
	}

	$VICTORY = [
		VICTORYi::NONE => 'Standard',
		VICTORYi::ARTIFACT => 'Acquire a specific artifact',
		VICTORYi::ACCCREATURES => 'Accumulate creatures',
		VICTORYi::ACCRESOURCES => 'Accumulate resources',
		VICTORYi::UPGRADETOWN => 'Upgrade a specific town',
		VICTORYi::BUILDGRAIL => 'Build the grail structure',
		VICTORYi::DEFEATHERO => 'Defeat a specific Hero',
		VICTORYi::CAPTURETOWN => 'Capture a specific town',
		VICTORYi::KILLMONSTER => 'Defeat a specific monster',
		VICTORYi::FLAGWELLINGS => 'Flag all creature dwelling',
		VICTORYi::FLAGMINES => 'Flag all mines',
		VICTORYi::TRANSPORTART => 'Transport a specific artifact',
		VICTORYi::ELIMINATEMONSTERS => 'Eliminate all monsters',
		VICTORYi::SURVIVETIME => 'Survive for certain time',
	];

	$LOSS = [
		LOSSi::NONE => 'None',
		LOSSi::TOWN => 'Lose a specific town',
		LOSSi::HERO => 'Lose a specific hero',
		LOSSi::TIME => 'Time expires',
	];


?>
