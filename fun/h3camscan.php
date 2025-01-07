<?php

require_once 'fun/bytereader.php';

const H3C_PRINTINFO    = 0x0001; //prints cam info, requires webmode
const H3C_SAVECAMDB    = 0x0002; //saves cam info to DB
const H3C_EXPORTMAPS   = 0x0004; //export maps
const H3C_CAMHTMCACHE  = 0x0008; //save printinfo htm file, requires printinfo
const H3C_CAMSAVEMAPS  = 0x0010; //save maps to DB
const H3C_SCENARIOMAPS = 0x0020; //save maps directly without DB

class H3CAMSCAN {
	//map versions
	public const ROE  = 4;
	public const AB   = 5;
	public const SOD  = 6;
	public const WOG  = 6;
	public const HOTA = 10;

	public const HOTA_SUBREV1 = 1;

	private $camversion = 0; //cam version
	private $mapsversion = 0; //maps version, this value is save to DB
	private $hota_subrev = 0;
	private $versionname = '';
	private $cam_name = '';
	private $description = '';
	private $language = null;


	//CAM
	private $region_index = 0; //campaign map version, determines h3c layout, This is the region index
	private $diffchoice = 0;
	private $mapscount = 0; //map count based on h3c layout
	private $mapscountreal = 0; //real map count of imported maps
	private $scenarios = [];
	private $scenario_sorted = [];
	private $scenarios_size = []; //array of scenario map sizes

	private $name = '';

	private $isGzip;
	private $gzipparts;
	private $gzipcount;
	private $mapfile = '';
	private $mapfilename = '';
	private $mapfileout = '';
	//private $mapimage; //mapfile name for DB
	private $mapfileinfo;
	private $printinfo = '';


	private $CC; //heroes campaign constants class
	private $CS; //heroes constants class
	private $SC; //String Convert

	//mode switches
	private $printoutput = false; //print info
	private $save = false; //save to db
	private $exportmaps = false; //esport maps
	private $htmcache = false; //cache htm printinfo
	private $savemaps = false; //save maps to db
	private $scenario_maps = false; //export scenario maps without getting data from DB

	private $filemtime = '';
	private $filectime = '';
	private $filesizeC = 0;
	private $filesizeU = 0;
	private $filebad = false;
	public $readok = false;

	private $br = null; //bytereader

	private $md5hash = '';
	public  $camid = 0; //when scanning, can serve as id_campaign for exporting maps OR as selecting from DB

	private $onlyunzip = false;
	private $saveH3M = false;

	//from CAMPTEXT.TXT
	private $ScenarioCount = [
		3, 4, 3, 7, 4, 3, 3,  //roe 7  0-6
		4, 4, 4, 4, 3, 8,     //ab  6  7-12
		4, 5, 4, 4, 4, 12, 4, //sod 7  13-19
		3, 6, 4, 8, 32        //hota 20-24
	];
	// 1-3 2-3 3-3 4-4 5-3 6-3 7-3          22
	// 8-8 9-4 10-4 11-4 12-3 13-4          27
	// 14-4 15-4 16-4 17-5 18-4 19-12 20-4  37
	// 21-3 22-6 23-3 24-8 25-32            52

	public function __construct($mapfile, $modes = 0) {
		$this->saveH3M = true;

		$this->mapfile = $mapfile;

		$this->printoutput   = ($modes & H3C_PRINTINFO);
		$this->save          = ($modes & H3C_SAVECAMDB);
		$this->exportmaps    = ($modes & H3C_EXPORTMAPS);
		$this->htmcache      = ($modes & H3C_CAMHTMCACHE);
		$this->savemaps      = ($modes & H3C_CAMSAVEMAPS);
		$this->scenario_maps = ($modes & H3C_SCENARIOMAPS);

		$path = pathinfo($this->mapfile);
		$this->mapfileinfo = $path;

		$this->mapfileout = MAPDIRCAMEXP.$path['filename'].'.'.$path['extension'];

		$h3mfile_exists = file_exists($this->mapfile); //original compressed map
		$h3mfileun_exists = file_exists($this->mapfileout); //uncompressed map

		//map is alrady uncompressed
		if($h3mfile_exists && $this->IsGZIP() == false) {
			$this->mapfileout = $this->mapfile;
		}
		else {
			if(!$h3mfile_exists) {
				echo $this->mapfile.' does not exists!'.ENVE;
				$this->filebad = true;
				return;
			}
		}

		$this->mapfilename = $path['filename'];

		//split only when file is gzipped
		if($this->isGzip) {
			$this->GzipSplit();
		}
		else {
			$this->br = new ByteReader(file_get_contents($this->mapfile));
		}

		$this->filesizeC = filesize($this->mapfile);
		$this->filemtime = filemtime($this->mapfile);
		$this->filectime = filectime($this->mapfile);

		$this->mapfile = $path['basename']; //cut folder path, no needed from here
	}

	private function SaveCam() {

		$mapfile = mes($this->mapfile);

		$sql = "SELECT m.idm FROM heroes3_maps AS m WHERE md5='".$this->md5hash."'";
		$camid = mgr($sql);
		if($camid) {
			$this->camid = $camid;
			return;
		}

		$mapdir = mes($this->mapfileinfo['dirname']);
		$camname = mes($this->cam_name);
		$camdesc = mes($this->description);

		$mapsizes = implode(', ', $this->scenarios_size); //saves to mapsizes now

		//if camversion is HOTA, then maps are always HOTA. There can be situation for former HOTA campaigns, where camversion is SOD, but maps are HOTA
		$version = $this->camversion == 'HOTA' ? 'HOTA' : $this->mapsversion;


		$sql = "INSERT INTO heroes3_maps (heroes, campaign, id_campaign, `mapfile`, `mapdir`, `mapname`, `author`, `language`, `mapdesc`, `version`, `subversion`,
			`size`, `sizename`, `levels`, `diff`, `diffname`,
			`playersnum`, `playhuman`, `playai`, `teamnum`, `victory`, `loss`, `filecreate`, `filechanged`, `filesizeC`, `filesizeU`,
			`mapimage`, `md5`) VALUES
			(3, 1, 0, '$mapfile', '$mapdir/', '$camname', '', '".$this->language."', '$camdesc', '".$this->mapsversion."', '".$this->hota_subrev."',
				".$this->mapscountreal.", '', ".$this->region_index.", '".$this->diffchoice."', '',
				0, 0, 0,
				0, 0, 0,
			FROM_UNIXTIME(".$this->filectime."), FROM_UNIXTIME(".$this->filemtime."), ".$this->filesizeC.", 0, '$mapsizes', '".$this->md5hash."')";


		$m = mq($sql);
		$this->camid = mii();
	}

	public function SetCamId($camid) {
		$this->camid = $camid;
	}

	public function GetMapsSizes() {
		return $this->scenarios_size;
	}

	public function CamHeaderInfo() {
		$headerInfo = [
			'mapfile' => $this->mapfile,
			'version' => $this->camversion,
			'camname' => $this->cam_name,
			'camdesc' => $this->description,
			'mapsizes' => $this->scenarios_size,
			'filesizeC' => $this->filesizeC,
			'filetime' => $this->filemtime,
			'md5hash' => $this->md5hash,
		];
		return $headerInfo;
	}

	public function ReadCam() {
		try {
			$this->ReadCamEx();
		}
		catch(Exception $e) {
			echo $e->GetMessage().ENVE;
			return;
		}
	}

	public function ReadCamEx() {
		if($this->filebad) {
			return;
		}

		$this->br->ResetPos();
		$this->CC = new CampaignConstants();
		$this->CS = new HeroesConstants();
		//$this->SC = new StringConvert();


		$this->camversion = $this->br->ReadUint32();

		$has_region_map = 0;
		if($this->camversion ==$this::HOTA) {
				$this->hota_subrev = $this->br->ReadUint32();
				$has_region_map = $this->br->ReadUint8();
				$this->br->SkipBytes(4); //uint32 background
				$this->mapscount = $this->br->ReadUint32();
		}

		$this->region_index = $this->br->ReadUint8(); //region index
		$this->cam_name = $this->ReadString();

		//reset language which was set bases on mapname and let base it on description, which is usually longer
		$this->language = null;

		$this->description = $this->ReadString();

		if($this->camversion > $this::ROE) {
			$this->diffchoice = $this->br->ReadUint8();
		}

		$music = $this->br->ReadUint8();

		if($this->camversion != $this::HOTA || $has_region_map) {
			$this->mapscount = $this->ScenarioCount[$this->region_index - 1]; //array start with 0, reduct by 1
		}

		//READ SCENARIOS
		$part_index = 1;
		for ($i = 0; $i < $this->mapscount; $i++) {
			$this->scenarios[$i] = $this->ReadScenario($i);
			if($this->scenarios[$i]->size != 0) {
				$this->scenarios[$i]->part_index = $part_index++;
			}
		}

		// ==== print info about campaign
		$totalsize = 0;
		$maps = [];
		for ($i = 0; $i < $this->mapscount; $i++) {

			if($this->scenarios[$i]->size == 0) {
				continue;
			}

			$totalsize += $this->scenarios[$i]->size;
			$maps[$this->scenarios[$i]->mapname] = $this->scenarios[$i]->size;
			$this->mapscountreal++;
		}

		if($this->printoutput) {
			$this->GetVersionName();

			$hot_rev = ($this->camversion == $this::HOTA) ? ' '.$this->hota_subrev : '';

			$this->printinfo .= '
			<p class="campaign-info">
				Campaign name: '.$this->cam_name.'<br />
				File name: '.$this->mapfile.'<br />
				Version: '.$this->versionname.$hot_rev.'<br />
				Campaign layout: '.GetCampaignLayout($this->region_index).'<br />
				Map Count: '.$this->mapscountreal.'<br />
			</p>';
		}

		//sort maps as they go in campaign by precondition
		$this->SortScenarios();

		//show

		$this->readok = true;
	}

	private function ReadScenario($index) {
		$map = new Scenario();
		$map->mapname = $this->ReadString();
		$map->size = $this->br->ReadUint32(); //packed size

		//read precondition, depends on region count. Could be also 3 bytes, but there is no region with such count, so at this moment we dont care
		$precondition_size = intval(($this->mapscount + 7) / 8);
		if($precondition_size == 1) {
			$precond = $this->br->ReadUint8();
		}
		elseif($precondition_size == 2) {
			$precond = $this->br->ReadUint16();
		}
		else {
			$precond = $this->br->ReadUint32();
		}

		$map->precondition = [];
		$pre_limit = $precondition_size * 8;
		for ($i = 0; $i < $pre_limit; $i++) {
			if((1 << $i) & $precond) {
				$map->precondition[] = $i;
			}
		}

		$map->color = $this->br->ReadUint8();
		$map->diff = $this->br->ReadUint8();
		$map->text = $this->ReadString();

		$map->prolog = $this->ReadMapStory();
		//extra prologs, TODO: read it and print it later
		if($this->camversion == $this::HOTA) {
			$this->ReadMapStory();
			$this->ReadMapStory();
		}

		$map->epilog = $this->ReadMapStory();
		//extra epilogs, TODO: read it and print it later
		if($this->camversion == $this::HOTA) {
			$this->ReadMapStory();
			$this->ReadMapStory();
		}

		$map->keepHero = $this->br->ReadUint8();

		$mon_bytes = ($this->camversion == $this::HOTA) ? CAM_MONSTERS_QUANTITY_HOTA : CAM_MONSTERS_QUANTITY;
		$mon_bytes = intval(($mon_bytes + 7) / 8);
		for ($i = 0; $i < $mon_bytes; $i++) {
			$map->keepMonster[] = $this->br->ReadUint8();
		}

		$art_bytes = ($this->camversion == $this::HOTA) ? CAM_ARTIFACT_QUANTITY_HOTA : CAM_ARTIFACT_QUANTITY;
		$art_bytes = intval(($art_bytes + 7) / 8);
		for ($i = 0; $i < $art_bytes; $i++) {
			$map->keepArtifact[] = $this->br->ReadUint8();
		}

		$bonus = [];
		$startoptions = $this->br->ReadUint8();
		switch($startoptions) {
			case 0:
				break;
			case 1:
				$bonus['pcolor'] = $this->br->ReadUint8();
				$bonus['num'] = $this->br->ReadUint8();
				for ($i = 0; $i < $bonus['num']; $i++) {
					$bonus[$i]['type'] = $this->br->ReadUint8();
					$bonus[$i]['text'] = '';

					switch ($bonus[$i]['type']) {
					/*
					SPELL, MONSTER, BUILDING,
					ARTIFACT, SPELL_SCROLL,
					PRIMARY_SKILL, SECONDARY_SKILL, RESOURCE,
					HEROES_FROM_PREVIOUS_SCENARIO, HERO
					*/
						case CAMBONUS::SPELL:
							$bonus[$i]['hero'] = $this->br->ReadUint16();
							$bonus[$i]['spell'] = $this->br->ReadUint8();
							$bonus[$i]['text'] = $this->GetHeroById($bonus[$i]['hero']).': '.$this->GetSpellById($bonus[$i]['spell']);
							break;

						case CAMBONUS::MONSTER:
							$bonus[$i]['hero'] = $this->br->ReadUint16();
							$bonus[$i]['monster'] = $this->br->ReadUint16();
							$bonus[$i]['count'] = $this->br->ReadUint16();
							$bonus[$i]['text'] = $this->GetHeroById($bonus[$i]['hero']).': '.$this->GetCreatureById($bonus[$i]['monster']).' ('.$bonus[$i]['count'].')';
							break;

						case CAMBONUS::BUILDING:
							$bonus[$i]['building'] = $this->br->ReadUint8();
							$bonus[$i]['text'] = $this->GetBuildingById($bonus[$i]['building']);
							break;

						case CAMBONUS::ARTIFACT:
							$bonus[$i]['hero'] = $this->br->ReadUint16();
							$bonus[$i]['artid'] = $this->br->ReadUint16();
							$bonus[$i]['text'] = $this->GetHeroById($bonus[$i]['hero']).': '.$this->GetArtifactById($bonus[$i]['artid']);
							break;

						case CAMBONUS::SPELL_SCROLL:
							$bonus[$i]['hero'] = $this->br->ReadUint16();
							$bonus[$i]['spell'] = $this->br->ReadUint8();
							$bonus[$i]['text'] = $this->GetHeroById($bonus[$i]['hero']).': '.$this->GetSpellById($bonus[$i]['spell']);
							break;

						case CAMBONUS::PRIMARY_SKILL:
							$bonus[$i]['hero'] = $this->br->ReadUint16();
							$bonus[$i]['attack'] = $this->br->ReadUint8();
							$bonus[$i]['def'] = $this->br->ReadUint8();
							$bonus[$i]['power'] = $this->br->ReadUint8();
							$bonus[$i]['knowledge'] = $this->br->ReadUint8();

							if($bonus[$i]['attack'] > 0) {
								$bonus[$i]['text'] .= 'Attack='.$bonus[$i]['attack'].' ';
							}
							if($bonus[$i]['def'] > 0) {
								$bonus[$i]['text'] .= 'Defense='.$bonus[$i]['def'].' ';
							}
							if($bonus[$i]['power'] > 0) {
								$bonus[$i]['text'] .= 'Spell Power='.$bonus[$i]['power'].' ';
							}
							if($bonus[$i]['knowledge'] > 0) {
								$bonus[$i]['text'] .= 'Knowledge='.$bonus[$i]['knowledge'].' ';
							}
							break;

						case CAMBONUS::SECONDARY_SKILL:
							$bonus[$i]['hero'] = $this->br->ReadUint16();
							$bonus[$i]['skill'] = $this->br->ReadUint8();
							$bonus[$i]['level'] = $this->br->ReadUint8();
							$bonus[$i]['text'] = $this->GetHeroById($bonus[$i]['hero']).': '.$this->GetSecskillById($bonus[$i]['skill']).' ['.$this->GetSecskillLevelById($bonus[$i]['level']).']';
							break;

						case CAMBONUS::RESOURCE:
							$bonus[$i]['res'] = $this->br->ReadUint8(); //TODO: 0xFE = mercury+sulfur+crystal+gem
							$bonus[$i]['count'] = $this->br->ReadUint32();
							$bonus[$i]['text'] = $this->GetResourceById($bonus[$i]['res']).' ['.$bonus[$i]['count'].']';
							break;

						default:
							echo 'Corrupted H3C<br />';
							break;
					} //for bonus


				} // switch bonus start
				break;

			//choose player color
			case 2:
				$bonus['num'] = $this->br->ReadUint8();
				for ($i = 0; $i < $bonus['num']; $i++) {
					$bonus[$i]['type'] = CAMBONUS::HEROES_PREVIOUS;
					$bonus[$i]['pcolor'] = $this->br->ReadUint8();
					$bonus[$i]['scenario'] = $this->br->ReadUint8();
					$bonus[$i]['text'] = $bonus[$i]['pcolor'].' '.$bonus[$i]['scenario'];
				}
				break;

			//choose hero from previous scenario
			case 3:
				$bonus['num'] = $this->br->ReadUint8();
				for ($i = 0; $i < $bonus['num']; $i++) {
					$bonus[$i]['type'] = CAMBONUS::HERO;
					$bonus[$i]['pcolor'] = $this->br->ReadUint8();
					$bonus[$i]['hero'] = $this->br->ReadUint16();
					$bonus[$i]['text'] = $bonus[$i]['pcolor'].' '.$this->GetHeroById($bonus[$i]['hero']);
				}
				break;

			default:
				vdc($startoptions);
				echo 'Corrupted H3C<br />';
				break;
		}

		$map->bonus = $bonus;
		//vdc($map);

		return $map;
	}

	//prologs and epilogs
	private function ReadMapStory() {
		$story = ['video' => '', 'music' => '', 'text' => ''];
		$hasStory = $this->br->ReadUint8();
		if($hasStory) {
			$story['video'] = $this->br->ReadUint8();
			$story['music'] = $this->br->ReadUint8();
			$story['text']  = $this->ReadString();
		}
		return $story;
	}

	public function ReadMaps() {
		if($this->filebad) {
			return;
		}

		//cam header was not read properly
		if($this->readok == false) {
			return;
		}

		$makehtm = $this->printoutput || $this->htmcache;

		if($makehtm) {
			$this->printinfo .= '
			<table class="campaign-info">
				<tr>
					<th class="colw150">Map File</th>
					<th>Version</th>
					<th class="colw150">Map Name</th>
					<th>Description</th>
					<th>Map Size</th>
					<th>Levels</th>
					<th>Players</th>
					<th>Teams</th>
					<th>Level Cap</th>
					<th>Victory</th>
					<th>Loss</th>
					<th>Heroes Placeholders</th>
					<th>Bonus</th>
					<th>Image</th>
					<th>Detail</th>
				</tr>';
		}

		//cam dir for exports and saving maps
		$SC = new StringConvert();

		if($this->camid) {
			$camdir = MAPDIRCAMEXP . $SC->SanityString(str_ireplace('.h3c', '', $this->mapfile)).'_'.$this->camid.'/';
		}
		else {
			$camdir = MAPDIRCAMEXP . $SC->SanityString(str_ireplace('.h3c', '', $this->mapfile)).'/';
		}

		//EXPORT MAPS TO DIRECTORY
		if($this->exportmaps) {
			if(!file_exists($camdir) && is_writable(MAPDIRCAMEXP)) {
				mkdir($camdir);
			}

			$dirok = file_exists($camdir);

			$scenario_num = 0;
			if(empty($this->scenarios)) {
				echo 'No scenarios found';
				return;
			}

			for ($i = 1; $i < $this->gzipcount; $i++) {

				while($this->scenarios[$scenario_num]->size == 0) {
					$scenario_num++;
					if($scenario_num > $this->mapscount) {
						return;
					}
				}

				$mapname = $this->scenarios[$scenario_num++]->mapname;
				if($mapname == '') {
					continue;
				}
				$mapname = $SC->SanityString($mapname);

				if($dirok) {
					file_write($camdir.$mapname, $this->gzipparts[$i]);
				}
			}
		}

		//$this->Scenarios_sorted_dbg();

		//PRINT WEB INFO
		$tdalign = ['ac', 'ac', 0, 0, 'ac', 'ac', 'ac', 'ac', 'ac', 0, 0, 0, 'ac', 'ac'];

		//get images for maps
		if($makehtm && $this->camid) {
			$mapdb = [];
			$sql = "SELECT idm, mapfile, mapimage FROM heroes3_maps WHERE id_campaign=".$this->camid;
			$query = mq($sql);
			while($res = mfs($query)) {
				$mapdb[] = $res;
			}
		}

		//get basic info, that is saved with cam
		foreach($this->scenario_sorted as $scenario_num) {

			$gzip_index = $this->scenarios[$scenario_num]->part_index;
			$mapfile = $this->scenarios[$scenario_num]->mapname;

			//mapfile ensures that it will be displayed in html later as part of map headerinfo
			$mapmode = $this->scenario_maps ? H3M_BUILDMAP : (H3M_WEBMODE | H3M_BASICONLY);
			$map = new H3MAPSCAN($mapfile, $mapmode, gzdecode($this->gzipparts[$gzip_index]));
			$map->ReadMap();
			$headerInfo = $map->MapHeaderInfo();

			$this->mapsversion = $map->GetMapVersion();
			$this->scenarios_size[] = $headerInfo['mapsize'];

			$mapid = 0;

			//hero placeholders
			$placeholders = '';
			foreach ($map->heroes_placeholder as $k => $hero) {
				$placeholders .= $this->GetPlayerColorById($hero['owner']).' '.$this->GetHeroById($hero['heroid']).'<br />';
			}

			//extra info for web display
			if($makehtm) {
				$bonus = $this->scenarios[$scenario_num]->bonus;
				$bonuses = [''];

				for ($b = 0; $b < $bonus['num']; $b++) {
					$bonuses[$b] = '';
					if($b > 0) {
						$bonuses[$b] .= '<tr>';
					}
					$bonuses[$b] .= '<td>'.$this->GetCamBonusById($bonus[$b]['type']).' - '.$bonus[$b]['text'].'</td>'.EOL;
					if($b > 0) {
						$bonuses[$b] .= '</tr>';
					}
				}

				if($bonus['num'] == 0) {
					$bonuses[0] = '<td></td>';
				}

				//display images where able
				$image = '';
				if(!empty($mapdb)) {
					foreach($mapdb as $m) {
						if($m['mapfile'] != $SC->SanityString($mapfile)) {
							continue;
						}

						$mapid = $m['idm'];
						$mapimage = $m['mapimage'];

						$imgmapnameG = MAPDIRIMG.$mapimage.'_g.png';
						$imgg = file_exists($imgmapnameG) ? '<img src="'.$imgmapnameG.'" alt="Ground" title="Ground" style="height:144px;" />' : '';
						$imgu = '';

						if($headerInfo['levels']) {
							$imgmapnameU = MAPDIRIMG.$mapimage.'_u.png';
							$imgu = file_exists($imgmapnameU) ? '<img src="'.$imgmapnameU.'" alt="Underground" title="Underground" style="height:144px;" />' : '';
						}
						$image = $imgg.$imgu;
					}
				}
				elseif($this->scenario_maps) {
					$mapimage = str_ireplace('.h3m', '', $mapfile);
					$imgmapnameG = MAPDIRIMG.$mapimage.'_g.png';
					$imgg = file_exists($imgmapnameG) ? '<img src="'.$imgmapnameG.'" alt="Ground" title="Ground" style="height:144px;" />' : '';
					$imgu = '';

					if($headerInfo['levels']) {
						$imgmapnameU = MAPDIRIMG.$mapimage.'_u.png';
						$imgu = file_exists($imgmapnameU) ? '<img src="'.$imgmapnameU.'" alt="Underground" title="Underground" style="height:144px;" />' : '';
					}
					$image = $imgg.$imgu;
				}

				//table row with map info
				$this->printinfo .= '<tr>';

				$rowspan = $bonus['num'] > 0 ? ' rowspan="'.$bonus['num'].'"' : '';
				$k = 0;
				foreach($headerInfo as $hi) {
					if($k > 10) { //only use map header data we want here. Better than index would be using assoc keys
						break;
					}

					//link for mapname
					if($k == 0 && $mapid > 0) {
					  $hi = '<a href="?mapid='.$mapid.'">'.$hi.'</a>';
					}

					$class = $tdalign[$k] ? ' class="'.$tdalign[$k].'"' : '';
					$this->printinfo .= '<td'.$rowspan.$class.'>'.$hi.'</td>';
					$k++;
				}

				$this->printinfo .= '<td'.$rowspan.' class="ac vac">'.$placeholders.'</td>'.EOL;
				$this->printinfo .= $bonuses[0];
				$this->printinfo .= '<td'.$rowspan.' class="ac vac">'.$image.'</td>'.EOL;
				$this->printinfo .= '<td'.$rowspan.' class="colw100 downbutton ac"><span onclick="MapScan('.$mapid.'); return false;" title="Very detailed map scan" >Map Scan</span></td>';
				$this->printinfo .= '</tr>'.EOL;

				for ($i = 1; $i < count($bonuses); $i++) {
					$this->printinfo .= $bonuses[$i];
				}
			}

			$scenario_num++;
		}

		if($makehtm) {
			$this->printinfo .= '</table>';
		}
		
		if($this->printoutput) {
			echo $this->printinfo;
			$this->CamScenarioDetails();
		}

		if($this->htmcache) {
			file_write(MAPDIRINFO.str_ireplace('.h3c', '.htm', $this->mapfile).'.gz', gzencode($this->printinfo));
		}
		
		//SAVE CAM TO DB
		if($this->save) {
			$this->SaveCam();
		}

		//SAVE MAPS TO DB
		if($this->savemaps) {
			foreach($this->scenario_sorted as $scenario_num) {
				$mapname = $this->scenarios[$scenario_num]->mapname;
				if($mapname == '') {
					continue;
				}
				$mapname = $SC->SanityString($mapname);

				$mapfile = $camdir.$mapname;

				$map = new H3MAPSCAN($mapfile, H3M_BUILDMAP | H3M_SAVEMAPDB);
				$map->SetCamId($this->camid);
				$map->ReadMap();
			}
		}
	}
	
	//sorts scenarios as they go in the campaign, which can differ from the order in gzip
	private function SortScenarios() {
		$this->scenario_sorted = [];
		$n = 0;
		while($this->mapscountreal != count($this->scenario_sorted)) {
			foreach($this->scenarios as $k => $s) {
				if($s->size > 0 && !in_array($k, $this->scenario_sorted)) {
					if(empty($s->precondition)) {
						$this->scenario_sorted[] = $k;
					}
					elseif($n > 0) {
						$prereq = 0;
						$prereq_total = count($s->precondition);
						foreach($s->precondition as $precon) {
							if(in_array($precon, $this->scenario_sorted)) {
								$prereq++;
							}
							if($prereq == $prereq_total) {
								$this->scenario_sorted[] = $k;
								break;
							}
						}
					}
				}
			}
			$n++;
			if($n > $this->mapscountreal) {
				break; //safety
			}
		}
	}

	public function CamScenarioDetails() {
		if(!$this->printoutput) {
			return;
		}
	
		echo '
			<p class="campaign-info"><span class="campaign-title">'.$this->cam_name.'</span><br />'
				.nl2br($this->description).'</p>';

		echo '
			<table class="campaign-info">
				<tr>
					<th>Map File</th>
					<th>Version</th>
					<th>Color</th>
					<th>Difficulty</th>
					<th>Map Text</th>
					<th>Prolog video/music</th>
					<th>Prolog text</th>
					<th>Epilog video/music</th>
					<th>Epilog text</th>
					<th>Hero carry</th>
					<th>Monster carry</th>
					<th>Artifacts carry</th>
				</tr>';

		foreach($this->scenario_sorted as $scenario_num) {

			$sc = $this->scenarios[$scenario_num];

			$crossover = [];
			for ($i = 0; $i < 5; $i++) {
				switch($sc->keepHero & (1 << $i)) {
					case  1: $crossover[] = 'Experience'; break;
					case  2: $crossover[] = 'Primary skills'; break;
					case  4: $crossover[] = 'Secondary skills'; break;
					case  8: $crossover[] = 'Spells'; break;
					case 16: $crossover[] = 'Artifacts'; break;
				}
			}


			$keepM = implode(' ', $sc->keepMonster);
			$keepA = implode(' ', $sc->keepArtifact);

			$artcarry = [];
			foreach($sc->keepArtifact as $i => $byte) {
				for($n = 0; $n < 8; $n++) {
					if(($byte & (1 << $n)) != 0) {
						$ida = $i * 8 + $n;
						$artcarry[] = $this->GetArtifactById($ida);
					}
				}
			}

			$moncarry = [];
			foreach($sc->keepMonster as $i => $byte) {
				for($n = 0; $n < 8; $n++) {
					if(($byte & (1 << $n)) != 0) {
						$ida = $i * 8 + $n;
						$moncarry[] = $this->GetCreatureById($ida);
					}
				}
			}

			$moncarrySTR = count($moncarry) < 100 ? implode('<br />', $moncarry) : 'All';

			echo '
			<tr>
				<td>'.$sc->mapname.'</td>
				<td>'.$sc->version.'</td>
				<td class="ac">'.$this->GetPlayerColorById($sc->color).'</td>
				<td class="ac">'.$this->GetDifficulty($sc->diff).'</td>
				<td>'.nl2br($sc->text).'</td>
				<td class="ac">'.$sc->prolog['video'].'/'.$sc->prolog['music'].'</td>
				<td>'.nl2br($sc->prolog['text']).'</td>
				<td class="ac">'.$sc->epilog['video'].'/'.$sc->epilog['music'].'</td>
				<td>'.nl2br($sc->epilog['text']).'</td>
				<td class="nowrap" nowrap="nowrap">'.implode('<br />', $crossover).'</td>
				<td>'.$moncarrySTR.'</td>
				<td class="nowrap" nowrap="nowrap">'.implode('<br />', $artcarry).'</td>
			</tr>';
		}

		echo '</table>';

	}

	private function GetVersionName() {
		switch($this->camversion) {
			case $this::ROE:  $this->versionname = 'ROE';  break;
			case $this::AB:   $this->versionname = 'AB';   break;
			case $this::SOD:  $this->versionname = 'SOD';  break;
			case $this::WOG:  $this->versionname = 'WOG';  break;
			case $this::HOTA: $this->versionname = 'HOTA'; break;
			default:          $this->versionname = '?';    break;
		}
	}

	private function GetDifficulty($diff) {
		switch($diff) {
			case 0:  return 'Easy';
			case 1:  return 'Normal';
			case 2:  return 'Hard';
			case 3:  return 'Expert';
			case 4:  return 'Impossible';
			default: return '?';
		}
	}

	private function GetCamBonusById($id) {
		return FromArray($id, $this->CC->cambonus);
	}

	public function GetPlayerColorById($id, $withcolor = true) {
		$color = '';
		if($withcolor && ($id >= 0 && $id <= 7 || $id == 255)) {
			$color .= '<span class="color'.($id + 1).'">&nbsp;</span>&nbsp;';
		}
		return $color; //.FromArray($id, $this->CS->PlayersColors);
	}

	private function GetArtifactById($artid) {
		if($this->camversion == $this::HOTA && $artid >= HOTA_ARTIFACTS_IDS) {
			return FromArray($artid, $this->CS->ArtefactsHota);
		}
		return FromArray($artid, $this->CS->Artefacts);
	}

	public function GetCreatureById($monid) {
		if($this->camversion == $this::HOTA && $monid >= HOTA_MONSTER_IDS) {
			return FromArray($monid, $this->CS->MonsterHota);
		}
		return FromArray($monid, $this->CS->Monster);
	}

	private function GetResourceById($id) {
		return FromArray($id, $this->CS->Resources);
	}

	private function GetHeroById($id) {
		return FromArray($id, $this->CS->Heroes);
	}

	private function GetTownById($id) {
		return FromArray($id, $this->CS->TownType);
	}

	private function GetBuildingById($id) {
		return FromArray($id, $this->CS->Buildings);
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
				echo 'H3C file seems to be corrupted. Bad unpacked size<br />';
				$this->filebad = true;
			}
		}
		return $this->isGzip;
	}
	
	private function GzipSplit() {
		//campaign files are several gzipped files together, split them first
		//first is campaign header, rest are maps
		$gzmark = chr(0x1F).chr(0x8B).chr(0x08).chr(0x00); //00088B1F

		$g = 0;
		$this->gzipparts = [''];

		$gzfile = file_get_contents($this->mapfile);

		$this->md5hash = md5($gzfile);

		$pos = 0;
		$length = strlen($gzfile);
		$gzmarkpos = strpos($gzfile, $gzmark, $pos);
		while($gzmarkpos !== false) {
			if($gzmarkpos > $pos) {
				$this->gzipparts[$g++] = substr($gzfile, $pos, $gzmarkpos - $pos);
				$pos = $gzmarkpos;
			}
			//+1 so the same markpos is not found again
			$gzmarkpos = strpos($gzfile, $gzmark, $pos + 1);

			//last part
			if($gzmarkpos === false) {
				$this->gzipparts[$g++] = substr($gzfile, $pos, $length - $pos);
			}
		}

		$this->gzipcount = $g;

		$this->br = new ByteReader(gzdecode($this->gzipparts[0]));
	}

	private function ReadString() {
	  return $this->LangConvert($this->br->ReadString());
	}

	private function LangConvert($text) {
		if($this->language == null) {
			$this->GuessLanguage($text);
			//echo EOL.'['.$this->language.']'.EOL;
		}

		switch ($this->language) {
			case 'pl':
			case 'cz': return @iconv('WINDOWS-1250', 'UTF-8', $text); //middle/eastern europe
			case 'ru': return @iconv('WINDOWS-1251', 'UTF-8', $text); //russian
			case 'cn': return @iconv('GB2312', 'UTF-8', $text); //chinese
			case 'en':
			default: return @iconv('WINDOWS-1250', 'UTF-8', $text);
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
					//vd(dechex(ord($ch)));
					//vd($this->language);
					//showbytes($text);
					return;
				}
			}
		}

		//default
		$this->language = 'en';
	}

	private function Scenarios_sorted_dbg() {
		echo '<table class="campaign-info"><tr><td style="width:600px;">Scenario list<br />
			<table><tr><td>Index</td><td>Map</td><td>Prerequisites</td><td>Size</td><td>Part index</td></tr>';
		foreach($this->scenarios as $k => $s) {
			echo '<tr><td>'.$k.'</td><td>'.$s->mapname.'</td><td class="ac">'.implode(' ', $s->precondition).'</td><td class="ar">'.$s->size.'</td><td class="ac">'.$s->part_index.'</td></tr>';
		}
		echo '</table>
			</td><td>Scenarios sorted<br />
				<table><tr><td>Index</td><td>Map</td><td>Prerequisites</td><td>Part index</td></tr>';
		foreach($this->scenario_sorted as $si) {
			$s = $this->scenarios[$si];
			echo '<tr><td>'.$si.'</td><td>'.$s->mapname.'</td><td class="ac">'.implode(' ', $s->precondition).'</td><td class="ac">'.$s->part_index.'</td></tr>';
		}
		echo '</table>
			</td></tr></table>';
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


class Scenario {
	public $mapname;
	public $size;
	public $version;
	public $color;
	public $diff;
	public $precondition;
	public $text;
	public $prolog;
	public $epilog;
	public $part_index = 0; //order in gzip parts

	public $bonus;

	//carry to next scenario
	public $keepHero; //hero stuff
	public $keepMonster; //monsters
	public $keepArtifact; //artifacts
}


?>
