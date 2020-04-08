<?php

const CAMROE = 4;
const CAMAB  = 5;
const CAMSOD = 6;
const CAMWOG = 6;

const H3C_PRINTINFO   = 0x0001; //prints cam info, requires webmode
const H3C_SAVECAMDB   = 0x0002; //saves cam info to DB
const H3C_EXPORTMAPS  = 0x0004; //export maps
const H3C_CAMHTMCACHE = 0x0008; //save printinfo htm file, requires printinfo

class H3CAMSCAN {
	const ROE  = 0x0e;
	const AB   = 0x15;
	const SOD  = 0x1c;
	const WOG  = 0x33;
	const HOTA = 0x20;

	private $camversion = 0; //cam version
	private $mapsversion = 0; //maps version, this value is save to DB
	private $versionname = '';
	private $cam_name = '';
	private $description = '';
	private $language = null; 


	//CAM
	private $mapversion = 0; //campaign map version, determines h3c layout
	private $diffchoice = 0;
	private $mapscount = 0; //map count based on h3c layout
	private $mapscountreal = 0; //real map count of imported maps
	private $scenarios = array();

	private $name = '';

	private $isGzip;
	private $gzipparts;
	private $gzipcount;
	private $mapdata = '';
	private $mapfile = '';
	private $mapfilename = '';
	private $mapfileout = '';
	private $mapimage; //mapfile name for DB
	private $mapfileinfo;


	private $CC; //heroes campaign constants class
	private $CS; //heroes constants class
	private $SC; //String Convert
	
	private $printoutput = false; //print info
	private $save = false; //save to db
	private $exportmaps = false; //esport maps
	private $htmcache = false; //cache htm printinfo

	private $pos = 0;
	private $length = 0;

	private $filemtime = '';
	private $filectime = '';
	private $filesizeC = 0;
	private $filesizeU = 0;
	private $filebad = false;
	public $readok = false;

	private $md5hash = '';

	private $onlyunzip = false;
	private $saveH3M = false;

	//from CAMPTEXT.TXT
	private $ScenarioCount = [
		3, 4, 3, 7, 4, 3, 3,  //roe 7  0-6
		4, 4, 4, 4, 3, 8,      //ab  6  7-C
		4, 5, 4, 4, 4, 12, 4  //sod 7  D-13
	];
	// 1-3 2-3 3-3 4-4 5-3 6-3 7-3  22
	// 8-8 9-4 10-4 11-4 12-3 13-4  27
	// 14-4 15-4 16-4 17-5 18-4 19-12 20-4  37

	public function __construct($mapfile, $modes) {
		$this->saveH3M = true;

		$this->mapfile = $mapfile;
		
		$this->printoutput = ($modes & H3C_PRINTINFO);
		$this->save        = ($modes & H3C_SAVECAMDB);
		$this->exportmaps  = ($modes & H3C_EXPORTMAPS);
		$this->htmcache    = ($modes & H3C_CAMHTMCACHE);

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

		$this->GzipSplit();

		$this->filesizeC = filesize($this->mapfile);
		$this->filemtime = filemtime($this->mapfile);
		$this->filectime = filectime($this->mapfile);

		$this->length = strlen($this->mapdata);

		$this->mapfile = $path['basename']; //cut folder path, no needed from here
	}
	
	private function SaveCam() {

		$mapfile = mes($this->mapfile);

		$sql = "SELECT m.mapfile FROM heroes3_maps AS m WHERE m.mapfile='$mapfile' AND md5='".$this->md5hash."'";
		$mapdb = mgr($sql);
		if($mapdb) {
			return;
		}

		$this->mapimage = sanity_string($this->mapfilename);

		$mapdir = mes($this->mapfileinfo['dirname']);
		$camname = mes($this->cam_name);
		$camdesc = mes($this->description);

		$sql = "INSERT INTO heroes3_maps (heroes, campaign, id_campaign, `mapfile`, `mapdir`, `mapname`, `author`, `language`, `mapdesc`, `version`, `subversion`,
			`size`, `sizename`, `levels`, `diff`, `diffname`,
			`playersnum`, `playhuman`, `playai`, `teamnum`, `victory`, `loss`, `filecreate`, `filechanged`, `filesizeC`, `filesizeU`,
			`mapimage`, `md5`) VALUES
			(3, 1, 0, '$mapfile', '$mapdir/', '$camname', '', '".$this->language."', '$camdesc', '".$this->mapsversion."', 0,
				".$this->mapscountreal.", '', ".$this->mapversion.", '".$this->diffchoice."', '',
				0, 0, 0,
				0, 0, 0,
			FROM_UNIXTIME(".$this->filectime."), FROM_UNIXTIME(".$this->filemtime."), ".$this->filesizeC.", 0, '', '".$this->md5hash."')";
		
		
		$m = mq($sql);
		if(!$m) {
			file_append('sql.log', $sql.EOL.EOL);
		}
	}

	public function ReadCam() {
		if($this->filebad) {
			return;
		}

		$this->pos = 0;
		$this->CC = new CampaignConstants();
		$this->CS = new HeroesConstants();
		//$this->SC = new StringConvert();


		$this->camversion = $this->ReadUint32();
		$this->mapversion = $this->ReadUint8();
		$this->cam_name = $this->ReadString();
		
		//reset language which was set bases on mapname and let base it on description, which is usually longer
		$this->language = null;
		
		$this->description = $this->ReadString();

		if($this->camversion > CAMROE) {
			$this->diffchoice = $this->ReadUint8();
		}

		$music = $this->ReadUint8();


		$this->mapscount = $this->ScenarioCount[$this->mapversion - 1]; //array start with 0, reduct by 1
		

		if($this->printoutput) {
			echo 'Campaign name: '.$this->cam_name.'<br />
				Version: '.$this->camversion.'<br />
				Cam type: '.$this->mapversion.'<br />
				Map Count: '.$this->mapscount.'<br />';
			}

		for ($i = 0; $i < $this->mapscount; $i++) {
			$this->scenarios[$i] = $this->ReadScenario();
		}

		//====
		$totalsize = 0;
		$maps = [];
		for ($i = 0; $i < $this->mapscount; $i++) {

			if($this->printoutput) {
				echo $this->scenarios[$i]->mapname.' '.$this->scenarios[$i]->size.'<br />';
			}
			
			if($this->scenarios[$i]->size == 0) {
				continue;
			}

			$totalsize += $this->scenarios[$i]->size;
			$maps[$this->scenarios[$i]->mapname] = $this->scenarios[$i]->size;
			$this->mapscountreal++;
		}
		
		//show
		$this->mapdata = null;
		//vd($this);

		$this->readok = true;
	}

	private function ReadScenario() {
		$map = new Scenario();
		$map->mapname = $this->ReadString();
		$map->size = $this->ReadUint32(); //packed size


		if($this->mapversion == 19) { //unholly alliance
			$precond = $this->ReadUint16();
		}
		else {
			$precond = $this->ReadUint8();
		}

		$map->precondition = array();
		for ($i = 0; $i < 16; $i++) {
			if((1 << $i) & $precond) {
				$map->precondition[] = $i;
			}
		}


		$map->color = $this->ReadUint8();
		$map->diff = $this->ReadUint8();
		$map->text = $this->ReadString();

		$hasProlog = $this->ReadUint8();
		if($hasProlog) {
			$map->prolog['video'] = $this->ReadUint8();
			$map->prolog['music'] = $this->ReadUint8();
			$map->prolog['text'] = $this->ReadString();
		}
		$hasEpilog = $this->ReadUint8();
		if($hasEpilog) {
			$map->epilog['video'] = $this->ReadUint8();
			$map->epilog['music'] = $this->ReadUint8();
			$map->epilog['text'] = $this->ReadString();
		}


		$herokeep = $this->ReadUint8();
		$this->SkipBytes(19); //monster bits
		if($this->camversion < CAMSOD) {
			$this->SkipBytes(18 - 1); //artifacts bits
		}
		else {
			$this->SkipBytes(18); //artifacts bits
		}

		$bonus = [];
		$startoptions = $this->ReadUint8();
		switch($startoptions) {
			case 0:
				break;
			case 1:
				$bonus['pcolor'] = $this->ReadUint8();
				$bonus['num'] = $this->ReadUint8();
				for ($i = 0; $i < $bonus['num']; $i++) {
					$bonus[$i]['type'] = $this->ReadUint8();
					$bonus[$i]['text'] = '';

					switch ($bonus[$i]['type']) {
					/*
					SPELL, MONSTER, BUILDING,
					ARTIFACT, SPELL_SCROLL,
					PRIMARY_SKILL, SECONDARY_SKILL, RESOURCE,
					HEROES_FROM_PREVIOUS_SCENARIO, HERO
					*/
						case CAMBONUS::SPELL:
							$bonus[$i]['hero'] = $this->ReadUint16();
							$bonus[$i]['spell'] = $this->ReadUint8();
							$bonus[$i]['text'] = $this->GetHeroById($bonus[$i]['hero']).': '.$this->GetSpellById($bonus[$i]['spell']);
							break;

						case CAMBONUS::MONSTER:
							$bonus[$i]['hero'] = $this->ReadUint16();
							$bonus[$i]['monster'] = $this->ReadUint16();
							$bonus[$i]['count'] = $this->ReadUint16();
							$bonus[$i]['text'] = $this->GetHeroById($bonus[$i]['hero']).': '.$this->GetCreatureById($bonus[$i]['monster']).' ['.$bonus[$i]['count'].']';
							break;

						case CAMBONUS::BUILDING:
							$bonus[$i]['building'] = $this->ReadUint8();
							$bonus[$i]['text'] = $this->GetBuildingById($bonus[$i]['building']).' ('.$bonus[$i]['building'].')';
							break;

						case CAMBONUS::ARTIFACT:
							$bonus[$i]['hero'] = $this->ReadUint16();
							$bonus[$i]['artid'] = $this->ReadUint16();
							$bonus[$i]['text'] = $this->GetHeroById($bonus[$i]['hero']).': '.$this->GetArtifactById($bonus[$i]['artid']);
							break;

						case CAMBONUS::SPELL_SCROLL:
							$bonus[$i]['hero'] = $this->ReadUint16();
							$bonus[$i]['spell'] = $this->ReadUint8();
							$bonus[$i]['text'] = $this->GetHeroById($bonus[$i]['hero']).': '.$this->GetSpellById($bonus[$i]['spell']);
							break;

						case CAMBONUS::PRIMARY_SKILL:
							$bonus[$i]['hero'] = $this->ReadUint16();
							$bonus[$i]['attack'] = $this->ReadUint8();
							$bonus[$i]['def'] = $this->ReadUint8();
							$bonus[$i]['power'] = $this->ReadUint8();
							$bonus[$i]['knowledge'] = $this->ReadUint8();

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
							$bonus[$i]['hero'] = $this->ReadUint16();
							$bonus[$i]['skill'] = $this->ReadUint8();
							$bonus[$i]['level'] = $this->ReadUint8();
							$bonus[$i]['text'] = $this->GetHeroById($bonus[$i]['hero']).': '.$this->GetSecskillById($bonus[$i]['skill']).' ['.$this->GetSecskillLevelById($bonus[$i]['level']).']';
							break;

						case CAMBONUS::RESOURCE:
							$bonus[$i]['res'] = $this->ReadUint8();
							$bonus[$i]['count'] = $this->ReadUint32();
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
				$bonus['num'] = $this->ReadUint8();
				for ($i = 0; $i < $bonus['num']; $i++) {
					$bonus[$i]['type'] = CAMBONUS::HEROES_PREVIOUS;
					$bonus[$i]['pcolor'] = $this->ReadUint8();
					$bonus[$i]['scenario'] = $this->ReadUint8();
					$bonus[$i]['text'] = $bonus[$i]['pcolor'].' '.$bonus[$i]['scenario'];
				}
				break;

			//choose hero from previous scenario
			case 3:
				$bonus['num'] = $this->ReadUint8();
				for ($i = 0; $i < $bonus['num']; $i++) {
					$bonus[$i]['type'] = CAMBONUS::HERO;
					$bonus[$i]['pcolor'] = $this->ReadUint8();
					$bonus[$i]['hero'] = $this->ReadUint16();
					$bonus[$i]['text'] = $bonus[$i]['pcolor'].' '.$this->GetHeroById($bonus[$i]['hero']);
				}
				break;

			default:
				echo 'Corrupted H3C<br />';
				break;
		}

		$map->bonus = $bonus;

		//$map->travel;
		return $map;
	}

	public function ReadMaps() {
		if($this->filebad) {
			return;
		}
		
		$makehtm = $this->printoutput || $this->htmcache;
		
		$printinfo = '';

		if($makehtm) {
			$printinfo .= '<table>
				<tr>
					<th>Map File</th>
					<th>Version</th>
					<th>Map Name</th>
					<th style="width:500px;">Description</th>
					<th>Map Size</th>
					<th>Levels</th>
					<th>Players</th>
					<th>Teams</th>
					<th>Level Cap</th>
					<th>Victory</th>
					<th>Loss</th>
					<th>Bonus</th>
				</tr>';
		}
			

		$scenario_num = 0;

		if($this->exportmaps) {
		  $camdir = MAPDIRCAMEXP.sanity_string(str_ireplace('.h3c', '', $this->mapfile)).'/';
			if(!file_exists($camdir)) {
				mkdir($camdir);
			}
			foreach ($this->gzipparts as $k => $part) {
				if($k == 0) {
					continue;
				}
				$mapname = $this->scenarios[$k - 1]->mapname;
				if($mapname == '') {
					continue;
				}
				//echo "$k : $mapname - ".strlen($part).'<br />';
				file_write($camdir.$mapname, $part);
			}
		}
		
		//echo '<br /><br /> MAPS<br />';
		
		for ($i = 1; $i < $this->gzipcount; $i++) {

			while($this->scenarios[$scenario_num]->size == 0) {
				$scenario_num++;
			}

			$mapfile = $this->scenarios[$scenario_num]->mapname; //not good

			$map = new H3MAPSCAN($mapfile, H3M_WEBMODE | H3M_BASICONLY, gzdecode($this->gzipparts[$i]));
			$map->ReadMap();
			$headerInfo = $map->MapHeaderInfo();

			$this->mapsversion = $map->GetMapVersion();
			
			if($makehtm) {
				$bonus = $this->scenarios[$scenario_num]->bonus;
				$scenario_num++;
				$bonuses = '';

				for ($b = 0; $b < $bonus['num']; $b++) {
					if($b > 0) {
						$bonuses .= '<br />';
					}
					$bonuses .= $this->GetCamBonusById($bonus[$b]['type']).' - '.$bonus[$b]['text'];
				}

				//echo $mapfile.' - '.$i.' '.strlen($this->gzipparts[$i]).'<br />';
			
				$printinfo .= '<tr>';
				foreach($headerInfo as $hi) {
					$printinfo .= '<td>'.$hi.'</td>';
				}
				$printinfo .= '<td>'.$bonuses.'</td>';
				$printinfo .= '</tr>';
			}
		}
		
		if($makehtm) {
			$printinfo .= '</table>';
		}
		
		if($this->printoutput) {
			echo $printinfo;
		}

		if($this->htmcache) {
			file_write(MAPDIRINFO.str_ireplace('.h3c', '.htm', $this->mapfile).'.gz', gzencode($printinfo));
		}
		
		
		if($this->save) {
			$this->SaveCam();
		}
	}


	private function GetCamBonusById($id) {
		return FromArray($id, $this->CC->cambonus);
	}

	private function GetArtifactById($artid) {
		return FromArray($artid, $this->CS->Artefacts);
	}

	private function GetCreatureById($monid) {
		//$this->mapversions == $this::HOTA &&
		if($monid >= HOTAMONSTERIDS) {
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
		$this->gzipparts = array('');

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

		$this->mapdata = gzdecode($this->gzipparts[0]);
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

	private function ReadInt8(){
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

	private function ReadString($length = -1){
		$res = '';
		if($this->pos >= $this->length || $this->pos < 0){
			dbglog();
			$this->mapdata = null;
			$this->CC = null;
			//vd($this);
			die('Bad string pos '.$this->pos);
			return;
		}

		if($length == -1){
			$length = $this->ReadUint32();
			if($length == 0) return $res;
			if($length > 100000 || $length < 0) {
				dbglog();
				$this->mapdata = null;
				$this->CC = null;
				//vd($this->objects);
				vd($this);
				//rename(CAMDIR.$this->mapfile, 'mapsx/'.$this->mapfile);
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
				chr(0xc0), chr(0xc5), chr(0xc7), chr(0xce), chr(0xd0), chr(0xde), chr(0xdf),
				chr(0xe0), chr(0xe5), chr(0xe7), chr(0xee), chr(0xf0), chr(0xfe), chr(0xff)
			),
			//polish
			'pl' => array(
				chr(0xa3), chr(0xa5), chr(0xaf), chr(0xca), chr(0xd1),
				chr(0xb3), chr(0xb9), chr(0xbf), chr(0xea), chr(0xf1)
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

	public $travel;
	public $bonus;
}


?>
