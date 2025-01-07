<?php


const CAM_MONSTERS_QUANTITY      = 145; //ROE,AB,SOD,WOG
const CAM_MONSTERS_QUANTITY_HOTA = 186; //HOTA

const CAM_ARTIFACT_QUANTITY      = 144; //ROE,AB,SOD,WOG
const CAM_ARTIFACT_QUANTITY_HOTA = 166; //HOTA

class CAMBONUS {
	public const SPELL           = 0;
	public const MONSTER         = 1;
	public const BUILDING        = 2;
	public const ARTIFACT        = 3;
	public const SPELL_SCROLL    = 4;
	public const PRIMARY_SKILL   = 5;
	public const SECONDARY_SKILL = 6;
	public const RESOURCE        = 7;
	public const HEROES_PREVIOUS = 8;
	public const HERO            = 9;
}

class CampaignConstants {

	public $cambonus = [
		0 => 'Spell',
		1 => 'Monster',
		2 => 'Building',
		3 => 'Artifact',
		4 => 'Spell Scroll',
		5 => 'Primary',
		6 => 'Secondary',
		7 => 'Resource',
		8 => 'Previous Heroes',
		9 => 'Hero',
	];

	public $camlayout = [
		1  => 'Long Live the Queen',
		2  => 'Liberation',
		3  => 'Song for the Father',
		4  => 'Dungeons and Devils',
		5  => 'Long Live the King',
		6  => 'Spoils of War',
		7  => 'Seeds of Discontent',
		8  => 'Dragon Slayer',
		9  => 'Foolhardy Waywardness',
		10 => 'Festival of life',
		11 => 'Dragon\'s Blood',
		12 => 'Playing with Fire',
		13 => 'Armageddon\'s Blade',
		14 => 'Hack and Slash',
		15 => 'Birth of a Barbarian',
		16 => 'New Beginning',
		17 => 'Elixir of Life',
		18 => 'Rise of the Necromancer',
		19 => 'Unholy Alliance',
		20 => 'Specter of Power',
		21 => 'Under rhe Jolly Roger',
		22 => 'Terror of the Seas',
		23 => 'Horn of the Abbys',
		24 => 'Forged in Fire',
		25 => 'Antagarich',
 ];

}

?>
