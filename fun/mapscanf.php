<?php

const H3M_WEBMODE       = 0x0001; //required for printinfo
const H3M_PRINTINFO     = 0x0002; //prints map info, requires webmode
const H3M_BUILDMAP      = 0x0004; //builds map image
const H3M_SAVEMAPDB     = 0x0008; //saves map info to DB
const H3M_EXPORTMAP     = 0x0010; //uncompresses and saves pure h3m file
const H3M_BASICONLY     = 0x0020; //reads only basic info about map, for fast read, when active, wont read and build map image
const H3M_MAPHTMCACHE   = 0x0040; //save printinfo htm file, requires printinfo
const H3M_SPECIALACCESS = 0x0080; //displays some objects on map in different color

class H3MAPSCAN {
	const IMGSIZE = 576;
	const ROE  = 0x0e;
	const AB   = 0x15;
	const SOD  = 0x1c;
	const WOG  = 0x33;
	const HOTA = 0x20;

	const HOTA_SUBREV1 = 1;
	const HOTA_SUBREV3 = 3;

	private $version = '';
	private $versionname = '';
	private $hota_subrev = 0;
	private $map_name = '';
	private $description = '';
	private $author = '';
	private $language = null;
	private $underground = 0;
	private $map_diff = -1;     //difficulty
	private $map_diffname = ''; //difficulty name
	private $hero_any_onmap = 0;
	private $hero_levelcap = 0;
	private $teamscount;
	private $teams = array();
	private $victoryCond = array();
	private $lossCond = array();
	private $victoryInfo = '';
	private $lossInfo = '';
	private $playerMask = 0; //allowed players on map

	private $rumorsCount = 0;
	private $rumors = array();
	private $events = array();

	private $allowedArtifacts = array();
	private $disabledArtifacts = array();
	private $allowedSpells = array();
	private $disabledSpellsId = array();
	private $disabledSpells = array();
	private $allowedSkills = array();
	private $disabledSkills = array();

	private $objTemplatesNum = 0;
	private $objTemplates = array();
	private $objectsNum = 0;
	private $objects = array();
	private $objects_unique = array();

	private $freeHeroes = array();
	private $disabledHeroes = array();
	private $customHeroes = array();
	private $heroesPredefined = array();

	//HOTA extras
	private $hota_arena = 0;
	private $monplague_week = 0;
	private $combat_round_limit = 0;

	//object of interest lists
	private $artifacts_list = array();
	private $heroes_list = array();
	private $towns_list = array();
	private $mines_list = array();
	private $monsters_list = array();
	private $event_list = array(); //global events
	private $quest_list = array();
	private $events_list = array(); //map event, pandora, town event
	private $messages_list = array(); //signs and bottles

	//curent object being read and its coords
	private $curobj;
	private $curcoor;
	private $curowner;

	private $mapobjects = array(); //heroes, towns and monsters

	private $map_size = 0;
	private $map_sizename = '';
	private $terrain = array();
	private $terrainRate = array();

	private $name = '';

	private $isGzip;
	private $mapdata = '';
	private $mapfile = '';
	private $mapfilename = '';
	private $mapfileout = '';
	private $mapimage; //mapfile name for DB
	private $mapfileinfo;
	private $md5hash = '';

	private $players = array();
	private $mapplayersnum = 0;
	private $mapplayershuman = 0;
	private $mapplayersai = 0;

	private $CS; //heroes constants class
	private $SC; //String Convert

	//mode switches
	private $webmode = false; //webmode, when not in webmode, print info and build map will be skipped
	private $printoutput = false; //print info
	private $buildMapImage = false; //build map
	private $save = false; //save maps to db
	private $exportmap = false; //uncompress gzip and save pure h3m file
	private $basiconly = false; //read only basic info map, wont make map nor object info
	private $special_access = false; //draw special tiles on map image
	private $maphtmcache = false; //cache htm printinfo

	private $debug;

	private $pos = 0;
	private $length = 0;

	private $filemtime = '';
	private $filectime = '';
	private $filesizeC = 0;
	private $filesizeU = 0;
	private $filebad = false;
	public $readok = false;


	public function __construct($mapfile, $modes, $mapdata = null) {
		$this->webmode        = ($modes & H3M_WEBMODE);
		$this->printoutput    = ($modes & H3M_PRINTINFO);
		$this->buildMapImage  = ($modes & H3M_BUILDMAP);
		$this->save           = ($modes & H3M_SAVEMAPDB);
		$this->exportmap      = ($modes & H3M_EXPORTMAP);
		$this->basiconly      = ($modes & H3M_BASICONLY);
		$this->maphtmcache    = ($modes & H3M_MAPHTMCACHE);
		$this->special_access = ($modes & H3M_SPECIALACCESS);

		if($mapdata != null) {
			$this->mapfile = $mapfile;
			$this->mapdata = $mapdata;
			$this->length = strlen($this->mapdata);
			return;
		}

		$this->mapfile = $mapfile;
		$path = pathinfo($this->mapfile);
		$this->mapfileinfo = $path;

		$this->mapfileout = MAPDIREXP.$path['filename'].'.'.$path['extension'];

		$h3mfile_exists = file_exists($this->mapfile); //original compressed map
		$h3mfileun_exists = file_exists($this->mapfileout); //uncompressed map

		//map is alrady uncompressed
		if($h3mfile_exists && $this->IsGZIP() == false) {
			$this->mapfileout = $this->mapfile;
		}
		else {
			$this->md5hash = md5(file_get_contents($this->mapfile));
			if(!$h3mfile_exists) {
				echo $this->mapfile.' does not exists!'.ENVE;
				$this->filebad = true;
				return;
			}
		}

		$this->mapfilename = $path['filename'];

		if(!$h3mfileun_exists || filemtime($this->mapfileout) < filemtime($this->mapfile)) {
			if(!$this->filebad) {
				if($this->isGzip) {
					$this->mapdata = gzdecode(file_get_contents($this->mapfile));
					//$this->Ungzip();
				}
				else {
					$this->mapdata = file_get_contents($this->mapfileout);
				}
			}
			if($this->mapdata === '') {
				echo $this->mapfile.' could not be uncompressed'.ENVE;
				return;
			}
			if($this->exportmap) {
				//if want to save some unzipping, we can write it so nexttime its not unzipped
				file_write($this->mapfileout, $this->mapdata);
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

		//$this->mapdata = file_get_contents($this->mapfileout); //we got mapdata from unzip
		$this->length = strlen($this->mapdata);
		$this->filesizeU = $this->length;

		$this->mapfile = $path['basename']; //cut folder path, no needed from here
	}

	private function SaveMap() {
		$mapfile = mes($this->mapfile);

		$sql = "SELECT m.mapfile FROM heroes3_maps AS m WHERE m.mapfile='$mapfile' AND md5='".$this->md5hash."'";
		$mapdb = mgr($sql);
		if($mapdb) {
			return;
		}

		$this->mapimage = sanity_string($this->mapfilename);

		$mapdir = mes($this->mapfileinfo['dirname']);
		$mapname = mes($this->map_name);
		$mapdesc = mes($this->description);
		$mapimage = mes($this->mapimage);

		$sql = "INSERT INTO heroes3_maps (`mapfile`, `mapdir`, `mapname`, `author`, `language`, `mapdesc`, `version`, `size`, `sizename`, `levels`, `diff`, `diffname`,
			`playersnum`, `playhuman`, `playai`, `teamnum`, `victory`, `loss`, `filecreate`, `filechanged`, `filesizeC`, `filesizeU`,
			`mapimage`, `md5`) VALUES
			('$mapfile', '$mapdir/', '$mapname', '".$this->author."', '".$this->language."', '$mapdesc', '".$this->versionname."', ".$this->map_size.", '".$this->map_sizename."',
				".$this->underground.", '".$this->map_diff."', '".$this->map_diffname."', ".$this->mapplayersnum.", ".$this->mapplayershuman.", ".$this->mapplayersai.",
				".$this->teamscount.", ".$this->victoryCond['type'].", ".$this->lossCond['type'].",
			FROM_UNIXTIME(".$this->filectime."), FROM_UNIXTIME(".$this->filemtime."), ".$this->filesizeC.", ".$this->filesizeU.", '".$mapimage."', '".$this->md5hash."')";
		mq($sql);
	}

	public function MapHeaderInfo() {
		//$this->ParseFinish();
		$subrev = ($this->version == $this::HOTA) ? ' '.$this->hota_subrev : '';
		$headerInfo = array(
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
		);
		return $headerInfo;
	}

	public function PrintMapInfo() {
		$this->ParseFinish();

		$subrev = ($this->version == $this::HOTA) ? ' '.$this->hota_subrev : '';

		$print = '';

		$print .= '<table>
				<tr><td>File</td><td>'.$this->mapfile.'</td></tr>
				<tr><td>Name</td><td>'.$this->map_name.'</td></tr>
				<tr><td>Description</td><td>'.nl2br($this->description).'</td></tr>
				<tr><td>Version</td><td>'.$this->versionname.$subrev.'</td></tr>
				<tr><td>Size</td><td>'.$this->map_sizename.'</td></tr>
				<tr><td>Levels</td><td>'.($this->underground ? 2 : 1).'</td></tr>
				<tr><td>Difficulty</td><td>'.$this->map_diffname.'</td></tr>
				<tr><td>Victory</td><td>'.$this->victoryInfo.'</td></tr>
				<tr><td>Loss</td><td>'.$this->lossInfo.'</td></tr>
				<tr><td>Players count</td><td>'.$this->mapplayersnum.', '.$this->mapplayershuman.'/'.$this->mapplayersai.'</td></tr>
				<tr><td>Team count</td><td>'.$this->teamscount.'</td></tr>
				<tr><td>Heroes level cap</td><td>'.$this->hero_levelcap.'</td></tr>
				<tr><td>Language</td><td>'.$this->GetLanguage().'</td></tr>
			</table>';

		$print .= '<table class="smalltable">
				<tr>
					<th>#</th>
					<th>Colour</th>
					<th>Human</th>
					<th>AI</th>
					<th>Behaviour</th>
					<th>Team</th>
					<th>Town count</th>
					<th>Owned towns</th>
					<th>Random town</th>
					<th>Main town</th>
					<th>Hero at Main</th>
					<th>Generate hero</th>
					<th>Town coords</th>
					<th>Random Hero</th>
					<th>Main hero</th>
					<th>Heroes count</th>
					<th>Heroes ids</th>
					<th>Heroes names</th>
				</tr>';


		foreach($this->players as $k => $player) {
			$print .= '<tr>
					<td class="ac">'.($k + 1).'</td>
					<td>'.$this->GetPlayerColorById($k).'</td>
					<td class="ac">'.$player['human'].'</td>
					<td class="ac">'.$player['ai'].'</td>
					<td>'.$this->GetBehaviour($player['behaviour']).'</td>
					<td class="ac">'.$this->teams[$k].'</td>
					<td class="ar">'.$player['townsOwned'].'</td>
					<td>'.$player['towns_allowed'].'</td>
					<td class="ac">'.$player['IsRandomTown'].'</td>
					<td class="ac">'.$player['HasMainTown'].'</td>
					<td class="ac">'.$player['HeroAtMain'].'</td>
					<td class="ac">'.$player['GenerateHero'].'</td>
					<td>'.$player['townpos']->GetCoords().'</td>
					<td class="ac">'.$player['RandomHero'].'</td>
					<td>'.$player['MainHeroName'].'</td>
					<td class="ar">'.$player['HeroCount'].'</td>
					<td>'.implode($player['HeroFace'], ', ').'</td>
					<td>'.implode($player['HeroName'], ', ').'</td>
				</tr>';
		}
		$print .= '</table>';


		$this->BuildMap();
		$print .= $this->DisplayMap();

		//terrain percentage
		$totalsize1 = $this->map_size * $this->map_size;
		$totalsize2 = $totalsize1 * ($this->underground + 1);

		$print .= '<table><tr>';
		for ($i = 0; $i < 3; $i++) {
			$totalsize = $i == 2 ? $totalsize2 : $totalsize1;
			if($i == 0) {
				$title = 'Ground';
			}
			elseif($i == 1) {
				$title = 'Underground';
			}
			else {
				$title = 'Both';
			}

			$n = 0;
			arsort($this->terrainRate[$i]);
			$print .= '<td>'.$title.'
				<table class="smalltable">
					<tr><th>#</th><th>Terrain</th><th>Percentage</th></tr>';
			foreach($this->terrainRate[$i] as $terrain => $ratio) {
				$print .= '<tr>
					<td class="ac">'.(++$n).'</td>
					<td>'.$this->CS->TerrainType[$terrain].'</td>
					<td class="ar">'.comma(100 * $ratio / $totalsize, 1).' %</td>
				</tr>';
			}
			$print .= '</table></td>';
		}
		$print .= '</tr></table>';


		//disabled heroes
		$n = 0;
		$print .= '
			<table class="smalltable">
				<tr><th>#</th><th colspan="2">Unavailable heroes</th></tr>';
		foreach($this->disabledHeroes as $class => $heroes) {
			$print .= '<tr>
				<td class="ac">'.(++$n).'</td>
				<td>'.$class.'</td>
				<td>'.implode($heroes, ', ').'</td>
			</tr>';
		}
		$print .= '</table>';

		$print .= '
			<table class="smalltable">
				<tr>
					<th>Custom heroes</th>
					<th>Name (Defname)</th>
					<th>Players</th>
					<th>Exp</th>
					<th>Sex</th>
					<th>Bio</th>
					<th>Primary</th>
					<th>Skills</th>
					<th>Spells</th>
					<th>Artifact</th>
				</tr>';
		foreach($this->heroesPredefined as $k => $hero) {
			if($hero['mask'] == 0) {
				//continue;
			}
			$playermask = $this->playerMask & $hero['mask'];

			$skills = array();
			foreach($hero['skills'] as $skill) {
				$skills[] = $skill[0].': '.$skill[1];
			}

			$print .= '<tr>
				<td class="ac">'.($k+1).'</td>
				<td>'.$hero['name'].'<br />('.$hero['defname'].')</td>
				<td>'.$this->PlayerColors($playermask).'</td>
				<td class="ar">'.comma($hero['exp']).'</td>
				<td class="ac">'.$hero['sex'].'</td>
				<td>'.nl2br($hero['bio']).'</td>
				<td>'.implode($hero['priskills'], ', ').'</td>
				<td>'.implode($skills, '<br />').'</td>
				<td>'.implode($hero['spells'], ', ').'</td>
				<td>'.implode($hero['artifacts'], '<br />').'</td>
			</tr>';
		}
		$print .= '</table>';


		sort($this->disabledArtifacts);
		$print .= '
			<table class="smalltable">
				<tr><th>#</th><th>Disabled Artifacts</th></tr>';
		foreach($this->disabledArtifacts as $k => $art) {
			$print .= '<tr>
				<td class="ac">'.($k+1).'</td>
				<td>'.$art.'</td>
			</tr>';
		}
		$print .= '</table>';

		sort($this->disabledSpells);
		$print .= '
			<table class="smalltable">
				<tr><th>#</th><th>Disabled Spells</th></tr>';
		foreach($this->disabledSpells as $k => $spell) {
			$print .= '<tr>
				<td class="ac">'.($k+1).'</td>
				<td>'.$spell.'</td>
			</tr>';
		}
		$print .= '</table>';

		sort($this->disabledSkills);
		$print .= '
			<table class="smalltable">
				<tr><th>#</th><th>Disabled Skills</th></tr>';
		foreach($this->disabledSkills as $k => $spell) {
			$print .= '<tr>
				<td class="ac">'.($k+1).'</td>
				<td>'.$spell.'</td>
			</tr>';
		}
		$print .= '</table>';

		//towns list
		usort($this->towns_list, 'SortTownsByName');
		$n = 0;
		$print .= '
			<table class="smalltable">
				<tr><th>Towns</th><th>Name</th><th>Position</th><th>Owner</th><th>Type</th><th>Events</th><th>Troops</th><th>Spell</th></tr>';
		foreach($this->towns_list as $towno) {
			$town = $towno['data'];
			$print .= '<tr>
				<td class="ac">'.(++$n).'</td>
				<td>'.$town['name'].'</td>
				<td>'.$towno['pos']->GetCoords().'</td>
				<td>'.$town['player'].'</td>
				<td>'.$town['affiliation'].'</td>
				<td class="ar">'.$town['eventsnum'].'</td>
				<td class="colw100">'.$this->PrintStack($town['stack']).'</td>
				<td>'.$town['spells'].'</td>
			</tr>';
		}
		$print .= '</table>';

		//heroes list
		$n = 0;
		$print .= '
			<table class="smalltable">
				<tr><th>Heroes</th><th>Name</th><th>Position</th><th>Owner</th><th>Class</th>
					<th>Exp</th><th>Primary</th><th>Secondary</th><th>Troops</th><th>Artifacts</th></tr>';
		foreach($this->heroes_list as $hero) {
			$color = $hero['data']['prisoner'] ? 'Prisoner' : $this->GetPlayerColorById($hero['data']['PlayerColor']);

			$classid = (int)($hero['data']['subid'] / HEROES_PER_TYPE);
			$class = $this->GetHeroClassById($classid);

			$primary = implode($hero['data']['priskills'], ' ');
			$secondary = '';
			foreach($hero['data']['skills'] as $k => $skill) {
				if($k > 0) {
					$secondary .= '<br />';
				}
				$secondary .= $skill['skill'].': '.$skill['level'];
			}
			$artifacts = implode($hero['data']['artifacts'], '<br />');

			$level = $this->GetLevelByExp($hero['data']['exp']);

			$print .= '<tr>
				<td>'.(++$n).'</td>
				<td>'.$hero['data']['name'].'</td>
				<td>'.$hero['pos']->GetCoords().'</td>
				<td>'.$color.'</td>
				<td>'.$class.'</td>
				<td>'.comma($hero['data']['exp']).'<br />Level '.$level.'</td>
				<td>'.$primary.'</td>
				<td>'.$secondary.'</td>
				<td>'.$this->PrintStack($hero['data']['stack']).'</td>
				<td>'.$artifacts.'</td>
			</tr>';
		}
		$print .= '</table>';


		//artifact list
		usort($this->artifacts_list, 'ListSortByName');
		$n = 0;
		$print .= '
			<table class="smalltable">
				<tr><th>Artifacts</th><th>Name</th><th>Position</th><th>Parent</th></tr>';
		foreach($this->artifacts_list as $art) {
			$print .= '<tr>
				<td class="ac">'.(++$n).'</td>
				<td>'.$art->name.'</td>
				<td>'.$art->mapcoor->GetCoords().'</td>
				<td>'.$art->parent.'</td>
			</tr>';
		}

		$print .= '</table>';
		//mines list
		usort($this->mines_list, 'ListSortByName');
		$n = 0;
		$print .= '
			<table class="smalltable">
				<tr><th>Mines</th><th>Name</th><th>Position</th><th>Owner</th><th>Resources</th></tr>';
		foreach($this->mines_list as $mine) {

			$print .= '<tr>
				<td class="ac">'.(++$n).'</td>
				<td>'.$mine->name.'</td>
				<td>'.$mine->mapcoor->GetCoords().'</td>
				<td>'.$this->GetPlayerColorById($mine->owner).'</td>
				<td>'.$mine->info.'</td>
			</tr>';
		}
		$print .= '</table>';

		//monster list
		usort($this->monsters_list, 'ListSortByName');
		$n = 0;
		$print .= '
			<table class="smalltable">
				<tr><th>Monsters</th><th>Name</th><th>Count</th><th>Position</th><th>Parent</th><th>Treasure</th><th>Info</th></tr>';
		foreach($this->monsters_list as $mon) {
			$print .= '<tr>
				<td class="ac">'.(++$n).'</td>
				<td>'.$mon->name.'</td>
				<td class="ar">'.comma($mon->count).'</td>
				<td>'.$mon->mapcoor->GetCoords().'</td>
				<td>'.$mon->parent.'</td>
				<td>'.$mon->owner.'</td>
				<td>'.$mon->info.'</td>
			</tr>';
		}
		$print .= '</table>';


		//seers huts and quest master
		usort($this->quest_list, 'ListSortByName');
		$n = 0;
		$print .= '
			<table class="smalltable">
				<tr><th>Quest</th><th>Giver</th><th>Position</th><th>Quest</th><th colspan="3">Reward</th></tr>';
		foreach($this->quest_list as $quest) {
			$questtext = $quest->parent;
			if($quest->add1 > 0) {
				$questtext .= $this->GetMapObjectByUID(MAPOBJECTS::NONE, $quest->add1);
			}

			$print .= '<tr>
				<td class="ac">'.(++$n).'</td>
				<td>'.$quest->name.'</td>
				<td>'.$quest->mapcoor->GetCoords().'</td>
				<td>'.$questtext.'</td>
				<td>'.$quest->owner.'</td>
				<td>'.$quest->info.'</td>
				<td>'.$quest->count.'</td>
			</tr>';
		}
		$print .= '</table>';


		//towns events list
		$n = 0;
		$print .= '
			<table class="smalltable">
				<tr><th>Towns</th><th>Name</th><th>Position</th><th>Owner</th><th>Type</th>
					<th>Event #</th><th>Name</th><th>Players</th><th>Human / AI</th><th>First</th><th>Period</th><th>Resources</th>
					<th>Monsters</th><th>Buildings</th><th>Message</th></tr>';
		foreach($this->towns_list as $towno) {
			$town = $towno['data'];

			if($town['eventsnum'] == 0) {
				continue;
			}

			$monlvlprint = false;
			$monIdOffset = 0;
			if($towno['id'] == OBJECTS::RANDOM_TOWN) {
				$monlvlprint = true;
			}
			else {
				$monIdOffset = $towno['subid'] * 14;
			}

			$rows = $town['eventsnum'];

			$print .= '<tr>
				<td class="ac" rowspan="'.$rows.'">'.(++$n).'</td>
				<td rowspan="'.$rows.'">'.$town['name'].'</td>
				<td rowspan="'.$rows.'">'.$towno['pos']->GetCoords().'</td>
				<td rowspan="'.$rows.'">'.$town['player'].'</td>
				<td rowspan="'.$rows.'">'.$town['affiliation'].'</td>';

			usort($town['events'], 'SortTownEventsByDate');
			foreach($town['events'] as $e => $event) {
				if($e > 0) {
					$print .= '<tr>';
				}

				$resources = array();
				foreach($event['res'] as $rid => $amount) {
					$resources[] = $this->GetResourceById($rid).' = '.$amount;
				}

				$monsters = array();
				foreach($event['monsters'] as $lvl => $amount) {
					if($amount > 0) {
						$monname = $monlvlprint ? 'Lvl '.($lvl + 1) : $this->GetCreatureById(2 * $lvl + $monIdOffset);
						$monsters[] = $monname.' = '.$amount;
					}
				}

				$buildings = array();
				foreach($event['buildings'] as $k => $bbyte) {
					for ($i = 0; $i < 8; $i++) {
						if(($bbyte >> $i) & 0x01) {
							$bid = $k * 8 + $i;
							$buildings[] = $this->GetBuildingById($bid);
						}
					}
				}

				$print .= '
						<td class="ac">'.($e + 1).'</td>
						<td>'.$event['name'].'</td>
						<td>'.$this->PlayerColors($event['players']).'</td>
						<td class="ac">'.$event['human'].'/'.$event['computerAffected'].'</td>
						<td class="ac">'.$event['firstOccurence'].'</td>
						<td class="ac">'.$event['nextOccurence'].'</td>
						<td>'.implode($resources, '<br />').'</td>
						<td>'.implode($monsters, '<br />').'</td>
						<td>'.implode($buildings, '<br />').'</td>
						<td>'.nl2br($event['message']).'</td>
					</tr>';
			}

		}
		$print .= '</table>';


		//events, pandora box
		$n = 0;
		$print .= '
			<table class="smalltable">
				<tr><th>#</th><th>Event / Box</th><th>Position</th><th>Available for</th><th>Human/AI</th><th>One visit</th>
					<th>Guards</th><th>Content</th>
					<th style="width: 50%;">Text</th>
				</tr>';
		foreach($this->events_list as $evento) {
			$event = $evento['data'];

			$stack = '';
			$msg = '';
			if(!empty($event['MessageStack'])) {
				$msg = nl2br($event['MessageStack']['message']);
				if(array_key_exists('stack', $event['MessageStack'])) {
					$stack = $this->PrintStack($event['MessageStack']['stack']);
				}
			}

			$content = array();
			if($event['gainedExp'] > 0) {
				$content[] = 'Experience = '.$event['gainedExp'];
			}
			if($event['manaDiff'] != 0) {
				$content[] = 'Mana = '.$event['manaDiff'];
			}
			if($event['moraleDiff'] != 0) {
				$content[] = 'Morale = '.$event['moraleDiff'];
			}
			if($event['luckDiff'] != 0) {
				$content[] = 'Luck = '.$event['luckDiff'];
			}
			foreach($event['resources'] as $rid => $amount) {
				$content[] = $this->GetResourceById($rid).' = '.$amount;
			}
			foreach($event['priSkill'] as $k => $ps) {
				if($ps > 0) {
					$content[] = $this->GetPriskillById($k).' = '.$ps;
				}
			}
			foreach($event['secSkill'] as $skill) {
				$content[] = $skill['skill'].' = '.$skill['level'];
			}
			if(!empty($event['artifacts'])) {
				$content[] = 'Artifacts: '.implode($event['artifacts'], ', ');
			}
			if(!empty($event['spells'])) {
				$content[] = 'Spells: '.implode($event['spells'], ', ');
			}
			if(array_key_exists('stack', $event)) {
				$content[] = $this->PrintStack($event['stack']);
			}


			$print .= '<tr>
				<td class="ac">'.(++$n).'</td>
				<td>'.$evento['objname'].'</td>
				<td>'.$evento['pos']->GetCoords().'</td>
				<td>'.$this->PlayerColors($event['availableFor']).'</td>
				<td class="ac">'.$event['humanActivate'].'/'.$event['computerActivate'].'</td>
				<td class="ac">'.$event['removeAfterVisit'].'</td>
				<td>'.$stack.'</td>
				<td>'.implode($content, '<br />').'</td>
				<td>'.$msg.'</td>
			</tr>';
		}
		$print .= '</table>';

		//signs and bottles
		$n = 0;
		$print .= '
			<table class="smalltable">
				<tr><th colspan="2">Signs and bottles</th><th>Position</th><th>Text</th></tr>';
		foreach($this->messages_list as $msg) {
			$print .= '<tr>
				<td class="ac">'.(++$n).'</td>
				<td>'.$msg['objname'].'</td>
				<td>'.$msg['pos']->GetCoords().'</td>
				<td>'.nl2br($msg['data']['text']).'</td>
			</tr>';
		}
		$print .= '</table>';

		//rumors
		$print .= '
			<table class="smalltable">
				<tr><th colspan="3">Rumors</th></tr>';
		if(empty($this->rumors)) {
			$print .= '<tr><td colspan="3">None</td></tr>';
		}

		foreach($this->rumors as $k => $rumor) {
			$print .= '<tr>
				<td class="ac">'.($k+1).'</td>
				<td>'.$rumor['name'].'</td>
				<td>'.nl2br($rumor['desc']).'</td>
			</tr>';
		}
		$print .= '</table>';


		//day events
		usort($this->events, 'EventSortByDate');
		$print .= '
			<table class="smalltable">
				<tr><th>Events Date</th><th>Name</th><th>Human</th><th>AI</th><th>Players</th><th>First</th><th>Interval</th>
					<th>Resources</th><th>Message</th></tr>';
		foreach($this->events as $k => $event) {
			$eres = array();
			foreach($event['resources'] as $r => $res) {
				if($res != 0) {
					$eres[] = $this->GetResourceById($r).' '.$res;
				}
			}

			$print .= '<tr>
				<td class="ac">'.($k+1).'</td>
				<td>'.$event['name'].'</td>
				<td class="ac">'.$event['humanAble'].'</td>
				<td class="ac">'.$event['aiAble'].'</td>
				<td>'.$this->PlayerColors($event['players'], true).'</td>
				<td class="ar">'.$event['first'].'</td>
				<td class="ar">'.$event['interval'].'</td>
				<td>'.implode($eres, ',').'</td>
				<td>'.nl2br($event['message']).'</td>
			</tr>';
		}
		$print .= '</table>';


		$print .= '<br />Templates count: '.$this->objTemplatesNum.'<br />';
		/*
		$print .= '<table>';
		foreach($this->objTemplates as $temp) {
			$print .= '<tr><td>ID:'.$temp['id'].', SubID:'.$temp['subid'].'<td>'.$temp['animation'].'</td><td>'.nl2br($temp['mask']).'</td></tr>';
		}
		$print .= '</table>';
		*/

		$print .= 'Objects type count: '.count($this->objects_unique).'<br />';
		$print .= 'Objects total count: '.$this->objectsNum.'<br />';

		asort($this->objects_unique);
		$n = 0;
		$print .= '
			<table class="smalltable">
				<tr><th>Objects</th><th>ID</th><th>Name</th><th>Count</th></tr>';
		foreach($this->objects_unique as $objid => $obju) {
			$print .= '<tr>
				<td class="ac">'.(++$n).'</td>
				<td>'.$objid.'</td>
				<td>'.$obju['name'].'</td>
				<td class="ar">'.$obju['count'].'</td>
			</tr>';
		}
		$print .= '</table>';

		echo $print;
		if($this->maphtmcache) {
			file_write(MAPDIRINFO.str_ireplace('.h3m', '.htm', $this->mapfile).'.gz', gzencode($print));
		}
	}


	public function ReadMap() {
		if($this->filebad) {
			return;
		}



		$this->pos = 0;
		$this->CS = new HeroesConstants();
		$this->SC = new StringConvert();

		$this->version = $this->ReadUint32();
		if($this->version == $this::HOTA) {
			$this->hota_subrev = $this->ReadUint32();
			if($this->hota_subrev == $this::HOTA_SUBREV1) {
				$this->SkipBytes(2); //unknown
			}
			elseif($this->hota_subrev == $this::HOTA_SUBREV3) {
				$this->hota_arena = $this->ReadUint8();
				$this->SkipBytes(5); //unknown
			}
		}

		if($this->version < $this::ROE) {
			echo 'Invalid version '.$this->version.'. Possibly a campagn file or not a map<br />';
			//rename(MAPDIR.$this->mapfile, 'mapscam/'.str_ireplace('.h3m', '.h3c', $this->mapfile))	;
			return;
		}

		$this->hero_any_onmap = $this->ReadUint8(); //hero presence
		$this->map_size = $this->ReadUint32();
		$this->underground = $this->ReadUint8();
		$this->map_name = $this->ReadString();

		//reset language which was set bases on mapname and let base it on description, which is usually longer
		$this->language = null;

		$this->description = $this->ReadString();
		$this->map_diff = $this->ReadUint8();

		$this->GetVersionName();
		$this->GetMapSize();
		$this->GetDifficulty();

		if($this->version != $this::ROE) {
			$this->hero_levelcap = $this->ReadUint8(); //hero's cap
		}

		$this->ReadPlayersData();

		// Special Victory Condition
		$this->VictoryCondition();
		// Special loss condition
		$this->LossCondition();
		// Teams
		$this->Teams();

		if(!$this->basiconly) {
			// Free Heroes
			$this->FreeHeroes();

			$this->SkipBytes(31); //unused space

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

			//object definitions
			$this->ReadDefInfo();

			//objects
			$this->ReadObjects();

			//global event
			$this->ReadEvents();
		}


		if($this->printoutput && $this->webmode) {
			$this->PrintMapInfo();
		}

		if(!$this->webmode && !$this->basiconly) {
			$this->BuildMap();
		}

		if($this->save){
			$this->SaveMap();
		}

		$this->readok = true;

		/*if(!file_exists('maps_done/'.$this->mapfile)) {
			rename(MAPDIR.$this->mapfile, 'maps_done/'.$this->mapfile);
		}*/
	}

	private function ReadPlayersData() {
		//players
		for($i = 0; $i < PLAYERSNUM; $i++){
			$human = $this->ReadUint8();
			$ai = $this->ReadUint8();

			//nobody can play this colour
			if($human == 0 && $ai == 0) {
				switch($this->version){
					case $this::ROE: $this->SkipBytes(6);  break;
					case $this::AB:  $this->SkipBytes(12); break;
					default:         $this->SkipBytes(13); break;
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
			$this->players[$i]['HeroFace'] = array();
			$this->players[$i]['HeroName'] = array();
			$this->players[$i]['HeroCount'] = 0;
			$this->players[$i]['townsOwned'] = 0;
			$this->players[$i]['placeholder'] = OBJECT_INVALID;

			$this->players[$i]['behaviour'] = $this->ReadUint8();

			if($this->version >= $this::SOD) {
				$this->players[$i]['townOwned_isSet'] = $this->ReadUint8();
			}
			else {
				$this->players[$i]['townOwned_isSet'] = OBJECT_INVALID;
			}

			//allowed towns
			$maxtowns = PLAYERSNUM;
			if($this->version != $this::ROE) {
				$towns = $this->ReadUint16();
				$maxtowns = 10;
			}
			else {
				$towns = $this->ReadUint8();
			}

			$towns_allowed = array();

			if($towns == HNULL) {
				//nothing
			}
			elseif($towns == HNONE || $towns == HNONETOWN) {
				$towns_allowed[] = 'Random Town';
			}
			else {
				for($n = 0; $n < $maxtowns; $n++){
					if(($towns & (1 << $n)) != 0){
						$towns_allowed[] = $this->GetTownById($n);
					}
				}
			}
			$this->players[$i]['towns_allowed'] = implode($towns_allowed, ', ');

			$this->players[$i]['IsRandomTown'] = $this->ReadUint8();
			$this->players[$i]['HasMainTown'] = $this->ReadUint8();

			//def values
			$townpos;

			if($this->players[$i]['HasMainTown']){
				if($this->version != $this::ROE) {
					$this->players[$i]['HeroAtMain'] = $this->ReadUint8();
					$this->players[$i]['GenerateHero'] = dechex($this->ReadUint8());
				}
				$townpos = new MapCoords($this->ReadUint8(), $this->ReadUint8(), $this->ReadUint8());
			}
			else {
				$townpos = new MapCoords();
			}

			$this->players[$i]['townpos'] = $townpos;

			$heronum = 0;
			$this->players[$i]['RandomHero'] = $this->ReadUint8();
			$this->players[$i]['MainHeroType'] = $this->ReadUint8();

			$this->players[$i]['MainHeroName'] = 'Random';

			if($this->players[$i]['MainHeroType'] != HNONE){
				$heroid = $this->ReadUint8();
				$heroname = $this->ReadString();
				if($heroid != HNONE) {
					$this->players[$i]['HeroFace'][] = $heroid;
					$this->players[$i]['HeroName'][] = $heroname;
				}
				$this->players[$i]['MainHeroName'] = $this->GetHeroById($heroid);
			}

			if($this->version != $this::ROE) {
				$this->players[$i]['placeholder'] = $this->ReadUint8(); //placeholder

				$herocount = $this->ReadUint8();
				$this->players[$i]['HeroCount'] = $herocount;

				$this->SkipBytes(3);
				for($j = 0; $j < $herocount; $j++) {
					$heroid = $this->ReadUint8();
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

	private function FreeHeroes(){
		$heroes = 0;
		$limit = HEROES_QUANTITY;

		switch($this->version){
			case $this::ROE: $heroes = 16;	break;
			case $this::AB:
			case $this::SOD:
			case $this::WOG: $heroes = 20; break;
			case $this::HOTA: $heroes = 23; $limit = HEROES_QUANTITY_HOTA; break;
		}

		if($this->version == $this::HOTA) {
			$limit = $this->ReadUint32(); //hero count
		}

		for($i = 0; $i < $heroes; $i++) {
			$byte = $this->ReadUint8();

			for($n = 0; $n < 8; $n++) {
				$idh = $i * 8 + $n; //hero id
				if($idh >= $limit) {
					break;
				}
				if(($byte & (1 << $n)) == 0) {
					$this->disabledHeroes[$this->GetHeroClassById($i)][] = $this->GetHeroById($idh);
				}
			}
		}

		if($this->version > $this::ROE) {
			$placeholders = $this->ReadUint32(); //no use
			for ($i = 0; $i < $placeholders; $i++) {
				$hero['id'] = $this->ReadUint8();
				$hero['face'] = 0;
				$hero['name'] = $this->GetHeroById($hero['id']);
				$hero['mask'] = 0;
				$this->customHeroes[$hero['id']] = $hero;
			}

		}

		if($this->version >= $this::SOD) {
			//custom heroes, changed in editor
			$heroCustomCount = $this->ReadUint8();

			for($i = 0; $i < $heroCustomCount; $i++) {
				$hero['id'] = $this->ReadUint8();
				$hero['face'] = $this->ReadUint8();  //picture
				$hero['name'] = $this->Readstring();
				$hero['mask'] = $this->ReadUint8();  //player availability
				$this->customHeroes[$hero['id']] = $hero;
			}
		}
	}

	private function HotaMapExtra() {
		if($this->version != $this::HOTA) {
			return;
		}
		$this->monplague_week = $this->ReadUint8();
		$this->SkipBytes(3); //unknown

		if($this->hota_subrev >= $this::HOTA_SUBREV1) {
			$this->SkipBytes(6);
			if($this->hota_subrev >= $this::HOTA_SUBREV3) {
				$this->combat_round_limit = $this->ReadUint32();
			}
		}
	}

	private function Artifacts() {
		// Reading allowed artifacts:	17 or 18 bytes, or X for HOTA
		//1=disabled, 0=enabled
		if($this->version != $this::ROE) {
			$bytes = $this->version == $this::AB ? 17 : 18;
			if($this->version == $this::HOTA) {
				$artcount = $this->ReadUint32(); //artifact id count
				$bytes = ceil($artcount / 8); //21
			}

			for($i = 0; $i < $bytes; $i++) {
				$byte = $this->ReadUint8(); //ids of artifacts

				for($n = 0; $n < 8; $n++) {
					if(($byte & (1 << $n)) != 0) {
						$ida = $i * 8 + $n;
						if($this->version != $this::WOG && $ida > 140) {
							break;
						}
						$this->disabledArtifacts[] = $this->GetArtifactById($ida).' '.$ida;
					}
				}
			}
		}
	}

	private function AllowedSpellsAbilities(){
		if($this->version >= $this::SOD) {
			// Reading allowed spells (9 bytes)
			for($i = 0; $i < SPELL_BYTE; $i++) {
				$byte = $this->ReadUint8(); //ids of spells
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
				$byte = $this->ReadUint8(); //ids of skills
				for($n = 0; $n < 8; $n++) {
					if(($byte & (1 << $n)) != 0) {
						$this->disabledSkills[] = $this->GetSecskillById($i * 8 + $n);
					}
				}
			}
		}
	}

	private function Rumors(){
		$this->rumorsCount = $this->ReadUint32();
		if($this->rumorsCount){
			for($i = 0; $i < $this->rumorsCount; $i++){
				$rumor['name'] = $this->ReadString();
				$rumor['desc'] = $this->ReadString();
				$this->rumors[] = $rumor;
			}
		}
	}

	private function VictoryCondition(){
		// 1	Special Victory Condition:
		$this->victoryCond['type'] = $this->ReadUint8();
		if($this->victoryCond['type'] == VICTORY::NONE) {
			$this->victoryCond['name'] = 'None';
			$this->victoryInfo = 'Defeat all players';
			return;
		}

		$this->victoryCond['Normal_end'] = $this->ReadUint8();
		$this->victoryCond['AI_cancomplete'] = $this->ReadUint8();

		switch($this->victoryCond['type']){
			case VICTORY::NONE: break; // not
			case VICTORY::ARTIFACT: // 00 - Acquire a specific artifact
				$this->victoryCond['name'] = 'Acquire a specific artifact';
				$this->victoryCond['art'] = $this->ReadUint8();
				$this->victoryInfo = 'Acquire a specific artifact '.$this->GetArtifactById($this->victoryCond['art']);
				if($this->version != $this::ROE) {
					$this->SkipBytes(1);
				}
				break;
			case VICTORY::ACCCREATURES: // 01 - Accumulate creatures
				$this->victoryCond['name'] = 'Accumulate creatures';
				$monid = $this->ReadUint8();
				$this->victoryCond['unit'] = $this->GetCreatureById($monid);
				if($this->version != $this::ROE) {
					$this->SkipBytes(1);
				}
				$this->victoryCond['unit_count'] = $this->ReadUint32();
				$this->victoryInfo = 'Accumulate creatures, '.$this->victoryCond['unit'].', count '.comma($this->victoryCond['unit_count']);
				break;
			case VICTORY::ACCRESOURCES: // 02 - Accumulate resources
				$this->victoryCond['name'] = 'Accumulate resources';
				$this->victoryCond['resource'] = $this->ReadUint8();
				// 0 - Wood	 1 - Mercury	2 - Ore	3 - Sulfur	4 - Crystal	5 - Gems	6 - Gold
				$this->victoryCond['resource_count'] = $this->ReadUint32();
				$this->victoryInfo = 'Accumulate resources: '.$this->GetResourceById($this->victoryCond['resource'])
					.', count: '.comma($this->victoryCond['resource_count']);
				break;
			case VICTORY::UPGRADETOWN: // 03 - Upgrade a specific town
				$this->victoryCond['name'] = 'Upgrade a specific town';
				$this->victoryCond['coor'] = new MapCoords($this->ReadUint8(), $this->ReadUint8(), $this->ReadUint8());
				$this->victoryCond['hall_lvl'] = $this->GetHall($this->ReadUint8());
				// Hall Level:	 0-Town, 1-City,	2-Capitol
				$this->victoryCond['castle_lvl'] = $this->GetFort($this->ReadUint8());
				// Castle Level: 0-Fort, 1-Citadel, 2-Castle

				$this->victoryInfo = 'Upgrade a specific town at '.$this->victoryCond['coor']->GetCoords()
					.' to '.$this->victoryCond['hall_lvl'].' and '.$this->victoryCond['castle_lvl'];
				break;
			case VICTORY::BUILDGRAIL: // 04 - Build the grail structure
				$this->victoryCond['name'] = 'Build the grail structure';
				$this->victoryCond['coor'] = new MapCoords($this->ReadUint8(), $this->ReadUint8(), $this->ReadUint8());
				$this->victoryInfo = 'Build the grail structure at town '.$this->victoryCond['coor']->GetCoords();
				break;
			case VICTORY::DEFEATHERO: // 05 - Defeat a specific Hero
				$this->victoryCond['name'] = 'Defeat a specific Hero';
				$this->victoryCond['coor'] = new MapCoords($this->ReadUint8(), $this->ReadUint8(), $this->ReadUint8());
				$this->victoryInfo = 'Defeat a specific Hero at '.$this->victoryCond['coor']->GetCoords();
				break;
			case VICTORY::CAPTURETOWN: // 06 - Capture a specific town
				$this->victoryCond['name'] = 'Capture a specific town';
				$this->victoryCond['coor'] = new MapCoords($this->ReadUint8(), $this->ReadUint8(), $this->ReadUint8());
				$this->victoryInfo = 'Capture a specific town at'.$this->victoryCond['coor']->GetCoords();
				break;
			case VICTORY::KILLMONSTER: // 07 - Defeat a specific monster
				$this->victoryCond['name'] = 'Defeat a specific monster';
				$this->victoryCond['coor'] = new MapCoords($this->ReadUint8(), $this->ReadUint8(), $this->ReadUint8());
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
				$this->victoryCond['art'] = $this->ReadUint8();
				$this->victoryCond['coor'] = new MapCoords($this->ReadUint8(), $this->ReadUint8(), $this->ReadUint8());
				$this->victoryInfo = 'Transport '.$this->GetArtifactById($this->victoryCond['art']).' to town at '.$this->victoryCond['coor']->GetCoords();
				break;
			case VICTORY::ELIMINATEMONSTERS: // 0A - Transport a specific artifact
				$this->victoryCond['name'] = 'Eliminate all creatures';
				$this->victoryInfo = 'Eliminate all creatures';
				break;
			case VICTORY::SURVIVETIME: // 0A - Transport a specific artifact
				$this->victoryCond['name'] = 'Survive for certain time';
				$this->victoryCond['days'] = $this->ReadUint32();
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

	private function LossCondition(){
		// 1	Special loss condition
		$this->lossCond['type'] = $this->ReadUint8();
		if($this->lossCond['type'] == LOSS::NONE) {
			$this->lossCond['name'] = 'None';
			$this->lossInfo = 'Loose all towns and heroes';
			return;
		}

		switch($this->lossCond['type']){
			case LOSS::NONE: break; // not
			case LOSS::TOWN: // 00 - Lose a specific town
				$this->lossCond['name'] = 'Lose a specific town';
				$this->lossCond['coor'] = new MapCoords($this->ReadUint8(), $this->ReadUint8(), $this->ReadUint8());
				$this->lossInfo = 'Lose a specific town at '.$this->lossCond['coor']->GetCoords();
				break;
			case LOSS::HERO: // 01 - Lose a specific hero
				$this->lossCond['name'] = 'Lose a specific hero';
				$this->lossCond['coor'] = new MapCoords($this->ReadUint8(), $this->ReadUint8(), $this->ReadUint8());
				$this->lossInfo = 'Lose a specific hero at '.$this->lossCond['coor']->GetCoords();
				break;
			case LOSS::TIME: // 02 - time
				$this->lossCond['name'] = 'Time expires';
				$this->lossCond['time'] = $this->ReadUint16();
				$time = floor($this->lossCond['time'] / 28).' months and '.($this->lossCond['time'] % 28).' days';
				$this->lossInfo = 'Complete in '.$time;
				break;
			default: // ff - not
		}
	}

	private function Teams(){
		$this->teamscount = $this->ReadUint8();
		for($i = 0; $i < PLAYERSNUM; $i++){
			$this->teams[$i] = ($this->teamscount != 0) ? $this->ReadUint8() : 0;
		}
	}

	//another block for changed heroes in editor
	private function ReadPredefinedHeroes() {

		$limit = HEROES_QUANTITY;
		if($this->version == $this::HOTA) {
			$limit = $this->ReadUint32(); //hero count
		}

		switch($this->version) {
			case $this::SOD:
			case $this::WOG:
			case $this::HOTA:
				// Disposed heroes
				for($i = 0; $i < $limit; $i++) {

					//is hero custom, if not, skip to next
					$custom = $this->ReadUint8();
					if(!$custom) {
						continue;
					}

					$hero = array();
					$hero['id'] = $i;
					$hero['name'] = '';
					$hero['mask'] = 0;
					$hero['face'] = 0;

					$hero['defname'] = $this->GetHeroById($i);
					$hero['name'] = $hero['defname'];
					$hero['exp'] = 0;
					$hero['sex'] = '';
					$hero['bio'] = '';
					$hero['priskills'] = array();
					$hero['skills'] = array();
					$hero['spells'] = array();
					$hero['artifacts'] = array();

					if(!empty($this->customHeroes)) {
						$heroc = FromArray($hero['id'], $this->customHeroes, false);
						if(is_array($heroc)) {
							$hero['name'] = $heroc['name'];
							$hero['mask'] = $heroc['mask'];
							$hero['face'] = $heroc['face'];
						}
					}

					$hasExp = $this->ReadUint8();
					if($hasExp) {
						$hero['exp'] = $this->ReadUint32();
					}
					else {
						$heroExp = 0;
					}

					$hasSecSkills = $this->ReadUint8();
					if($hasSecSkills) {
						$howMany = $this->ReadUint32();
						for($j = 0; $j < $howMany; $j++) {
							$secSkills[0] = $this->GetSecskillById($this->ReadUint8());
							$secSkills[1] = $this->GetSecskillLevelById($this->ReadUint8());
							$hero['skills'][] = $secSkills;
						}
					}

					$this->curobj = 'Hero def: '.$hero['name'];
					$this->curcoor = new MapCoords();
					$hero['artifacts'] = $this->LoadArtifactsOfHero();

					$hasCustomBio = $this->ReadUint8();
					if($hasCustomBio) {
						$hero['bio'] = $this->ReadString();
					}

					// 0xFF is default, 00 male, 01 female
					$herosex = $this->ReadUint8();
					$hero['sex'] = $herosex == HNONE ? 'Default' : ($herosex ? 'Female' : 'Male');

					$hasCustomSpells = $this->ReadUint8();
					if($hasCustomSpells) {
						$hero['spells'] = $this->ReadSpells();
					}

					$hasCustomPrimSkills = $this->ReadUint8();
					if($hasCustomPrimSkills) {
						for($j = 0; $j < PRIMARY_SKILLS; $j++) {
							$hero['priskills'][] = $this->ReadUint8();
						}
					}

					$this->heroesPredefined[] = $hero;
				}
				break;
		}
	}

	private function ReadHero() {
		$hero = array();
		$hero['name'] = 'Default';
		$hero['epx'] = 0;
		$hero['uid'] = 0;

		if($this->version > $this::ROE) {
			$hero['uid'] = $this->ReadUint32();
		}

		$hero['PlayerColor'] = $this->ReadUint8();
		$hero['subid'] = $this->ReadUint8();

		$hasName = $this->ReadUint8();
		if($hasName) {
			$hero['name'] = $this->ReadString();
		}
		else {
			$hero['name'] = $this->GetHeroById($hero['subid']);
		}

		$hero['exp'] = 0;
		if($this->version > $this::AB) {
			$hasExp = $this->ReadUint8();
			if($hasExp) {
				$hero['exp'] = $this->ReadUint32();
			}
		}
		else {
			$hero['exp'] = $this->ReadUint32();
		}

		$hasPortrait = $this->ReadUint8();
		if($hasPortrait) {
			$hero['portrait'] = $this->ReadUint8();
		}

		//is hero in prison
		$hero['prisoner'] = ($this->curobj == OBJECTS::PRISON);

		$hero['skills'] = array();

		$hasSecSkills = $this->ReadUint8();
		if($hasSecSkills) {
			$howMany = $this->ReadUint32();
			for($yy = 0; $yy < $howMany; $yy++) {
				$hero['skills'][] = array(
					'skill' => $this->GetSecskillById($this->ReadUint8()),
					'level' => $this->GetSecskillLevelById($this->ReadUint8()),
				);
			}
		}

		$hero['stack'] = array();
		$hasGarison = $this->ReadUint8();
		if($hasGarison) {
			$hero['stack'] = $this->ReadCreatureSet(7);
		}

		$this->curobj = 'Hero: '.$hero['name'];

		$hero['formation'] = $this->ReadUint8();
		$hero['artifacts'] = $this->LoadArtifactsOfHero();

		$hero['patrol'] = $this->ReadUint8();
		if($hero['patrol'] == HNONE) {
			$hero['patrol'] = 0;
		}

		$hero['bio'] = '';
		$hero['sex'] = 'default';
		if($this->version > $this::ROE) {
			$hasCustomBiography = $this->ReadUint8();
			if($hasCustomBiography) {
				$hero['bio'] = $this->ReadString();
			}
			$herosex = $this->ReadUint8();
			$hero['sex'] = $herosex == HNONE ? 'Default' : ($herosex ? 'Female' : 'Male');
		}

		$hero['spells'] = '';

		// Spells
		if($this->version > $this::AB) {
			$hasCustomSpells = $this->ReadUint8();
			if($hasCustomSpells) {
				$hero['spells'] = $this->ReadSpells();
			}
		}
		else if($this->version == $this::AB) {
			//we can read one spell
			$buff = $this->ReadUint8();
		}

		$hero['priskills'] = array();
		if($this->version > $this::AB) {
			$hasCustomPrimSkills = $this->ReadUint8();
			if($hasCustomPrimSkills) {
				for($j = 0; $j < PRIMARY_SKILLS; $j++) {
					$hero['priskills'][] = $this->ReadUint8();
				}
			}
		}
		$this->SkipBytes(16);
		return $hero;
}

	private function LoadArtifactsOfHero() {
		$artifacts = array();

		$artSet = $this->ReadUint8();

		// True if artifact set is not default (hero has some artifacts)
		if($artSet)	{
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
				$this->SkipBytes(1);
			}

			// bag artifacts
			// number of artifacts in hero's bag
			$amount = $this->ReadUint16();
			for($i = 0; $i < $amount; $i++) {
				$this->LoadArtifactToSlot($artifacts, 19); //ArtifactPosition::BACKPACK
			}
		}

		return $artifacts;
	}

	private function LoadArtifactToSlot(&$artifacts, $slot) {
		$artmask = ($this->version == $this::ROE) ? 0xff : 0xffff;
		$artid = OBJECT_INVALID;

		if($this->version == $this::ROE) {
			$artid = $this->ReadUint8();
		}
		else {
			$artid = $this->ReadUint16();
		}

		if($artid != $artmask) {
			$artifact = $this->GetArtifactById($artid);
			$this->artifacts_list[] = new ListObject($artifact, $this->curcoor, $this->curobj);
			$artifacts[] = $this->GetArtifactPosById($slot).': '.$artifact;
		}
	}

	//spell mask
	private function ReadSpells() {
		$spells = array();
		for($i = 0; $i < SPELL_BYTE; $i++) {
			$byte = $this->ReadUint8();
			for($n = 0; $n < 8; $n++) {
				if(($byte & (1 << $n)) != 0) {
					$spells[] = $this->GetSpellById($i * 8 + $n);
				}
			}
		}
		return $spells;
	}

	private function ReadTerrain() {
		//if we dont need build map image, we dont need to read terrain at all
		if(!$this->buildMapImage) {
			$this->SkipBytes($this->map_size * $this->map_size * ($this->underground + 1) * TILEBYTESIZE);
			return;
		}

		$csurfaces = count($this->CS->TerrainType);
		for($i = 0; $i < $csurfaces; $i++) {
			$this->terrainRate[0][$i] = 0; //up
			$this->terrainRate[1][$i] = 0; //down
			$this->terrainRate[2][$i] = 0; //both
		}

		for($z = 0; $z < $this->underground + 1; $z++) {
			for($x = 0; $x < $this->map_size; $x++) {
				for($y = 0; $y < $this->map_size; $y++) {
					$cell = new MapCell();
					$cell->surface = $this->ReadUint8();
					//skip props we dont use anyway in this reader
					$this->SkipBytes(6);
					//$cell->surface_type = $this->ReadUint8();
					//$cell->river = $this->ReadUint8();
					//$cell->river_type = $this->ReadUint8();
					//$cell->road = $this->ReadUint8();
					//$cell->road_type = $this->ReadUint8();
					//$cell->mirror = $this->ReadUint8();
					$cell->access = 0;
					$cell->owner = OWNERNONE;
					$cell->special = MAPSPECIAL::NONE;

					$this->terrain[$z][$x][$y] = $cell;

					if($cell->surface < $csurfaces) {
						$this->terrainRate[$z][$cell->surface]++;
						$this->terrainRate[2][$cell->surface]++;
					}
				}
			}
		}
	}

	public function BuildMap() {

		if($this->buildMapImage) {
			$this->mapimage = sanity_string($this->mapfilename);

			//image path and filenames
			$imgmapnameg = MAPDIRIMG.$this->mapimage.'_g.png'; //ground
			$imgmapnameu = MAPDIRIMG.$this->mapimage.'_u.png'; //underground

			$img = imagecreate($this->map_size, $this->map_size); //map by size
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

			$imgcolors = array(
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
			);

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

	public function DisplayMap() {
		$imgmapnameg = MAPDIRIMG.$this->mapimage.'_g.png';
		$imgmapnameu = MAPDIRIMG.$this->mapimage.'_u.png';

		$mapsizepow = $this->map_size * $this->map_size;
		$output = '<br />Map : size='.$this->map_size.', cells='.$mapsizepow.', bytes='.($mapsizepow * 7).'<br />';

		$imgground = file_exists($imgmapnameg) ? '<img src="'.$imgmapnameg.'" alt="ground" title="ground" />' : 'Map Ground';
		$output .= '<table><tr><td>'.$imgground.'</td>';
		if($this->underground) {
			$imguground = file_exists($imgmapnameu) ? '<img src="'.$imgmapnameu.'" alt="ground" title="ground" />' : 'Map Ground';
			$output .= '<td>'.$imguground.'</td>';
		}
		$output .= '</tr></table>';
		return $output;
	}

	private function ReadDefInfo() {
		$this->objTemplatesNum = $this->ReadUint32();

		// Read custom defs
		for($i = 0; $i < $this->objTemplatesNum; $i++) {
			$objtemp = array();
			$objtemp['animation'] = $this->ReadString();

			$blockMask = array();
			$visitMask = array();
			$usedTiles = array();

			//read tile masks only when building image
			if($this->buildMapImage) {
				//blockMask
				for($j = 0; $j < 6; $j++) {
					$blockMask[] = $this->ReadUint8();
				}
				//visitMask
				for($j = 0; $j < 6; $j++) {
					$visitMask[] = $this->ReadUint8();
				}

				//build object shape
				for ($r = 0; $r < 6; $r++) { // 6 rows y-axis
					for ($c = 0; $c < 8; $c++) { // 8 columns	 x-axis
						$tile = BLOCKMAPBITS::VISIBLE; // assume that all tiles are visible
						if ((($blockMask[$r] >> $c) & 1) == 0) {
							$tile |= BLOCKMAPBITS::BLOCKED;
						}
						if ((($visitMask[$r] >> $c) & 1) != 0) {
							$tile |= BLOCKMAPBITS::VISITABLE;
						}

						$usedTiles[5 - $r][7 - $c] = $tile;
					}
				}
			}
			else {
				$this->SkipBytes(12); //skip masks
			}

			$this->SkipBytes(2); //not sure
			$this->SkipBytes(2); //allowed terrain for object, not needed

			$objtemp['id'] = $this->ReadUint32();
			$objtemp['subid'] = $this->ReadUint32();
			$objtemp['type'] = $this->ReadUint8();
			$objtemp['printpriority'] = $this->ReadUint8();
			$objtemp['tiles'] = $usedTiles;

			$this->SkipBytes(16);

			$this->objTemplates[] = $objtemp;
		}
	}

	private function ReadMessageAndGuards() {
		$mag = array();
		$hasMessage = $this->ReadUint8();
		if($hasMessage) {
			$mag['message'] = $this->ReadString();
			$hasGuards = $this->ReadUint8();
			if($hasGuards) {
				$mag['stack'] = $this->ReadCreatureSet(7);
			}
			$this->SkipBytes(4);
		}
		return $mag;
	}

	private function ReadCreatureSet($number) {
		$version = ($this->version > $this::ROE);
		$maxID = $version ? 0xffff : 0xff;

		$stack = array();

		for($i = 0; $i < $number; $i++) {
			$creatureID = $version ? $this->ReadUint16() : $this->ReadUint8();
			$count = $this->ReadUint16();

			// Empty slot
			if($creatureID == $maxID) {
				continue;
			}
			if($creatureID > $maxID - 0x0f) {
				//this will happen when random object has random army
				$creatureID = ($maxID - $creatureID - 1) + 1000; //arbitrary 1000 for extension of monster ids
			}
			$stack[] = array('id' => $creatureID, 'count' => $count);
		}
		return $stack;
	}

	private function ReadObjects() {

		$this->objectsNum = $this->ReadUint32();

		for($i = 0; $i < $this->objectsNum; $i++) {
			$obj = array();
			$tileowner = OWNERNONE; //player coloured tile
			$special = MAPSPECIAL::NONE; //special object displayed on map
			$saveobject = false; //no need to save any object to array, not used anywhere currently

			$x = $this->ReadUint8();
			$y = $this->ReadUint8();
			$z = $this->ReadUint8();

			$obj['pos'] = new MapCoords($x, $y, $z);
			$this->curcoor = $obj['pos'];

			$defnum = $this->ReadUint32(); //maybe object id, or just number in array
			$obj['defnum'] = $defnum;

			$objid = $objsubid = OBJECT_INVALID;

			if(array_key_exists($defnum, $this->objTemplates)){
				$objid = $this->objTemplates[$defnum]['id'];
				$obj['id'] = $objid;
				$obj['subid'] = $this->objTemplates[$defnum]['subid'];
				$obj['type'] = $this->objTemplates[$defnum]['type'];
				$obj['objname'] = $this->GetObjectById($objid);
				$objsubid = $obj['subid'];

				if(!array_key_exists($objid, $this->objects_unique)){
					$this->objects_unique[$objid] = array('name' => $this->GetObjectById($objid), 'count' => 0);
				}
				$this->objects_unique[$objid]['count']++;
			}
			else {
				$objid = OBJECT_INVALID;
				$obj['id'] = $objid;
			}

			$this->curobj = $objid;

			$this->SkipBytes(5);

			switch($objid) {
				case OBJECTS::EVENT:
				case OBJECTS::PANDORAS_BOX:
					$event = array();
					$event['MessageStack'] = $this->ReadMessageAndGuards();

					$event['gainedExp'] = $this->ReadUint32();
					$event['manaDiff'] = $this->ReadInt32();
					$event['moraleDiff'] = $this->ReadInt8();
					$event['luckDiff'] = $this->ReadInt8();

					$event['resources'] = $this->ReadResourses();

					$event['priSkill'] = array();
					$event['secSkill'] = array();
					$event['artifacts'] = array();
					$event['spells'] = array();
					$event['stack'] = array();

					for($j = 0; $j < 4; $j++) {
						$event['priSkill'][$j] = $this->ReadUint8();
					}

					$secSkillsNum = $this->ReadUint8(); // Number of gained abilities
					for($j = 0; $j < $secSkillsNum; $j++) {
						$event['secSkill'][] = array(
							'skill' => $this->GetSecskillById($this->ReadUint8()),
							'level' => $this->GetSecskillLevelById($this->ReadUint8())
						);
					}

					$artinum = $this->ReadUint8(); // Number of gained artifacts
					for($j = 0; $j < $artinum; $j++) {
						if($this->version == $this::ROE) {
							$artid = $this->ReadUint8();
						}
						else {
							$artid = $this->ReadUint16();
						}
						$artifact = $this->GetArtifactById($artid);
						$event['artifacts'][] = $artifact;
						$this->artifacts_list[] = new ListObject($artifact, $obj['pos'], 'Event');
					}

					$spellnum = $this->ReadUint8(); // Number of gained spells
					for($j = 0; $j < $spellnum; $j++) {
						$event['spells'][] = $this->GetSpellById($this->ReadUint8());
					}

					$stackNum = $this->ReadUint8(); //number of gained creatures
					$event['stack'] = $this->ReadCreatureSet($stackNum);

					$this->SkipBytes(8);

					//event has some extras
					if($objid == OBJECTS::EVENT) {
						$event['availableFor'] = $this->ReadUint8();
						$event['computerActivate'] = $this->ReadUint8();
						$event['removeAfterVisit'] = $this->ReadUint8();
						if($this->hota_subrev == $this::HOTA_SUBREV3) {
							$event['humanActivate'] = $this->ReadUint8();
						}
						else {
							$event['humanActivate'] = 1;
						}

						$this->SkipBytes(4);
					}
					else {
						$event['availableFor'] = HNONE;
						$event['computerActivate'] = 1;
						$event['removeAfterVisit'] = '';
						$event['humanActivate'] = 1;
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
					$this->mapobjects[] = array(
						'object' => MAPOBJECTS::HERO,
						'objid' => $objid,
						'pos' => $obj['pos'],
						'name' => $obj['data']['name'],
						'owner' => $tileowner,
						'type' => $objsubid,
						'uid' => $obj['data']['uid']
					);
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
				$monster = array();
				$monster['uid'] = OBJECT_INVALID;

				$monster['name'] = ($objid == OBJECTS::MONSTER) ? $this->GetCreatureById($objsubid) : $obj['objname'];

				if($this->version > $this::ROE) {
					$monster['uid'] = $this->ReadUint32();
				}

				$monster['count'] = $this->ReadUint16();

				$monster['character'] = $this->GetMonsterCharacter($this->ReadUint8());

				$hasMessage = $this->ReadUint8();
				$artifact = '';
				if($hasMessage) {
					$monster['message'] = $this->ReadString();
					$monster['resources'] = $this->ReadResourses();

					if ($this->version == $this::ROE) {
						$artid = $this->ReadUint8();
					}
					else {
						$artid = $this->ReadUint16();
					}

					if($this->version == $this::ROE && $artid == HNONE) {
						$artid = HNONE16;
					}

					if($artid != HNONE16) {
						$artifact = $this->GetArtifactById($artid);
						$this->artifacts_list[] = new ListObject($artifact, $obj['pos'], 'Monster: '.$monster['name']);
					}
				}
				$monster['neverFlees'] = $this->ReadUint8();
				$monster['notGrowingTeam'] = $this->ReadUint8();

				$this->SkipBytes(2);

				if($this->hota_subrev >= $this::HOTA_SUBREV3) {
					$monster['characterSpec'] = $this->ReadUint32(); //precise setup      num of ffffffff
					$monster['moneyJoin'] = $this->ReadUint8();
					$monster['percentJoin'] = $this->ReadUint32();
					$monster['upgradedStack'] = $this->ReadUint32(); //upgraded stack in not upgraded monster 0/1/ffffffff (def)
					$monster['stacksCount'] = $this->ReadUint32(); //stack count       00=one more, ffffffff=def, fdffffff=avg, fdffffff=on less or num
				}

				$obj['data'] = $monster;

				$info = $monster['character'].($monster['neverFlees'] ? ', Never fless' : '');
				$this->monsters_list[] = new ListObject($monster['name'], $this->curcoor, 'Map', $artifact, $monster['count'], $info);

				$this->mapobjects[] = array(
					'object' => MAPOBJECTS::MONSTER,
					'objid' => $objid,
					'pos' => $obj['pos'],
					'name' => $monster['name'],
					'owner' => OWNERNONE,
					'type' => $objsubid,
					'uid' => $monster['uid'],
				);
				break;

			case OBJECTS::OCEAN_BOTTLE:
			case OBJECTS::SIGN:
				$signbottle['text'] = $this->ReadString();
				$this->SkipBytes(4);
				$obj['data'] = $signbottle;
				$this->messages_list[] = $obj;
				break;

			case OBJECTS::SEER_HUT:
				$obj['data'] = $this->ReadSeerHut();
				break;

			case OBJECTS::WITCH_HUT:
				// in RoE we cannot specify it - all are allowed (I hope)
				$allowed = array();
				if($this->version > $this::ROE) {
					for($j = 0 ; $j < 4; $j++) {
						$c = $this->ReadUint8();
						$allowed[] = sprintf('%08b ', $c);
					}
				}

				$obj['data'] = $allowed;
				break;

			case OBJECTS::SCHOLAR:
				$scholar['bonusType'] = $this->ReadUint8();
				$scholar['bonusID'] = $this->ReadUint8();
				$this->SkipBytes(6);
				$obj['data'] = $scholar;
				break;

			case OBJECTS::GARRISON:
			case OBJECTS::GARRISON2:
				$stack = array();
				$stack['owner'] = $this->ReadUint8();
				$tileowner = $stack['owner'];
				$this->SkipBytes(3);
				$stack['monsters'] = $this->ReadCreatureSet(7);
				if($this->version > $this::ROE) {
					$stack['removableUnits'] = $this->ReadUint8();
				}
				else {
					$stack['removableUnits'] = 1;
				}
				$this->SkipBytes(8);

				$obj['data'] = $stack;
				break;

			case OBJECTS::ARTIFACT:
			case OBJECTS::RANDOM_ART:
			case OBJECTS::RANDOM_TREASURE_ART:
			case OBJECTS::RANDOM_MINOR_ART:
			case OBJECTS::RANDOM_MAJOR_ART:
			case OBJECTS::RANDOM_RELIC_ART:
			case OBJECTS::SPELL_SCROLL:
				$artifact = array();
				$artifact['artid'] = OBJECT_INVALID;
				$artifact['spellid'] = OBJECT_INVALID;

				$artifact['stack'] = $this->ReadMessageAndGuards();

				if($objid == OBJECTS::SPELL_SCROLL) {
					$spellid = $this->ReadUint32();
					$artifact['name'] = $obj['objname'].': '.$this->GetSpellById($spellid);
				}
				elseif($objid == OBJECTS::ARTIFACT) {
					$artifact['name'] = $this->GetArtifactById($obj['subid']); //artid
				}
				else {
					$artifact['name'] = $obj['objname'];
				}

				$this->artifacts_list[] = new ListObject($artifact['name'], $obj['pos'], 'Map');

				$obj['data'] = $artifact;
				break;

			case OBJECTS::RANDOM_RESOURCE:
			case OBJECTS::RESOURCE:
					$res = array();
					$res['stack'] = $this->ReadMessageAndGuards();
					$res['amount'] = $this->ReadUint32();
					$this->SkipBytes(4);
					$obj['data'] = $res;
					break;

			case OBJECTS::RANDOM_TOWN:
			case OBJECTS::TOWN:
					$obj['data'] = $this->ReadTown();

					$tileowner = $obj['data']['owner'];
					if($tileowner >= 0 && $tileowner <= 7){
						$this->players[$tileowner]['townsOwned']++;
					}

					$affiliation = ($objid == OBJECTS::TOWN) ? $this->GetTownById($objsubid) : 'Random Town';
					$obj['data']['affiliation'] = $affiliation;

					$this->towns_list[] = $obj;

					$obj['pos']->x -=2; //substract 2, to make position centered to town gate
					$this->mapobjects[] = array(
						'object' => MAPOBJECTS::TOWN,
						'objid' => $objid,
						'pos' => $obj['pos'],
						'name' => $obj['data']['name'],
						'owner' => $tileowner,
						'type' => $objsubid,
						'uid' => $obj['data']['uid']
					);
					break;

			case OBJECTS::MINE:
			case OBJECTS::ABANDONED_MINE:
					$mine['owner'] = $this->ReadUint8(); //owner or resource mask for abandoned mine
					$this->SkipBytes(3);
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
					$dwelling['owner'] = $this->ReadUint8();
					$this->SkipBytes(3);
					$tileowner = $dwelling['owner'];

					$obj['data'] = $dwelling;
					break;

			case OBJECTS::SHRINE_OF_MAGIC_INCANTATION:
			case OBJECTS::SHRINE_OF_MAGIC_GESTURE:
			case OBJECTS::SHRINE_OF_MAGIC_THOUGHT:
					$shrine = array();
					$shrine['spellid'] = $this->ReadUint8();
					$this->SkipBytes(3);
					$obj['data'] = $shrine;
					break;

			case OBJECTS::GRAIL:
				$grail['radius'] = $this->ReadUint32();
				$obj['data'] = $grail;
				continue;

			case OBJECTS::RANDOM_DWELLING: //same as castle + level range
			case OBJECTS::RANDOM_DWELLING_LVL: //same as castle, fixed level
			case OBJECTS::RANDOM_DWELLING_FACTION: //level range, fixed faction
					$dwelling = array();
					$dwelling['player'] = $this->ReadUint32();

					//216 and 217
					if ($objid == OBJECTS::RANDOM_DWELLING || $objid == OBJECTS::RANDOM_DWELLING_LVL) {
						$dwelling['uid'] =	$this->ReadUint32();
						if(!$dwelling['uid']) {
							$dwelling['asCastle'] = 0;
							$dwelling['castles'][0] = $this->ReadUint8();
							$dwelling['castles'][1] = $this->ReadUint8();
						}
						else {
							$dwelling['asCastle'] = 1;
						}
					}

					//216 and 218
					if ($objid == OBJECTS::RANDOM_DWELLING || $objid == OBJECTS::RANDOM_DWELLING_FACTION) {
						$dwelling['minLevel'] = max($this->ReadUint8(), 1);
						$dwelling['maxLevel'] = min($this->ReadUint8(), 7);
					}

					$obj['data'] = $dwelling;
					break;

			case OBJECTS::QUEST_GUARD:
					$quest = $this->ReadQuest();

					$this->quest_list[] = new ListObject('Quest Guard', $this->curcoor, $quest['Qtext'], '', '', '', $quest['uid']);

					$obj['data'] = $quest;
					break;

			case OBJECTS::SHIPYARD:
					$shipyard['owner'] = $this->ReadUint32();
					$obj['data'] = $shipyard;
					$tileowner = $shipyard['owner'];
					break;

			case OBJECTS::HERO_PLACEHOLDER: //hero placeholder
					$tileowner = $this->ReadUint8();
					$placeholder['owner'] = $tileowner;
					$placeholder['heroid'] = $this->ReadUint8(); //hero type id

					if($placeholder['heroid'] == HNONE) {
						$placeholder['power'] = $this->ReadUint8();
					}
					else {
						$placeholder['power'] = 0;
					}

					$obj['data'] = $placeholder;
					$obj['pos']->x -= 1; //place holder is never in town, but it must be centered for victory/loss conditions too

					$this->mapobjects[] = array(
						'object' => MAPOBJECTS::HERO,
						'objid' => $objid,
						'pos' => $obj['pos'],
						'name' => $this->GetHeroById($placeholder['heroid']),
						'owner' => $tileowner,
						'type' => $objsubid,
						'uid' => 0
					);

					break;

			case OBJECTS::BORDERGUARD:
			case OBJECTS::BORDER_GATE:
			case OBJECTS::KEYMASTER:
				break;

			case OBJECTS::PYRAMID: //Pyramid of WoG object
				break;

			case OBJECTS::LIGHTHOUSE: //Lighthouse
				$lighthouse['owner'] = $this->ReadUint32();
				$tileowner = $lighthouse['owner'];

				$obj['data'] = $lighthouse;
				break;

			//HOTA
			case OBJECTS::CREATURE_BANK:
			case OBJECTS::CRYPT:
			case OBJECTS::SHIPWRECK:
			case OBJECTS::DERELICT_SHIP:
			case OBJECTS::DRAGON_UTOPIA:
				if($this->hota_subrev >= $this::HOTA_SUBREV3) {
					$bank = array();
					$bank['variant'] = $this->ReadUint32();
					$bank['upgraded'] = $this->ReadUint8();
					$bank['artnum'] = $this->ReadUint32();
					for($j = 0; $j < $bank['artnum']; $j++) {
						$artid = $this->ReadUint32();
						if($artid == 0xffffffff) {
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

			default:
				//any other object, we dont want to save to array
				//as a matter of fact, we save that only for debug purpose, the class object is not used anywhere since this
				$saveobject = false;
				break;
		}

			//object tiles
			//if we dont build map, we dont need to save terrain access
			if($this->buildMapImage && $objid != OBJECT_INVALID) {
				$mapsizemax = $this->map_size - 1; //index starts with 0, we make variable here to not substract 1 in loop to make more readable
				for($iy = 0; $iy < 6; $iy++){ //y-axis of object tiles
					for($ix = 0; $ix < 8; $ix++){ //x-axis of object tiles
						//real xy position on map
						$mx = $x - $ix;
						$my = $y - $iy;

						//object tile out of bound check
						if($z > 1 || $my > $mapsizemax || $my < 0 || $mx > $mapsizemax || $mx < 0){
							continue;
						}

						//tile already has owner or is special -> it will have color independent on access
						if($this->terrain[$z][$my][$mx]->owner != OWNERNONE || $this->terrain[$z][$my][$mx]->special != MAPSPECIAL::NONE) {
							continue;
						}

						//object tilemask for current tile. With this, it can be checked whether tile can be stepped on
						$tilemask = $this->objTemplates[$defnum]['tiles'][$iy][$ix];

						//check if tile has object on it, if yes, continue with checks
						if(($tilemask & BLOCKMAPBITS::COMBINED) != TILETYPE::FREE) {
							//is object owned? if yes, mark tile as owned
							if($tileowner != OWNERNONE) {
								$this->terrain[$z][$my][$mx]->owner = $tileowner;
							}
							//has object some special color rule? if yes, mark as special
							elseif($special != MAPSPECIAL::NONE){
								$this->terrain[$z][$my][$mx]->special = MAPSPECIAL::ANY;
							}
							//can object tile be stepped on? if no, apply tilemask, which marks access as nono
							elseif(($tilemask & BLOCKMAPBITS::VISITABLE) != TILETYPE::ACCESSIBLE) {
								$this->terrain[$z][$my][$mx]->access = $tilemask;
							}
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
		$town = array();
		$town['uid'] = OBJECT_INVALID;
		$town['name'] = 'Random name';

		if($this->version > $this::ROE) {
			$town['uid'] = $this->ReadUint32();
		}

		$town['owner'] = $this->ReadUint8();
		$town['player'] = $this->GetPlayerColorById($town['owner']);

		$hasName = $this->ReadUint8();
		if($hasName) {
			$town['name'] = $this->ReadString();
		}

		$town['stack'] = array();
		$hasGarrison = $this->ReadUint8();
		if($hasGarrison) {
			$town['stack'] = $this->ReadCreatureSet(7);
		}
		$town['formation'] = $this->ReadUint8();

		$hasCustomBuildings = $this->ReadUint8();
		if($hasCustomBuildings) {
			$this->SkipBytes(12); //not really used right now
			/*for($i = 0; $i < 6; $i++){
				$town['buildingsBuilt'][] = sprintf('%08b ', $this->ReadUint8());
			}

			for($i = 0; $i < 6; $i++){
				$town['buildingsDisabled'][] = sprintf('%08b ', $this->ReadUint8());
			}*/
		}
		// Standard buildings
		else {
			$town['fort'] = 'no';
			$hasFort = $this->ReadUint8();
			if($hasFort) {
				$town['fort'] = 'yes';
			}
		}

		//spells always
		$town['spellsA'] = array();
		if($this->version > $this::ROE) {
			for($i = 0; $i < SPELL_BYTE; $i++) {
				$spellb = $this->ReadUint8();
				for($s = 0; $s < 8; $s++) {
					if(($spellb >> $s) & 0x01) { //add obligatory spell even if it's banned on a map
						$spellid = $i * 8 + $s;
						if($spellid >= SPELLS_QUANTITY) {
							break;
						}
						$town['spellsA'][] = $this->GetSpellById($spellid);
					}
				}
			}
		}

		//spells random
		$town['spellsD'] = array();
		for($i = 0; $i < SPELL_BYTE; $i++) {
			//$this->SkipBytes(1); //spells, not used currently in mapscan
			$spellb = $this->ReadUint8();
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
			$this->SkipBytes(1); //spell research, not used currently in mapscan
		}

		// Read castle events
		$town['eventsnum'] = $this->ReadUint32();
		for($e = 0; $e < $town['eventsnum']; $e++) {
			$event = array();

			$event['name'] = $this->ReadString();
			$event['message'] = $this->ReadString();

			$event['res'] = $this->ReadResourses();

			$event['players'] = $this->ReadUint8();
			if($this->version > $this::AB) {
				$event['human'] = $this->ReadUint8();
			}
			else {
				$event['human'] = 1;
			}

			$event['computerAffected'] = $this->ReadUint8();
			$event['firstOccurence'] = $this->ReadUint16() + 1;
			$event['nextOccurence'] =	$this->ReadUint8();

			$this->SkipBytes(17);

			for($i = 0; $i < 6; $i++){
				$event['buildings'][] = $this->ReadUint8();
			}

			for($i = 0; $i < 7; $i++) {
				$event['monsters'][] = $this->ReadUint16();
			}
			$this->SkipBytes(4);
			$town['events'][] = $event;
		}

		if($this->version > $this::AB) {
			$town['alignment'] = $this->ReadUint8();
		}
		$this->SkipBytes(3);

		return $town;
	}

	private function ReadSeerHut() {
		$hut = array();
		$numquest = 1;

		$hut['quest'][0]['taskid'] = QUESTMISSION::NONE;


		if($this->version > $this::ROE) {
			if($this->hota_subrev == $this::HOTA_SUBREV3) {
				$numquest = $this->ReadUint32(); //number of quests
			}
			for($i = 0; $i < $numquest; $i++) {
				$hut['quest'][$i] = $this->ReadQuest();
				$hut['quest'][$i]['task'] = FromArray($hut['quest'][$i]['taskid'], $this->CS->QuestMission);

				//skip false reward when there is more quests
				if($this->hota_subrev == $this::HOTA_SUBREV3 && ($i < $numquest - 1)) {
					$this->SkipBytes(1);
				}
			}
		}
		else {
			//RoE
			$hut['quest'][0]['Qtext'] = '';
			$hut['quest'][0]['uid'] = 0;

			$artid = $this->ReadUint8();
			if ($artid != HNONE) {
				$hut['quest'][0]['taskid'] = QUESTMISSION::ART;
				$hut['quest'][0]['Qtext'] = 'Artifacts: '.$this->GetArtifactById($artid);
			}
			else {
				$hut['quest'][0]['taskid'] = QUESTMISSION::NONE;
			}
			$hut['quest'][0]['timeout'] = OBJECT_INVALID; //no timeout
			$hut['quest'][0]['task'] = FromArray($hut['quest'][0]['taskid'], $this->CS->QuestMission);
		}

		$hut['rewardid'] = $this->ReadUint8();
		$hut['rewardType'] = FromArray($hut['rewardid'], $this->CS->RewardType);
		$hut['id'] = '';
		$hut['value'] = '';

		switch($hut['rewardid']) {
			case REWARDTYPE::EXPERIENCE:
				$hut['value'] = $this->ReadUint32();
				break;
			case REWARDTYPE::MANA_POINTS:
				$hut['value'] = $this->ReadUint32();
				break;
			case REWARDTYPE::MORALE_BONUS:
				$hut['value'] = $this->ReadUint8();
				break;
			case REWARDTYPE::LUCK_BONUS:
				$hut['value'] = $this->ReadUint8();
				break;
			case REWARDTYPE::RESOURCES:
				$hut['id'] = $this->GetResourceById($this->ReadUint8());
				$hut['value'] = $this->ReadUint32() & 0x00ffffff;
				break;
			case REWARDTYPE::PRIMARY_SKILL:
				$hut['id'] = $this->GetPriskillById($this->ReadUint8());
				$hut['value'] = $this->ReadUint8();
				break;
			case REWARDTYPE::SECONDARY_SKILL:
				$hut['id'] = $this->GetSecskillById($this->ReadUint8());
				$hut['value'] = $this->GetSecskillLevelById($this->ReadUint8());
				break;
			case REWARDTYPE::ARTIFACT:
				if ($this->version == $this::ROE) {
					$hut['artid'] = $this->ReadUint8();
				}
				else {
					$hut['artid'] = $this->ReadUint16();
				}
				$artifact = $this->GetArtifactById($hut['artid']);
				$hut['value'] = $artifact;

				$this->artifacts_list[] = new ListObject($artifact, $this->curcoor, 'Seer Hut');
				break;
			case REWARDTYPE::SPELL:
				$hut['value'] = $this->GetSpellById($this->ReadUint8());
				break;
			case REWARDTYPE::CREATURE:
				if($this->version > $this::ROE) {
					$hut['id'] = $this->GetCreatureById($this->ReadUint16());
				}
				else {
					$hut['id'] = $this->GetCreatureById($this->ReadUint8());
				}
				$hut['value'] = $this->ReadUint16();
				break;
		}
		$this->SkipBytes(2);

		if($this->hota_subrev == $this::HOTA_SUBREV3) {
			$this->SkipBytes(4);
		}

		$this->quest_list[] = new ListObject('Seer\'s Hut', $this->curcoor, $hut['quest'][0]['Qtext'], $hut['rewardType'], $hut['value'], $hut['id'], $hut['quest'][0]['uid']);

		return $hut;
	}

	private function ReadQuest() {
		$quest = array();
		$quest['taskid'] = $this->ReadUint8();

		$quest['Qtext'] = '';
		$quest['uid'] = 0;

		switch($quest['taskid']) {
			case QUESTMISSION::NONE:
				return $quest;
			case QUESTMISSION::PRIMARY_STAT:
				for($x = 0; $x < 4; $x++) {
					$value = $this->ReadUint8();
					$quest['Qpriskill'][] = $value;
					if($value > 0) {
						$quest['Qtext'] = $this->GetPriskillById($x).': '.$value;
					}
				}
				break;
			case QUESTMISSION::LEVEL:
				$quest['Qlevel'] = $this->ReadUint32();
				$quest['Qtext'] = 'Level: '.$quest['Qlevel'];
				break;
			case QUESTMISSION::KILL_HERO:
				$quest['Qkillhero'] = $this->ReadUint32();
				$quest['Qtext'] = 'Kill hero: ';
				$quest['uid'] = $quest['Qkillhero'];
				break;
			case QUESTMISSION::KILL_CREATURE:
				$quest['Qkillmon'] = $this->ReadUint32();
				$quest['Qtext'] = 'Kill monster: ';
				$quest['uid'] = $quest['Qkillmon'];
				break;
			case QUESTMISSION::ART:
				$artNumber = $this->ReadUint8();
				$quest['Qtext'] = 'Artifacts: ';
				for($x = 0; $x < $artNumber; $x++) {
					$artifact = $this->GetArtifactById($this->ReadUint16());
					$quest['Qtext'] .= $artifact.' ';
				}
				break;
			case QUESTMISSION::ARMY:
				$typeNumber = $this->ReadUint8();
				$quest['Qtext'] = 'Bring army:<br />';
				for($x = 0; $x < $typeNumber; $x++) {
					$monster = $this->GetCreatureById($this->ReadUint16());
					$count = $this->ReadUint16();
					$quest['Qarmy'] = array('monid' => $monster, 'count' => $count);
					$quest['Qtext'] .= ($x > 0 ? '<br />' : '').$monster.': '.$count;
				}
				break;
			case QUESTMISSION::RESOURCES:
				for($x = 0; $x < 7; $x++) {
					$count = $this->ReadUint32();
					$quest['Qres'][] = $count;
					if($count > 0) {
						$resall[] = $this->GetResourceById($x).': '.$count;
					}
				}
				$quest['Qtext'] = 'Resource:<br />'.implode($resall, '<br />');
				break;
			case QUESTMISSION::HERO:
				$quest['Qhero'] = $this->ReadUint8();
				$quest['Qtext'] = 'Be hero: '.$this->GetHeroById($quest['Qhero']);
				break;
			case QUESTMISSION::PLAYER:
				$quest['Qplayer'] = $this->ReadUint8();
				$quest['Qtext'] = 'Be player: '.$this->GetPlayerColorById($quest['Qplayer']);
				break;
			case QUESTMISSION::HOTA_EXTRA:
				$hotaquestid = $this->ReadUint32();
				if($hotaquestid == QUESTMISSION::HOTA_CLASS) {
					$this->SkipBytes(7);
				}
				elseif($hotaquestid == QUESTMISSION::HOTA_NOTBEFORE) {
					$quest['ReturnAfter'] = $this->ReadUint32();
				}
				break;
		}

		$limit = $this->ReadUint32();
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

	private function ReadEvents() {
		$numberOfEvents = $this->ReadUint32();
		$event = array();
		for($i = 0; $i < $numberOfEvents; $i++) {
			$event['order'] = $i;
			$event['name'] = $this->ReadString();
			$event['message']= $this->ReadString();

			$event['resources'] = $this->ReadResourses();
			$event['players']= $this->ReadUint8();
			if($this->version > $this::AB) {
				$event['humanAble']= $this->ReadUint8();
			}
			else {
				$event['humanAble']= 1;
			}
			$event['aiAble']= $this->ReadUint8();
			$event['first']= $this->ReadUint16() + 1;
			$event['interval']= $this->ReadUint8();

			$this->SkipBytes(17);

			$this->events[] = $event;
		}
	}

	private function ReadResourses() {
		$resources = array();
		for($i = 0; $i < 7; $i++) {
			$res = $this->ReadInt32();
			if($res != 0) {
				$resources[$i] = $res;
			}
		}
		return $resources;
	}

	private function GetCellSurface($cell){
		if($cell->owner != OWNERNONE) {
			if($cell->owner > TERRAIN::NEUTRAL - TERRAIN::OFFPLAYERS) {
				return TERRAIN::NEUTRAL;
			}
			return $cell->owner + TERRAIN::OFFPLAYERS;
		}
		elseif($this->special_access && $cell->special != MAPSPECIAL::NONE) {
			return $cell->special + TERRAIN::OFFSPECIAL;
		}
		elseif($cell->access == 0){
			return $cell->surface;
		}
		else {
			return $cell->surface + TERRAIN::OFFBLOCKED;
		}
	}

	private function ParseFinish(){
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
		switch($this->victoryCond['type']){
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

	private function PrintStack($creatures) {
		$out = '';
		foreach($creatures as $k => $mon) {
			if($k > 0) {
				$out .= '<br />';
			}
			$out .= $this->GetCreatureById($mon['id']).': '.comma($mon['count']);
		}
		return $out;
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
		if($this->map_size > 144 && $this->version != $this::HOTA) {
			$this->versionname = 'HOTA';
		}
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

	private function GetBehaviour($aibeh) {
		switch($aibeh) {
			case 0: return 'Random';
			case 1: return 'Warrior';
			case 2: return 'Builder';
			case 3: return 'Explorer';
			default: return '?';
		}
	}

	private function GetLanguage() {
		switch($this->language) {
			case 'en': return 'English';
			case 'cz': return 'Czech';
			case 'ru': return 'Russian';
			case 'cn': return 'Chinese';
			default: return '?';
		}
	}

	private function PlayerColors($playermask, $withtext = false) {
		$colors = '';
		$playermask &= $this->playerMask; //consider only allowed players
		for($i = 0; $i < PLAYERSNUM; $i++) {
			if(($playermask & (1 << $i)) != 0) {
				$colors .= '<span class="color'.($i + 1).'">&nbsp;</span>&nbsp;';
				if($withtext) {
					$colors .= FromArray($i, $this->CS->PlayersColours).'<br />';
				}
			}
		}
		return $colors;
	}

	private function GetPlayerColorById($id, $withcolor = true) {
		$color = '';
		if($withcolor && ($id >= 0 && $id <= 7 || $id == 255)) {
			$color .= '<span class="color'.($id + 1).'">&nbsp;</span>&nbsp;';
		}
		return $color.FromArray($id, $this->CS->PlayersColours);
	}

	private function GetArtifactById($artid) {
		return FromArray($artid, $this->CS->Artefacts);
	}

	private function GetArtifactPosById($artid) {
		return FromArray($artid, $this->CS->ArtifactPosition);
	}

	private function GetCreatureById($monid) {
		if($this->version == $this::HOTA && $monid >= HOTAMONSTERIDS) {
			return FromArray($monid, $this->CS->MonsterHota);
		}
		return FromArray($monid, $this->CS->Monster);
	}

	private function GetMonsterCharacter($charid) {
		return FromArray($charid, $this->CS->monchar);
	}

	private function GetResourceById($id) {
		return FromArray($id, $this->CS->Resources);
	}

	private function GetMineById($id) {
		return FromArray($id, $this->CS->Mines);
	}

	private function GetHeroById($id) {
		return FromArray($id, $this->CS->Heroes);
	}

	private function GetHeroClassById($id) {
		return FromArray($id, $this->CS->HeroClass);
	}

	private function GetTownById($id) {
		return FromArray($id, $this->CS->TownType);
	}

	private function GetBuildingById($id) {
		return FromArray($id, $this->CS->Buildings);
	}

	private function GetObjectById($id) {
		return FromArray($id, $this->CS->Objects);
	}

	private function GetSpellById($id) {
		return FromArray($id, $this->CS->SpellID);
	}

	private function GetPriskillById($id) {
		return FromArray($id, $this->CS->PrimarySkill);
	}

	private function GetSecskillById($id) {
		return FromArray($id, $this->CS->SecondarySkill);
	}

	private function GetSecskillLevelById($id) {
		return FromArray($id, $this->CS->SecSkillLevel);
	}

	private function GetLevelByExp($experience) {
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

	private function GetMapObjectByUID($mapobjectid, $uid) {
		foreach($this->mapobjects as $mapobj) {
			if($mapobj['uid'] == $uid) {
				return $mapobj['name'].' at '.$mapobj['pos']->GetCoords();
			}
		}
		return '? '.$uid;
	}


	public function ObjectsShow() {

		$valido = array(
			OBJECTS::ABANDONED_MINE,
			OBJECTS::ARTIFACT,
			OBJECTS::BORDER_GATE,
			OBJECTS::BORDERGUARD,
			OBJECTS::CREATURE_GENERATOR1,
			OBJECTS::CREATURE_GENERATOR2,
			OBJECTS::CREATURE_GENERATOR3,
			OBJECTS::CREATURE_GENERATOR4,
			OBJECTS::GARRISON,
			OBJECTS::GARRISON2,
			OBJECTS::GRAIL,
			OBJECTS::HERO,
			OBJECTS::HERO_PLACEHOLDER,
			OBJECTS::KEYMASTER,
			OBJECTS::LIGHTHOUSE,
			OBJECTS::MINE,
			OBJECTS::MONSTER,
			OBJECTS::OCEAN_BOTTLE,
			OBJECTS::PANDORAS_BOX,
			OBJECTS::PRISON,
			OBJECTS::PYRAMID,
			OBJECTS::QUEST_GUARD,
			OBJECTS::RANDOM_ART,
			OBJECTS::RANDOM_DWELLING,
			OBJECTS::RANDOM_DWELLING_FACTION,
			OBJECTS::RANDOM_DWELLING_LVL,
			OBJECTS::RANDOM_HERO,
			OBJECTS::RANDOM_MAJOR_ART,
			OBJECTS::RANDOM_MINOR_ART,
			OBJECTS::RANDOM_MONSTER,
			OBJECTS::RANDOM_MONSTER_L1,
			OBJECTS::RANDOM_MONSTER_L2,
			OBJECTS::RANDOM_MONSTER_L3,
			OBJECTS::RANDOM_MONSTER_L4,
			OBJECTS::RANDOM_MONSTER_L5,
			OBJECTS::RANDOM_MONSTER_L6,
			OBJECTS::RANDOM_MONSTER_L7,
			OBJECTS::RANDOM_RELIC_ART,
			OBJECTS::RANDOM_RESOURCE,
			OBJECTS::RANDOM_TOWN,
			OBJECTS::RANDOM_TREASURE_ART,
			OBJECTS::RESOURCE,
			OBJECTS::SEER_HUT,
			OBJECTS::SHIPYARD,
			OBJECTS::SHRINE_OF_MAGIC_GESTURE,
			OBJECTS::SHRINE_OF_MAGIC_INCANTATION,
			OBJECTS::SHRINE_OF_MAGIC_THOUGHT,
			OBJECTS::SCHOLAR,
			OBJECTS::SIGN,
			OBJECTS::SPELL_SCROLL,
			OBJECTS::TOWN,
			OBJECTS::WITCH_HUT,
			OBJECTS::EVENT
		);

		echo count($this->objects);
		foreach($this->objects as $o) {
			if(!in_array($o['id'], $valido)) continue;
			if($o['id'] != OBJECTS::SIGN) continue;

			/*if(!array_key_exists($o['subid'], $mines)) {
				$mines[$o['subid']] = 0;
			}
			$mines[$o['subid']]++;
			if($o['subid'] != 7) continue;*/
			/*if(!array_key_exists($o['data']['name'], $mons)) {
				$mons[$o['data']['name']] = 0;
			}
			$mons[$o['data']['name']]++;*/
		}
		//ksort($mines);
		//ksort($mons);

		return;
		$mobs = array();
		foreach($this->mapobjects as $mo) {
			$mobs[] = $this->GetObjectById($mo['objid']).', '.$mo['name'].'<br />';
		}
		sort($mobs);
		foreach($mobs as $mo) {
			echo $mo;
		}
	}

	//check, if map is compressed or not, compressed starts with 1F 8B 08 00 in LE, that's 0x00088B1F
	private function IsGZIP() {
		$file = fopen($this->mapfile, 'rb');

		//get file header to check if it is gzip
		$gzipheader = fread($file, 4);

		//if gzip, last 4 bytes are incompressed size
		fseek($file, -4, SEEK_END);
		$gzipend = fread($file, 4);
		fclose($file);

		$header = unpack('V', $gzipheader); //ulong 32 bit LE
		$header = end($header);
		if($header == 0x00088b1f) {
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
				echo 'H3M file seems to be corrupted<br />';
				$this->filebad = true;
			}
		}
		return $this->isGzip;
	}

	private function UnGZIP() {
		$this->mapdata = gzdecode(file_get_contents($this->mapfile));
		return;

		/*
		// Raising this value may increase performance
		$buffer_size = 10240; // read 10 kB at a time

		$gzfile = gzopen($this->mapfile, 'rb');
		// Keep repeating until the end of the input file
		while(!gzeof($gzfile)) {
			// Read buffer-size bytes
			$this->mapdata .= gzread($gzfile, $buffer_size);
		}
		gzclose($gzfile);
		*/
	}

	private function ReadUint8(){
		if($this->pos >= $this->length || $this->pos < 0){
			dbglog();
			die('Bad position '.$this->pos);
			return;
		}
		return ord($this->mapdata[$this->pos++]);
	}

	private function ReadUint16(){
		$res = $this->ReadUint8();
		$res += $this->ReadUint8() << 8;
		return $res;
	}

	private function ReadUint32(){
		$res = $this->ReadUint16();
		$res += $this->ReadUint16() << 16;
		return $res;
	}

	public function ReadInt8(){
		$res = $this->ReadUint8();
		if($res > 0x7E) {
			$res -= 0x100;
		}
		return $res;
	}

	private function ReadInt32(){
		$res = $this->ReadUint32();
		if($res > MAXINT32) {
			$res -= MININT32;
		}
		return $res;
	}

	//unused, no 64 bit in map format
	/*private function ReadUint64(){
		return $this->fix64($this->ReadUint32(), $this->ReadUint32());
	}

	private function fix64($numL, $numH){
		if($numH < 0) {
			$numH += MININT32;
		}
		if($numL < 0) {
			$numL += MININT32;
		}
		$num = bcadd($numL, bcmul($numH, MININT32));
		if($num > bcpow(2, 63)) {
			return bcsub($num, bcpow(MININT32, 2)); // 2, 64
		}
		return $num;
	}
	*/

	private function ReadString($length = -1){
		$res = '';
		if($this->pos >= $this->length || $this->pos < 0){
			dbglog();
			$this->mapdata = null;
			$this->terrain = null;
			$this->CS = null;
			//vd($this);
			die('Bad string pos '.$this->pos);
			return;
		}

		if($length == -1){
			$length = $this->ReadUint32();
			if($length == 0) return $res;
			if($length > 100000 || $length < 0) {
				vd($length);
				dbglog();
				$this->mapdata = null;
				$this->terrain = null;
				$this->CS = null;
				//$this->objTemplates = null;
				//vd($this->objects);
				//vd($this);
				die('Too long string '.$length);
				return;
			}
			$res = substr($this->mapdata, $this->pos, $length);
			$this->pos += $length;
		}
		elseif($length > 0){
			$res = substr($this->mapdata, $this->pos, $length);
			$this->pos += $length;
		}
		/*else {
			return;
			while(ord($this->mapdata[$this->pos]) != 0) {
				$res .= $this->mapdata[$this->pos++];
			}
			$this->pos++; // advance pointer after finding the 0
		}*/

		return $this->LangConvert($res);
	}

	private function SkipBytes($bytes = 31){
		$this->pos += $bytes;
	}

	private function SetPos($pos){
		$this->pos = $pos;
	}

	private function GetPos(){
		return $this->pos;
	}

	private function LangConvert($text) {
		if($this->language == null) {
			$this->GuessLanguage($text);
		}

		switch ($this->language) {
			case 'cz': return @iconv('WINDOWS-1250', 'UTF-8', $text); //middle/eastern europe
			case 'ru': return @iconv('WINDOWS-1251', 'UTF-8', $text); //russian
			case 'cn': return @iconv('GB2312', 'UTF-8', $text); //chinese
			case 'en':
			default: return @iconv('WINDOWS-1250', 'UTF-8', $text);
		}
	}

	private function GuessLanguage($text) {
		$langpatterns = array(
			//chinese
			'cn' => array(
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
			),
			//russian
			'ru' => array(
				chr(0xc0), chr(0xc1), chr(0xc5), chr(0xc7), chr(0xce), chr(0xd0), chr(0xd3), chr(0xde), chr(0xdf),
				chr(0xe0), chr(0xe1), chr(0xe5), chr(0xe7), chr(0xee), chr(0xf0), chr(0xf3), chr(0xfe), chr(0xff)
			),
			//czech
			'cz' => array(
				chr(0x8a), chr(0x8e), chr(0xc8), chr(0xc9), chr(0xcc), chr(0xd8), chr(0xd9),
				chr(0x9a), chr(0x9e), chr(0xe8), chr(0xe9), chr(0xec), chr(0xf8), chr(0xf9)
			),
		);

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
	private function ppos(){
		vd(dechex($this->pos). ' '.$this->pos);
	}

	private function pvar($var){
		echo ' '.dechex($var). ' '.$var.'<br />';
	}

	private function bvar($var){
		$bprint = sprintf('%08b', $var & 0xff);
		if($var > 0xff) {
			$bprint = sprintf('%08b', ($var >> 8) & 0xff).' '.$bprint;
		}
		return $bprint;
	}

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
	public $special;      //display some object on map with special colour
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

	public function GetCoords(){
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
	public $add1; //additional info

	public function __construct($name, $coor, $parent, $owner = OWNERNONE, $count = 0, $info = '', $add1 = null) {
		$this->name = $name;
		$this->mapcoor = $coor;
		$this->parent = $parent;
		$this->owner = $owner;
		$this->count = $count;
		$this->info = $info;
		$this->add1 = $add1;
	}
}

function EventSortByDate($a, $b){
	if($a['first'] > $b['first']) return 1;
	if($a['first'] < $b['first']) return -1;
	if($a['order'] > $b['order']) return 1;
	else -1;
}

function ListSortByName($a, $b){
	return strcmp($a->name, $b->name);
}

function SortTownsByName($a, $b){
	return strcmp($a['data']['name'], $b['data']['name']);
}

function SortTownEventsByDate($a, $b){
	if($a['firstOccurence'] > $b['firstOccurence']) return 1;
	if($a['firstOccurence'] < $b['firstOccurence']) return -1;
	return 0;
	//return strcmp($a['name'], $b['name']);
}
?>
