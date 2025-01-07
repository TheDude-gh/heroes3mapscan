<?php

	//global constants
	const OWNERNONE = 0xfe;
	const OWNNOONE = 0xff;
	const OBJECT_INVALID = -1; //invalid object id
	const COOR_INVALID = -1; //invalid coordinates
	const HOTA_RANDOM = -1; //random chance in stuff

	const PRIMARY_SKILLS = 4;
	const SPELL_BYTE = 9;
	const SECSKILL_BYTE = 4;
	const RESOURCE_QUANTITY = 8;
	const HEROES_PER_TYPE = 8; //amount of heroes of each type

	const TILEBYTESIZE = 7;

	const HEROES_QUANTITY = 156; //156 ? ROE,AB,SOD,WOG
	const HEROES_QUANTITY_HOTA = 178; //HOTA
	const SPELLS_QUANTITY = 70; //69 visible in editor
	const MAX_TOWNS = 11; //original=9, HOTA=11

	const PLAYERSNUM = 8;
	const HNULL = 0;
	const HNONE = 0xff; //general heroes NONE value
	const HNONE_TOWN = 0x1ff;
	const HOTA_MONSTER_IDS = 151; //hota monster start index
	const HOTA_ARTIFACTS_IDS = 141; //hota artifacts start index
	const HNONE16 = 0xffff; //general heroes NONE value, 16 bit
	const HNONE32 = 0xffffffff; //general heroes NONE value, 32 bit

	//some unused constants
	/*
	const BACKPACK_START = 19;
	const CREATURES_PER_TOWN = 7; //without upgrades
	const SPELL_LEVELS = 5;
	const SPELL_SCHOOL_LEVELS = 4;
	const ARMY_SIZE = 7;
	const SKILL_PER_HERO = 8;
	const SKILL_QUANTITY = 28;
	const TERRAIN_TYPES = 10;
	const F_NUMBER = 9;
	const ARTIFACTS_QUANTITY = 171;
	const CREATURES_COUNT = 197;
	*/

	//constants classes
	class MAPOBJECTS {
		public const NONE = 0;
		public const HERO = 1;
		public const TOWN = 2;
		public const MONSTER = 3;
	}

	class MAPSPECIAL {
		public const NONE	   = 0;
		public const MINE     = 1;
		public const ARTIFACT = 2;
		public const MONSTER  = 3;
		public const ANY      = 4;
	}

	class TILETYPE {
		public const FREE     = 0;
		public const POSSIBLE = 1;
		public const BLOCKED  = 2;
		public const USED     = 3;
		public const ACCESSIBLE = 1;
	}

	class BLOCKMAPBITS {
		public const VISIBLE   = 1; //free tile
		public const VISITABLE = 2; //tile with object, that can be stepped on
		public const BLOCKED   = 4; //tile with inaccessible object
		public const COMBINED  = 6; //tile with any object, VISITABLE | BLOCKED
	}

	class TERRAIN {
		//normal
		public const DIRT      = 0;
		public const SAND      = 1;
		public const GRASS     = 2;
		public const SNOW      = 3;
		public const SWAMP     = 4;
		public const ROUGH     = 5;
		public const SUBTERAIN = 6;
		public const LAVA      = 7;
		public const WATER     = 8;
		public const ROCK      = 9;
		public const HIGHLANDS = 10;
		public const WASTELAND = 11;

		//blocked
		public const BDIRT      = 20;
		public const BSAND      = 21;
		public const BGRASS     = 22;
		public const BSNOW      = 23;
		public const BSWAMP     = 24;
		public const BROUGH     = 25;
		public const BSUBTERAIN = 26;
		public const BLAVA      = 27;
		public const BWATER     = 28;
		public const BROCK      = 29;
		public const BHIGHLANDS = 30;
		public const BWASTELAND = 31;

		//players
		public const RED        = 40;
		public const BLUE       = 41;
		public const TAN        = 42;
		public const GREEN      = 43;
		public const ORANGE     = 44;
		public const PURPLE     = 45;
		public const TEAL       = 46;
		public const PINK       = 47;
		public const NEUTRAL    = 48;
		//special
		public const NONE       = 50;
		public const MINE       = 51;
		public const ARTIFACT   = 52;
		public const MONSTER    = 53;
		public const ANY        = 54;

		//offsets
		public const OFFBLOCKED = 20;
		public const OFFPLAYERS = 40;
		public const OFFSPECIAL = 50;

		//count
		public const TERRAINNUM = 12;
	}

	class VICTORY {
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

	class LOSS {
		public const TOWN = 0;
		public const HERO = 1;
		public const TIME = 2;
		public const NONE = 0xff;
	}

	class OBJECTS {
		public const NO_OBJ = -1;
		public const ALTAR_OF_SACRIFICE = 2;
		public const ANCHOR_PO = 3;
		public const ARENA = 4;
		public const ARTIFACT = 5;
		public const PANDORAS_BOX = 6;
		public const BLACK_MARKET = 7;
		public const BOAT = 8;
		public const BORDERGUARD = 9;
		public const KEYMASTER = 10;
		public const BUOY = 11;
		public const CAMPFIRE = 12;
		public const CARTOGRAPHER = 13;
		public const SWAN_POND = 14;
		public const COVER_OF_DARKNESS = 15;
		public const CREATURE_BANK = 16;
		public const CREATURE_GENERATOR1 = 17;
		public const CREATURE_GENERATOR2 = 18;
		public const CREATURE_GENERATOR3 = 19;
		public const CREATURE_GENERATOR4 = 20;
		public const CURSED_GROUND1 = 21;
		public const CORPSE = 22;
		public const MARLETTO_TOWER = 23;
		public const DERELICT_SHIP = 24;
		public const DRAGON_UTOPIA = 25;
		public const EVENT = 26;
		public const EYE_OF_MAGI = 27;
		public const FAERIE_RING = 28;
		public const FLOTSAM = 29;
		public const FOUNTAIN_OF_FORTUNE = 30;
		public const FOUNTAIN_OF_YOUTH = 31;
		public const GARDEN_OF_REVELATION = 32;
		public const GARRISON = 33;
		public const HERO = 34;
		public const HILL_FORT = 35;
		public const GRAIL = 36;
		public const HUT_OF_MAGI = 37;
		public const IDOL_OF_FORTUNE = 38;
		public const LEAN_TO = 39;
		public const LIBRARY_OF_ENLIGHTENMENT = 41;
		public const LIGHTHOUSE = 42;
		public const MONOLITH_ONE_WAY_ENTRANCE = 43;
		public const MONOLITH_ONE_WAY_EXIT = 44;
		public const MONOLITH_TWO_WAY = 45;
		public const MAGIC_PLAINS1 = 46;
		public const SCHOOL_OF_MAGIC = 47;
		public const MAGIC_SPRING = 48;
		public const MAGIC_WELL = 49;
		public const MERCENARY_CAMP = 51;
		public const MERMAID = 52;
		public const MINE = 53;
		public const MONSTER = 54;
		public const MYSTICAL_GARDEN = 55;
		public const OASIS = 56;
		public const OBELISK = 57;
		public const REDWOOD_OBSERVATORY = 58;
		public const OCEAN_BOTTLE = 59;
		public const PILLAR_OF_FIRE = 60;
		public const STAR_AXIS = 61;
		public const PRISON = 62;
		public const PYRAMID = 63;
		public const WOG_OBJECT = 63;
		public const RALLY_FLAG = 64;
		public const RANDOM_ART = 65;
		public const RANDOM_TREASURE_ART = 66;
		public const RANDOM_MINOR_ART = 67;
		public const RANDOM_MAJOR_ART = 68;
		public const RANDOM_RELIC_ART = 69;
		public const RANDOM_HERO = 70;
		public const RANDOM_MONSTER = 71;
		public const RANDOM_MONSTER_L1 = 72;
		public const RANDOM_MONSTER_L2 = 73;
		public const RANDOM_MONSTER_L3 = 74;
		public const RANDOM_MONSTER_L4 = 75;
		public const RANDOM_RESOURCE = 76;
		public const RANDOM_TOWN = 77;
		public const REFUGEE_CAMP = 78;
		public const RESOURCE = 79;
		public const SANCTUARY = 80;
		public const SCHOLAR = 81;
		public const SEA_CHEST = 82;
		public const SEER_HUT = 83;
		public const CRYPT = 84;
		public const SHIPWRECK = 85;
		public const SHIPWRECK_SURVIVOR = 86;
		public const SHIPYARD = 87;
		public const SHRINE_OF_MAGIC_INCANTATION = 88;
		public const SHRINE_OF_MAGIC_GESTURE = 89;
		public const SHRINE_OF_MAGIC_THOUGHT = 90;
		public const SIGN = 91;
		public const SIRENS = 92;
		public const SPELL_SCROLL = 93;
		public const STABLES = 94;
		public const TAVERN = 95;
		public const TEMPLE = 96;
		public const DEN_OF_THIEVES = 97;
		public const TOWN = 98;
		public const TRADING_POST = 99;
		public const LEARNING_STONE = 100;
		public const TREASURE_CHEST = 101;
		public const TREE_OF_KNOWLEDGE = 102;
		public const SUBTERRANEAN_GATE = 103;
		public const UNIVERSITY = 104;
		public const WAGON = 105;
		public const WAR_MACHINE_FACTORY = 106;
		public const SCHOOL_OF_WAR = 107;
		public const WARRIORS_TOMB = 108;
		public const WATER_WHEEL = 109;
		public const WATERING_HOLE = 110;
		public const WHIRLPOOL = 111;
		public const WINDMILL = 112;
		public const WITCH_HUT = 113;
		public const HOLE = 124;
		public const WATER_RESOURCE = 145;
		public const SEA_UNIVERSITY = 146;
		public const RANDOM_MONSTER_L5 = 162;
		public const RANDOM_MONSTER_L6 = 163;
		public const RANDOM_MONSTER_L7 = 164;
		public const BORDER_GATE = 212;
		public const FREELANCERS_GUILD = 213;
		public const HERO_PLACEHOLDER = 214;
		public const QUEST_GUARD = 215;
		public const RANDOM_DWELLING = 216;
		public const RANDOM_DWELLING_LVL = 217;
		public const RANDOM_DWELLING_FACTION = 218;
		public const GARRISON2 = 219;
		public const ABANDONED_MINE = 220;
		public const TRADING_POST_SNOW = 221;
		public const CLOVER_FIELD = 222;
		public const CURSED_GROUND2 = 223;
		public const EVIL_FOG = 224;
		public const FAVORABLE_WINDS = 225;
		public const FIERY_FIELDS = 226;
		public const HOLY_GROUNDS = 227;
		public const LUCID_POOLS = 228;
		public const MAGIC_CLOUDS = 229;
		public const MAGIC_PLAINS2 = 230;
		public const ROCKLANDS = 231;
	}

	//quests
	class REWARDTYPE {
		public const NOTHING = 0;
		public const EXPERIENCE = 1;
		public const MANA_POINTS = 2;
		public const MORALE_BONUS = 3;
		public const LUCK_BONUS = 4;
		public const RESOURCES = 5;
		public const PRIMARY_SKILL = 6;
		public const SECONDARY_SKILL = 7;
		public const ARTIFACT = 8;
		public const SPELL = 9;
		public const CREATURE = 10;
	}

	class QUESTMISSION {
		public const NONE = 0;
		public const LEVEL = 1;
		public const PRIMARY_STAT = 2;
		public const KILL_HERO = 3;
		public const KILL_CREATURE = 4;
		public const ART = 5;
		public const ARMY = 6;
		public const RESOURCES = 7;
		public const HERO = 8;
		public const PLAYER = 9;
		public const KEYMASTER = 10;
		public const HOTA_EXTRA = 10;
		public const HOTA_CLASS = 0;
		public const HOTA_NOTBEFORE = 1;
	};

	//constants class with items names
	class HeroesConstants {

		public $PlayersColors = [
			0 => 'Red',
			1 => 'Blue',
			2 => 'Tan',
			3 => 'Green',
			4 => 'Orange',
			5 => 'Purple',
			6 => 'Teal',
			7 => 'Pink',
			0xff => 'Neutral'
		];

		public $PrimarySkill = [
			0 => 'Attack',
			1 => 'Defense',
			2 => 'Spell Power',
			3 => 'Knowledge',
			4 => 'Experience'
		];

		public $SecondarySkill = [
			-1 => 'Default',
			0 => 'Pathfinding',
			1 => 'Archery',
			2 => 'Logistics',
			3 => 'Scouting',
			4 => 'Diplomacy',
			5 => 'Navigation',
			6 => 'Leadership',
			7 => 'Wisdom',
			8 => 'Mysticism',
			9 => 'Luck',
			10 => 'Ballistics',
			11 => 'Eagle Eye',
			12 => 'Necromancy',
			13 => 'Estates',
			14 => 'Fire Magic',
			15 => 'Air Magic',
			16 => 'Water Magic',
			17 => 'Earth Magic',
			18 => 'Scholar',
			19 => 'Tactics',
			20 => 'Artillery',
			21 => 'Learning',
			22 => 'Offense',
			23 => 'Armorer',
			24 => 'Intelligence',
			25 => 'Sorcery',
			26 => 'Resistance',
			27 => 'First Aid',
			28 => 'Interference', //hota
		];

		public $Alignment = [
			0 => 'GOOD',
			1 => 'EVIL',
			2 => 'NEUTRAL',
		];

		public $TownType = [
			-1 => 'Any',
			0 => 'Castle',
			1 => 'Rampart',
			2 => 'Tower',
			3 => 'Inferno',
			4 => 'Necropolis',
			5 => 'Dungeon',
			6 => 'Stronghold',
			7 => 'Fortress',
			8 => 'Conflux',
			9 => 'Neutral/Cove',
			10 => 'Factory',
		];

		public $AiTactic = [
			-1 => 'NONE',
			0 => 'RANDOM',
			1 => 'WARRIOR',
			2 => 'BUILDER',
			3 => 'EXPLORER',
		];


		public $TileType = [
			TILETYPE::FREE     => 'Free',
			TILETYPE::POSSIBLE => 'Possible',
			TILETYPE::BLOCKED  => 'Blocked',
			TILETYPE::USED     => 'Used',
		];

		//unused, but maybe in future
		/*
		public $TeleportChannelType = [
			0 => 'IMPASSABLE',
			1 => 'BIDIRECTIONAL',
			2 => 'UNIDIRECTIONAL',
			3 => 'MIXED',
		];

		public $RiverType = [
			0 => 'NO RIVER',
			1 => 'CLEAR RIVER',
			2 => 'ICY RIVER',
			3 => 'MUDDY RIVER',
			4 => 'LAVA RIVER',
		];

		public $RoadType = [
			0 => 'NO ROAD',
			1 => 'DIRT ROAD',
			2 => 'GRAVEL ROAD',
			3 => 'COBBLESTONE ROAD',
		];

		public $SpellSchool = [
			0 => 'AIR',
			1 => 'FIRE',
			2 => 'WATER',
			3 => 'EARTH'
		];
		*/

		public $SecSkillLevel = [
			0 => 'None',
			1 => 'Basic',
			2 => 'Advanced',
			3 => 'Expert',
		];

		public $TerrainType = [
			0 => 'Dirt',
			1 => 'Sand',
			2 => 'Grass',
			3 => 'Snow',
			4 => 'Swamp',
			5 => 'Rough',
			6 => 'Subterranean',
			7 => 'Lava',
			8 => 'Water',
			9 => 'Rock',
			10 => 'Highlands',
			11 => 'Wasteland'
		];


		public $ArtifactPosition = [
			-2 => 'First available',
			-1 => 'Pre first', //sometimes used as error, sometimes as first free in backpack
			0 => 'Head',
			1 => 'Shoulders',
			2 => 'Neck',
			3 => 'Right hand',
			4 => 'Left hand',
			5 => 'Torso',
			6 => 'Right ring',
			7 => 'Left ring',
			8 => 'Feet',
			9 => 'Misc1',
			10 => 'Misc2',
			11 => 'Misc3',
			12 => 'Misc4',
			13 => 'Mach1',
			14 => 'Mach2',
			15 => 'Mach3',
			16 => 'Mach4',
			17 => 'Spellbook',
			18 => 'Misc5',
			19 => 'Backpack',
		];

		public $SpellID = [
			-2 => 'Preset',
			-1 => 'None',
			0 => 'Summon Boat',
			1 => 'Scuttle Boat',
			2 => 'Visions',
			3 => 'View Earth',
			4 => 'Disguise',
			5 => 'View Air',
			6 => 'Fly',
			7 => 'Water Walk',
			8 => 'Dimension Door',
			9 => 'Town Portal',
			10 => 'Quicksand',
			11 => 'Land Mine',
			12 => 'Force Field',
			13 => 'Fire Wall',
			14 => 'Earthquake',
			15 => 'Magic Arrow',
			16 => 'Ice Bolt',
			17 => 'Lightning Bolt',
			18 => 'Implosion',
			19 => 'Chain Lightning',
			20 => 'Frost Ring',
			21 => 'Fireball',
			22 => 'Inferno',
			23 => 'Meteor Shower',
			24 => 'Death Ripple',
			25 => 'Destroy Undead',
			26 => 'Armageddon',
			27 => 'Shield',
			28 => 'Air Shield',
			29 => 'Fire Shield',
			30 => 'Protection From Air',
			31 => 'Protection From Fire',
			32 => 'Protection From Water',
			33 => 'Protection From Earth',
			34 => 'Anti Magic',
			35 => 'Dispel',
			36 => 'Magic Mirror',
			37 => 'Cure',
			38 => 'Resurrection',
			39 => 'Animate Dead',
			40 => 'Sacrifice',
			41 => 'Bless',
			42 => 'Curse',
			43 => 'Bloodlust',
			44 => 'Precision',
			45 => 'Weakness',
			46 => 'Stone Skin',
			47 => 'Disrupting Ray',
			48 => 'Prayer',
			49 => 'Mirth',
			50 => 'Sorrow',
			51 => 'Fortune',
			52 => 'Misfortune',
			53 => 'Haste',
			54 => 'Slow',
			55 => 'Slayer',
			56 => 'Frenzy',
			57 => 'Titans Lightning Bolt',
			58 => 'Counterstrike',
			59 => 'Berserk',
			60 => 'Hypnotize',
			61 => 'Forgetfulness',
			62 => 'Blind',
			63 => 'Teleport',
			64 => 'Remove Obstacle',
			65 => 'Clone',
			66 => 'Summon Fire Elemental',
			67 => 'Summon Earth Elemental',
			68 => 'Summon Water Elemental',
			69 => 'Summon Air Elemental',
			70 => 'First Non Spell',
			71 => 'Poison',
			72 => 'Bind',
			73 => 'Disease',
			74 => 'Paralyze',
			75 => 'Age',
			76 => 'Death Cloud',
			77 => 'Thunderbolt',
			78 => 'Dispel Helpful Spells',
			79 => 'Death Stare',
			80 => 'Acid Breath Defense',
			81 => 'Acid Breath Damage',
			82 => 'After Last',
		];

		//full defines of obj, monsters, heroes
		public $Monster = [
			0 => 'Pikeman',
			1 => 'Halberdier',
			2 => 'Archer',
			3 => 'Marksman',
			4 => 'Griffin',
			5 => 'Royal Griffin',
			6 => 'Swordsman',
			7 => 'Crusader',
			8 => 'Monk',
			9 => 'Zealot',
			10 => 'Cavalier',
			11 => 'Champion',
			12 => 'Angel',
			13 => 'Archangel',
			14 => 'Centaur',
			15 => 'Centaur Captain',
			16 => 'Dwarf',
			17 => 'Battle Dwarf',
			18 => 'Wood Elf',
			19 => 'Grand Elf',
			20 => 'Pegasus',
			21 => 'Silver Pegasus',
			22 => 'Dendroid Guard',
			23 => 'Dendroid Soldier',
			24 => 'Unicorn',
			25 => 'War Unicorn',
			26 => 'Green Dragon',
			27 => 'Gold Dragon',
			28 => 'Gremlin',
			29 => 'Master Gremlin',
			30 => 'Stone Gargoyle',
			31 => 'Obsidian Gargoyle',
			32 => 'Stone Golem',
			33 => 'Iron Golem',
			34 => 'Mage',
			35 => 'Arch Mage',
			36 => 'Genie',
			37 => 'Master Genie',
			38 => 'Naga',
			39 => 'Naga Queen',
			40 => 'Giant',
			41 => 'Titan',
			42 => 'Imp',
			43 => 'Familiar',
			44 => 'Gog',
			45 => 'Magog',
			46 => 'Hell Hound',
			47 => 'Cerberus',
			48 => 'Demon',
			49 => 'Horned Demon',
			50 => 'Pit Fiend',
			51 => 'Pit Lord',
			52 => 'Efreeti',
			53 => 'Efreet Sultan',
			54 => 'Devil',
			55 => 'Arch Devil',
			56 => 'Skeleton',
			57 => 'Skeleton Warrior',
			58 => 'Walking Dead',
			59 => 'Zombie',
			60 => 'Wight',
			61 => 'Wraith',
			62 => 'Vampire',
			63 => 'Vampire Lord',
			64 => 'Lich',
			65 => 'Power Lich',
			66 => 'Black Knight',
			67 => 'Dread Knight',
			68 => 'Bone Dragon',
			69 => 'Ghost Dragon',
			70 => 'Troglodyte',
			71 => 'Infernal Troglodyte',
			72 => 'Harpy',
			73 => 'Harpy Hag',
			74 => 'Beholder',
			75 => 'Evil Eye',
			76 => 'Medusa',
			77 => 'Medusa Queen',
			78 => 'Minotaur',
			79 => 'Minotaur King',
			80 => 'Manticore',
			81 => 'Scorpicore',
			82 => 'Red Dragon',
			83 => 'Black Dragon',
			84 => 'Goblin',
			85 => 'Hobgoblin',
			86 => 'Wolf Rider',
			87 => 'Wolf Raider',
			88 => 'Orc',
			89 => 'Orc Chieftain',
			90 => 'Ogre',
			91 => 'Ogre Mage',
			92 => 'Roc',
			93 => 'Thunderbird',
			94 => 'Cyclops',
			95 => 'Cyclops King',
			96 => 'Behemoth',
			97 => 'Ancient Behemoth',
			98 => 'Gnoll',
			99 => 'Gnoll Marauder',
			100 => 'Lizardman',
			101 => 'Lizard Warrior',
			102 => 'Gorgon',
			103 => 'Mighty Gorgon',
			104 => 'Serpent Fly',
			105 => 'Dragon Fly',
			106 => 'Basilisk',
			107 => 'Greater Basilisk',
			108 => 'Wyvern',
			109 => 'Wyvern Monarch',
			110 => 'Hydra',
			111 => 'Chaos Hydra',
			112 => 'Air Elemental',
			113 => 'Earth Elemental',
			114 => 'Fire Elemental',
			115 => 'Water Elemental',
			116 => 'Gold Golem',
			117 => 'Diamond Golem',
			118 => 'Pixie',
			119 => 'Sprite',
			120 => 'Psychic Elemental',
			121 => 'Magic Elemental',
			122 => 'NOT USED (attacker)',
			123 => 'Ice Elemental',
			124 => 'NOT USED (defender)',
			125 => 'Magma Elemental',
			126 => 'NOT USED (3)',
			127 => 'Storm Elemental',
			128 => 'NOT USED (4)',
			129 => 'Energy Elemental',
			130 => 'Firebird',
			131 => 'Phoenix',
			132 => 'Azure Dragon',
			133 => 'Crystal Dragon',
			134 => 'Faerie Dragon',
			135 => 'Rust Dragon',
			136 => 'Enchanter',
			137 => 'Sharpshooter',
			138 => 'Halfling',
			139 => 'Peasant',
			140 => 'Boar',
			141 => 'Mummy',
			142 => 'Nomad',
			143 => 'Rogue',
			144 => 'Troll',
			145 => 'Catapult (specialty X1)',
			146 => 'Ballista (specialty X1)',
			147 => 'First Aid Tent (specialty X1)',
			148 => 'Ammo Cart (specialty X1)',
			149 => 'Arrow Towers (specialty X1)',
			//WOG
			150 => 'Supreme Archangel',
			151 => 'Diamond Dragon',
			152 => 'Lord of Thunder',
			153 => 'Antichrist',
			154 => 'Blood Dragon',
			155 => 'Darkness Dragon',
			156 => 'Ghost Behemoth',
			157 => 'Hell Hydra',
			158 => 'Sacred Phoenix',
			159 => 'Ghost',
			160 => 'Emissary of War',
			161 => 'Emissary of Peace',
			162 => 'Emissary of Mana',
			163 => 'Emissary of Lore',
			164 => 'Fire Messenger',
			165 => 'Earth Messenger',
			166 => 'Air Messenger',
			167 => 'Water Messenger',
			168 => 'Gorynych',
			169 => 'War zealot',
			170 => 'Arctic Sharpshooter',
			171 => 'Lava Sharpshooter',
			172 => 'Nightmare',
			173 => 'Santa Gremlin',
			174 => 'Paladin (attacker)',
			175 => 'Hierophant (attacker)',
			176 => 'Temple Guardian (attacker)',
			177 => 'Succubus (attacker)',
			178 => 'Soul Eater (attacker)',
			179 => 'Brute (attacker)',
			180 => 'Ogre Leader (attacker)',
			181 => 'Shaman (attacker)',
			182 => 'Astral Spirit (attacker)',
			183 => 'Paladin (defender)',
			184 => 'Hierophant (defender)',
			185 => 'Temple Guardian (defender)',
			186 => 'Succubus (defender)',
			187 => 'Soul Eater (defender)',
			188 => 'Brute (defender)',
			189 => 'Ogre Leader (defender)',
			190 => 'Shaman (defender)',
			191 => 'Astral Spirit (defender)',
			192 => 'Sylvan Centaur',
			193 => 'Sorceress',
			194 => 'Werewolf',
			195 => 'Hell Steed',
			196 => 'Dracolich',
			1000 => 'Random lvl 1',
			1001 => 'Random lvl 1 Upg',
			1002 => 'Random lvl 2',
			1003 => 'Random lvl 2 Upg',
			1004 => 'Random lvl 3',
			1005 => 'Random lvl 3 Upg',
			1006 => 'Random lvl 4',
			1007 => 'Random lvl 4 Upg',
			1008 => 'Random lvl 5',
			1009 => 'Random lvl 5 Upg',
			1010 => 'Random lvl 6',
			1011 => 'Random lvl 6 Upg',
			1012 => 'Random lvl 7',
			1013 => 'Random lvl 7 Upg',
		];

		//HOTA
		public $MonsterHota = [
			151 => 'Sea Dog',
			153 => 'Nymph',
			154 => 'Oceanid',
			155 => 'Crew Mate',
			156 => 'Seaman',
			157 => 'Pirate',
			158 => 'Corsair',
			159 => 'Stormbird',
			160 => 'Ayssid',
			161 => 'Sea Witch',
			162 => 'Sorceress',
			163 => 'Nix',
			164 => 'Nix Warrior',
			165 => 'Sea Serpent',
			166 => 'Haspid',
			167 => 'Satyr',
			168 => 'Fangarm',
			169 => 'Leprechaun',
			170 => 'Steel Golem',
			//138 => 'Halfling',
			171 => 'Halfling Grenadier',
			172 => 'Mechanic',
			173 => 'Engineer',
			174 => 'Armadillo',
			175 => 'Bellwether Armadillo',
			176 => 'Automaton',
			177 => 'Sentinel Automaton',
			178 => 'Sandworm',
			179 => 'Olgoi-Khorkhoi',
			180 => 'Gunslinger',
			181 => 'Bounty Hunter',
			182 => 'Couatl',
			183 => 'Crimson Couatl',
			184 => 'Dreadnought',
			185 => 'Juggernaut',
			255 => 'Random',

		];

		//array with monster indexes for town events
		public $TownUnits = [
			[0, 2, 4, 6, 8, 10, 12], //castle
			[14, 16, 18, 20, 22, 24, 26], //rampart
			[28, 30, 32, 34, 36, 38, 40], //tower
			[42, 44, 46, 48, 50, 52, 54], //inferno
			[56, 58, 60, 62, 64, 66, 68], //necropolis
			[70, 72, 74, 76, 78, 80, 82], //dungeon
			[84, 86, 88, 90, 92, 94, 96], //stronghold
			[98, 100, 102, 104, 106, 108, 110], //fortress
			[118, 112, 115, 114, 113, 120, 130], //conflux
			[153, 155, 157, 159, 161, 163, 165], //cove
			[171, 173, 175, 177, 179, 181, 183, 185], //factory, 8 unit types
		];

		public $monchar = [
			0 => 'Always join', //compliant
			1 => 'Likely join', //friendly
			2 => 'May join', //aggressive
			3 => 'Unlikely to join', //hostile
			4 => 'Never join', //savage
		];

		public $Objects = [
			0 => '<none>',
			1 => '<none>',
			2 => 'Altar of Sacrifice',
			3 => 'Anchor Point',
			4 => 'Arena',
			5 => 'Artifact',
			6 => 'Pandora\'s Box',
			7 => 'Black Market',
			8 => 'Boat',
			9 => 'Borderguard',
			10 => 'Keymaster\'s Tent',
			11 => 'Buoy',
			12 => 'Campfire',
			13 => 'Cartographer',
			14 => 'Swan Pond',
			15 => 'Cover of Darkness',
			16 => 'Creature Bank',
			17 => 'Creature Generator 1',
			18 => 'Creature Generator 2',
			19 => 'Creature Generator 3',
			20 => 'Creature Generator 4',
			21 => 'Cursed Ground',
			22 => 'Corpse',
			23 => 'Marletto Tower',
			24 => 'Derelict Ship',
			25 => 'Dragon Utopia',
			26 => 'Event',
			27 => 'Eye of the Magi',
			28 => 'Faerie Ring',
			29 => 'Flotsam',
			30 => 'Fountain of Fortune',
			31 => 'Fountain of Youth',
			32 => 'Garden of Revelation',
			33 => 'Garrison',
			34 => 'Hero',
			35 => 'Hill Fort',
			36 => 'Grail',
			37 => 'Hut of the Magi',
			38 => 'Idol of Fortune',
			39 => 'Lean To',
			40 => '<blank>',
			41 => 'Library of Enlightenment',
			42 => 'Lighthouse',
			43 => 'Monolith One Way Entrance',
			44 => 'Monolith One Way Exit',
			45 => 'Monolith Two Way',
			46 => 'Magic Plains',
			47 => 'School of Magic',
			48 => 'Magic Spring',
			49 => 'Magic Well',
			50 => '<blank>',
			51 => 'Mercenary Camp',
			52 => 'Mermaid',
			53 => 'Mine',
			54 => 'Monster',
			55 => 'Mystical Garden',
			56 => 'Oasis',
			57 => 'Obelisk',
			58 => 'Redwood Observatory',
			59 => 'Ocean Bottle',
			60 => 'Pillar of Fire',
			61 => 'Star Axis',
			62 => 'Prison',
			63 => 'Pyramid',
			64 => 'Rally Flag',
			65 => 'Random Artifact',
			66 => 'Random Treasure Artifact',
			67 => 'Random Minor Artifact',
			68 => 'Random Major Artifact',
			69 => 'Random Relic',
			70 => 'Random Hero',
			71 => 'Random Monster',
			72 => 'Random Monster 1',
			73 => 'Random Monster 2',
			74 => 'Random Monster 3',
			75 => 'Random Monster 4',
			76 => 'Random Resource',
			77 => 'Random Town',
			78 => 'Refugee Camp',
			79 => 'Resource',
			80 => 'Sanctuary',
			81 => 'Scholar',
			82 => 'Sea Chest',
			83 => 'Seer\'s Hut',
			84 => 'Crypt',
			85 => 'Shipwreck',
			86 => 'Shipwreck Survivor',
			87 => 'Shipyard',
			88 => 'Shrine of Magic Incantation',
			89 => 'Shrine of Magic Gesture',
			90 => 'Shrine of Magic Thought',
			91 => 'Sign',
			92 => 'Sirens',
			93 => 'Spell Scroll',
			94 => 'Stables',
			95 => 'Tavern',
			96 => 'Temple',
			97 => 'Den of Thieves',
			98 => 'Town',
			99 => 'Trading Post',
			100 => 'Learning Stone',
			101 => 'Treasure Chest',
			102 => 'Tree of Knowledge',
			103 => 'Subterranean Gate',
			104 => 'University',
			105 => 'Wagon',
			106 => 'War Machine Factory',
			107 => 'School of War',
			108 => 'Warrior\'s Tomb',
			109 => 'Water Wheel',
			110 => 'Watering Hole',
			111 => 'Whirlpool',
			112 => 'Windmill',
			113 => 'Witch Hut',
			114 => 'Brush',
			115 => 'Bush',
			116 => 'Cactus',
			117 => 'Canyon',
			118 => 'Crater',
			119 => 'Dead Vegetation',
			120 => 'Flowers',
			121 => 'Frozen Lake',
			122 => 'Hedge',
			123 => 'Hill',
			124 => 'Hole',
			125 => 'Kelp',
			126 => 'Lake',
			127 => 'Lava Flow',
			128 => 'Lava Lake',
			129 => 'Mushrooms',
			130 => 'Log',
			131 => 'Mandrake',
			132 => 'Moss',
			133 => 'Mound',
			134 => 'Mountain',
			135 => 'Oak Trees',
			136 => 'Outcropping',
			137 => 'Pine Trees',
			138 => 'Plant',
			143 => 'River Delta',
			146 => 'Seafaring Academy',
			147 => 'Rock',
			148 => 'Sand Dune',
			149 => 'Sand Pit',
			150 => 'Shrub',
			151 => 'Skull',
			152 => 'Stalagmite',
			153 => 'Stump',
			154 => 'Tar Pit',
			155 => 'Trees',
			156 => 'Vine',
			157 => 'Volcanic Vent',
			158 => 'Volcano',
			159 => 'Willow Trees',
			160 => 'Yucca Trees',
			161 => 'Reef',
			162 => 'Random Monster 5',
			163 => 'Random Monster 6',
			164 => 'Random Monster 7',
			165 => 'Brush',
			166 => 'Bush',
			167 => 'Cactus',
			168 => 'Canyon',
			169 => 'Crater',
			170 => 'Dead Vegetation',
			171 => 'Flowers',
			172 => 'Frozen Lake',
			173 => 'Hedge',
			174 => 'Hill',
			175 => 'Hole',
			176 => 'Kelp',
			177 => 'Lake',
			178 => 'Lava Flow',
			179 => 'Lava Lake',
			180 => 'Mushrooms',
			181 => 'Log',
			182 => 'Mandrake',
			183 => 'Moss',
			184 => 'Mound',
			185 => 'Mountain',
			186 => 'Oak Trees',
			187 => 'Outcropping',
			188 => 'Pine Trees',
			189 => 'Plant',
			190 => 'River Delta',
			191 => 'Rock',
			192 => 'Sand Dune',
			193 => 'Sand Pit',
			194 => 'Shrub',
			195 => 'Skull',
			196 => 'Stalagmite',
			197 => 'Stump',
			198 => 'Tar Pit',
			199 => 'Trees',
			200 => 'Vine',
			201 => 'Volcanic Vent',
			202 => 'Volcano',
			203 => 'Willow Trees',
			204 => 'Yucca Trees',
			205 => 'Reef',
			206 => 'Desert Hills',
			207 => 'Dirt Hills',
			208 => 'Grass Hills',
			209 => 'Rough Hills',
			210 => 'Subterranean Rocks',
			211 => 'Swamp Foliage',
			212 => 'Border Gate',
			213 => 'Freelancer\'s Guild',
			214 => 'Hero Placeholder',
			215 => 'Quest Guard',
			216 => 'Random Dwelling',
			217 => 'Random dwelling with no home castle type',
			218 => 'Random dwelling with home castle type',
			219 => 'Garrison',
			220 => 'Abandoned Mine',
			221 => 'Trading Post',
			222 => 'Clover Field',
			223 => 'Cursed Ground',
			224 => 'Evil Fog',
			225 => 'Favourable Winds',
			226 => 'Fiery Fields',
			227 => 'Holy Ground',
			228 => 'Lucid Pools',
			229 => 'Magic Clouds',
			230 => 'Magic Plains',
			231 => 'Rocklands',
		];

		public $Mines = [
			0 => 'Sawmill',
			1 => 'Alchemist\'s Lab',
			2 => 'Ore Pit',
			3 => 'Sulfur Dune',
			4 => 'Crystal Cavern',
			5 => 'Gem Pond',
			6 => 'Gold Mine',
			7 => 'Abandoned Mine',
		];

		public $Resources = [
			0 => 'Wood',
			1 => 'Mercury',
			2 => 'Ore',
			3 => 'Sulfur',
			4 => 'Crystal',
			5 => 'Gems',
			6 => 'Gold',
			253 => 'Wood and Ore',
			254 => 'Mercury, Sulfur, Crystal and Gems',
		];

		public $Artefacts = [
			0 => 'Spell book',
			1 => 'Spell Scroll',
			2 => 'Grail',
			3 => 'Catapult',
			4 => 'Ballista',
			5 => 'Ammo Cart',
			6 => 'First Aid Tent',
			7 => 'Centaur Axe',
			8 => 'Blackshard of the Dead Knight',
			9 => 'Greater Gnoll\'s Flail',
			10 => 'Ogre\'s Club of Havoc',
			11 => 'Sword of Hellfire',
			12 => 'Titan\'s Gladius',
			13 => 'Shield of the Dwarven Lords',
			14 => 'Shield of the Yawning Dead',
			15 => 'Buckler of the Gnoll King',
			16 => 'Targ of the Rampaging Ogre',
			17 => 'Shield of the Damned',
			18 => 'Sentinel\'s Shield',
			19 => 'Helm of the Alabaster Unicorn',
			20 => 'Skull Helmet',
			21 => 'Helm of Chaos',
			22 => 'Crown of the Supreme Magi',
			23 => 'Hellstorm Helmet',
			24 => 'Thunder Helmet',
			25 => 'Breastplate of Petrified Wood',
			26 => 'Rib Cage',
			27 => 'Scales of the Greater Basilisk',
			28 => 'Tunic of the Cyclops King',
			29 => 'Breastplate of Brimstone',
			30 => 'Titan\'s Cuirass',
			31 => 'Armor of Wonder',
			32 => 'Sandals of the Saint',
			33 => 'Celestial Necklace of Bliss',
			34 => 'Lion\'s Shield of Courage',
			35 => 'Sword of Judgement',
			36 => 'Helm of Heavenly Enlightenment',
			37 => 'Quiet Eye of the Dragon',
			38 => 'Red Dragon Flame Tongue',
			39 => 'Dragon Scale Shield',
			40 => 'Dragon Scale Armor',
			41 => 'Dragonbone Greaves',
			42 => 'Dragon Wing Tabard',
			43 => 'Necklace of Dragonteeth',
			44 => 'Crown of Dragontooth',
			45 => 'Still Eye of the Dragon',
			46 => 'Clover of Fortune',
			47 => 'Cards of Prophecy',
			48 => 'Ladybird of Luck',
			49 => 'Badge of Courage',
			50 => 'Crest of Valor',
			51 => 'Glyph of Gallantry',
			52 => 'Speculum',
			53 => 'Spyglass',
			54 => 'Amulet of the Undertaker',
			55 => 'Vampire\'s Cowl',
			56 => 'Dead Man\'s Boots',
			57 => 'Garniture of Interference',
			58 => 'Surcoat of Counterpoise',
			59 => 'Boots of Polarity',
			60 => 'Bow of Elven Cherrywood',
			61 => 'Bowstring of the Unicorn\'s Mane',
			62 => 'Angel Feather Arrows',
			63 => 'Bird of Perception',
			64 => 'Stoic Watchman',
			65 => 'Emblem of Cognizance',
			66 => 'Statesman\'s Medal',
			67 => 'Diplomat\'s Ring',
			68 => 'Ambassador\'s Sash',
			69 => 'Ring of the Wayfarer',
			70 => 'Equestrian\'s Gloves',
			71 => 'Necklace of Ocean Guidance',
			72 => 'Angel Wings',
			73 => 'Charm of Mana',
			74 => 'Talisman of Mana',
			75 => 'Mystic Orb of Mana',
			76 => 'Collar of Conjuring',
			77 => 'Ring of Conjuring',
			78 => 'Cape of Conjuring',
			79 => 'Orb of the Firmament',
			80 => 'Orb of Silt',
			81 => 'Orb of Tempestuous Fire',
			82 => 'Orb of Driving Rain',
			83 => 'Recanter\'s Cloak',
			84 => 'Spirit of Oppression',
			85 => 'Hourglass of the Evil Hour',
			86 => 'Tome of Fire Magic',
			87 => 'Tome of Air Magic',
			88 => 'Tome of Water Magic',
			89 => 'Tome of Earth Magic',
			90 => 'Boots of Levitation',
			91 => 'Golden Bow',
			92 => 'Sphere of Permanence',
			93 => 'Orb of Vulnerability',
			94 => 'Ring of Vitality',
			95 => 'Ring of Life',
			96 => 'Vial of Lifeblood',
			97 => 'Necklace of Swiftness',
			98 => 'Boots of Speed',
			99 => 'Cape of Velocity',
			100 => 'Pendant of Dispassion',
			101 => 'Pendant of Second Sight',
			102 => 'Pendant of Holiness',
			103 => 'Pendant of Life',
			104 => 'Pendant of Death',
			105 => 'Pendant of Free Will',
			106 => 'Pendant of Negativity',
			107 => 'Pendant of Total Recall',
			108 => 'Pendant of Courage',
			109 => 'Everflowing Crystal Cloak',
			110 => 'Ring of Infinite Gems',
			111 => 'Everpouring Vial of Mercury',
			112 => 'Inexhaustible Cart of Ore',
			113 => 'Eversmoking Ring of Sulfur',
			114 => 'Inexhaustible Cart of Lumber',
			115 => 'Endless Sack of Gold',
			116 => 'Endless Bag of Gold',
			117 => 'Endless Purse of Gold',
			118 => 'Legs of Legion',
			119 => 'Loins of Legion',
			120 => 'Torso of Legion',
			121 => 'Arms of Legion',
			122 => 'Head of Legion',
			123 => 'Sea Captain\'s Hat',
			124 => 'Spellbinder\'s Hat',
			125 => 'Shackles of War',
			126 => 'Orb of Inhibition',
			127 => 'Vial of Dragon Blood',
			128 => 'Armageddon\'s Blade',
			129 => 'Angelic Alliance',
			130 => 'Cloak of the Undead King',
			131 => 'Elixir of Life',
			132 => 'Armor of the Damned',
			133 => 'Statue of Legion',
			134 => 'Power of the Dragon Father',
			135 => 'Titan\'s Thunder',
			136 => 'Admiral\'s Hat',
			137 => 'Bow of the Sharpshooter',
			138 => 'Wizard\'s Well',
			139 => 'Ring of the Magi',
			140 => 'Cornucopia',

			//WOG
			141 => 'Magic Wand',
			142 => 'Gold Tower Arrow',
			143 => 'Monster\'s Power',
			144 => 'Highlighted Slot',
			145 => 'Artifact Lock',
			146 => 'Axe of Smashing',
			147 => 'Mithril Mail',
			148 => 'Sword of Sharpness',
			149 => 'Helm of Immortality',
			150 => 'Pendant of Sorcery',
			151 => 'Boots of Haste',
			152 => 'Bow of Seeking',
			153 => 'Dragon Eye Ring',
			154 => 'Hardened Shield',
			155 => 'Slava\'s Ring of Power',
			156 => 'Warlord\'s banner',
			157 => 'Crimson Shield of Retribution',
			158 => 'Barbarian Lord\'s Axe of Ferocity',
			159 => 'Dragonheart',
			160 => 'Gate Key',
			161 => 'Blank Helmet',
			162 => 'Blank Sword',
			163 => 'Blank Shield',
			164 => 'Blank Horned Ring',
			165 => 'Blank Gemmed Ring',
			166 => 'Blank Neck Broach',
			167 => 'Blank Armor',
			168 => 'Blank Surcoat',
			169 => 'Blank Boots',
			170 => 'Blank Horn',
		];

		//HOTA
		public $ArtefactsHota = [
			141 => 'Diplomat\'s Cloak',
			142 => 'Pendant of Reflection',
			143 => 'Ironfist of the Ogre',
			146 => 'Cannon',
			147 => 'Trident of Dominion',
			148 => 'Shield of Naval Glory',
			149 => 'Royal Armor of Nix',
			150 => 'Crown of the Five Seas',
			151 => 'Wayfarer\'s Boots',
			152 => 'Runes of Imminency',
			153 => 'Demon\'s Horseshoe',
			154 => 'Shaman\'s Puppet',
			155 => 'Hideous Mask',
			156 => 'Ring of Suppression',
			157 => 'Pendant of Downfall',
			158 => 'Ring of Oblivion',
			159 => 'Cape of Silence',
			160 => 'Golden Goose',
			161 => 'Horn of the Abyss',
			162 => 'Charm of Eclipse',
			163 => 'Seal of Sunset',
			164 => 'Plate of Dying Light',
			165 => 'Sleepkeeper',
		];

		public $HeroClass = [
			0 => 'Knight',
			1 => 'Cleric',
			2 => 'Ranger',
			3 => 'Druid',
			4 => 'Alchemist',
			5 => 'Wizard',
			6 => 'Demoniac',
			7 => 'Heretic',
			8 => 'Death Knight',
			9 => 'Necromancer',
			10 => 'Overlord',
			11 => 'Warlock',
			12 => 'Barbarian',
			13 => 'Battle Mage',
			14 => 'Beastmaster',
			15 => 'Witch',
			16 => 'PlanesWalker',
			17 => 'Elementalist',
			18 => 'Captain',
			19 => 'Navigator',
			20 => 'Mercenary',
			21 => 'Artificer',
			22 => 'Random',
		];


		public $Heroes = [
			//Knights
			0 => 'Orrin',
			1 => 'Valeska',
			2 => 'Edric',
			3 => 'Sylvia',
			4 => 'Lord Haart',
			5 => 'Sorsha',
			6 => 'Christian',
			7 => 'Tyris',
			//Clerics
			8 => 'Rion',
			9 => 'Adela',
			10 => 'Cuthbert',
			11 => 'Adelaide',
			12 => 'Ingham',
			13 => 'Sanya',
			14 => 'Loynis',
			15 => 'Caitlin',
			//Rangers
			16 => 'Mephala',
			17 => 'Ufretin',
			18 => 'Jenova',
			19 => 'Ryland',
			20 => 'Thorgrim',
			21 => 'Ivor',
			22 => 'Clancy',
			23 => 'Kyrre',
			//Druids
			24 => 'Coronius',
			25 => 'Uland',
			26 => 'Elleshar',
			27 => 'Gem',
			28 => 'Malcom',
			29 => 'Melodia',
			30 => 'Alagar',
			31 => 'Aeris',
			//Alchemists
			32 => 'Piquedram',
			33 => 'Thane',
			34 => 'Josephine',
			35 => 'Neela',
			36 => 'Torosar',
			37 => 'Fafner',
			38 => 'Rissa',
			39 => 'Iona',
			//Wizards
			40 => 'Astral',
			41 => 'Halon',
			42 => 'Serena',
			43 => 'Daremyth',
			44 => 'Theodorus',
			45 => 'Solmyr',
			46 => 'Cyra',
			47 => 'Aine',
			//Demoniacs
			48 => 'Fiona',
			49 => 'Rashka',
			50 => 'Marius',
			51 => 'Ignatius',
			52 => 'Octavia',
			53 => 'Calh',
			54 => 'Pyre',
			55 => 'Nymus',
			//Heretics
			56 => 'Ayden',
			57 => 'Xyron',
			58 => 'Axsis',
			59 => 'Olema',
			60 => 'Calid',
			61 => 'Ash',
			62 => 'Zydar',
			63 => 'Xarfax',
			//Death Knights
			64 => 'Straker',
			65 => 'Vokial',
			66 => 'Moandor',
			67 => 'Charna',
			68 => 'Tamika',
			69 => 'Isra',
			70 => 'Clavius',
			71 => 'Galthran',
			//Necromancers
			72 => 'Septienna',
			73 => 'Aislinn',
			74 => 'Sandro',
			75 => 'Nimbus',
			76 => 'Thant',
			77 => 'Xsi',
			78 => 'Vidomina',
			79 => 'Nagash',
			//Overlords
			80 => 'Lorelei',
			81 => 'Arlach',
			82 => 'Dace',
			83 => 'Ajit',
			84 => 'Damacon',
			85 => 'Gunnar',
			86 => 'Synca',
			87 => 'Shakti',
			//Warlocks
			88 => 'Alamar',
			89 => 'Jaegar',
			90 => 'Malekith',
			91 => 'Jeddite',
			92 => 'Geon',
			93 => 'Deemer',
			94 => 'Sephinroth',
			95 => 'Darkstorn',
			//Barbarians
			96 => 'Yog',
			97 => 'Gurnisson',
			98 => 'Jabarkas',
			99 => 'Shiva',
			100 => 'Gretchin',
			101 => 'Krellion',
			102 => 'Crag Hack',
			103 => 'Tyraxor',
			//Battle Mages
			104 => 'Gird',
			105 => 'Vey',
			106 => 'Dessa',
			107 => 'Terek',
			108 => 'Zubin',
			109 => 'Gundula',
			110 => 'Oris',
			111 => 'Saurug',
			//Beastmasters
			112 => 'Bron',
			113 => 'Drakon',
			114 => 'Wystan',
			115 => 'Tazar',
			116 => 'Alkin',
			117 => 'Korbac',
			118 => 'Gerwulf',
			119 => 'Broghild',
			//Witches
			120 => 'Mirlanda',
			121 => 'Rosic',
			122 => 'Voy',
			123 => 'Verdish',
			124 => 'Merist',
			125 => 'Styg',
			126 => 'Andra',
			127 => 'Tiva',
			//Planeswalkers
			128 => 'Pasis',
			129 => 'Thunar',
			130 => 'Ignissa',
			131 => 'Lacus',
			132 => 'Monere',
			133 => 'Erdamon',
			134 => 'Fiur',
			135 => 'Kalt',
			//Elementalists
			136 => 'Luna',
			137 => 'Brissa',
			138 => 'Ciele',
			139 => 'Labetha',
			140 => 'Inteus',
			141 => 'Aenain',
			142 => 'Gelare',
			143 => 'Grindan',
			//Extension Heroes
			144 => 'Sir Mullich', //knight
			145 => 'Adrienne', //witch
			146 => 'Catherine', //knight
			147 => 'Dracon', //wizard
			148 => 'Gelu', //ranger
			149 => 'Kilgor', //barbarian
			150 => 'Lord Haart', //death knight
			151 => 'Mutare',  //overlord
			152 => 'Roland', //knight
			153 => 'Mutare Drake',  //overlord
			154 => 'Boragus', //barbarian
			155 => 'Xeron', //demoniac

			//HOTA
			//Captain
			156 => 'Corkes',
			157 => 'Jeremy',
			158 => 'Illor',
			159 => 'Derek',
			160 => 'Leena',
			161 => 'Anabel',
			162 => 'Cassiopeia',
			163 => 'Miriam',
			//Navigator
			164 => 'Casmetra',
			165 => 'Eovacius',
			166 => 'Spint',
			167 => 'Andal',
			168 => 'Manfred',
			169 => 'Zilare',
			170 => 'Astra',
			//extra
			171 => 'Dargem', //navigator
			172 => 'Bidley', //captain
			173 => 'Tark', //captain
			174 => 'Elmore', //captain
			175 => 'Beatrice', //knight
			176 => 'Kinkeria', //witch
			177 => 'Ranloo', //death knight
			178 => 'Giselle', //ranger
			//Mercenary
			179 => 'Henrietta',
			180 => 'Sam',
			181 => 'Tancred',
			182 => 'Melchior',
			183 => 'Floribert',
			184 => 'Wynona',
			185 => 'Dury',
			186 => 'Morton',
			//Artificer
			187 => 'Celestine',
			188 => 'Todd',
			189 => 'Agar',
			190 => 'Bertram',
			191 => 'Wrathmont',
			192 => 'Ziph',
			193 => 'Victoria',
			194 => 'Eanswythe',
			//factory campaign heroes
			195 => 'Frederick', //Artificer
			196 => 'Tavin', //Mercenary
			197 => 'Murdoch', //Mercenary

			255 => 'Random',
			65533 => 'Most Powerful Hero'
		];

		//heroes class: heroid => classid
		public $HeroesClass = [
			0, 0, 0, 0, 0, 0, 0, 0, //Knights  0-7
			1, 1, 1, 1, 1, 1, 1, 1, //Clerics  8-15
			2, 2, 2, 2, 2, 2, 2, 2, //Rangers 16-23
			3, 3, 3, 3, 3, 3, 3, 3, //Druids 24-31
			4, 4, 4, 4, 4, 4, 4, 4, //Alchemists 32-39
			5, 5, 5, 5, 5, 5, 5, 5, //Wizards 40-47
			6, 6, 6, 6, 6, 6, 6, 6, //Demoniacs 48-55
			7, 7, 7, 7, 7, 7, 7, 7, //Heretics 56-63
			8, 8, 8, 8, 8, 8, 8, 8, //Death Knights 64-71
			9, 9, 9, 9, 9, 9, 9, 9, //Necromancers 72-79
			10, 10, 10, 10, 10, 10, 10, 10, //Overlords 80-87
			11, 11, 11, 11, 11, 11, 11, 11, //Warlocks 88-95
			12, 12, 12, 12, 12, 12, 12, 12, //Barbarians 96-103
			13, 13, 13, 13, 13, 13, 13, 13, //Battle Mages 104-111
			14, 14, 14, 14, 14, 14, 14, 14, //Beastmasters 112-119
			15, 15, 15, 15, 15, 15, 15, 15, //Witches 120-127
			16, 16, 16, 16, 16, 16, 16, 16, //Planeswalkers 128-135
			17, 17, 17, 17, 17, 17, 17, 17, //Elementalists 136-143
			0, 15, 0, 5, 2, 12, 8, 10, 0, 10, 12, 6, //SOD extra heroes 144-155

			//HOTA
			18, 18, 18, 18, 18, 18, 18, 18, //Captain 156-163
			19, 19, 19, 19, 19, 19, 19, 19, //Navigator 164-171
			18, 18, 18, 0, 15, 8, //HOTA extra heroes 172-177
			2, //178 ranger
			20, 20, 20, 20, 20, 20, 20, 20, //Mercenary 179-186
			21, 21, 21, 21, 21, 21, 21, 21, //Artificer 187-194
			21, 20, 20, //factory campaign 195-197
		];

		public $Buildings = [
			//byte 0
			0 => 'Town hall',  
			1 => 'City hall',
			2 => 'Capitol',
			3 => 'Fort',
			4 => 'Citadel',
			5 => 'Castle',
			6 => 'Tavern',
			7 => 'Blacksmith',
			//byte 1
			8 => 'Marketplace',
			9 => 'Resource silo',
			11 => 'Mages guild 1',
			12 => 'Mages guild 2',
			13 => 'Mages guild 3',
			14 => 'Mages guild 4',
			15 => 'Mages guild 5',
			//byte 2
			16 => 'Shipyard',
			17 => 'Grail',
			18 => 'Special 1',
			19 => 'Special 2', //?
			20 => 'Special 3',
			21 => 'Special 4',
			22 => 'Dwelling lvl 1',
			23 => 'Dwelling lvl 1 upg',
			//byte 3
			24 => 'Horde lvl 1',
			25 => 'Dwelling lvl 2',
			26 => 'Dwelling lvl 2 upg',
			27 => 'Horde lvl 2',
			28 => 'Dwelling lvl 3',
			29 => 'Dwelling lvl 3 upg',
			30 => 'Horde lvl 3',
			31 => 'Dwelling lvl 4',
			//byte 4
			32 => 'Dwelling lvl 4 upg',
			33 => 'Horde lvl 4',
			34 => 'Dwelling lvl 5',
			35 => 'Dwelling lvl 5 upg',
			36 => 'Horde lvl 5',
			37 => 'Dwelling lvl 6',
			38 => 'Dwelling lvl 6 upg',
			39 => 'Dwelling lvl 7',
			//byte 5
			40 => 'Dwelling lvl 7 upg',
		];
		
		public $MonolithsOne = [
			0 => 'Blue',
			1 => 'Pink',
			2 => 'Orange',
			3 => 'Yellow',
			4 => 'Purple portal',
			5 => 'Orange portal',
			6 => 'Red portal',
			7 => 'Cyan portal',
			8 => 'Turquoise',
			9 => 'Violet',
			10 => 'Chartreuse',
			11 => 'White',
		];
		
		public $MonolithsTwo = [
			0 => 'Green',
			1 => 'Brown',
			2 => 'Violet',
			3 => 'Orange',
			4 => 'Green portal',
			5 => 'Yellow portal',
			6 => 'Red portal',
			7 => 'Cyan portal',
			8 => 'White sea portal',
			9 => 'Pink',
			10 => 'Turquoise',
			11 => 'Yellow',
			12 => 'Black',
			13 => 'Chartreuse portal',
			14 => 'Turquoise portal',
			15 => 'Violet portal',
			16 => 'Orange portal',
			17 => 'Blue',
			18 => 'Red',
			19 => 'Pink portal',
			20 => 'Blue portal',
			21 => 'Red sea portal',
			22 => 'Blue sea portal',
			23 => 'Green sea portal',
			24 => 'Yellow sea portal',
		];

		public $Experience = [
			1 => 0,
			2 => 1000,
			3 => 2000,
			4 => 3200,
			5 => 4600,
			6 => 6200,
			7 => 8000,
			8 => 10000,
			9 => 12200,
			10 => 14700,
			11 => 17500,
			12 => 20600,
			13 => 24320,
			14 => 28784,
			15 => 34140,
			16 => 40567,
			17 => 48279,
			18 => 57533,
			19 => 68637,
			20 => 81961,
			21 => 97949,
			22 => 117134,
			23 => 140156,
			24 => 167782,
			25 => 200933,
			26 => 240714,
			27 => 288451,
			28 => 345735,
			29 => 414475,
			30 => 496963,
			31 => 595948,
			32 => 714730,
			33 => 857268,
			34 => 1028313,
			35 => 1233567,
			36 => 1479871,
			37 => 1775435,
			38 => 2130111,
			39 => 2555722,
			40 => 3066455,
			41 => 3679334,
			42 => 4414788,
			43 => 5297332,
			44 => 6356384,
			45 => 7627246,
			46 => 9152280,
			47 => 10982320,
			48 => 13178368,
			49 => 15813625,
			50 => 18975933,
			51 => 22770702,
			52 => 27324424,
			53 => 32788890,
			54 => 39346249,
			55 => 47215079,
			56 => 56657675,
			57 => 67988790,
			58 => 81586128,
			59 => 97902933,
			60 => 117483099,
			61 => 140979298,
			62 => 169174736,
			63 => 203009261,
			64 => 243610691,
			65 => 292332407,
			66 => 350798466,
			67 => 420957736,
			68 => 505148860,
			69 => 606178208,
			70 => 727413425,
			71 => 872895685,
			72 => 1047474397,
			73 => 1256968851,
			74 => 1508362195,
			75 => 1810034207,
			//76 => -2122926675,
			76 => 0x100000000,
		];
		
		public $ObjectColors = [
			0 => 'Blue',
			1 => 'Green',
			2 => 'Red',
			3 => 'Dark Blue',
			4 => 'Tan',
			5 => 'Purple',
			6 => 'White',
			7 => 'Black',
		];

		public $BlockMapBits = [
			BLOCKMAPBITS::VISIBLE   => 'Visible', //1
			BLOCKMAPBITS::VISITABLE => 'Visitable', //2
			BLOCKMAPBITS::BLOCKED   => 'Blocked',  //4
			BLOCKMAPBITS::COMBINED  => 'Combined', //6
		];

		public $RewardType = [
			REWARDTYPE::NOTHING => 'Nothing',
			REWARDTYPE::EXPERIENCE => 'Experience',
			REWARDTYPE::MANA_POINTS => 'Mana points',
			REWARDTYPE::MORALE_BONUS => 'Morale bonus',
			REWARDTYPE::LUCK_BONUS => 'Luck bonus',
			REWARDTYPE::RESOURCES => 'Resources',
			REWARDTYPE::PRIMARY_SKILL => 'Primary Skill',
			REWARDTYPE::SECONDARY_SKILL => 'Secondary skill',
			REWARDTYPE::ARTIFACT => 'Artifact',
			REWARDTYPE::SPELL => 'Spell',
			REWARDTYPE::CREATURE => 'Creature',
	];

		public $QuestMission = [
			QUESTMISSION::NONE => 'None',
			QUESTMISSION::LEVEL => 'Level',
			QUESTMISSION::PRIMARY_STAT => 'Primary stat',
			QUESTMISSION::KILL_HERO => 'Kill hero',
			QUESTMISSION::KILL_CREATURE => 'Kill creature',
			QUESTMISSION::ART => 'Artifact',
			QUESTMISSION::ARMY => 'Army',
			QUESTMISSION::RESOURCES => 'Resources',
			QUESTMISSION::HERO => 'Hero',
			QUESTMISSION::PLAYER => 'Player',
			QUESTMISSION::KEYMASTER => 'Keymaster',
		];

		public $Victory = [
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

		public $Loss = [
			LOSS::NONE => 'None',
			LOSS::TOWN => 'Lose a specific town',
			LOSS::HERO => 'Lose a specific hero',
			LOSS::TIME => 'time',
		];
	}







?>
