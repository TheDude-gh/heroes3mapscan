<?php

	//victory and loss class constants for maplist and form, so we dont have to load whole H3 constats file

	class VICTORYi {
		public const ARTIFACT = 0;
		public const ACCCREATURES = 1;
		public const ACCRESOURCES = 2;
		public const UPGRADETOWN = 3;
		public const BUILDGRAIL = 4;
		public const DEFEATHERO = 5;
		public const CAPTURETOWN = 6;
		public const KILLMONSTER = 7;
		public const FLAGWELLINGS = 8;
		public const FLAGMINES = 9;
		public const TRANSPORTART = 10;
		public const ELIMINATEMONSTERS = 11;
		public const SURVIVETIME = 12;
		public const NONE = 0xff;
	}

	class LOSSi {
		public const TOWN = 0;
		public const HERO = 1;
		public const TIME = 2;
		public const NONE = 0xff;
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
