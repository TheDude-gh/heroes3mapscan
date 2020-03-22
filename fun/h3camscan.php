<?php

const CAMDIR = './mapscam/';
const CAMDIRGZ = CAMDIR.'./gzip/';
const CAMDIREXP = CAMDIR.'./exp/';

const CAMROE = 4;
const CAMAB = 5;
const CAMSOD = 6;
const CAMWOG = 6;

class H3CAMSCAN {
	const IMGSIZE = 576;
	const ROE  = 0x0e;
	const AB   = 0x15;
	const SOD  = 0x1c;
	const WOG  = 0x33;
	const HOTA = 0x20;

	private $version = '';
	private $versionname = '';
	private $cam_name = '';
	private $description = '';


	//CAM
	private $mapversion = 0;
	private $diffchoice = 0;
	private $mapscount = 0;
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

	private $pos = 0;
	private $length = 0;

	private $filemtime = '';
	private $filectime = '';
	private $filesizeC = 0;
	private $filesizeU = 0;
	private $filebad = false;
	public $readok = false;
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

	public function __construct($mapfile, $onlyunzip = false) {

		$this->mapfile = $mapfile;
		$this->onlyunzip = $onlyunzip;

		$path = pathinfo($this->mapfile);
		$this->mapfileinfo = $path;

		$this->mapfileout = CAMDIREXP.$path['filename'].'.'.$path['extension'];

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

		/*if(!$h3mfileun_exists || filemtime($this->mapfileout) < filemtime($this->mapfile)) {
			$this->Ungzip();
			if($this->mapdata === '') {
				echo $this->mapfile.' could not be uncompressed'.ENVE;
				return;
			}

			if($this->onlyunzip) {
				$this->filebad = true;
				return;
			}

			file_write($this->mapfileout, $this->mapdata);
		}

		if(!file_exists($this->mapfileout)) {
			echo $this->mapfileout.' does not exists!'.ENVE;
			$this->filebad = true;
			return;
		}

		$this->filesizeC = filesize($this->mapfile);
		$this->filesizeU = filesize($this->mapfileout);
		$this->filemtime = filemtime($this->mapfile);
		$this->filectime = filectime($this->mapfile);*/

		/*if($this->mapdata == '') {
			$this->mapdata = file_get_contents($this->mapfileout);
		}*/
		$this->length = strlen($this->mapdata);

		$this->mapfile = $path['basename']; //cut folder path, no needed from here
	}

	//check, if map is compressed or not, compressed starts with 1F 8B 08 00
	private function IsGZIP() {
		$fh = fopen($this->mapfile, 'r');
		$buffer = fgets($fh, 5);
		fclose($fh);
		$buffer = unpack('Lhead', $buffer);
		if(dechex($buffer['head'] & 0xffffff) == '88b1f') {
			$this->isGzip = true;
		}
		else {
			$this->isGzip = false;
		}
		return $this->isGzip;
	}

	public function ReadCam() {
		if($this->filebad) {
			return;
		}

		$this->pos = 0;
		$this->CC = new CampaignConstants();
		$this->CS = new HeroesConstants();
		$this->SC = new StringConvert();



		$this->version = $this->ReadUint32();
		$this->mapversion = $this->ReadUint8() - 1;
		$this->cam_name = $this->ReadString();
		$this->description = $this->ReadString();

		if($this->version > CAMROE) {
			$this->diffchoice = $this->ReadUint8();
		}

		$music = $this->ReadUint8();


		//$map_count = something($this->mapversion);
		$this->mapscount = $this->ScenarioCount[$this->mapversion]; //minumum is 3 anyway
		//$this->mapscount = 8;


		echo 'Campaign name: '.$this->cam_name.'<br />
			Version: '.$this->version.'<br />
			Cam type: '.$this->mapversion.'<br />
			Map Count: '.$this->mapscount.'<br />';

		for ($i = 0; $i < $this->mapscount; $i++) {
			$this->scenarios[$i] = $this->ReadScenario();
		}

		//====
		$totalsize = 0;
		$maps = [];
		for ($i = 0; $i < $this->mapscount; $i++) {

			echo $this->scenarios[$i]->mapname.' '.$this->scenarios[$i]->size.'<br />';

			if($this->scenarios[$i]->size == 0) {
				continue;
			}

			$totalsize += $this->scenarios[$i]->size;
			$maps[$this->scenarios[$i]->mapname] = $this->scenarios[$i]->size;
		}


		/*
		//extract compressed maps
		echo '<br /><br />';
		if(!file_exists(CAMDIRIMG.$this->cam_name)) {
			mkdir(CAMDIRIMG.$this->cam_name);
		}
		$undata = file_get_contents(CAMDIR.$this->mapfile);
		$mapoffset = strlen($undata) - $totalsize;
		$curpos = $mapoffset;

		foreach ($maps as $name => $size) {
			file_write(CAMDIRIMG.$this->cam_name.'/'.$name, substr($undata, $curpos, $size));
			$curpos += $size;

			echo "$name -> $curpos, $size<br />";
			$this->pvar($curpos);
		}*/

		//$this->ppos();
		//vd($totalsize);

		//show
		$this->mapdata = null;
		//vd($this);

		$this->readok = true;
	}

	private function ReadScenario() {
		$map = new Scenario();
		$map->mapname = $this->ReadString();
		$map->size = $this->ReadUint32(); //packed size


		if($this->mapversion == 18) { //unholly alliance
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
		if($this->version < CAMSOD) {
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

		echo '<table>
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
		$scenario_num = 0;
		for ($i = 1; $i < $this->gzipcount; $i++) {

			while($this->scenarios[$scenario_num]->size == 0) {
				$scenario_num++;
			}

			$mapfile = $this->scenarios[$scenario_num]->mapname; //not good
			$bonus = $this->scenarios[$scenario_num]->bonus;
			$scenario_num++;
			$bonuses = '';

			for ($b = 0; $b < $bonus['num']; $b++) {
				if($b > 0) {
					$bonuses .= '<br />';
				}
				$bonuses .= $this->GetCamBonusById($bonus[$b]['type']).' - '.$bonus[$b]['text'];
			}
			//vd($bonus);

			$map = new H3MAPSCAN($mapfile, false, true, gzdecode($this->gzipparts[$i]));
			$map->PrintStateSet(true, false);
			$map->ReadMap();
			$headerInfo = $map->MapHeaderInfo();

			//vd($headerInfo);

			echo '<tr>';
			foreach($headerInfo as $hi) {
				echo '<td>'.$hi.'</td>';
			}
			echo '<td>'.$bonuses.'</td>';
			echo '</tr>';
		}
		echo '</table>';
	}


	private function GetCamBonusById($id) {
		return FromArray($id, $this->CC->cambonus);
	}

	private function GetArtifactById($artid) {
		return FromArray($artid, $this->CS->Artefacts);
	}

	private function GetCreatureById($monid) {
		if($this->version == $this::HOTA && $monid >= HOTAMONSTERIDS) {
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

	private function GzipSplit() {
		//campaign files are several gzipped files together, split them first
		//first is campaign header, rest are maps
		$gzmark = chr(0x1F).chr(0x8B).chr(0x08).chr(0x00); //00088B1F

		$g = 0;
		$this->gzipparts = array('');

		$gzfile = file_get_contents($this->mapfile);

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

		//$this->mapdata = gzuncompress($this->gzipparts[0]);
		$this->mapdata = gzdecode($this->gzipparts[0]);

		/*$buffer_size = 1024;
		$gzfile = fopen($this->mapfile, "rb");
		$first = true;

		while(!feof($gzfile)) {
			$gzpart = fread($gzfile, $buffer_size);

			$gzmarkpos = strpos($gzpart, $gzmark);
			if(!$first && $gzmarkpos !== false) {
				vd($gzmarkpos);
				$this->gzipparts[$g] .= substr($gzpart, 0, $gzmarkpos);
				$g++;
				$this->gzipparts[$g] = substr($gzpart, $gzmarkpos);
			}
			else {
				$this->gzipparts[$g] .= $gzpart;
				$first = false;
			}
		}
		$this->gzipcount = $g + 1;
		fclose($gzfile);*/

		//vd($this->gzipcount);

		//save map files
		if($this->saveH3M) {
			foreach ($this->gzipparts as $k => $part) {
				echo "$k : ".strlen($part).'<br />';
				file_write(CAMDIRGZ.$k.'.h3m', $part);
			}
		}
	}

	private function Ungzip() {
		// Raising this value may increase performance
		$buffer_size = 4096; // read 4 kB at a time

		//get uncompressed size from gzip
		$hfile = fopen($this->mapfile, "rb");
		fseek($hfile, -4, SEEK_END);
		$buf = fread($hfile, 4);
		$unpacked = unpack("V", $buf);
		$uncompressedSize = end($unpacked);
		fclose($hfile);

		//check size, we will presume no map is bigger than 10 MB, bigger size means gzip file is corrupt
		if($uncompressedSize > 10485760) {
			echo 'H3M file seems to be corrupted<br />';
			$this->filebad = true;
			return;
		}

		// Open our files (in binary mode)
		$gzfile = gzopen($this->mapfile, 'rb');

		// Keep repeating until the end of the input file
		while(!gzeof($gzfile)) {
			// Read buffer-size bytes
			// Both fwrite and gzread and binary-safe
			$this->mapdata .= gzread($gzfile, $buffer_size);
		}

		// Files are done, close files
		gzclose($gzfile);
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
			$this->terrain = null;
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
				$this->terrain = null;
				$this->CC = null;
				$this->objTemplates = null;
				//vd($this->objects);
				vd($this);
				//rename(CAMDIR.$this->mapfile, 'mapsx/'.$this->mapfile);
				die('Too long string '.$length);
				return;
			}
			$res = substr($this->mapdata, $this->pos, $length);
			$this->pos += $length;

			//return $res;
			return $this->SC->Convert($res);
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
		return $this->SC->Convert($res);
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
