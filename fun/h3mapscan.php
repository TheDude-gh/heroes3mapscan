<?php

require_once 'fun/bytereader.php';

const H3M_WEBMODE       = 0x0001; //required for printinfo
const H3M_PRINTINFO     = 0x0002; //prints map info, requires webmode
const H3M_BUILDMAP      = 0x0004; //builds map image
const H3M_SAVEMAPDB     = 0x0008; //saves map info to DB
const H3M_EXPORTMAP     = 0x0010; //uncompresses and saves pure h3m file
const H3M_BASICONLY     = 0x0020; //reads only basic info about map, for fast read, when active, wont read and build map image
const H3M_MAPHTMCACHE   = 0x0040; //save printinfo htm file, requires printinfo
const H3M_SPECIALACCESS = 0x0080; //displays some objects on map in different color
const H3M_TERRAINONLY   = 0x0100; //reads basic info and terrain only

class H3MAPSCAN {
	const IMGSIZE = 576;
	const ROE  = 0x0e;
	const AB   = 0x15;
	const SOD  = 0x1c;
	const WOG  = 0x33;
	const HOTA = 0x20;

	const HOTA_SUBREV1 = 1;
	const HOTA_SUBREV2 = 2;
	const HOTA_SUBREV3 = 3;
	const HOTA_SUBREV4 = 4;
	const HOTA_SUBREV5 = 5;

	//variables to simplify version checks
	private $isROE   = false;
	private $isAB    = false;
	private $isSOD   = false;
	private $isWOG   = false;
	private $isHOTA  = false;

	public  $version = '';
	public  $versionname = '';
	public  $hota_subrev = 0;
	public  $map_name = '';
	public  $description = '';
	private $author = '';
	private $language = null;
	public  $underground = 0;
	private $map_diff = -1;     //difficulty
	public  $map_diffname = ''; //difficulty name
	private $hero_any_onmap = 0;
	public  $hero_levelcap = 0;
	public  $teamscount;
	public  $teams = [];
	private $victoryCond = [];
	private $lossCond = [];
	public  $victoryInfo = '';
	public  $lossInfo = '';
	public  $playerMask = 0; //allowed players on map

	private $rumorsCount = 0;
	public  $rumors = [];
	public  $events = [];

	private $allowedArtifacts = [];
	public  $disabledArtifacts = [];
	private $allowedSpells = [];
	private $disabledSpellsId = [];
	public  $disabledSpells = [];
	private $allowedSkills = [];
	public  $disabledSkills = [];

	public  $objTemplatesNum = 0;
	private $objTemplates = [];
	public  $objectsNum = 0;
	private $objects = [];
	public  $objects_unique = [];

	private $freeHeroes = [];
	public  $disabledHeroes = [];
	private $customHeroes = [];
	public  $heroesPredefined = [];

	//HOTA extras
	private $hota_arena = 0;
	private $monplague_week = 0;
	private $combat_round_limit = 0;

	//object of interest lists
	public  $artifacts_list = [];
	public  $heroes_list = [];
	public  $heroes_placeholder = [];
	public  $spells_list = [];
	public  $towns_list = [];
	public  $mines_list = [];
	public  $monsters_list = [];
	public  $event_list = []; //global events
	public  $quest_list = [];
	public  $events_list = []; //map event, pandora, town event
	public  $messages_list = []; //signs and bottles
	public  $keys_list = [];
	public  $monolith_list = [];

	//curent object being read and its coords
	private $curobj;
	private $curcoor;
	private $curowner;

	private $mapobjects = []; //heroes, towns and monsters

	public  $map_size = 0;
	public  $map_sizename = '';
	private $terrain = [];
	public  $terrainRate = [[], [], []];

	private $name = '';

	private $isGzip;
	public  $mapfile = '';
	private $mapfilename = '';
	private $mapfileout = '';
	private $mapimage; //mapfile name for DB
	private $mapfileinfo;
	private $md5hash = '';
	public  $mapid = 0;
	public  $camid = 0;

	public  $players = [];
	public  $mapplayersnum = 0;
	public  $mapplayershuman = 0;
	public  $mapplayersai = 0;

	public  $CS; //heroes constants class
	private $SC; //String Convert

	//mode switches
	private $webmode = false; //webmode, when not in webmode, print info and build map will be skipped
	private $printoutput = false; //print info
	private $buildMapImage = false; //build map
	private $save = false; //save maps to db
	private $exportmap = false; //uncompress gzip and save pure h3m file
	private $basiconly = false; //read only basic info map, wont make map nor object info
	private $special_access = false; //draw special tiles on map image
	public  $maphtmcache = false; //cache htm printinfo
	private $terrainonly = false; //reads only basic info and terrain

	private $fastread = false; //fast read is combination of $buildMapImage and $save for cases only to build map image and save basic info to DB
	private $skipstrings = false; // when fast info, string after map description will be skipped

	private $debug;
	private $tm; //TimeMeasure

	private $br = null; //bytereader

	private $filemtime = '';
	private $filectime = '';
	private $filesizeC = 0;
	private $filesizeU = 0;
	private $filebad = false;
	public $readok = false;

	//print class
	private $h3mapscan_print;


	public function __construct($mapfile, $modes = 0, $mapdata = null) {
		$this->webmode        = ($modes & H3M_WEBMODE);
		$this->printoutput    = ($modes & H3M_PRINTINFO);
		$this->buildMapImage  = ($modes & H3M_BUILDMAP);
		$this->save           = ($modes & H3M_SAVEMAPDB);
		$this->exportmap      = ($modes & H3M_EXPORTMAP);
		$this->basiconly      = ($modes & H3M_BASICONLY);
		$this->maphtmcache    = ($modes & H3M_MAPHTMCACHE);
		$this->special_access = ($modes & H3M_SPECIALACCESS);
		$this->terrainonly    = ($modes & H3M_TERRAINONLY);
		$this->fastread       = ($modes == (H3M_BUILDMAP | H3M_SAVEMAPDB));

		if($mapdata != null) {
			$this->br = new ByteReader($mapdata);
			$this->mapfile = $mapfile;
			$this->mapfilename = str_ireplace('.h3m', '', $mapfile); //when given from h3camscan, the mapfile is with extension -> remove it here for images
			return;
		}

		//$this->tm = new TimeMeasure();

		$this->mapfile = $mapfile;
		$path = pathinfo($this->mapfile);
		$this->mapfileinfo = $path;

		$this->mapfileout = MAPDIREXP.$path['filename'].'.'.$path['extension'];

		$h3mfile_exists = file_exists($this->mapfile); //original compressed map
		$h3mfileun_exists = file_exists($this->mapfileout); //uncompressed map

		//map is already uncompressed
		if($h3mfile_exists && $this->IsGZIP() == false) {
			$this->mapfileout = $this->mapfile;
		}
		//map is gzipped (normal)
		else {
			if(!$h3mfile_exists) {
				echo 'Map file '.$path['filename'].'.'.$path['extension'].' does not exists!'.ENVE;
				$this->filebad = true;
				return;
			}
		}

		$this->mapfilename = $path['filename'];

		if(!$h3mfileun_exists || filemtime($this->mapfileout) < filemtime($this->mapfile)) {
			if(!$this->filebad) {
				if($this->isGzip) {
					$mapdata = gzdecode(file_get_contents($this->mapfile));
					if(!$mapdata) {
						echo $this->mapfile.' could not be uncompressed'.ENVE;
						$this->filebad = true;
					}
				}
				else {
					$mapdata = file_get_contents($this->mapfileout);
				}
			}
			if($mapdata === '') {
				echo $this->mapfile.' could not be uncompressed'.ENVE;
				return;
			}
			if($this->exportmap) {
				//if want to save some unzipping, we can write it so nexttime its not unzipped
				file_write($this->mapfileout, $mapdata);
			}
		}

		/*if(!file_exists($this->mapfileout)) {
			echo $this->mapfileout.' does not exists!'.ENVE;
			$this->filebad = true;
			return;
		}*/

		$this->filesizeC = filesize($this->mapfile);
		//$this->filesizeU = filesize($this->mapfileout);
		$this->filemtime = filemtime($this->mapfile);
		$this->filectime = filectime($this->mapfile);

		$this->br = new ByteReader($mapdata);
		$this->filesizeU = $this->br->GetLength();

		$this->md5hash = md5($this->br->data);

		$this->mapfile = $path['basename']; //cut folder path, no needed from here

		$mapdata = null; //clear memory
	}

	//return true on valid version, false otherwise
	private function CheckVersion() {
		return (in_array($this->version, [$this::ROE, $this::AB, $this::SOD, $this::WOG]) || ($this->version == $this::HOTA && $this->hota_subrev <= $this::HOTA_SUBREV5));
	}

	private function SaveMap() {
		$mapfile = mes($this->mapfile);

		$sql = "SELECT m.mapfile FROM heroes3_maps AS m WHERE m.mapfile='$mapfile' AND md5='".$this->md5hash."'";
		$mapdb = mgr($sql);
		if($mapdb) {
			return;
		}

		$mapdir = mes($this->mapfileinfo['dirname']);
		$mapname = mes($this->map_name);
		$mapdesc = mes($this->description);
		$mapimage = mes($this->mapimage);

		$sql = "INSERT INTO heroes3_maps (heroes, campaign, id_campaign, `mapfile`, `mapdir`, `mapname`, `author`, `language`, `mapdesc`, `version`, `subversion`,
			`size`, `sizename`, `levels`, `diff`, `diffname`,
			`playersnum`, `playhuman`, `playai`, `teamnum`, `victory`, `loss`, `filecreate`, `filechanged`, `filesizeC`, `filesizeU`,
			`mapimage`, `md5`) VALUES
			(3, 0, ".$this->camid.", '$mapfile', '$mapdir/', '$mapname', '".$this->author."', '".$this->language."', '$mapdesc', '".$this->versionname."', ".$this->hota_subrev.",
				".$this->map_size.", '".$this->map_sizename."', ".$this->underground.", '".$this->map_diff."', '".$this->map_diffname."',
				".$this->mapplayersnum.", ".$this->mapplayershuman.", ".$this->mapplayersai.",
				".$this->teamscount.", ".$this->victoryCond['type'].", ".$this->lossCond['type'].",
			FROM_UNIXTIME(".$this->filectime."), FROM_UNIXTIME(".$this->filemtime."), ".$this->filesizeC.", ".$this->filesizeU.", '".$mapimage."', '".$this->md5hash."')";

		if(mq($sql)) {
			$this->mapid = mii();
			$sql = "UPDATE heroes3_maps SET mapimage=CONCAT(mapimage, '_', '{$this->mapid}') WHERE idm={$this->mapid}";
			mq($sql);
		}
	}

	public function SetCamId($camid) {
		$this->camid = $camid;
	}

	public function MapHeaderInfo() {
		//$this->ParseFinish();
		$subrev = ($this->version == $this::HOTA) ? ' '.$this->hota_subrev : '';
		$headerInfo = [
			'mapfile' => $this->mapfile,
			'version' => $this->versionname.$subrev,
			'mapname' => $this->map_name,
			'mapdesc' => nl2br($this->description),
			'mapsize' => $this->map_sizename,
			'levels' => ($this->underground + 1),
			'players' => $this->mapplayersnum,
			'teams' => $this->teamscount,
			'levelcap' => $this->hero_levelcap,
			'victory' => $this->victoryInfo,
			'loss' => $this->lossInfo,
			'filesizeC' => $this->filesizeC,
			'filesizeU' => $this->filesizeU,
			'filetime' => $this->filemtime,
			'md5hash' => $this->md5hash,
		];
		return $headerInfo;
	}



	public function ReadMap() {
		if($this->filebad) {
			return;
		}

		//when there is some problem with map structure, exception is thrown at reading functions, and scanning stops
		try {
			$this->ReadMapEx();
		}
		catch(Exception $e) {
			echo $e->GetMessage().ENVE;
			return;
		}

		$this->GetVersionName();
		$this->GetMapSize();
		$this->GetDifficulty();

		//prepare mapimage file here, so it's used in both save and build
		$SC = new StringConvert();
		$this->mapimage = $SC->SanityString($this->mapfilename);

		if($this->printoutput && $this->webmode) {
			require_once 'fun/h3mapscan-print.php';
			$this->br = null; //free some memory
			$this->ParseFinish();
			$h3map_print = new H3MAPSCAN_PRINT($this);
		}

		if($this->save) {
			$this->SaveMap();
		}

	if(!$this->webmode && !$this->basiconly) {
			$this->BuildMap();
		}
	}

	public function ReadMapEx() {

		$this->br->ResetPos();
		$this->CS = new HeroesConstants();
		//$this->SC = new StringConvert();

		$this->version = $this->br->ReadUint32();

		if($this->version == $this::HOTA) {
			$this->hota_subrev = $this->br->ReadUint32();
			if($this->hota_subrev >= $this::HOTA_SUBREV1) {
				$mirror = $this->br->ReadUint8();
				$this->hota_arena = $this->br->ReadUint8(); //arena
			}
			if($this->hota_subrev >= $this::HOTA_SUBREV2) {
				$this->terrain_count = $this->br->ReadUint32();
			}
			if($this->hota_subrev >= $this::HOTA_SUBREV4) {
				$town_type_count = $this->br->ReadInt32();
				$fixed_difficulty_level = $this->br->ReadUint8();
			}
		}

		switch($this->version) {
			case $this::ROE:  $this->isROE  = true; break;
			case $this::AB:   $this->isAB   = true; break;
			case $this::SOD:  $this->isSOD  = true; break;
			case $this::WOG:  $this->isWOG  = true; break;
			case $this::HOTA: $this->isHOTA = true; break;
		}

		if($this->CheckVersion() == false) {
			throw new Exception('Unknown version='.$this->version.', subrev='.$this->hota_subrev.'. Possibly a campagn file or not a map ('.$this->mapfile.')');
			return;
		}

		$this->hero_any_onmap = $this->br->ReadUint8(); //hero presence
		$this->map_size = $this->br->ReadUint32();
		$this->underground = $this->br->ReadUint8();
		$this->map_name = $this->ReadString();

		//reset language which was set bases on mapname and let base it on description, which is usually longer
		$this->language = null;

		$this->description = $this->ReadString();
		$this->map_diff = $this->br->ReadUint8();

		if($this->fastread) {
			$this->br->skipstrings = true;
		}

		if(!$this->isROE) {
			$this->hero_levelcap = $this->br->ReadUint8(); //hero's cap
		}

		$this->ReadPlayersData();

		// Special Victory Condition
		$this->VictoryCondition();
		// Special loss condition
		$this->LossCondition();
		// Teams
		$this->Teams();

		if($this->basiconly) {
			$this->readok = true;
			return;
		}

		// Free Heroes
		$this->FreeHeroes();

		$this->br->SkipBytes(31); //unused space

		$this->HotaMapExtra(); //hota extras

		// Artefacts
		$this->Artifacts();

		//allowed spells and abilities
		$this->AllowedSpellsAbilities();

		// Rumors
		$this->Rumors();

		// Heroes Params
		$this->ReadPredefinedHeroes();

		// Map
		$this->ReadTerrain();

		if($this->terrainonly) {
			$this->readok = true;
			return;
		}

		//object definitions
		$this->ReadDefInfo();

		//objects
		$this->ReadObjects();

		//global event
		$this->ReadEvents();

		$this->readok = true;
	}

	private function ReadPlayersData() {
		//players
		for($i = 0; $i < PLAYERSNUM; $i++) {
			$human = $this->br->ReadUint8();
			$ai = $this->br->ReadUint8();

			//nobody can play this color
			if($human == 0 && $ai == 0) {
				switch($this->version) {
					case $this::ROE: $this->br->SkipBytes(6);  break;
					case $this::AB:  $this->br->SkipBytes(12); break;
					default:         $this->br->SkipBytes(13); break;
				}
				continue;
			}
			else {
				$this->playerMask |= (1 << $i);

				if($human) {
					$this->mapplayershuman++;
				}
				if($ai) {
					$this->mapplayersai++;
				}
				$this->mapplayersnum++;
			}

			$this->players[$i]['human'] = $human;
			$this->players[$i]['ai'] = $ai;

			//def values
			$this->players[$i]['HeroAtMain'] = 1;
			$this->players[$i]['GenerateHero'] = 0;
			$this->players[$i]['HeroFace'] = [];
			$this->players[$i]['HeroName'] = [];
			$this->players[$i]['HeroCount'] = 0;
			$this->players[$i]['townsOwned'] = 0;
			$this->players[$i]['placeholder'] = OBJECT_INVALID;

			$this->players[$i]['behaviour'] = $this->br->ReadUint8();

			if($this->version >= $this::SOD) {
				$this->players[$i]['townOwned_isSet'] = $this->br->ReadUint8();
			}
			else {
				$this->players[$i]['townOwned_isSet'] = OBJECT_INVALID;
			}

			//allowed towns
			$maxtowns = PLAYERSNUM;
			if($this->isROE) {
				$towns = $this->br->ReadUint8();
			}
			else {
				$towns = $this->br->ReadUint16();
				$maxtowns = MAX_TOWNS;
			}

			$towns_allowed = [];

			if($towns == HNONE || $towns == HNONE_TOWN) {
				$towns_allowed[] = 'Random Town';
			}
			elseif($towns != HNULL) {
				for($n = 0; $n < $maxtowns; $n++) {
					if(($towns & (1 << $n)) != 0) {
						$towns_allowed[] = $this->GetTownById($n);
					}
				}
			}
			$this->players[$i]['towns_allowed'] = implode($towns_allowed, ', ');

			$this->players[$i]['IsRandomTown'] = $this->br->ReadUint8();
			$this->players[$i]['HasMainTown'] = $this->br->ReadUint8();

			//def values
			$townpos;

			if($this->players[$i]['HasMainTown']) {
				if(!$this->isROE) {
					$this->players[$i]['HeroAtMain'] = $this->br->ReadUint8();
					$this->players[$i]['GenerateHero'] = dechex($this->br->ReadUint8());
				}
				$townpos = new MapCoords($this->br->ReadUint8(), $this->br->ReadUint8(), $this->br->ReadUint8());
			}
			else {
				$townpos = new MapCoords();
			}

			$this->players[$i]['townpos'] = $townpos;

			$heronum = 0;
			$this->players[$i]['RandomHero'] = $this->br->ReadUint8();
			$this->players[$i]['MainHeroType'] = $this->br->ReadUint8();

			$this->players[$i]['MainHeroName'] = 'Random';

			if($this->players[$i]['MainHeroType'] != HNONE) {
				$heroid = $this->br->ReadUint8();
				$heroname = $this->ReadString();
				if($heroid != HNONE) {
					$this->players[$i]['HeroFace'][] = $heroid;
					$this->players[$i]['HeroName'][] = $heroname;
				}
				$this->players[$i]['MainHeroName'] = $this->GetHeroById($heroid);
			}

			if(!$this->isROE) {
				$this->players[$i]['placeholder'] = $this->br->ReadUint8(); //placeholder

				$herocount = $this->br->ReadUint8();
				$this->players[$i]['HeroCount'] = $herocount;

				$this->br->SkipBytes(3);
				for($j = 0; $j < $herocount; $j++) {
					$heroid = $this->br->ReadUint8();
					$heroname = $this->ReadString();
					if(!$heroname) {
						$heroname = $this->GetHeroById($heroid);
					}
					$this->players[$i]['HeroFace'][] = $heroid;
					$this->players[$i]['HeroName'][] = $heroname;
				}
			}
		}

	}

	private function FreeHeroes() {
		$heroes = 0;
		$limit = HEROES_QUANTITY;

		switch($this->version) {
			case $this::ROE:  $heroes = 16;	break;
			case $this::AB:
			case $this::SOD:
			case $this::WOG:  $heroes = 20; break;
			case $this::HOTA: $heroes = 23; break;
		}

		if($this->isHOTA) {
			$limit = $this->br->ReadUint32(); //hero count
			$heroes = intval(($limit + 7) / 8);
		}

		for($i = 0; $i < $heroes; $i++) {
			$byte = $this->br->ReadUint8();

			for($n = 0; $n < 8; $n++) {
				$idh = $i * 8 + $n; //hero id
				if($idh >= $limit) {
					break;
				}
				if(($byte & (1 << $n)) == 0) {
					$this->disabledHeroes[$this->GetHeroClassByHeroId($idh)][] = $this->GetHeroById($idh);
				}
			}
		}

		if($this->version > $this::ROE) {
			$placeholders = $this->br->ReadUint32(); //no use
			for ($i = 0; $i < $placeholders; $i++) {
				$hero['id'] = $this->br->ReadUint8();
				$hero['face'] = 0;
				$hero['name'] = $this->GetHeroById($hero['id']);
				$hero['mask'] = 0;
				$this->customHeroes[$hero['id']] = $hero;
			}
		}

		if($this->version >= $this::SOD) {
			//custom heroes, changed in editor
			$heroCustomCount = $this->br->ReadUint8();

			for($i = 0; $i < $heroCustomCount; $i++) {
				$hero['id'] = $this->br->ReadUint8();
				$hero['face'] = $this->br->ReadUint8();  //picture
				$hero['name'] = $this->ReadString();
				$hero['mask'] = $this->br->ReadUint8();  //player availability
				$this->customHeroes[$hero['id']] = $hero;
			}
		}
	}

	private function HotaMapExtra() {
		if(!$this->isHOTA) {
			return;
		}
		$this->monplague_week = $this->br->ReadUint32();

		if($this->hota_subrev >= $this::HOTA_SUBREV1) {
			$art_combinated = $this->br->ReadUint32();
			if($art_combinated > 0) {
				$art_comb_num = (int)ceil($art_combinated / 8);
				$this->br->SkipBytes($art_comb_num); //skip, not in scanner
			}

			if($this->hota_subrev >= $this::HOTA_SUBREV3) {
				$this->combat_round_limit = $this->br->ReadUint32();
			}
			if($this->hota_subrev >= $this::HOTA_SUBREV4) {
				//forbid_hiring_heroes 8*byte for each player
				$this->br->SkipBytes(8);
			}
		}
	}

	private function Artifacts() {
		// Reading allowed artifacts:	17 or 18 bytes, or X for HOTA
		//1=disabled, 0=enabled
		if($this->isROE) {
			return;
		}

		$bytes = $this->version == $this::AB ? 17 : 18;
		if($this->isHOTA) {
			$artcount = $this->br->ReadUint32(); //artifact id count
			$bytes = ceil($artcount / 8); //21
		}

		for($i = 0; $i < $bytes; $i++) {
			$byte = $this->br->ReadUint8(); //ids of artifacts

			for($n = 0; $n < 8; $n++) {
				if(($byte & (1 << $n)) != 0) {
					$ida = $i * 8 + $n;
					if(!$this->isWOG && $ida > 140) {
						break;
					}
					$this->disabledArtifacts[] = $this->GetArtifactById($ida).' '.$ida;
				}
			}
		}
	}

	private function AllowedSpellsAbilities() {
		if($this->version >= $this::SOD) {
			// Reading allowed spells (9 bytes)
			for($i = 0; $i < SPELL_BYTE; $i++) {
				$byte = $this->br->ReadUint8(); //ids of spells
				for($n = 0; $n < 8; $n++) {
					if(($byte & (1 << $n)) != 0) {
						$spellid = $i * 8 + $n;
						$this->disabledSpellsId[] = $spellid;
						$this->disabledSpells[] = $this->GetSpellById($spellid);
					}
				}
			}
			// Allowed hero's abilities (4 bytes)
			for($i = 0; $i < SECSKILL_BYTE; $i++) {
				$byte = $this->br->ReadUint8(); //ids of skills
				for($n = 0; $n < 8; $n++) {
					if(($byte & (1 << $n)) != 0) {
						$this->disabledSkills[] = $this->GetSecskillById($i * 8 + $n);
					}
				}
			}
		}
	}

	private function Rumors() {
		$this->rumorsCount = $this->br->ReadUint32();
		if($this->rumorsCount) {
			for($i = 0; $i < $this->rumorsCount; $i++) {
				$rumor['name'] = $this->ReadString();
				$rumor['desc'] = $this->ReadString();
				$this->rumors[] = $rumor;
			}
		}
	}

	private function VictoryCondition() {
		// 1	Special Victory Condition:
		$this->victoryCond['type'] = $this->br->ReadUint8();
		if($this->victoryCond['type'] == VICTORY::NONE) {
			$this->victoryCond['name'] = 'None';
			$this->victoryInfo = 'Defeat all players';
			return;
		}

		$this->victoryCond['Normal_end'] = $this->br->ReadUint8();
		$this->victoryCond['AI_cancomplete'] = $this->br->ReadUint8();

		switch($this->victoryCond['type']) {
			case VICTORY::NONE: break; // not
			case VICTORY::ARTIFACT: // 00 - Acquire a specific artifact
				$this->victoryCond['name'] = 'Acquire a specific artifact';
				$this->victoryCond['art'] = $this->br->ReadUint8();
				$this->victoryInfo = 'Acquire a specific artifact '.$this->GetArtifactById($this->victoryCond['art']);
				if(!$this->isROE) {
					$this->br->SkipBytes(1);
				}
				break;
			case VICTORY::ACCCREATURES: // 01 - Accumulate creatures
				$this->victoryCond['name'] = 'Accumulate creatures';
				$monid = $this->br->ReadUint8();
				$this->victoryCond['unit'] = $this->GetCreatureById($monid);
				if(!$this->isROE) {
					$this->br->SkipBytes(1);
				}
				$this->victoryCond['unit_count'] = $this->br->ReadUint32();
				$this->victoryInfo = 'Accumulate creatures, '.$this->victoryCond['unit'].', count '.comma($this->victoryCond['unit_count']);
				break;
			case VICTORY::ACCRESOURCES: // 02 - Accumulate resources
				$this->victoryCond['name'] = 'Accumulate resources';
				$this->victoryCond['resource'] = $this->br->ReadUint8();
				// 0 - Wood	 1 - Mercury	2 - Ore	3 - Sulfur	4 - Crystal	5 - Gems	6 - Gold
				$this->victoryCond['resource_count'] = $this->br->ReadUint32();
				$this->victoryInfo = 'Accumulate resources: '.$this->GetResourceById($this->victoryCond['resource'])
					.', count: '.comma($this->victoryCond['resource_count']);
				break;
			case VICTORY::UPGRADETOWN: // 03 - Upgrade a specific town
				$this->victoryCond['name'] = 'Upgrade a specific town';
				$this->victoryCond['coor'] = new MapCoords($this->br->ReadUint8(), $this->br->ReadUint8(), $this->br->ReadUint8());
				$this->victoryCond['hall_lvl'] = $this->GetHall($this->br->ReadUint8());
				// Hall Level:	 0-Town, 1-City,	2-Capitol
				$this->victoryCond['castle_lvl'] = $this->GetFort($this->br->ReadUint8());
				// Castle Level: 0-Fort, 1-Citadel, 2-Castle

				$this->victoryInfo = 'Upgrade a specific town at '.$this->victoryCond['coor']->GetCoords()
					.' to '.$this->victoryCond['hall_lvl'].' and '.$this->victoryCond['castle_lvl'];
				break;
			case VICTORY::BUILDGRAIL: // 04 - Build the grail structure
				$this->victoryCond['name'] = 'Build the grail structure';
				$this->victoryCond['coor'] = new MapCoords($this->br->ReadUint8(), $this->br->ReadUint8(), $this->br->ReadUint8());
				$this->victoryInfo = 'Build the grail structure at town '.$this->victoryCond['coor']->GetCoords();
				break;
			case VICTORY::DEFEATHERO: // 05 - Defeat a specific Hero
				$this->victoryCond['name'] = 'Defeat a specific Hero';
				$this->victoryCond['coor'] = new MapCoords($this->br->ReadUint8(), $this->br->ReadUint8(), $this->br->ReadUint8());
				$this->victoryInfo = 'Defeat a specific Hero at '.$this->victoryCond['coor']->GetCoords();
				break;
			case VICTORY::CAPTURETOWN: // 06 - Capture a specific town
				$this->victoryCond['name'] = 'Capture a specific town';
				$this->victoryCond['coor'] = new MapCoords($this->br->ReadUint8(), $this->br->ReadUint8(), $this->br->ReadUint8());
				$this->victoryInfo = 'Capture a specific town at'.$this->victoryCond['coor']->GetCoords();
				break;
			case VICTORY::KILLMONSTER: // 07 - Defeat a specific monster
				$this->victoryCond['name'] = 'Defeat a specific monster';
				$this->victoryCond['coor'] = new MapCoords($this->br->ReadUint8(), $this->br->ReadUint8(), $this->br->ReadUint8());
				$this->victoryInfo = 'Defeat a specific monster at '.$this->victoryCond['coor']->GetCoords();
				break;
			case VICTORY::FLAGWELLINGS: // 08 - Flag all creature dwelling
				$this->victoryCond['name'] = 'Flag all creature dwelling';
				$this->victoryInfo = $this->victoryCond['name'];
				break;
			case VICTORY::FLAGMINES: // 09 - Flag all mines
				$this->victoryCond['name'] = 'Flag all mines';
				$this->victoryInfo = $this->victoryCond['name'];
				break;
			case VICTORY::TRANSPORTART: // 0A - Transport a specific artifact
				$this->victoryCond['name'] = 'Transport a specific artifact';
				$this->victoryCond['art'] = $this->br->ReadUint8();
				$this->victoryCond['coor'] = new MapCoords($this->br->ReadUint8(), $this->br->ReadUint8(), $this->br->ReadUint8());
				$this->victoryInfo = 'Transport '.$this->GetArtifactById($this->victoryCond['art']).' to town at '.$this->victoryCond['coor']->GetCoords();
				break;
			case VICTORY::ELIMINATEMONSTERS: // 0A - Transport a specific artifact
				$this->victoryCond['name'] = 'Eliminate all creatures';
				$this->victoryInfo = 'Eliminate all creatures';
				break;
			case VICTORY::SURVIVETIME: // 0A - Transport a specific artifact
				$this->victoryCond['name'] = 'Survive for certain time';
				$this->victoryCond['days'] = $this->br->ReadUint32();
				$this->victoryInfo = 'Survive for '.$this->victoryCond['days'].' days';
				break;

			default: // ff - not
		}

		if($this->victoryCond['AI_cancomplete']) {
			$this->victoryInfo .= '<br />AI can complete condition too';
		}
		if($this->victoryCond['Normal_end']) {
			$this->victoryInfo .= '<br />Or standard end';
		}
	}

	private function LossCondition() {
		// 1	Special loss condition
		$this->lossCond['type'] = $this->br->ReadUint8();
		if($this->lossCond['type'] == LOSS::NONE) {
			$this->lossCond['name'] = 'None';
			$this->lossInfo = 'Loose all towns and heroes';
			return;
		}

		switch($this->lossCond['type']) {
			case LOSS::NONE: break; // not
			case LOSS::TOWN: // 00 - Lose a specific town
				$this->lossCond['name'] = 'Lose a specific town';
				$this->lossCond['coor'] = new MapCoords($this->br->ReadUint8(), $this->br->ReadUint8(), $this->br->ReadUint8());
				$this->lossInfo = 'Lose a specific town at '.$this->lossCond['coor']->GetCoords();
				break;
			case LOSS::HERO: // 01 - Lose a specific hero
				$this->lossCond['name'] = 'Lose a specific hero';
				$this->lossCond['coor'] = new MapCoords($this->br->ReadUint8(), $this->br->ReadUint8(), $this->br->ReadUint8());
				$this->lossInfo = 'Lose a specific hero at '.$this->lossCond['coor']->GetCoords();
				break;
			case LOSS::TIME: // 02 - time
				$this->lossCond['name'] = 'Time expires';
				$this->lossCond['time'] = $this->br->ReadUint16();
				$time = floor($this->lossCond['time'] / 28).' months and '.($this->lossCond['time'] % 28).' days';
				$this->lossInfo = 'Complete in '.$time;
				break;
			default: // ff - not
		}
	}

	private function Teams() {
		$this->teamscount = $this->br->ReadUint8();
		for($i = 0; $i < PLAYERSNUM; $i++) {
			$this->teams[$i] = ($this->teamscount != 0) ? $this->br->ReadUint8() : 0;
		}
	}

	//another block for changed heroes in editor
	private function ReadPredefinedHeroes() {

		$limit = HEROES_QUANTITY;
		if($this->isHOTA) {
			$limit = $this->br->ReadUint32(); //hero count
		}

		switch($this->version) {
			case $this::SOD:
			case $this::WOG:
			case $this::HOTA:
				// Disposed heroes
				for($i = 0; $i < $limit; $i++) {

					//is hero custom, if not, skip to next
					$custom = $this->br->ReadUint8();
					if(!$custom) {
						continue;
					}

					$hero = [];
					$hero['id'] = $i;
					$hero['name'] = '';
					$hero['mask'] = 0;
					$hero['face'] = 0;

					$hero['defname'] = $this->GetHeroById($i);
					$hero['name'] = $hero['defname'];
					$hero['exp'] = 0;
					$hero['sex'] = '';
					$hero['bio'] = '';
					$hero['priskills'] = [];
					$hero['skills'] = [];
					$hero['spells'] = [];
					$hero['artifacts'] = [];

					if(!empty($this->customHeroes)) {
						$heroc = FromArray($hero['id'], $this->customHeroes, false);
						if(is_array($heroc)) {
							$hero['name'] = $heroc['name'];
							$hero['mask'] = $heroc['mask'];
							$hero['face'] = $heroc['face'];
						}
					}

					$hasExp = $this->br->ReadUint8();
					if($hasExp) {
						$hero['exp'] = $this->br->ReadUint32();
					}
					else {
						$heroExp = 0;
					}

					$hasSecSkills = $this->br->ReadUint8();
					if($hasSecSkills) {
						$howMany = $this->br->ReadUint32();
						for($j = 0; $j < $howMany; $j++) {
							$secSkills[0] = $this->GetSecskillById($this->br->ReadUint8());
							$secSkills[1] = $this->GetSecskillLevelById($this->br->ReadUint8());
							$hero['skills'][] = $secSkills;
						}
					}

					$this->curobj = 'Hero def: '.$hero['name'];
					$this->curcoor = new MapCoords();
					$hero['artifacts'] = $this->LoadArtifactsOfHero();

					$hasCustomBio = $this->br->ReadUint8();
					if($hasCustomBio) {
						$hero['bio'] = $this->ReadString();
					}

					// 0xFF is default, 00 male, 01 female
					$herosex = $this->br->ReadUint8();
					$hero['sex'] = $herosex == HNONE ? 'Default' : ($herosex ? 'Female' : 'Male');

					$hasCustomSpells = $this->br->ReadUint8();
					if($hasCustomSpells) {
						$hero['spells'] = $this->ReadSpells();
					}

					$hasCustomPrimSkills = $this->br->ReadUint8();
					if($hasCustomPrimSkills) {
						for($j = 0; $j < PRIMARY_SKILLS; $j++) {
							$hero['priskills'][] = $this->br->ReadUint8();
						}
					}

					$this->heroesPredefined[] = $hero;
				}

				//extra hota stuff
				if($this->hota_subrev >= $this::HOTA_SUBREV4) {
					$this->br->SkipBytes($limit * 6); //not really useful stuff for now
					/*for ($i = 0; $i < $limit; $i++) {
						$add_skills = $this->br->ReadUint8();
						$hero_level_is_fixed = $this->br->ReadUint8();
						$level_fixed = $this->br->ReadInt32();
					}*/
				}
				break;
		}
	}

	private function ReadHero() {
		$hero = [];
		$hero['name'] = 'Default';
		$hero['epx'] = 0;
		$hero['uid'] = 0;

		if($this->version > $this::ROE) {
			$hero['uid'] = $this->br->ReadUint32();
		}

		$hero['PlayerColor'] = $this->br->ReadUint8();
		$hero['subid'] = $this->br->ReadUint8();

		$hasName = $this->br->ReadUint8();
		if($hasName) {
			$hero['name'] = $this->ReadString();
		}
		else {
			$hero['name'] = $this->GetHeroById($hero['subid']);
		}

		$hero['exp'] = 0;
		if($this->version > $this::AB) {
			$hasExp = $this->br->ReadUint8();
			if($hasExp) {
				$hero['exp'] = $this->br->ReadUint32();
			}
		}
		else {
			$hero['exp'] = $this->br->ReadUint32();
		}

		$hasPortrait = $this->br->ReadUint8();
		if($hasPortrait) {
			$hero['portrait'] = $this->br->ReadUint8();
		}

		//is hero in prison
		$hero['prisoner'] = ($this->curobj == OBJECTS::PRISON);

		$hero['skills'] = [];

		$hasSecSkills = $this->br->ReadUint8();
		if($hasSecSkills) {
			$howMany = $this->br->ReadUint32();
			for($yy = 0; $yy < $howMany; $yy++) {
				$hero['skills'][] = [
					'skill' => $this->GetSecskillById($this->br->ReadUint8()),
					'level' => $this->GetSecskillLevelById($this->br->ReadUint8()),
				];
			}
		}

		$hero['stack'] = [];
		$hasGarison = $this->br->ReadUint8();
		if($hasGarison) {
			$hero['stack'] = $this->ReadCreatureSet(7);
		}

		$this->curobj = 'Hero: '.$hero['name'];

		$hero['formation'] = $this->br->ReadUint8();
		$hero['artifacts'] = $this->LoadArtifactsOfHero();

		$hero['patrol'] = $this->br->ReadUint8();
		if($hero['patrol'] == HNONE) {
			$hero['patrol'] = 0;
		}

		$hero['bio'] = '';
		$hero['sex'] = 'default';
		if($this->version > $this::ROE) {
			$hasCustomBiography = $this->br->ReadUint8();
			if($hasCustomBiography) {
				$hero['bio'] = $this->ReadString();
			}
			$herosex = $this->br->ReadUint8();
			$hero['sex'] = $herosex == HNONE ? 'Default' : ($herosex ? 'Female' : 'Male');
		}

		$hero['spells'] = [];

		// Spells
		if($this->version > $this::AB) {
			$hasCustomSpells = $this->br->ReadUint8();
			if($hasCustomSpells) {
				$hero['spells'] = $this->ReadSpells();
			}
		}
		else if($this->isAB) {
			//we can read one spell
			$buff = $this->br->ReadUint8();
		}

		$hero['priskills'] = [];
		if($this->version > $this::AB) {
			$hasCustomPrimSkills = $this->br->ReadUint8();
			if($hasCustomPrimSkills) {
				for($j = 0; $j < PRIMARY_SKILLS; $j++) {
					$hero['priskills'][] = $this->br->ReadUint8();
				}
			}
		}
		$this->br->SkipBytes(16);

		//extra hota stuff
		if($this->hota_subrev >= $this::HOTA_SUBREV4) {
			$this->br->SkipBytes(6); //not really useful stuff for now
		}

		return $hero;
}

	private function LoadArtifactsOfHero() {
		$artifacts = [];

		// True if artifact set is not default (hero has some artifacts)
		$has_artifacts = $this->br->ReadUint8();
		if(!$has_artifacts) {
			return $artifacts;
		}


		for($a = 0; $a < 16; $a++) {
			$this->LoadArtifactToSlot($artifacts, $a);
		}

		if($this->version >= $this::SOD) {
			$this->LoadArtifactToSlot($artifacts, 16); //ArtifactPosition::MACH4
		}

		$this->LoadArtifactToSlot($artifacts, 17); //ArtifactPosition::SPELLBOOK

		if($this->version > $this::ROE) {
			$this->LoadArtifactToSlot($artifacts, 18); //ArtifactPosition::MISC5
		}
		else {
			$this->br->SkipBytes(1);
		}

		// bag artifacts
		// number of artifacts in hero's bag
		$amount = $this->br->ReadUint16();
		for($i = 0; $i < $amount; $i++) {
			$this->LoadArtifactToSlot($artifacts, 19); //ArtifactPosition::BACKPACK
		}

		return $artifacts;
	}

	private function LoadArtifactToSlot(&$artifacts, $slot) {
		if($this->isROE) {
			$artid = $this->br->ReadUint8();
			$artmask = HNONE;
		}
		elseif($this->hota_subrev >= $this::HOTA_SUBREV4) {
			$artid = $this->br->ReadUint32();
			$artmask = HNONE32;
		}
		else {
			$artid = $this->br->ReadUint16();
			$artmask = HNONE16;
		}

		if($artid != $artmask) {
			if($this->hota_subrev >= $this::HOTA_SUBREV4) {
				//could be scroll with spell
				$artifact = $this->ReadHotaArtifact($artid);
			}
			else {
				$artifact = $this->GetArtifactById($artid);
			}
			$this->artifacts_list[] = new ListObject($artifact, $this->curcoor, $this->curobj);
			$artifacts[] = $this->GetArtifactPosById($slot).': '.$artifact;
		}
	}

	//spell mask
	private function ReadSpells() {
		$spells = [];
		for($i = 0; $i < SPELL_BYTE; $i++) {
			$byte = $this->br->ReadUint8();
			for($n = 0; $n < 8; $n++) {
				if(($byte & (1 << $n)) != 0) {
					$spell = $this->GetSpellById($i * 8 + $n);
					$spells[] = $spell;
					$this->spells_list[] = new ListObject($spell, $this->curcoor, $this->curobj);
				}
			}
		}
		return $spells;
	}

	private function ReadTerrain() {
		//if we dont need build map image, we dont need to read terrain at all
		if(!$this->buildMapImage) {
			$this->br->SkipBytes($this->map_size * $this->map_size * ($this->underground + 1) * TILEBYTESIZE);
			return;
		}

		$csurfaces = count($this->CS->TerrainType);
		for($i = 0; $i < $csurfaces; $i++) {
			$this->terrainRate[0][$i] = 0; //up
			$this->terrainRate[1][$i] = 0; //down
			$this->terrainRate[2][$i] = 0; //both
		}

		$cell = new MapCell();
		$cell->access = 0;
		$cell->owner = OWNERNONE;
		$cell->special = MAPSPECIAL::NONE;

		for($z = 0; $z < $this->underground + 1; $z++) {
			for($x = 0; $x < $this->map_size; $x++) {
				for($y = 0; $y < $this->map_size; $y++) {

					$cell->surface = $this->br->ReadUint8();
					//skip props we dont use anyway in this reader
					$this->br->SkipBytes(6);
					//$cell->surface_type = $this->br->ReadUint8();
					//$cell->river = $this->br->ReadUint8();
					//$cell->river_type = $this->br->ReadUint8();
					//$cell->road = $this->br->ReadUint8();
					//$cell->road_type = $this->br->ReadUint8();
					//$cell->mirror = $this->br->ReadUint8();

					//cloning cell is faster than declaring new cell ever iteration
					$this->terrain[$z][$x][$y] = clone $cell;

					if($cell->surface < $csurfaces) {
						$this->terrainRate[$z][$cell->surface]++;
						$this->terrainRate[2][$cell->surface]++;
					}
				}
			}
		}
	}
	
	public function GetTerrainRate() {
		return $this->terrainRate;
	}

	public function BuildMap() {
		if($this->buildMapImage) {
			//image path and filenames
			if($this->mapid) {
				$imgmapnameg = MAPDIRIMG.$this->mapimage.'_'.$this->mapid.'_g.png'; //ground
				$imgmapnameu = MAPDIRIMG.$this->mapimage.'_'.$this->mapid.'_u.png'; //underground
			}
			else {
				$imgmapnameg = MAPDIRIMG.$this->mapimage.'_g.png'; //ground
				$imgmapnameu = MAPDIRIMG.$this->mapimage.'_u.png'; //underground
			}

			//images already exists
			if(file_exists($imgmapnameg) && ($this->underground == 0 || file_exists($imgmapnameu))) {
				return;
			}

			if(!is_writable(MAPDIRIMG)) {
				return;
			}

			$img = imagecreate($this->map_size, $this->map_size); //map by size

			if(!$img) {
				throw new Exception('Image Creation problem');
				return;
			}

			$imgmap = imagecreate($this::IMGSIZE, $this::IMGSIZE); //resized to constant size for all map sizes
			/* From web
				First byte - surface codes: (RGB colors on the map)
				ID   Terrain         WEB desc   Real map   Real map blocked    Players
				00 - Dirt            (50 3F 0F) #52 39 08  #39 29 08           #FF 00 00 Red
				01 - Sand            (DF CF 8F) #DE CE 8C  #A5 9C 6B           #31 52 FF Blue
				02 - Grass           (00 40 00) #00 42 00  #00 31 00           #9C 73 52 Tan
				03 - Snow            (B0 C0 C0) #B5 C6 C6  #8C 9C 9C           #42 94 29 Green
				04 - Swamp           (4F 80 6F) #4A 84 6B  #21 5A 42           #FF 84 00 Orange
				05 - Rough           (80 70 30) #84 73 31  #63 52 21           #8C 29 A5 Purple
				06 - Subterranean    (00 80 30) #84 31 00  #39 29 08           #08 9C A5 Teal
				07 - Lava            (4F 4F 4F) #4A 4A 4A  #29 29 29           #C6 7B 8C Pink
				08 - Water           (0F 50 90) #08 52 94  #00 29 6B           #84 84 84 Neutral
				09 - Rock            (00 00 00) #00 00 00
				10 - highlands                  #29 73 18  #21 52 10
				11 - wasteland                  #BD 5A 08  #9C 42 08
			*/

			$imgcolors = [
				//terrain
				TERRAIN::DIRT       => imagecolorallocate($img, 0x52, 0x39, 0x08),
				TERRAIN::SAND       => imagecolorallocate($img, 0xde, 0xce, 0x8c),
				TERRAIN::GRASS      => imagecolorallocate($img, 0x00, 0x42, 0x00),
				TERRAIN::SNOW       => imagecolorallocate($img, 0xb5, 0xc6, 0xc6),
				TERRAIN::SWAMP      => imagecolorallocate($img, 0x4a, 0x84, 0x6b),
				TERRAIN::ROUGH      => imagecolorallocate($img, 0x84, 0x73, 0x31),
				TERRAIN::SUBTERAIN  => imagecolorallocate($img, 0x84, 0x31, 0x00),
				TERRAIN::LAVA       => imagecolorallocate($img, 0x4a, 0x4a, 0x4a),
				TERRAIN::WATER      => imagecolorallocate($img, 0x08, 0x52, 0x94),
				TERRAIN::ROCK       => imagecolorallocate($img, 0x00, 0x00, 0x00),
				TERRAIN::HIGHLANDS  => imagecolorallocate($img, 0x29, 0x73, 0x18),
				TERRAIN::WASTELAND  => imagecolorallocate($img, 0xbd, 0x5a, 0x08),
				//terrain, blocked tiles
				TERRAIN::BDIRT      => imagecolorallocate($img, 0x39, 0x29, 0x08),
				TERRAIN::BSAND      => imagecolorallocate($img, 0xa5, 0x9c, 0x6b),
				TERRAIN::BGRASS     => imagecolorallocate($img, 0x00, 0x31, 0x00),
				TERRAIN::BSNOW      => imagecolorallocate($img, 0x8c, 0x9c, 0x9c),
				TERRAIN::BSWAMP     => imagecolorallocate($img, 0x21, 0x5a, 0x42),
				TERRAIN::BROUGH     => imagecolorallocate($img, 0x63, 0x52, 0x21),
				TERRAIN::BSUBTERAIN => imagecolorallocate($img, 0x5a, 0x08, 0x00),
				TERRAIN::BLAVA      => imagecolorallocate($img, 0x29, 0x29, 0x29),
				TERRAIN::BWATER     => imagecolorallocate($img, 0x00, 0x29, 0x6b),
				TERRAIN::BROCK      => imagecolorallocate($img, 0x00, 0x00, 0x00),
				TERRAIN::BHIGHLANDS => imagecolorallocate($img, 0x21, 0x52, 0x10),
				TERRAIN::BWASTELAND => imagecolorallocate($img, 0x9c, 0x42, 0x08),
				//player colors
				TERRAIN::RED        => imagecolorallocate($img, 0xff, 0x00, 0x00),
				TERRAIN::BLUE       => imagecolorallocate($img, 0x31, 0x52, 0xff),
				TERRAIN::TAN        => imagecolorallocate($img, 0x9c, 0x73, 0x52),
				TERRAIN::GREEN      => imagecolorallocate($img, 0x42, 0x94, 0x29),
				TERRAIN::ORANGE     => imagecolorallocate($img, 0xff, 0x84, 0x00),
				TERRAIN::PURPLE     => imagecolorallocate($img, 0x8c, 0x29, 0xa5),
				TERRAIN::TEAL       => imagecolorallocate($img, 0x08, 0x9c, 0xa5),
				TERRAIN::PINK       => imagecolorallocate($img, 0xc6, 0x7b, 0x8c),
				TERRAIN::NEUTRAL    => imagecolorallocate($img, 0x84, 0x84, 0x84),
				//special coloring
				TERRAIN::NONE       => imagecolorallocate($img, 0xff, 0xff, 0xff),
				TERRAIN::MINE       => imagecolorallocate($img, 0xff, 0x00, 0xcc),
				TERRAIN::ARTIFACT   => imagecolorallocate($img, 0x33, 0xff, 0xff),
				TERRAIN::MONSTER    => imagecolorallocate($img, 0x33, 0xff, 0x00),
				TERRAIN::ANY        => imagecolorallocate($img, 0xff, 0xff, 0x00),
			];

			// Map
			foreach($this->terrain as $level => $row) {
				foreach($row as $x => $col) {
					foreach($col as $y => $cell) {
						$color = $imgcolors[$this->GetCellSurface($cell)];
						imagesetpixel($img, $y, $x, $color);
					}
				}

				$imgmapname = $level == 0 ? $imgmapnameg : $imgmapnameu;
				imagecopyresized($imgmap, $img, 0, 0, 0, 0, $this::IMGSIZE, $this::IMGSIZE, $this->map_size, $this->map_size);
				imagepng($imgmap, $imgmapname);
			}

			imagedestroy($img);
			imagedestroy($imgmap);
		}
	}

	private function GetCellSurface($cell) {
		if($cell->owner != OWNERNONE) {
			if($cell->owner > TERRAIN::NEUTRAL - TERRAIN::OFFPLAYERS) {
				return TERRAIN::NEUTRAL;
			}
			return $cell->owner + TERRAIN::OFFPLAYERS;
		}
		elseif($this->special_access && $cell->special != MAPSPECIAL::NONE) {
			return $cell->special + TERRAIN::OFFSPECIAL;
		}
		elseif($cell->access == 0) {
			return $cell->surface;
		}
		else {
			return $cell->surface + TERRAIN::OFFBLOCKED;
		}
	}

	public function DisplayMap() {
		$imgmapnameg = MAPDIRIMG.$this->mapimage.'_g.png';
		$imgmapnameu = MAPDIRIMG.$this->mapimage.'_u.png';

		//$mapsizepow = $this->map_size * $this->map_size;
		//$output = '<br />Map : size='.$this->map_size.', cells='.$mapsizepow.', bytes='.($mapsizepow * 7).'<br />';

		$imgground = file_exists($imgmapnameg) ? '<img src="'.$imgmapnameg.'" alt="ground" title="ground" />' : 'Map Ground';
		$output = '<table class="mapimages"><tr><td>'.$imgground.'</td>';
		if($this->underground) {
			$imguground = file_exists($imgmapnameu) ? '<img src="'.$imgmapnameu.'" alt="ground" title="ground" />' : 'Map Underground';
			$output .= '<td>'.$imguground.'</td>';
		}
		$output .= '</tr></table>';
		return $output;
	}

	private function ReadDefInfo() {
		$this->objTemplatesNum = $this->br->ReadUint32();

		// Read custom defs
		for($i = 0; $i < $this->objTemplatesNum; $i++) {
			$objtemp = new ObjectTemplate();
			$objtemp->animation = $this->ReadString();

			$blockMask = [];
			$visitMask = [];
			$usedTiles = [];

			//read tile masks only when building image
			if($this->buildMapImage) {
				//blockMask
				for($j = 0; $j < 6; $j++) {
					$blockMask[] = $this->br->ReadUint8();
				}
				//visitMask
				for($j = 0; $j < 6; $j++) {
					$visitMask[] = $this->br->ReadUint8();
				}

				//build object shape
				for ($r = 0; $r < 6; $r++) { // 6 rows y-axis
					for ($c = 0; $c < 8; $c++) { // 8 columns	 x-axis
						$tile = TILETYPE::FREE; // default tile is empty
						//once tile is accesible, it's also blocked
						if ((($visitMask[$r] >> $c) & 1) != 0) {
							$tile = TILETYPE::ACCESSIBLE;
						}
						//once blocked, it's always blocked
						elseif ((($blockMask[$r] >> $c) & 1) == 0) {
							$tile = TILETYPE::BLOCKED;
						}

						if($tile != TILETYPE::FREE) {
							$usedTiles[5 - $r][7 - $c] = $tile;
						}
					}
				}
			}
			else {
				$this->br->SkipBytes(12); //skip masks
			}

			$this->br->SkipBytes(2); //landscape
			$this->br->SkipBytes(2); //allowed terrain for object, not needed

			$objtemp->id = $this->br->ReadUint32();
			$objtemp->subid = $this->br->ReadUint32();
			$this->br->SkipBytes(2); //type and print, not needed
			//$objtemp->type = $this->br->ReadUint8();
			//$objtemp->printpriority-> = $this->br->ReadUint8();
			$objtemp->tiles = $usedTiles;

			$this->br->SkipBytes(16);

			$this->objTemplates[] = $objtemp;
		}

	}

	private function ReadObjects() {

		$this->objectsNum = $this->br->ReadUint32();

		// ======= ITERATE THROUGH OBJECTS
		for($i = 0; $i < $this->objectsNum; $i++) {
			$obj = [];
			$tileowner = OWNERNONE; //player colored tile
			$special = MAPSPECIAL::NONE; //special object displayed on map
			$saveobject = false; //no need to save any object to array, not used anywhere currently

			$x = $this->br->ReadUint8();
			$y = $this->br->ReadUint8();
			$z = $this->br->ReadUint8();

			$obj['pos'] = new MapCoords($x, $y, $z);
			$this->curcoor = $obj['pos'];

			$defnum = $this->br->ReadUint32(); //template index in template array
			$obj['defnum'] = $defnum;

			$objid = $objsubid = OBJECT_INVALID;

			if(array_key_exists($defnum, $this->objTemplates)) {
				$objid = $this->objTemplates[$defnum]->id;
				$obj['id'] = $objid;
				$obj['subid'] = $this->objTemplates[$defnum]->subid;
				$obj['objname'] = $this->GetObjectById($objid);
				$objsubid = $obj['subid'];

				if(!array_key_exists($objid, $this->objects_unique)) {
					$this->objects_unique[$objid] = ['name' => $this->GetObjectById($objid), 'count' => 0];
				}
				$this->objects_unique[$objid]['count']++;
			}
			else {
				$objid = OBJECT_INVALID;
				$obj['id'] = $objid;
			}

			$this->curobj = $objid;

			$this->br->SkipBytes(5);

			//echo ($i + 1).'/'.$this->objectsNum.' :: '.$objid.' - '.$this->GetObjectById($objid)."  $x, $y, $z - [".$this->rpos().']<br />';
			if($objid < 0) {
				throw new Exception('Invalid object ID '.$objid.' - '.$this->GetObjectById($objid)."  $x, $y, $z. Possibly a read error (".$this->mapfile.')');
				return;
			}

		// ======= GET OBJECT DATA
			switch($objid) {
				case OBJECTS::EVENT:
				case OBJECTS::PANDORAS_BOX:
					$event = [];
					$event['MessageStack'] = $this->ReadMessageAndGuards();

					$event['gainedExp'] = $this->br->ReadUint32();
					$event['manaDiff'] = $this->br->ReadInt32();
					$event['moraleDiff'] = $this->br->ReadInt8();
					$event['luckDiff'] = $this->br->ReadInt8();

					$event['resources'] = $this->ReadResourses();

					$event['priSkill'] = [];
					$event['secSkill'] = [];
					$event['artifacts'] = [];
					$event['spells'] = [];
					$event['stack'] = [];

					for($j = 0; $j < 4; $j++) {
						$event['priSkill'][$j] = $this->br->ReadUint8();
					}

					$secSkillsNum = $this->br->ReadUint8(); // Number of gained abilities
					for($j = 0; $j < $secSkillsNum; $j++) {
						$event['secSkill'][] = [
							'skill' => $this->GetSecskillById($this->br->ReadUint8()),
							'level' => $this->GetSecskillLevelById($this->br->ReadUint8())
						];
					}

					$artinum = $this->br->ReadUint8(); // Number of gained artifacts
					for($j = 0; $j < $artinum; $j++) {
						if($this->isROE) {
							$artid = $this->br->ReadUint8();
						}
						elseif($this->hota_subrev >= $this::HOTA_SUBREV4) {
							$artid = $this->br->ReadUint32();
						}
						else {
							$artid = $this->br->ReadUint16();
						}
						$artifact = $this->GetArtifactById($artid);
						$event['artifacts'][] = $artifact;
						$this->artifacts_list[] = new ListObject($artifact, $obj['pos'], 'Event');
					}

					$spellnum = $this->br->ReadUint8(); // Number of gained spells
					for($j = 0; $j < $spellnum; $j++) {
						$spell = $this->GetSpellById($this->br->ReadUint8());;
						$event['spells'][] = $spell;
						$this->spells_list[] = new ListObject($spell, $this->curcoor, 'Event');
					}

					$stackNum = $this->br->ReadUint8(); //number of gained creatures
					$event['stack'] = $this->ReadCreatureSet($stackNum);

					$this->br->SkipBytes(8);

					//event has some extras
					if($objid == OBJECTS::EVENT) {
						$event['availableFor'] = $this->br->ReadUint8();
						$event['computerActivate'] = $this->br->ReadUint8();
						$event['removeAfterVisit'] = $this->br->ReadUint8();
						if($this->hota_subrev >= $this::HOTA_SUBREV3) {
							$event['humanActivate'] = $this->br->ReadUint8();
						}
						else {
							$event['humanActivate'] = 1;
						}
						if($this->hota_subrev >= $this::HOTA_SUBREV4) {
							$event['move_bonus_type'] = $this->br->ReadUint32();
							$event['move_bonus_value'] = $this->br->ReadUint32();
						}

						$this->br->SkipBytes(4);
					}
					else { //pandora
						$event['availableFor'] = HNONE;
						$event['computerActivate'] = 1;
						$event['removeAfterVisit'] = '';
						$event['humanActivate'] = 1;
						if($this->hota_subrev >= $this::HOTA_SUBREV4) {
							$event['humanActivate'] = $this->br->ReadUint8();
							$event['move_bonus_type'] = $this->br->ReadUint32();
							$event['move_bonus_value'] = $this->br->ReadUint32();
						}
					}

					$obj['data'] = $event;

					$this->events_list[] = $obj;
					break;

				case OBJECTS::HERO:
				case OBJECTS::RANDOM_HERO:
				case OBJECTS::PRISON:
					$obj['data'] = $this->ReadHero();
					$tileowner = $obj['data']['PlayerColor'];
					$this->heroes_list[] = $obj;

					$obj['pos']->x -= 1; //offset for hero in town gate
					$this->mapobjects[] = [
						'object' => MAPOBJECTS::HERO,
						'objid' => $objid,
						'pos' => $obj['pos'],
						'name' => $obj['data']['name'],
						'owner' => $tileowner,
						'type' => $objsubid,
						'uid' => $obj['data']['uid']
					];
					break;

				case OBJECTS::MONSTER:	//Monster
				case OBJECTS::RANDOM_MONSTER:
				case OBJECTS::RANDOM_MONSTER_L1:
				case OBJECTS::RANDOM_MONSTER_L2:
				case OBJECTS::RANDOM_MONSTER_L3:
				case OBJECTS::RANDOM_MONSTER_L4:
				case OBJECTS::RANDOM_MONSTER_L5:
				case OBJECTS::RANDOM_MONSTER_L6:
				case OBJECTS::RANDOM_MONSTER_L7:
					//read monster
					$monster = [];
					$monster['uid'] = OBJECT_INVALID;

					$monster['name'] = ($objid == OBJECTS::MONSTER) ? $this->GetCreatureById($objsubid) : $obj['objname'];

					if($this->version > $this::ROE) {
						$monster['uid'] = $this->br->ReadUint32();
					}

					$monster['count'] = $this->br->ReadUint16();

					$monster['character'] = $this->GetMonsterCharacter($this->br->ReadUint8());

					$hasMessage = $this->br->ReadUint8();
					$artifact = '';
					if($hasMessage) {
						$monster['message'] = $this->ReadString();
						$monster['resources'] = $this->ReadResourses();

						if ($this->isROE) {
							$artid = $this->br->ReadUint8();
						}
						else {
							$artid = $this->br->ReadUint16();
						}

						if($this->isROE && $artid == HNONE) {
							$artid = HNONE16;
						}

						if($artid != HNONE16) {
							$artifact = $this->GetArtifactById($artid);
							$this->artifacts_list[] = new ListObject($artifact, $obj['pos'], 'Monster: '.$monster['name']);
						}
					}
					$monster['neverFlees'] = $this->br->ReadUint8();
					$monster['notGrowingTeam'] = $this->br->ReadUint8();

					$this->br->SkipBytes(2);

					if($this->hota_subrev >= $this::HOTA_SUBREV3) {
						$monster['characterSpec'] = $this->br->ReadUint32(); //precise setup      num of ffffffff
						$monster['moneyJoin'] = $this->br->ReadUint8();
						$monster['percentJoin'] = $this->br->ReadUint32();
						$monster['upgradedStack'] = $this->br->ReadUint32(); //upgraded stack in not upgraded monster 0/1/ffffffff (def)
						$monster['stacksCount'] = $this->br->ReadUint32(); //stack count       00=one more, ffffffff=def, fdffffff=avg, fdffffff=on less or num
					}
					if($this->hota_subrev >= $this::HOTA_SUBREV4) {
						$this->br->SkipBytes(5);
						//$monster_value_is_set = $this->br->ReadUint8();
						//$monster['monster_value'] = $this->br->ReadInt32();
					}

					$obj['data'] = $monster;

					$info = $monster['character'].($monster['neverFlees'] ? ', Never fless' : '');
					$this->monsters_list[] = new ListObject($monster['name'], $this->curcoor, 'Map', $artifact, $monster['count'], $info);

					$this->mapobjects[] = [
						'object' => MAPOBJECTS::MONSTER,
						'objid' => $objid,
						'pos' => $obj['pos'],
						'name' => $monster['name'],
						'owner' => OWNERNONE,
						'type' => $objsubid,
						'uid' => $monster['uid'],
					];
					break;

				case OBJECTS::OCEAN_BOTTLE:
				case OBJECTS::SIGN:
					$signbottle['text'] = $this->ReadString();
					$this->br->SkipBytes(4);
					$obj['data'] = $signbottle;
					$this->messages_list[] = $obj;
					break;

				case OBJECTS::SEER_HUT:
					$obj['data'] = $this->ReadSeerHut();
					break;

				case OBJECTS::WITCH_HUT:
					// in RoE we cannot specify it - all are allowed (I hope)
					$allowed = [];
					if($this->version > $this::ROE) {
						for($j = 0 ; $j < 4; $j++) {
							$c = $this->br->ReadUint8();
							$allowed[] = sprintf('%08b ', $c);
						}
					}

					$obj['data'] = $allowed;
					break;

				case OBJECTS::SCHOLAR:
					$scholar['bonusType'] = $this->br->ReadUint8(); //255=random, 0=primary, 1=secondary, 2=spell
					$scholar['bonusID'] = $this->br->ReadUint8();
					$this->br->SkipBytes(6);
					$obj['data'] = $scholar;
					//spell
					if($scholar['bonusType'] == 2) {
						$spell = $this->GetSpellById($scholar['bonusID']);
						$this->spells_list[] = new ListObject($spell, $this->curcoor, 'Scholar');
					}
					break;

				case OBJECTS::GARRISON:
				case OBJECTS::GARRISON2:
					$stack = [];
					$stack['owner'] = $this->br->ReadUint8();
					$tileowner = $stack['owner'];
					$this->br->SkipBytes(3);
					$stack['monsters'] = $this->ReadCreatureSet(7);
					if($this->version > $this::ROE) {
						$stack['removableUnits'] = $this->br->ReadUint8();
					}
					else {
						$stack['removableUnits'] = 1;
					}
					$this->br->SkipBytes(8);

					$obj['data'] = $stack;
					break;

				case OBJECTS::ARTIFACT:
				case OBJECTS::RANDOM_ART:
				case OBJECTS::RANDOM_TREASURE_ART:
				case OBJECTS::RANDOM_MINOR_ART:
				case OBJECTS::RANDOM_MAJOR_ART:
				case OBJECTS::RANDOM_RELIC_ART:
				case OBJECTS::SPELL_SCROLL:
					$artifact = [];
					$artifact['artid'] = OBJECT_INVALID;
					$artifact['spellid'] = OBJECT_INVALID;

					$artifact['stack'] = $this->ReadMessageAndGuards();

					if($objid == OBJECTS::SPELL_SCROLL) {
						$spellid = $this->br->ReadUint32();

						$spell = $this->GetSpellById($spellid);
						$this->spells_list[] = new ListObject($spell, $this->curcoor, 'Spell Scroll');

						$artifact['name'] = $obj['objname'].': '.$spell;
					}
					elseif($objid == OBJECTS::ARTIFACT) {
						$artifact['name'] = $this->GetArtifactById($obj['subid']); //artid
					}
					else {
						$artifact['name'] = $obj['objname'];
					}

					//hota extra
					if($this->hota_subrev >= $this::HOTA_SUBREV4 && $objid != OBJECTS::SPELL_SCROLL) {
						$this->br->SkipBytes(5);
						//$art_visit_type = $this->br->ReadUint32();
						//$visit_flags = $this->br->ReadUint8();
					}

					$this->artifacts_list[] = new ListObject($artifact['name'], $obj['pos'], 'Map');

					$obj['data'] = $artifact;
					break;

				case OBJECTS::RANDOM_RESOURCE:
				case OBJECTS::RESOURCE:
					$res = [];
					$res['stack'] = $this->ReadMessageAndGuards();
					$res['amount'] = $this->br->ReadUint32();
					$this->br->SkipBytes(4);
					$obj['data'] = $res;
					break;

				case OBJECTS::RANDOM_TOWN:
				case OBJECTS::TOWN:
					$obj['data'] = $this->ReadTown();

					$tileowner = $obj['data']['owner'];
					if($tileowner >= 0 && $tileowner <= 7) {
						$this->players[$tileowner]['townsOwned']++;
					}

					$affiliation = ($objid == OBJECTS::TOWN) ? $this->GetTownById($objsubid) : 'Random Town';
					$obj['data']['affiliation'] = $affiliation;

					$this->towns_list[] = $obj;

					$obj['pos']->x -=2; //substract 2, to make position centered to town gate
					$this->mapobjects[] = [
						'object' => MAPOBJECTS::TOWN,
						'objid' => $objid,
						'pos' => $obj['pos'],
						'name' => $obj['data']['name'],
						'owner' => $tileowner,
						'type' => $objsubid,
						'uid' => $obj['data']['uid']
					];
					break;

				case OBJECTS::MINE:
				case OBJECTS::ABANDONED_MINE:
					$mine['owner'] = $this->br->ReadUint8(); //owner or resource mask for abandoned mine
					$this->br->SkipBytes(3);
					$tileowner = $mine['owner'];

					$resource = '';
					//subteranean and some other mines dont have correct objid, but subid is always 7 for abandoned mine
					if($objid == OBJECTS::ABANDONED_MINE || $obj['subid'] == 7) {
						$n = 0;
						//in this case, tileowner is mask for possible resources
						for($j = 0; $j < 7; $j++) {
							if($tileowner & (1 << $j)) {
								if($n++ > 0) {
									$resource .= ', ';
								}
								$resource .= $this->GetResourceById($j);
							}
						}
						$tileowner = HNONE;

						if($this->hota_subrev >= $this::HOTA_SUBREV4) {
							$this->br->SkipBytes(13);
								//not used
								/*uchar selected_variant;
								uint32 guard_id;
								uint32 quantity_min;
								uint32 quantity_max;*/
						}
					}
					else {
						$resource = $this->GetResourceById($obj['subid']);
					}

					$this->mines_list[] = new Listobject($this->GetMineById($obj['subid']), $this->curcoor, '', $tileowner, 0, $resource);

					$obj['data'] = $mine;
					break;

				case OBJECTS::CREATURE_GENERATOR1:
				case OBJECTS::CREATURE_GENERATOR2:
				case OBJECTS::CREATURE_GENERATOR3:
				case OBJECTS::CREATURE_GENERATOR4:
					$dwelling['owner'] = $this->br->ReadUint8();
					$this->br->SkipBytes(3);
					$tileowner = $dwelling['owner'];

					$obj['data'] = $dwelling;
					break;

				case OBJECTS::SHRINE_OF_MAGIC_INCANTATION:
				case OBJECTS::SHRINE_OF_MAGIC_GESTURE:
				case OBJECTS::SHRINE_OF_MAGIC_THOUGHT:
					$shrine = [];
					$shrine['spellid'] = $this->br->ReadUint8();
					$this->br->SkipBytes(3);
					$obj['data'] = $shrine;
					break;

				case OBJECTS::GRAIL:
					if($this->hota_subrev >= $this::HOTA_SUBREV3) {
						//if true, no grail allowed
						if($obj['subid'] >= 1000 && $obj['subid'] <= 1003) {
							break;
						}
					}

					$grail['radius'] = $this->br->ReadUint32();
					$obj['data'] = $grail;
					break;

				case OBJECTS::RANDOM_DWELLING: //same as castle + level range
				case OBJECTS::RANDOM_DWELLING_LVL: //same as castle, fixed level
				case OBJECTS::RANDOM_DWELLING_FACTION: //level range, fixed faction
					$dwelling = [];
					$dwelling['player'] = $this->br->ReadUint32();

					//216 and 217
					if ($objid == OBJECTS::RANDOM_DWELLING || $objid == OBJECTS::RANDOM_DWELLING_LVL) {
						$dwelling['uid'] =	$this->br->ReadUint32();
						if(!$dwelling['uid']) {
							$dwelling['asCastle'] = 0;
							$dwelling['castles'][0] = $this->br->ReadUint8();
							$dwelling['castles'][1] = $this->br->ReadUint8();
						}
						else {
							$dwelling['asCastle'] = 1;
						}
					}

					//216 and 218
					if ($objid == OBJECTS::RANDOM_DWELLING || $objid == OBJECTS::RANDOM_DWELLING_FACTION) {
						$dwelling['minLevel'] = max($this->br->ReadUint8(), 1);
						$dwelling['maxLevel'] = min($this->br->ReadUint8(), 7);
					}

					$obj['data'] = $dwelling;
					break;

				case OBJECTS::QUEST_GUARD:
					$quest = $this->ReadQuest();

					$this->quest_list[] = new ListObject('Quest Guard', $this->curcoor, $quest['Qtext'], '', '', '', $quest['uid'], [$quest['textFirst'], $quest['textRepeat'], $quest['textDone']]);

					$obj['data'] = $quest;
					break;

				case OBJECTS::SHIPYARD:
					$shipyard['owner'] = $this->br->ReadUint32();
					$obj['data'] = $shipyard;
					$tileowner = $shipyard['owner'];
					break;

				case OBJECTS::HERO_PLACEHOLDER: //hero placeholder
					$tileowner = $this->br->ReadUint8();
					$placeholder['owner'] = $tileowner;
					$placeholder['heroid'] = $this->br->ReadUint8(); //hero type id

					if($placeholder['heroid'] == HNONE) {
						$placeholder['power'] = $this->br->ReadUint8();
					}
					else {
						$placeholder['power'] = 0;
					}

					$heroname = $this->GetHeroById($placeholder['heroid']);

					//hota > sub5
					$placeholder['stack'] = [];
					$placeholder['artifacts'] = [];

					if($this->hota_subrev >= $this::HOTA_SUBREV5) {
						$has_monsters = $this->br->ReadUint8();
						for ($j = 0; $j < 7; $j++) {
							$count = $this->br->ReadUint32();
							$monsterid = $this->br->ReadUint32();
							if($monsterid != HNONE32) {
								$placeholder['stack'][] = ['count' => $count, 'id' => $monsterid];
							}
						}

						$art_count = $this->br->ReadInt32();
						for ($j = 0; $j < $art_count; $j++) {
							$spell = '';
							$artid = $this->br->ReadUint32();
							$artifact = $this->ReadHotaArtifact($artid);

							$placeholder['artifacts'][] = $artifact;
							$this->artifacts_list[] = new ListObject($artifact, $this->curcoor, 'Hero: '.$heroname);
						}
					}

					$obj['data'] = $placeholder;
					$obj['pos']->x -= 1; //place holder is never in town, but it must be centered for victory/loss conditions too

					$this->heroes_placeholder[] = [
						'object' => MAPOBJECTS::HERO,
						'objid' => $objid,
						'heroid' => $placeholder['heroid'],
						'pos' => $obj['pos'],
						'name' => $heroname,
						'owner' => $tileowner,
						'type' => $objsubid,
						'power' => $placeholder['power'],
						'stack' => $placeholder['stack'],
						'artifacts' => $placeholder['artifacts'],
					];
					
					$this->mapobjects[] = [
						'object' => MAPOBJECTS::HERO,
						'objid' => $objid,
						'uid' => 0,
						'pos' => $obj['pos'],
						'name' => $this->GetHeroById($placeholder['heroid']),
						'owner' => $tileowner,
						'type' => $objsubid,
					];



					break;

				case OBJECTS::BORDER_GATE:
					//HOTA quest gate
					if($this->hota_subrev >= $this::HOTA_SUBREV3) {
						if($obj['subid'] == 1000) {
							$quest = $this->ReadQuest();
							$this->quest_list[] = new ListObject('Quest Guard', $this->curcoor, $quest['Qtext'], '', '', '', $quest['uid'], [$quest['textFirst'], $quest['textRepeat'], $quest['textDone']]);
							$obj['data'] = $quest;
						}
					}
					if($this->hota_subrev >= $this::HOTA_SUBREV4) {
						//grave
						if($obj['subid'] == 1001) {
							$this->ReadHotaResoursesCache('Grave');
						}
					}

				case OBJECTS::BORDERGUARD:
				case OBJECTS::KEYMASTER:
					$this->keys_list[] = $obj;
					break;

				case OBJECTS::PYRAMID: //Pyramid or WoG object
					if($this->hota_subrev >= $this::HOTA_SUBREV4) {
						$this->ReadHotaSpellReward();
					}
					break;

				case OBJECTS::CAMPFIRE:
				case OBJECTS::LEAN_TO:
				case OBJECTS::WAGON:
					if($this->hota_subrev >= $this::HOTA_SUBREV4) {
						$this->ReadHotaResoursesCache();
					}
					break;

				case OBJECTS::WATER_RESOURCE:
					if($this->hota_subrev >= $this::HOTA_SUBREV4) {
						if($obj['subid'] == 0 || $obj['subid'] == 1) { //Ancient lamp, Sea barrel
							$this->ReadHotaResoursesCache('Ancient Lamp / Sea Barrel');
						}
						elseif($obj['subid'] == 2 || $obj['subid'] == 3) { //Jetsam, Vial of mana
							$this->ReadHotaSpellReward('Jetsam / Vial of Mana');
						}
					}
					break;

				case OBJECTS::CORPSE:
				case OBJECTS::FLOTSAM:
				case OBJECTS::SEA_CHEST:
				case OBJECTS::SHIPWRECK_SURVIVOR:
				case OBJECTS::TREASURE_CHEST:
				case OBJECTS::TREE_OF_KNOWLEDGE:
				case OBJECTS::WARRIORS_TOMB:
					if($this->hota_subrev >= $this::HOTA_SUBREV4) {
						$this->ReadHotaSpellReward();
					}
					break;

				case OBJECTS::LIGHTHOUSE: //Lighthouse
					$lighthouse['owner'] = $this->br->ReadUint32();
					$tileowner = $lighthouse['owner'];

					$obj['data'] = $lighthouse;
					break;

				//HOTA
				case OBJECTS::CREATURE_BANK:
				case OBJECTS::DERELICT_SHIP:
				case OBJECTS::DRAGON_UTOPIA:
				case OBJECTS::CRYPT:
				case OBJECTS::SHIPWRECK:
					if($this->hota_subrev >= $this::HOTA_SUBREV3) {
						$bank = [];
						$bank['variant'] = $this->br->ReadUint32();
						$bank['upgraded'] = $this->br->ReadUint8();
						$bank['artnum'] = $this->br->ReadUint32();
						for($j = 0; $j < $bank['artnum']; $j++) {
							$artid = $this->br->ReadUint32();
							if($artid == HNONE32) {
								$art = 'Random Artefact';
							}
							else {
								$art = $this->GetArtifactById($artid);
							}
							$bank['artefacts'][] = $art;
							$this->artifacts_list[] = new ListObject($art, $this->curcoor, 'Creature bank');
						}
						$obj['data'] = $bank;
					}
					break;

				case OBJECTS::MONOLITH_ONE_WAY_ENTRANCE:
				case OBJECTS::MONOLITH_ONE_WAY_EXIT:
				case OBJECTS::MONOLITH_TWO_WAY:
				case OBJECTS::WHIRLPOOL:
					//$this->monolith_list[] = $obj;
					$this->monolith_list[$obj['id']][$obj['subid']][] = $obj['pos']->GetCoords();
				  break;

				case OBJECTS::BLACK_MARKET:
					if($this->hota_subrev >= $this::HOTA_SUBREV4) {
						for ($j = 0; $j < 7; $j++) {
							$artid = $this->br->ReadUint32();
							if($artid != HNONE32) {
								$this->artifacts_list[] = new ListObject($this->GetArtifactById($artid), $this->curcoor, 'Black Market');
							}
						}
					}
					break;

				case OBJECTS::UNIVERSITY:
					if($this->hota_subrev >= $this::HOTA_SUBREV4) {
						$this->br->SkipBytes(8);
						//$obj['data']['variant'] = $this->br->ReadInt32(); //-1 => random
						//$obj['data']['skills'] = $this->br->ReadUint32();
					}
					break;

				case OBJECTS::SEA_UNIVERSITY:
					if($this->hota_subrev >= $this::HOTA_SUBREV4 && $obj['subid'] == 0) {
						$this->br->SkipBytes(8);
						//$obj['data']['variant'] = $this->br->ReadInt32(); //-1 => random
						//$obj['data']['skills'] = $this->br->ReadUint32();
					}
					break;


				default:
					//any other object, we dont want to save to array
					//as a matter of fact, we save that only for debug purpose, the class object is not used anywhere since this
					$saveobject = false;
					break;
			} //switch end

			//object tiles
			//if we dont build map, we dont need to save terrain access
			if($this->buildMapImage && $objid != OBJECT_INVALID) {
				$mapsizemax = $this->map_size - 1; //index starts with 0, we make variable here to not substract 1 in loop to make more readable

				foreach($this->objTemplates[$defnum]->tiles as $iy => $col) { //y-axis of object tiles
					foreach($col as $ix => $tilemask) { //x-axis of object tiles
						//real xy position on map
						$mx = $x - $ix;
						$my = $y - $iy;

						//object tile out of bound check
						if($z > 1 || $my > $mapsizemax || $my < 0 || $mx > $mapsizemax || $mx < 0) {
							continue;
						}

						//tile already has owner or is special -> it will have color independent on access
						if($this->terrain[$z][$my][$mx]->owner != OWNERNONE || $this->terrain[$z][$my][$mx]->special != MAPSPECIAL::NONE) {
							continue;
						}

						//$tilemask is object tilemask for current tile. With this, it can be checked whether tile can be stepped on
						//check if tile has object on it, if yes, continue with checks
						if($tilemask == TILETYPE::FREE) {
							continue;
						}
						//is object owned? if yes, mark tile as owned
						elseif($tileowner != OWNERNONE) {
							$this->terrain[$z][$my][$mx]->owner = $tileowner;
						}
						//has object some special color rule? if yes, mark as special
						elseif($special != MAPSPECIAL::NONE) {
							$this->terrain[$z][$my][$mx]->special = MAPSPECIAL::ANY;
						}
						//can object tile be stepped on? if no, apply tilemask, which marks access as blocked
						elseif($tilemask != TILETYPE::ACCESSIBLE) {
							$this->terrain[$z][$my][$mx]->access = $tilemask;
						}
						
					}
				}
			}
			
			if($saveobject) {
				$this->objects[] = $obj;
			}
		}
	}

	private function ReadTown() {
		$town = [];
		$town['uid'] = OBJECT_INVALID;
		$town['name'] = 'Random name';

		if($this->version > $this::ROE) {
			$town['uid'] = $this->br->ReadUint32();
		}

		$town['owner'] = $this->br->ReadUint8();
		$town['player'] = $this->GetPlayerColorById($town['owner']);

		$hasName = $this->br->ReadUint8();
		if($hasName) {
			$town['name'] = $this->ReadString();
		}

		$town['stack'] = [];
		$hasGarrison = $this->br->ReadUint8();
		if($hasGarrison) {
			$town['stack'] = $this->ReadCreatureSet(7);
		}
		$town['formation'] = $this->br->ReadUint8();

		$town['max_guild'] = '';

		$hasCustomBuildings = $this->br->ReadUint8();
		if($hasCustomBuildings) {
			//$this->br->SkipBytes(12); //not really used right now
			/*for($i = 0; $i < 6; $i++) {
				$town['buildingsBuilt'][] = sprintf('%08b ', $this->br->ReadUint8());
			}

			for($i = 0; $i < 6; $i++) {
				$town['buildingsDisabled'][] = sprintf('%08b ', $this->br->ReadUint8());
			}*/

			$this->br->SkipBytes(7); //not really used right now
			//$town['max_guild'] = sprintf('%08b ', $this->br->ReadUint8()).'<br />'; //$this->br->ReadUint8(); //mage guilds
			$mageguilds = $this->br->ReadUint8();
			$this->br->SkipBytes(4); //not really used right now

			//$town['max_guild'] = $mageguilds;
			// 0001 0000
			for ($i = 3; $i < 8; $i++) {
				if(($mageguilds & (1 << $i)) != 0) {
					$town['max_guild'] = $i - 3;
					break;
				}
			}
		}
		// Standard buildings
		else {
			$town['fort'] = 'no';
			$hasFort = $this->br->ReadUint8();
			if($hasFort) {
				$town['fort'] = 'yes';
			}
		}

		//spells always
		$town['spellsA'] = [];
		if($this->version > $this::ROE) {
			for($i = 0; $i < SPELL_BYTE; $i++) {
				$spellb = $this->br->ReadUint8();
				for($s = 0; $s < 8; $s++) {
					if(($spellb >> $s) & 0x01) { //add obligatory spell even if it's banned on a map
						$spellid = $i * 8 + $s;
						if($spellid >= SPELLS_QUANTITY) {
							break;
						}
						$spell = $this->GetSpellById($spellid);
						$town['spellsA'][] = $spell;
						$this->spells_list[] = new ListObject($spell, $this->curcoor, 'Town: '.$town['name']);
					}
				}
			}
		}

		//spells random
		$town['spellsD'] = [];
		for($i = 0; $i < SPELL_BYTE; $i++) {
			//$this->br->SkipBytes(1); //spells, not used currently in mapscan
			$spellb = $this->br->ReadUint8();
			for($s = 0; $s < 8; $s++) {
				//spells that cant appear
				if(($spellb >> $s) & 0x01) {
					$spellid = $i * 8 + $s;
					if($spellid >= SPELLS_QUANTITY) {
						break;
					}
					$town['spellsD'][] = $this->GetSpellById($spellid);
				}
			}
		}

		$town['spells'] = '';
		if(!empty($town['spellsA'])) {
			$town['spells'] .= 'Always: '.implode($town['spellsA'], ', ');
		}
		if(!empty($town['spellsD'])) {
			$town['spells'] .= '<br />Disabled: '.implode($town['spellsD'], ', ');
		}

		if($this->hota_subrev >= $this::HOTA_SUBREV1) {
			$this->br->SkipBytes(1); //spell research, not used currently in mapscan
		}
		if($this->hota_subrev >= $this::HOTA_SUBREV4) {
			$settings_count = $this->br->ReadInt32(); //special settings for towns
			$this->br->SkipBytes($settings_count);
		}

		// Read castle events
		$town['eventsnum'] = $this->br->ReadUint32();
		for($e = 0; $e < $town['eventsnum']; $e++) {
			$event = [];

			$event['name'] = $this->ReadString();
			$event['message'] = $this->ReadString();

			$event['res'] = $this->ReadResourses();

			$event['players'] = $this->br->ReadUint8();
			if($this->version > $this::AB) {
				$event['human'] = $this->br->ReadUint8();
			}
			else {
				$event['human'] = 1;
			}

			$event['computerAffected'] = $this->br->ReadUint8();
			$event['firstOccurence'] = $this->br->ReadUint16() + 1;
			$event['nextOccurence'] =	$this->br->ReadUint16();

			$this->br->SkipBytes(16);

			if($this->hota_subrev >= $this::HOTA_SUBREV4) {
				$this->br->ReadInt32(); //add_creature_count7b; //dreadnought bonus
				$bit_count = $this->br->ReadInt32();
				if($bit_count > 0) {
					$this->br->SkipBytes(intval(($bit_count + 7) / 8));
				}
			}

			for($i = 0; $i < 6; $i++) {
				$event['buildings'][] = $this->br->ReadUint8();
			}

			for($i = 0; $i < 7; $i++) {
				$event['monsters'][] = $this->br->ReadUint16();
			}
			$this->br->SkipBytes(4);
			$town['events'][] = $event;
		}

		if($this->version > $this::AB) {
			$town['alignment'] = $this->br->ReadUint8();
		}
		$this->br->SkipBytes(3);

		return $town;
	}

	private function ReadSeerHut() {
		$hut = [];
		$numquest = 1;
		$qi = 0; //quest iterator

		$hut['quest'][0]['taskid'] = QUESTMISSION::NONE;


		if($this->version > $this::ROE) {
			if($this->hota_subrev >= $this::HOTA_SUBREV3) {
				$numquest = $this->br->ReadUint32(); //number of quests
			}
			for($i = 0; $i < $numquest; $i++) {
				$hut['quest'][$qi] = $this->ReadQuest();
				$hut['quest'][$qi]['task'] = FromArray($hut['quest'][$qi]['taskid'], $this->CS->QuestMission);
				$qi++;

				//reward for each quest
				$reward = $this->ReadReward();
				$hut = array_merge($hut, $reward);
			}
		}
		else { //$qi is always 0
			//RoE
			$hut['quest'][0]['Qtext'] = '';
			$hut['quest'][0]['uid'] = 0;

			$artid = $this->br->ReadUint8();
			if ($artid != HNONE) {
				$hut['quest'][0]['taskid'] = QUESTMISSION::ART;
				$hut['quest'][0]['Qtext'] = 'Artifacts: '.$this->GetArtifactById($artid);
			}
			else {
				$hut['quest'][0]['taskid'] = QUESTMISSION::NONE;
			}
			$hut['quest'][0]['timeout'] = OBJECT_INVALID; //no timeout
			$hut['quest'][0]['task'] = FromArray($hut['quest'][0]['taskid'], $this->CS->QuestMission);
			$hut['quest'][0]['textFirst'] = '';
			$hut['quest'][0]['textRepeat'] = '';
			$hut['quest'][0]['textDone'] = '';

			$reward = $this->ReadReward();
			$hut = array_merge($hut, $reward);
		}

		//HOTA extra
		if($this->hota_subrev >= $this::HOTA_SUBREV3) {
			$numquest = $this->br->ReadUint32(); //number of multiple/cycled quests

			if($numquest > 0) {
				for($i = 0; $i < $numquest; $i++) {
					$hut['quest'][$qi] = $this->ReadQuest();
					$hut['quest'][$qi]['task'] = FromArray($hut['quest'][$qi]['taskid'], $this->CS->QuestMission);
					$qi++;

					$reward = $this->ReadReward();
					$hut = array_merge($hut, $reward);
				}
			}
		}

		$this->br->SkipBytes(2);
		
		$Qtext = '';
		foreach ($hut['quest'] as $k => $hutquest) {
			if($k > 0) {
				$Qtext .= '<br />';
			}
			$Qtext .= $hutquest['Qtext'];
		}

		$this->quest_list[] = new ListObject('Seer\'s Hut', $this->curcoor, $Qtext, $hut['rewardType'], $hut['value'], $hut['id'], $hut['quest'][0]['uid'],
			[
			$hut['quest'][0]['textFirst'],
			$hut['quest'][0]['textRepeat'],
			$hut['quest'][0]['textDone']
			]
		);

		return $hut;
	}

	private function ReadQuest() {
		$quest = [];
		$quest['taskid'] = $this->br->ReadUint8();

		$quest['Qtext'] = '';
		$quest['uid'] = 0;
		$quest['textFirst'] = '';
		$quest['textRepeat'] = '';
		$quest['textDone'] = '';

		switch($quest['taskid']) {
			case QUESTMISSION::NONE:
				return $quest;
			case QUESTMISSION::PRIMARY_STAT:
				for($x = 0; $x < 4; $x++) {
					$value = $this->br->ReadUint8();
					$quest['Qpriskill'][] = $value;
					if($value > 0) {
						$quest['Qtext'] = $this->GetPriskillById($x).': '.$value;
					}
				}
				break;
			case QUESTMISSION::LEVEL:
				$quest['Qlevel'] = $this->br->ReadUint32();
				$quest['Qtext'] = 'Level: '.$quest['Qlevel'];
				break;
			case QUESTMISSION::KILL_HERO:
				$quest['Qkillhero'] = $this->br->ReadUint32();
				$quest['Qtext'] = 'Kill hero: ';
				$quest['uid'] = $quest['Qkillhero'];
				break;
			case QUESTMISSION::KILL_CREATURE:
				$quest['Qkillmon'] = $this->br->ReadUint32();
				$quest['Qtext'] = 'Kill monster: ';
				$quest['uid'] = $quest['Qkillmon'];
				break;
			case QUESTMISSION::ART:
				$artNumber = $this->br->ReadUint8();
				$quest['Qtext'] = 'Artifacts: ';
				for($x = 0; $x < $artNumber; $x++) {
					if($this->hota_subrev >= $this::HOTA_SUBREV4) {
						$artifact = $this->ReadHotaArtifact($this->br->ReadUint32());
					}
					else {
						$artifact = $this->GetArtifactById($this->br->ReadUint16());
					}
					$quest['Qtext'] .= $artifact.' ';
				}
				break;
			case QUESTMISSION::ARMY:
				$typeNumber = $this->br->ReadUint8();
				$quest['Qtext'] = 'Bring army:<br />';
				for($x = 0; $x < $typeNumber; $x++) {
					$monster = $this->GetCreatureById($this->br->ReadUint16());
					$count = $this->br->ReadUint16();
					$quest['Qarmy'] = ['monid' => $monster, 'count' => $count];
					$quest['Qtext'] .= ($x > 0 ? '<br />' : '').$monster.': '.$count;
				}
				break;
			case QUESTMISSION::RESOURCES:
				for($x = 0; $x < 7; $x++) {
					$count = $this->br->ReadUint32();
					$quest['Qres'][] = $count;
					if($count > 0) {
						$resall[] = $this->GetResourceById($x).': '.$count;
					}
				}
				$quest['Qtext'] = 'Resource:<br />'.implode($resall, '<br />');
				break;
			case QUESTMISSION::HERO:
				$quest['Qhero'] = $this->br->ReadUint8();
				$quest['Qtext'] = 'Be hero: '.$this->GetHeroById($quest['Qhero']);
				break;
			case QUESTMISSION::PLAYER:
				$quest['Qplayer'] = $this->br->ReadUint8();
				$quest['Qtext'] = 'Be player: '.$this->GetPlayerColorById($quest['Qplayer']);
				break;
			case QUESTMISSION::HOTA_EXTRA:
				if($this->hota_subrev >= $this::HOTA_SUBREV3) {
					$hotaquestid = $this->br->ReadUint32();
					if($hotaquestid == QUESTMISSION::HOTA_CLASS) {
						$class_count = $this->br->ReadUint32();
						if ($class_count > 0) {
							$class_avail = (int)ceil($class_count / 8);
							$class = [];
							for ($x = 0; $x < $class_avail; $x++) {
								$classmask = $this->br->ReadUint8();
								for ($c = 0; $c < 8; $c++) {
									if(($classmask >> $c) & 0x01) {
										$class[] = $this->GetHeroClassById($x * 8 + $c);
									}
								}
							}
							$quest['Qtext'] = 'Be class: '.implode($class, ', ');
						}
					}
					elseif($hotaquestid == QUESTMISSION::HOTA_NOTBEFORE) {
						$quest['ReturnAfter'] = $this->br->ReadUint32();
						$quest['Qtext'] = 'Return after '.$quest['ReturnAfter'].' days';
					}
				}
				break;
		}

		$limit = $this->br->ReadUint32();
		if($limit == HNONE32) {
			$quest['timeout'] = OBJECT_INVALID;
		}
		else {
			$quest['timeout'] = $limit;
			$quest['Qtext'] .= '<br />Before days: '.$limit;
		}
		$quest['textFirst'] = $this->ReadString();
		$quest['textRepeat'] = $this->ReadString();
		$quest['textDone'] = $this->ReadString();

		return $quest;
	}
	
	private function ReadReward() {
		$reward['rewardid'] = $this->br->ReadUint8();
		$reward['rewardType'] = FromArray($reward['rewardid'], $this->CS->RewardType);
		$reward['id'] = '';
		$reward['value'] = '';

		switch($reward['rewardid']) {
			case REWARDTYPE::EXPERIENCE:
				$reward['value'] = $this->br->ReadUint32();
				break;
			case REWARDTYPE::MANA_POINTS:
				$reward['value'] = $this->br->ReadUint32();
				break;
			case REWARDTYPE::MORALE_BONUS:
				$reward['value'] = $this->br->ReadUint8();
				break;
			case REWARDTYPE::LUCK_BONUS:
				$reward['value'] = $this->br->ReadUint8();
				break;
			case REWARDTYPE::RESOURCES:
				$reward['id'] = $this->GetResourceById($this->br->ReadUint8());
				$reward['value'] = $this->br->ReadUint32() & 0x00ffffff;
				break;
			case REWARDTYPE::PRIMARY_SKILL:
				$reward['id'] = $this->GetPriskillById($this->br->ReadUint8());
				$reward['value'] = $this->br->ReadUint8();
				break;
			case REWARDTYPE::SECONDARY_SKILL:
				$reward['id'] = $this->GetSecskillById($this->br->ReadUint8());
				$reward['value'] = $this->GetSecskillLevelById($this->br->ReadUint8());
				break;
			case REWARDTYPE::ARTIFACT:
				if ($this->isROE) {
					$reward['artid'] = $this->br->ReadUint8();
				}
				elseif($this->hota_subrev >= $this::HOTA_SUBREV4) {
					$reward['artid'] = $this->br->ReadUint32();
				}
				else {
					$reward['artid'] = $this->br->ReadUint16();
				}
				$artifact = $this->GetArtifactById($reward['artid']);
				$reward['value'] = $artifact;

				$this->artifacts_list[] = new ListObject($artifact, $this->curcoor, 'Seer\'s Hut');
				break;
			case REWARDTYPE::SPELL:
				$spell = $this->GetSpellById($this->br->ReadUint8());
				$reward['value'] = $spell;
				$this->spells_list[] = new ListObject($spell, $this->curcoor, 'Seer\'s Hut');
				break;
			case REWARDTYPE::CREATURE:
				if($this->version > $this::ROE) {
					$reward['id'] = $this->GetCreatureById($this->br->ReadUint16());
				}
				else {
					$reward['id'] = $this->GetCreatureById($this->br->ReadUint8());
				}
				$reward['value'] = $this->br->ReadUint16();
				break;
		}
		
		return $reward;
	}

	private function ReadEvents() {
		$numberOfEvents = $this->br->ReadUint32();
		$event = [];
		for($i = 0; $i < $numberOfEvents; $i++) {
			$event['order'] = $i;
			$event['name'] = $this->ReadString();
			$event['message'] = $this->ReadString();

			$event['resources'] = $this->ReadResourses();
			$event['players'] = $this->br->ReadUint8();
			if($this->version > $this::AB) {
				$event['humanAble'] = $this->br->ReadUint8();
			}
			else {
				$event['humanAble'] = 1;
			}
			$event['aiAble'] = $this->br->ReadUint8();
			$event['first'] = $this->br->ReadUint16() + 1;
			$event['interval'] = $this->br->ReadUint16();

			$this->br->SkipBytes(16);

			if($this->hota_subrev >= $this::HOTA_SUBREV4) {
				///skip this, not really useful
				$this->br->SkipBytes(4);
				$bitcount = $this->br->ReadUint32();
				$this->br->SkipBytes(intval(($bitcount + 7) / 8));
			}

			$this->events[] = $event;
		}
	}

	private function ReadMessageAndGuards() {
		$mag = [];
		$hasMessage = $this->br->ReadUint8();
		if($hasMessage) {
			$mag['message'] = $this->ReadString();
			$hasGuards = $this->br->ReadUint8();
			if($hasGuards) {
				$mag['stack'] = $this->ReadCreatureSet(7);
			}
			$this->br->SkipBytes(4);
		}
		return $mag;
	}

	private function ReadCreatureSet($number) {
		$version = ($this->version > $this::ROE);
		$maxID = $version ? 0xffff : 0xff;

		$stack = [];

		for($i = 0; $i < $number; $i++) {
			$creatureID = $version ? $this->br->ReadUint16() : $this->br->ReadUint8();
			$count = $this->br->ReadUint16();

			// Empty slot
			if($creatureID == $maxID) {
				continue;
			}
			if($creatureID > $maxID - 0x0f) {
				//this will happen when random object has random army
				$creatureID = ($maxID - $creatureID - 1) + 1000; //arbitrary 1000 for extension of monster ids
			}
			$stack[] = ['id' => $creatureID, 'count' => $count];
		}
		return $stack;
	}
	
	private function ReadResourses() {
		$resources = [];
		for($i = 0; $i < 7; $i++) {
			$res = $this->br->ReadInt32();
			if($res != 0) {
				$resources[$i] = $res;
			}
		}
		return $resources;
	}

	private function ReadHotaSpellReward($object = false) {
		$variant = $this->br->ReadInt32();
		$spellid = $this->br->ReadInt32();

		if($spellid != HOTA_RANDOM) {
			$object_name = $object ? $object : $this->GetObjectById($this->curobj);
			$this->spells_list[] = new ListObject($this->GetSpellById($spellid), $this->curcoor, $object_name);
		}
	}

	private function ReadHotaResoursesCache($object = false) {
		$variant = $this->br->ReadInt32();
		$artid = $this->br->ReadInt32();

		if($artid != HOTA_RANDOM) {
			$object_name = $object ? $object : $this->GetObjectById($this->curobj);
			$this->artifacts_list[] = new ListObject($this->GetArtifactById($artid), $this->curcoor, $object_name);
		}

		$this->br->SkipBytes(10);
		/* //no interester in resources now
		$amount1 = $this->br->ReadInt32();
		$res1id = $this->br->ReadUint8();
		$amount2 = $this->br->ReadInt32();
		$res2id = $this->br->ReadUint8();*/
	}

	private function ReadHotaArtifact($artid) {
		//spellscroll, format aaaassss, a=artid, s=spellid
		$spell = '';
		if($artid > HNONE16) {
			$spellid = $artid >> 16;
			$artid = 1;
			$spell = $this->GetSpellById($spellid);
			$this->spells_list[] = new ListObject($spell, $this->curcoor, 'Hero Spell Scroll');
			$spell = ': '.$spell;
		}
		return $this->GetArtifactById($artid).$spell;
	}



	private function ParseFinish() {
		//determine, if hero is in castle by tile being blocked
		foreach($this->mapobjects as $k => $mapobjh) {
			if($mapobjh['object'] == MAPOBJECTS::HERO) {
				foreach($this->mapobjects as $n => $mapobjl) {
					if($mapobjl['object'] == MAPOBJECTS::TOWN) {
						if(	 $mapobjh['pos']->x - 1 == $mapobjl['pos']->x //hero at castle has x-1 compared to castle coord
							&& $mapobjh['pos']->y == $mapobjl['pos']->y
							&& $mapobjh['pos']->z == $mapobjl['pos']->z
							)
						{
							$this->mapobjects[$k]['pos']->x -= 1;
						}
					}
				}
			}
		}

		//update hero names from map to predefined array
		foreach($this->heroesPredefined as $k => $heroP) { //predefined
			foreach($this->heroes_list as $heroM) { //on map
				if($heroP['id'] == $heroM['data']['subid']) {
					$this->heroesPredefined[$k]['name'] .= $heroM['data']['name'];
					break;
				}
			}
		}

		//update victory and loss condition details
		switch($this->victoryCond['type']) {
			case VICTORY::UPGRADETOWN:
				$name = $this->GetMapObjectByPos(MAPOBJECTS::TOWN, $this->victoryCond['coor']);
				$this->victoryInfo = 'Upgrade town '.$name.' at '.$this->victoryCond['coor']->GetCoords()
					.' to '.$this->victoryCond['hall_lvl'].' and '.$this->victoryCond['castle_lvl'];
				break;
			case VICTORY::BUILDGRAIL:
				$name = $this->GetMapObjectByPos(MAPOBJECTS::TOWN, $this->victoryCond['coor']);
				$this->victoryInfo = 'Build the grail structure at town '.$name.' '.$this->victoryCond['coor']->GetCoords();
				break;
			case VICTORY::DEFEATHERO:
				$name = $this->GetMapObjectByPos(MAPOBJECTS::HERO, $this->victoryCond['coor']);
				$this->victoryInfo = 'Defeat hero '.$name.' at '.$this->victoryCond['coor']->GetCoords();
				break;
			case VICTORY::CAPTURETOWN:
				$name = $this->GetMapObjectByPos(MAPOBJECTS::TOWN, $this->victoryCond['coor']);
				$this->victoryInfo = 'Capture town '.$name.' at'.$this->victoryCond['coor']->GetCoords();
				break;
			case VICTORY::KILLMONSTER:
				$name = $this->GetMapObjectByPos(MAPOBJECTS::MONSTER, $this->victoryCond['coor']);
				$this->victoryInfo = 'Defeat monster '.$name.' at '.$this->victoryCond['coor']->GetCoords();
				break;
			case VICTORY::TRANSPORTART:
				$name = $this->GetMapObjectByPos(MAPOBJECTS::TOWN, $this->victoryCond['coor']);
				$this->victoryInfo = 'Transport '.$this->GetArtifactById($this->victoryCond['art'])
					.' to town '.$name.' at '.$this->victoryCond['coor']->GetCoords();
				break;
		}

		switch($this->lossCond['type']) {
			case LOSS::TOWN:
				$name = $this->GetMapObjectByPos(MAPOBJECTS::TOWN, $this->lossCond['coor']);
				$this->lossInfo = 'Lose town '.$name.' at '.$this->lossCond['coor']->GetCoords();
				break;
			case LOSS::HERO:
				$name = $this->GetMapObjectByPos(MAPOBJECTS::HERO, $this->lossCond['coor']);
				$this->lossInfo = 'Loose hero '.$name.' at '.$this->lossCond['coor']->GetCoords();
				break;
		}

	}

	public function PrintStack($creatures) {
		$out = '';
		foreach($creatures as $k => $mon) {
			if($k > 0) {
				$out .= '<br />';
			}
			$out .= $this->GetCreatureById($mon['id']).': '.comma($mon['count']);
		}
		return $out;
	}

	public function SetAuthor($author) {
		$this->author = $author;
	}

	private function GetVersionName() {
		switch($this->version) {
			case $this::ROE:  $this->versionname = 'ROE';  break;
			case $this::AB:   $this->versionname = 'AB';   break;
			case $this::SOD:  $this->versionname = 'SOD';  break;
			case $this::WOG:  $this->versionname = 'WOG';  break;
			case $this::HOTA: $this->versionname = 'HOTA'; break;
			default:          $this->versionname = '?';    break;
		}

		//apparently some older HOTA maps still use SOD version number, but have bigger size than SOD version can
		//so for map reading purpose it's SOD, but actually playable only as HOTA
		//disabled/altered for now, as it seems H3 HD mode can play SOD maps with bigger size
		/*if($this->map_size > 144 && $this->version != $this::HOTA) {
			$this->versionname = 'SOD (HD mod, VCMI)';
		}*/
	}

	private function GetMapSize() {
		switch($this->map_size) {
			case 36:  $this->map_sizename = 'S';  break;
			case 72:  $this->map_sizename = 'M';  break;
			case 108: $this->map_sizename = 'L';  break;
			case 144: $this->map_sizename = 'XL'; break;
			case 180: $this->map_sizename = 'H';  break;
			case 216: $this->map_sizename = 'XH'; break;
			case 252: $this->map_sizename = 'G';  break;
			default:  $this->map_sizename = '?';  break;
		}
	}

	private function GetDifficulty() {
		switch($this->map_diff) {
			case 0:  $this->map_diffname = 'Easy';       break;
			case 1:  $this->map_diffname = 'Normal';     break;
			case 2:  $this->map_diffname = 'Hard';       break;
			case 3:  $this->map_diffname = 'Expert';     break;
			case 4:  $this->map_diffname = 'Impossible'; break;
			default: $this->map_diffname = '?';          break;
		}
	}

	private function GetHall($hallid) {
		switch($hallid) {
			case 0: return 'Town';
			case 1: return 'City';
			case 2: return 'Capitol';
			default: return '?';
		}
	}

	private function GetFort($fort) {
		switch($fort) {
			case 0: return 'Fort';
			case 1: return 'Citadel';
			case 2: return 'Castle';
			default: return '?';
		}
	}

	public function GetBehaviour($aibeh) {
		switch($aibeh) {
			case 0: return 'Random';
			case 1: return 'Warrior';
			case 2: return 'Builder';
			case 3: return 'Explorer';
			default: return '?';
		}
	}

	public function GetLanguage() {
		switch($this->language) {
			case 'en': return 'English';
			case 'cz': return 'Czech';
			case 'ru': return 'Russian';
			case 'cn': return 'Chinese';
			default: return '?';
		}
	}

	public function GetMapVersion() {
		return $this->versionname;
	}

	public function GetMapSubVersion() {
		return $this->subversion;
	}

	public function PlayerColors($playermask, $withtext = false) {
		$colors = '';
		$playermask &= $this->playerMask; //consider only allowed players
		for($i = 0; $i < PLAYERSNUM; $i++) {
			if(($playermask & (1 << $i)) != 0) {
				$colors .= '<span class="color'.($i + 1).'">&nbsp;</span>&nbsp;';
				if($withtext) {
					$colors .= FromArray($i, $this->CS->PlayersColors).'<br />';
				}
			}
		}
		return $colors;
	}

	public function GetPlayerColorById($id, $withcolor = true) {
		$color = '';
		if($withcolor && ($id >= 0 && $id <= 7 || $id == 255)) {
			$color .= '<span class="color'.($id + 1).'">&nbsp;</span>&nbsp;';
		}
		return $color.FromArray($id, $this->CS->PlayersColors);
	}

	private function GetArtifactById($artid) {
		if($this->isHOTA && $artid >= HOTA_ARTIFACTS_IDS) {
			return FromArray($artid, $this->CS->ArtefactsHota);
		}
		return FromArray($artid, $this->CS->Artefacts);
	}

	private function GetArtifactPosById($artid) {
		return FromArray($artid, $this->CS->ArtifactPosition);
	}

	public function GetCreatureById($monid) {
		if($this->isHOTA && $monid >= HOTA_MONSTER_IDS) {
			return FromArray($monid, $this->CS->MonsterHota);
		}
		return FromArray($monid, $this->CS->Monster);
	}

	private function GetMonsterCharacter($charid) {
		return FromArray($charid, $this->CS->monchar);
	}

	public function GetResourceById($id) {
		return FromArray($id, $this->CS->Resources);
	}

	private function GetMineById($id) {
		return FromArray($id, $this->CS->Mines);
	}

	private function GetHeroById($id) {
		return FromArray($id, $this->CS->Heroes);
	}

	public function GetHeroClassByHeroId($id) {
		$classid = FromArray($id, $this->CS->HeroesClass);
		return FromArray($classid, $this->CS->HeroClass);
	}

	public function GetHeroClassById($id) {
		return FromArray($id, $this->CS->HeroClass);
	}

	private function GetTownById($id) {
		return FromArray($id, $this->CS->TownType);
	}

	public function GetBuildingById($id) {
		return FromArray($id, $this->CS->Buildings);
	}

	public function GetObjectById($id) {
		return FromArray($id, $this->CS->Objects);
	}

	private function GetSpellById($id) {
		return FromArray($id, $this->CS->SpellID);
	}

	public function GetPriskillById($id) {
		return FromArray($id, $this->CS->PrimarySkill);
	}

	private function GetSecskillById($id) {
		return FromArray($id, $this->CS->SecondarySkill);
	}

	private function GetSecskillLevelById($id) {
		return FromArray($id, $this->CS->SecSkillLevel);
	}

	public function GetLevelByExp($experience) {
		foreach($this->CS->Experience as $lvl => $exp) {
			if($exp > $experience) {
				return $lvl - 1;
			}
		}
	}

	private function GetMapObjectByPos($mapobjectid, $coords) {
		if($mapobjectid == MAPOBJECTS::TOWN && $coords->x == HNONE) {
			return 'Any';
		}

		foreach($this->mapobjects as $mapobj) {
			if($mapobj['object'] != $mapobjectid) {
				continue;
			}

			if($coords->x == $mapobj['pos']->x && $coords->y == $mapobj['pos']->y && $coords->z == $mapobj['pos']->z) {
				if($mapobjectid == MAPOBJECTS::MONSTER && $mapobj['objid'] != OBJECTS::MONSTER) {
					return $this->GetObjectById($mapobj['objid']);
				}
				return $mapobj['name'];
			}
		}
		return '?';
	}

	public function GetMapObjectByUID($mapobjectid, $uid) {
		foreach($this->mapobjects as $mapobj) {
			if($mapobj['uid'] == $uid) {
				return $mapobj['name'].' at '.$mapobj['pos']->GetCoords();
			}
		}
		return '? '.$uid;
	}

	//check, if map is compressed or not, compressed starts with 1F 8B 08 00 in LE, that's 0x00088B1F
	private function IsGZIP() {
		$file = fopen($this->mapfile, 'rb');

		//get file header to check if it is gzip
		$gzipheader = fread($file, 4);

		//if gzip, last 4 bytes are uncompressed size
		fseek($file, -4, SEEK_END);
		$gzipend = fread($file, 4);
		fclose($file);

		$header = unpack('V', $gzipheader); //ulong 32 bit LE
		$header = end($header);
		if($header == 0x00088b1f || $header == 0x08088b1f) { //possibly also 0x08088b1f
			$this->isGzip = true;
		}
		else {
			$this->isGzip = false;
		}

		//check only when gzip file
		if($this->isGzip) {
			$uncompressedSize = unpack('V', $gzipend);
			$uncompressedSize = end($uncompressedSize);
			//check size, we will presume no map is bigger than 10 MB, bigger size means gzip file is corrupt
			if($uncompressedSize > 10485760) {
				echo 'H3M file ('.$this->mapfile.') seems to be corrupted<br />';
				$this->filebad = true;
			}
		}
		return $this->isGzip;
	}

	/*private function UnGZIP() {
		$this->mapdata = gzdecode(file_get_contents($this->mapfile));
		return;
	}*/
	
	private function ReadString() {
		return $this->skipstrings ? $this->br->ReadString() : $this->LangConvert($this->br->ReadString());
	}

	private function LangConvert($text) {
		if($this->language == null) {
			$this->GuessLanguage($text);
		}

		switch ($this->language) {
			case 'pl':
			case 'cz': return @iconv('WINDOWS-1250', 'UTF-8', $text); //middle/eastern europe
			case 'ru': return @iconv('WINDOWS-1251', 'UTF-8', $text); //russian
			case 'cn': return @iconv('GB2312', 'UTF-8', $text); //chinese
			case 'en':
			default: return @iconv('WINDOWS-1252', 'UTF-8', $text);
		}
	}

	private function GuessLanguage($text) {
		$langpatterns = [
			//chinese
			'cn' => [
				chr(0xb7).chr(0xfc), chr(0xb5).chr(0xd8), chr(0xc4).chr(0xa7), chr(0xbe).chr(0xed), chr(0xcd).chr(0xc1), chr(0xd6).chr(0xd8), chr(0xc0).chr(0xb4),
				chr(0xa3).chr(0xac), chr(0xba).chr(0xda), chr(0xca).chr(0xd6), chr(0xd2).chr(0xd1), chr(0xbe).chr(0xad), chr(0xc9).chr(0xec), chr(0xcf).chr(0xf2),
				chr(0xc1).chr(0xcb), chr(0xbb).chr(0xf4), chr(0xb8).chr(0xf1), chr(0xce).chr(0xd6), chr(0xb4).chr(0xc4), chr(0xa1).chr(0xa3), chr(0xb9).chr(0xfe),
				chr(0xc0).chr(0xfb), chr(0xa1).chr(0xa4), chr(0xb2).chr(0xa8), chr(0xcc).chr(0xd8), chr(0xa3).chr(0xac), chr(0xc3).chr(0xfc), chr(0xb6).chr(0xa8),
				chr(0xb5).chr(0xc4), chr(0xb7).chr(0xb4), chr(0xbf).chr(0xb9), chr(0xd6).chr(0xae), chr(0xc8).chr(0xcb), chr(0xa3).chr(0xac), chr(0xbc).chr(0xb4),
				chr(0xbd).chr(0xab), chr(0xcc).chr(0xa4), chr(0xc9).chr(0xcf), chr(0xcb).chr(0xfb), chr(0xd2).chr(0xbb), chr(0xb8).chr(0xf6), chr(0xc8).chr(0xcb),
				chr(0xb5).chr(0xc4), chr(0xd5).chr(0xf7), chr(0xb3).chr(0xcc), chr(0xa1).chr(0xa3), chr(0xc7).chr(0xb0), chr(0xcd).chr(0xbe), chr(0xc2).chr(0xfe),
				chr(0xc2).chr(0xfe), chr(0xa3).chr(0xac), chr(0xca).chr(0xc7), chr(0xb8).chr(0xf6), chr(0xc8).chr(0xcb), chr(0xbe).chr(0xcd), chr(0xbb).chr(0xe1),
				chr(0xc3).chr(0xd4), chr(0xc3).chr(0xa3), chr(0xb5).chr(0xf8), chr(0xb5).chr(0xb9), chr(0xc1).chr(0xcb), chr(0xb1).chr(0xf0), chr(0xba).chr(0xa6),
				chr(0xc5).chr(0xc2), chr(0xa3).chr(0xac), chr(0xc5).chr(0xc4), chr(0xc5).chr(0xc4), chr(0xcd).chr(0xc1), chr(0xd5).chr(0xbe), chr(0xc6).chr(0xf0)
			],
			//russian
			'ru' => [
				chr(0xc0), chr(0xc5), chr(0xc7), chr(0xce), chr(0xd0), chr(0xde), chr(0xdf),
				chr(0xe0), chr(0xe5), chr(0xe7), chr(0xee), chr(0xf0), chr(0xfe), chr(0xff)
			],
			//polish
			'pl' => [
				chr(0xa3), chr(0xa5), chr(0xaf), chr(0xca), chr(0xd1),
				chr(0xb3), chr(0xb9), chr(0xbf), chr(0xea), chr(0xf1)
			],
			//czech
			'cz' => [
				chr(0x8a), chr(0x8e), chr(0xc8), chr(0xc9), chr(0xcc), chr(0xd8), chr(0xd9),
				chr(0x9a), chr(0x9e), chr(0xe8), chr(0xe9), chr(0xec), chr(0xf8), chr(0xf9)
			],
		];

		foreach($langpatterns as $lang => $chars) {
			foreach($chars as $ch) {
				if(strstr($text, $ch) !== false) {
					$this->language = $lang;
					return;
				}
			}
		}

		//default
		$this->language = 'en';
	}

	//print current position
	private function ppos() {
		$this->br->ppos();
	}

	//return current position in line
	private function rpos() {
		return dechex($this->br->pos). ' '.$this->br->pos;
	}

	private function pvar($var) {
		echo ' '.dechex($var). ' '.$var.'<br />';
	}

	private function bvar($var) {
		$bprint = sprintf('%08b', $var & 0xff);
		if($var > 0xff) {
			$bprint = sprintf('%08b', ($var >> 8) & 0xff).' '.$bprint;
		}
		return $bprint;
	}

}


class ObjectTemplate {
	public $id;   //object id
	public $subid; //object subid, e.g. castle type, etc.
	//public $type; //type something, not used here
	//public $printpriority; //ingame only, not used here
	public $animation; //sprite name, used only for debug here
	public $tiles; //object tiles and tilemasks 
}

class MapCell {
	public $surface;      //surface land type
	//skip props we dont use anyway
	//public $surface_type; //land sprite num
	//public $river;        //has river?
	//public $river_type;   //river sprite num
	//public $road;         //has road?
	//public $road_type;    //road sprite num
	//public $mirror;       //sprite mirror
	public $access;       //accessibility
	public $owner;        //is object on tile owned -> owner id
	public $special;      //display some object on map with special color
}

class MapCoords {
	public $x;
	public $y;
	public $z;

	public function __construct($x = COOR_INVALID, $y = COOR_INVALID, $z = COOR_INVALID) {
		$this->x = $x;
		$this->y = $y;
		$this->z = $z;
	}

	public function GetCoords() {
		if($this->x == COOR_INVALID) {
			return '?';
		}
		return '['.$this->x.','.$this->y.','.$this->z.']';
	}
}

class ListObject {
	public $name;
	public $mapcoor;
	public $parent;
	public $owner;
	public $count;
	public $info;
	public $add1; //additional info 1
	public $add2; //additional info 2

	public function __construct($name, $coor, $parent, $owner = OWNERNONE, $count = 0, $info = '', $add1 = null, $add2 = null) {
		$this->name = $name;
		$this->mapcoor = $coor;
		$this->parent = $parent;
		$this->owner = $owner;
		$this->count = $count;
		$this->info = $info;
		$this->add1 = $add1;
		$this->add2 = $add2;
	}
}

function EventSortByDate($a, $b) {
	if($a['first'] > $b['first']) return 1;
	if($a['first'] < $b['first']) return -1;
	if($a['order'] > $b['order']) return 1;
	else -1;
}

function ListSortByName($a, $b) {
	return strcmp($a->name, $b->name);
}

function SortTownsByName($a, $b) {
	return strcmp($a['data']['name'], $b['data']['name']);
}

function SortTownEventsByDate($a, $b) {
	if($a['firstOccurence'] > $b['firstOccurence']) return 1;
	if($a['firstOccurence'] < $b['firstOccurence']) return -1;
	return 0;
	//return strcmp($a['name'], $b['name']);
}

function KeyMasterSort($a, $b) {
	$res = $a['id'] <=> $b['id'];
	return $res ? $res : $a['subid'] <=> $b['subid'];
}

?>
