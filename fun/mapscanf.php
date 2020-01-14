<?php

class H3MAPSCAN {
	const IMGSIZE = 576;
	const ROE = 0x0e;
	const AB  = 0x15;
	const SOD = 0x1c;
	const WOG = 0x33;
	const HOTA = 0x20;

	private $version = '';
	private $versionname = '';
	private $map_name = '';
	private $description = '';
	private $language = 0;
	private $underground = 0;
	private $map_diff = -1;
	private $map_diffname = '';
	private $hero_any_onmap = 0;
	private $hero_levelcap = 0;
	private $teamscount;
	private $teams = array();
	private $victoryCond = array();
	private $lossCond = array();
	private $victoryInfo = '';
	private $lossInfo = '';
	
	private $rumorsCount = 0;
	private $rumors = array();
	private $events = array();
	
	private $allowedArtifacts = array();
	private $disabledArtifacts = array();
	private $allowedSpells = array();
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

	private $mapobjects = array(); //heroes, towns and monsters
	
	private $map_size = 0;
	private $map_sizename = '';
	private $terrain = array();

	private $img;
	private $imgcolors = array();
	
	private $name = '';
	
	private $isGzip;
	private $mapdata = '';
	private $mapfile = '';
	private $mapfilename = '';
	private $mapfileout = '';
	private $mapimage; //mapfile name for DB
	
	private $players = array();
	private $mapplayersnum = 0;
	private $mapplayershuman = 0;
	private $mapplayersai = 0;
	private $playersnum = 8;
	private $playerscolours = array('Red', 'Blue', 'Tan', 'Green', 'Orange', 'Purple', 'Teal', 'Pink');
	
	private $CS;
	
	private $printoutput = false;
	private $webmode = true;
	private $buildMapImage = true;

	private $debug;
	
	private $pos = 0;
	private $length = 0;
	
	private $filemtime = '';
	private $filectime = '';
	private $filesizeC = 0;
	private $filesizeU = 0;
	private $filebad = false;
	
	private $save = false; //save maps to db
	

	public function __construct($mapfile, $webmode) {
		$this->webmode = $webmode;
		$this->printoutput = $webmode;

		$this->mapfile = $mapfile;
		$path = pathinfo($this->mapfile);

		//map is alrady uncompressed
		
		if($this->IsGZIP() == false) { //strpos($this->mapfile, '_ugz')
			$this->mapfileout = $this->mapfile;
		}
		else {
			if(!file_exists($this->mapfile)) {
				echo $this->mapfile.' does not exists!'.ENVE;
				return false;
			}

			$this->mapfileout = MAPDIREXP.$path['filename'].'.'.$path['extension'];
		}
		
		$this->mapfilename = $path['filename'];
		
		if(!file_exists($this->mapfileout) || filemtime($this->mapfileout) < filemtime($this->mapfile)) {
			$this->Ungzip();
			if($this->mapdata === '') {
				//echo $this->mapfile.' could not be uncompressed'.ENVE;
				return false;
			}
			//echo $this->mapfile.' was uncompressed'.ENVE;
			file_write($this->mapfileout, $this->mapdata);
		}
		
		if(!file_exists($this->mapfileout)) {
			echo $this->mapfileout.' does not exists!'.ENVE;
			$this->filebad = true;
			return false;
		}

		$this->filesizeC = filesize($this->mapfile);
		$this->filesizeU = filesize($this->mapfileout);
		$this->filemtime = filemtime($this->mapfile);
		$this->filectime = filectime($this->mapfile);
		
		$this->mapdata = file_get_contents($this->mapfileout);
		$this->length = strlen($this->mapdata);
		
		//$this->ReadMap();
	}

	//check, if map is compressed or not, compressed starts with 1F 8B 08 00
	private function IsGZIP() {
		$fh = fopen($this->mapfile, 'r');
		$buffer = fgets($fh, 5);
		fclose($fh);
		$buffer = unpack('Lhead', $buffer);
		if(dechex($buffer['head']) == '88b1f') {
			$this->isGzip = true;
		}
		else {
			$this->isGzip = false;
		}
		return $this->isGzip;
	}
	
	public function SetSaveMap($value) {
		$this->save = $value;
	}
	
	public function SaveMap() {
		$mappi = pathinfo($this->mapfile);
		$mapfile = mes($mappi['basename']);


		$sql = "SELECT m.mapfile FROM heroes_maps AS m WHERE m.mapfile='$mapfile'";
		$mapdb = mgr($sql);
		if($mapdb) {
			return;
		}

		$mapdir = mes($mappi['dirname']);
		$mapname = mes($this->map_name);
		$mapdesc = mes($this->description);
    $mapimage = mes($this->mapimage);

		$sql = "INSERT INTO heroes_maps (`mapfile`, `mapdir`, `mapname`, `mapdesc`, `version`, `size`, `sizename`, `levels`, `diff`,
			`playersnum`, `playhuman`, `playai`, `teamnum`, `victory`, `loss`, `filecreate`, `filechanged`, `filesizeC`, `filesizeU`,
			`mapimage`) VALUES
			('$mapfile', '$mapdir/', '$mapname', '$mapdesc', '".$this->versionname."', ".$this->map_size.", '".$this->map_sizename."',
				".$this->underground.", '".$this->map_diffname."', ".$this->mapplayersnum.", ".$this->mapplayershuman.", ".$this->mapplayersai.",
				 ".$this->teamscount.", ".$this->victoryCond['type'].", ".$this->lossCond['type'].",
			FROM_UNIXTIME(".$this->filectime."), FROM_UNIXTIME(".$this->filemtime."), ".$this->filesizeC.", ".$this->filesizeU.", '".$mapimage."')";
		mq($sql);
	}
	
	public function PrintStateSet($enable, $mapbuild = true) {
		$this->printoutput = $enable ? true : false;
		$this->buildMapImage = $mapbuild ? true : false;
	}
	
	public function PrintMapInfo() {
		$this->ParseFinish();

		echo '<table>
				<tr><td>Name</td><td>'.$this->map_name.'</td></tr>
				<tr><td>Description</td><td>'.nl2br($this->description).'</td></tr>
				<tr><td>Version</td><td>'.$this->versionname.'</td></tr>
				<tr><td>Size</td><td>'.$this->map_sizename.'</td></tr>
				<tr><td>Levels</td><td>'.($this->underground ? 2 : 1).'</td></tr>
				<tr><td>Difficulty</td><td>'.$this->map_diffname.'</td></tr>
				<tr><td>Victory</td><td>'.$this->victoryInfo.'</td></tr>
				<tr><td>Loss</td><td>'.$this->lossInfo.'</td></tr>
				<tr><td>Players count</td><td>'.$this->mapplayersnum.', '.$this->mapplayershuman.'/'.$this->mapplayersai.'</td></tr>
				<tr><td>Team count</td><td>'.$this->teamscount.'</td></tr>
				<tr><td>Heroes level cap</td><td>'.$this->hero_levelcap.'</td></tr>
			</table>';
			
			echo '<table class="smalltable">
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
				if(!$player['human'] && !$player['ai']) continue;

				echo '<tr>
						<td>'.($k+1).'</td>
						<td>'.$this->playerscolours[$k].'</td>
						<td>'.$player['human'].'</td>
						<td>'.$player['ai'].'</td>
						<td>'.$this->GetBehaviour($player['behaviour']).'</td>
						<td>'.$this->teams[$k].'</td>
						<td>'.$player['townsOwned'].'</td>
						<td>'.$player['towns_allowed'].'</td>
						<td>'.$player['IsRandomTown'].'</td>
						<td>'.$player['HasMainTown'].'</td>
						<td>'.$player['HeroAtMain'].'</td>
						<td>'.$player['GenerateHero'].'</td>
						<td>'.$player['townpos']->GetCoords().'</td>
						<td>'.$player['RandomHero'].'</td>
						<td>'.$player['MainHeroName'].'</td>
						<td>'.$player['HeroCount'].'</td>
						<td>'.implode($player['HeroFace'], ', ').'</td>
						<td>'.implode($player['HeroName'], ', ').'</td>
					</tr>';
			}
			echo '</table>';

			$this->DisplayMap();

	 		$n = 0;
			echo '
				<table class="smalltable">
					<tr><th>#</th><th colspan="2">Unavailable heroes</th></tr>';
			foreach($this->disabledHeroes as $class => $heroes) {
				echo '<tr>
					<td>'.(++$n).'</td>
					<td>'.$class.'</td>
					<td>'.implode($heroes, ', ').'</td>
				</tr>';
			}
			echo '</table>';
			
			
			echo 'Custom heroes
				<table class="smalltable">
					<tr>
						<th>#</th>
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
				$skills = array();
				foreach($hero['skills'] as $skill) {
					$skills[] = $skill[0].': '.$skill[1];
				}
				
				echo '<tr>
					<td>'.($k+1).'</td>
					<td>'.$hero['name'].' ('.$hero['defname'].')</td>
					<td>'.$hero['mask'].'</td>
					<td>'.$hero['Exp'].'</td>
					<td>'.$hero['sex'].'</td>
					<td>'.nl2br($hero['bio']).'</td>
					<td>'.implode($hero['priskills'], ', ').'</td>
					<td>'.implode($skills, '<br />').'</td>
					<td>'.implode($hero['spells'], ', ').'</td>
					<td>'.implode($hero['artifacts'], '<br />').'</td>
				</tr>';
			}
			echo '</table>';
			
			echo '
				<table class="smalltable">
					<tr><th colspan="3">Rumors</th></tr>';
			if(empty($this->rumors)) echo '<tr><td colspan="3">None</td></tr>';
			
			foreach($this->rumors as $k => $rumor) {
				echo '<tr>
				<td>'.($k+1).'</td>
					<td>'.$rumor['name'].'</td>
					<td>'.$rumor['desc'].'</td>
				</tr>';
			}
			echo '</table>';
			
			
			sort($this->disabledArtifacts);
			echo '
				<table class="smalltable">
					<tr><th>#</th><th>Disabled Artifacts</th></tr>';
			foreach($this->disabledArtifacts as $k => $art) {
				echo '<tr>
				<td>'.($k+1).'</td>
					<td>'.$art.'</td>
				</tr>';
			}
			echo '</table>';
			
			sort($this->disabledSpells);
			echo '
				<table class="smalltable">
					<tr><th>#</th><th>Disabled Spells</th></tr>';
			foreach($this->disabledSpells as $k => $spell) {
				echo '<tr>
				<td>'.($k+1).'</td>
					<td>'.$spell.'</td>
				</tr>';
			}
			echo '</table>';
			
			sort($this->disabledSkills);
			echo '
				<table class="smalltable">
					<tr><th>#</th><th>Disabled Skills</th></tr>';
			foreach($this->disabledSkills as $k => $spell) {
				echo '<tr>
				<td>'.($k+1).'</td>
					<td>'.$spell.'</td>
				</tr>';
			}
			echo '</table>';
			
			//if($this->version == $this::HOTA) return;

			echo '<br />Templates: '.$this->objTemplatesNum.'<br />';
			/*echo '<table>';
			foreach($this->objTemplates as $temp) {
				echo '<tr><td>ID:'.$temp['id'].', SubID:'.$temp['subid'].'<td>'.$temp['animation'].'</td><td>'.nl2br($temp['mask']).'</td></tr>';
			}
			echo '</table>';*/
			
			echo '<br />Objects: '.$this->objectsNum.'<br />';
			
			usort($this->events, 'EventSortByDate');
			echo '<br />Events: ';
			echo '
				<table class="smalltable">
					<tr><th>#</th><th>Name</th><th>Human</th><th>AI</th><th>Players</th><th>First</th><th>Interval</th>
						<th>Resources</th><th>Message</th></tr>';
			foreach($this->events as $k => $event) {
				$eres = array();
				foreach($event['resources'] as $r => $res) {
					if($res == 0) continue;
					$eres[] = $res.' '.$this->GetResource($r);
				}
				
				echo '<tr>
					<td>'.($k+1).'</td>
					<td>'.$event['name'].'</td>
					<td>'.$event['humanAble'].'</td>
					<td>'.$event['aiAble'].'</td>
					<td>'.ByteBin($event['players']).'</td>
					<td>'.$event['first'].'</td>
					<td>'.$event['interval'].'</td>
					<td>'.implode($eres, ',').'</td>
					<td>'.nl2br($event['message']).'</td>
				</tr>';
			}
			echo '</table>';
			
			echo '<br />Objects count: '.count($this->objects_unique);
			asort($this->objects_unique);
			$n = 0;
			echo '
				<table class="smalltable">
					<tr><th>#</th><th>ID</th><th>Name</th><th>Count</th></tr>';
			foreach($this->objects_unique as $objid => $obju) {
				echo '<tr>
					<td>'.(++$n).'</td>
					<td>'.$objid.'</td>
					<td>'.$obju['name'].'</td>
					<td>'.$obju['count'].'</td>
				</tr>';
			}
			echo '</table>';
			
	}
	
	public function ReadMap() {
		if($this->filebad) return;
		
		$this->pos = 0;
		$this->CS = new HeroesConstants();
	
		$this->version = $this->ReadUint32();
		if($this->version == $this::HOTA) {
			$this->SkipBytes(4); //zeroes so far
		}
		
		$this->hero_any_onmap = $this->ReadUint8(); //hero presenc
		$this->map_size = $this->ReadUint32();
		$this->underground = $this->ReadUint8();
		$this->map_name = $this->ReadString();
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
		
		// Free Heroes
		$this->FreeHeroes();

		$this->SkipBytes(31); //unused space
		
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
		
		if($this->printoutput && $this->webmode) {
			$this->PrintMapInfo();
		}
		
		if($this->webmode == false) {
			$this->DisplayMap();
		}

		if($this->save == true){
			$this->SaveMap();
		}
	}
		
	private function ReadPlayersData() {
		//players
		for($i = 0; $i < $this->playersnum; $i++){
			$this->players[$i]['human'] = $this->ReadUint8();
			$this->players[$i]['ai'] = $this->ReadUint8();
			
			//def values
			$this->players[$i]['HeroAtMain'] = 1;
			$this->players[$i]['GenerateHero'] = 0;
			$this->players[$i]['HeroFace'] = array();
			$this->players[$i]['HeroName'] = array();
			$this->players[$i]['HeroCount'] = 0;
			$this->players[$i]['behaviour'] = '';
			$this->players[$i]['townOwned_isSet'] = '';
			$this->players[$i]['townsOwned'] = 0;
			
			//nobody can play this colour
			if($this->players[$i]['human'] == 0 &&	$this->players[$i]['ai'] == 0){
				/*switch($this->version){
					case $this::SOD:
					case $this::WOG:
						$this->SkipBytes(13);
						break;
					case $this::AB:
						$this->SkipBytes(12);
						break;
					case $this::ROE:
						$this->SkipBytes(6);
						break;
				}*/
				//return;
			}
			else {
				if($this->players[$i]['human']) $this->mapplayershuman++;
				if($this->players[$i]['ai']) $this->mapplayersai++;
				$this->mapplayersnum++;
			}
			
			$this->players[$i]['behaviour'] = $this->ReadUint8();

			if($this->version >= $this::SOD) { // || $this->version == $this::WOG
				$this->players[$i]['townOwned_isSet'] = $this->ReadUint8();
			}
			else {
				$this->players[$i]['townOwned_isSet'] = -1;
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
						$towns_allowed[] = FromArray($n, $this->CS->TownType);
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
				
				$townpos = new MApCoords($this->ReadUint8(), $this->ReadUint8(), $this->ReadUint8());
			}
			else {
				$townpos = new MApCoords(-1, -1, -1);
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
				$this->players[$i]['MainHeroName'] = FromArray($heroid, $this->CS->Heroes);
			}

			if($this->version != $this::ROE) {
				$this->players[$i]['ub'] = $this->ReadUint8(); //placeholder

				$herocount = $this->ReadUint8();
				$this->players[$i]['HeroCount'] = $herocount;

				$this->SkipBytes(3);
				for($j = 0; $j < $herocount; $j++) {
					$heroid = $this->ReadUint8();
					$heroname = $this->ReadString();
					if(!$heroname) {
						$heroname = FromArray($heroid, $this->CS->Heroes);
					}
					$this->players[$i]['HeroFace'][] = $heroid;
					$this->players[$i]['HeroName'][] = $heroname;
				}
			}
			else {
				$this->players[$i]['ub'] = -1;
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
		//$heroes = $this->version == $this::ROE ? 16 : 20; //156 heroes
		for($i = 0; $i < $heroes; $i++){
			$byte = $this->ReadUint8();

			for($n = 0; $n < 8; $n++){
				$idh = $i * 8 + $n;
				if($idh >= $limit) break;
				if(($byte & (1 << $n)) == 0){
					$this->disabledHeroes[$this->GetHeroClassById($i)][] = $this->GetHeroById($idh);
				}
			}
		}

		if($this->version > $this::ROE) {
			$placeholders = $this->ReadUint32(); //no use
			$this->SkipBytes($placeholders);
		}

		if($this->version >= $this::SOD) {
			$heroCustomCount = $this->ReadUint8();
			
			for($i = 0; $i < $heroCustomCount; $i++) {
				$hero['id'] = $this->ReadUint8();
				$hero['face'] = $this->ReadUint8();
				//$hero['defname'] = $this->GetHeroByID($hero['id']);
				$hero['name'] = $this->Readstring();
				$hero['mask'] = $this->ReadUint8();
				$this->customHeroes[$hero['id']] = $hero;
			}
		}
	}

	private function Artifacts() {
		// Reading allowed artifacts:	17 or 18 bytes
		//1=disabled, 0=enabled
		if($this->version != $this::ROE) {
			$bytes = $this->version == $this::AB ? 17 : 18;
			if($this->version == $this::HOTA) {
				$this->ReadUint32(); //probably version
				$artcount = $this->ReadUint32(); //artifact id count
				$bytes = ceil($artcount / 8); //21
			}
			
			for($i = 0; $i < $bytes; $i++) {
				$byte = $this->ReadUint8(); //ids of artifacts

				 for($n = 0; $n < 8; $n++) {
					if(($byte & (1 << $n)) != 0) {
						$ida = $i * 8 + $n;
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
				//$this->allowedSpells[] = sprintf('%08b ', $this->ReadUint8() ^ 0xff);
				for($n = 0; $n < 8; $n++) {
					if(($byte & (1 << $n)) != 0) {
						$this->disabledSpells[] = $this->GetSpellById($i * 8 + $n);
					}
				}
			}
			// Allowed hero's abilities (4 bytes)
			for($i = 0; $i < SECSKILL_BYTE; $i++) {
				$byte = $this->ReadUint8(); //ids of skills
				//$this->allowedSkills[] = sprintf('%08b ', $this->ReadUint8() ^ 0xff);
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
		if($this->victoryCond['type'] == 0xff) {
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
				$this->victoryInfo = 'Accumulate creatures, '.$this->victoryCond['unit'].', count '.$this->victoryCond['unit_count'];
				break;
			case VICTORY::ACCRESOURCES: // 02 - Accumulate resources
				$this->victoryCond['name'] = 'Accumulate resources';
				$this->victoryCond['resource'] = $this->ReadUint8();
				// 0 - Wood	 1 - Mercury	2 - Ore	3 - Sulfur	4 - Crystal	5 - Gems	6 - Gold
				$this->victoryCond['resource_count'] = $this->ReadUint32();
				$this->victoryInfo = 'Accumulate resources: '.$this->GetResource($this->victoryCond['resource'])
					.', count: '.$this->victoryCond['resource_count'];
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
		
		if($this->victoryCond['AI_cancomplete']) $this->victoryInfo .= '<br />AI can complete condition too';
		if($this->victoryCond['Normal_end']) $this->victoryInfo .= '<br />Or standard end';
	}

	public function LossCondition(){
		// 1	Special loss condition
		$this->lossCond['type'] = $this->ReadUint8();
		if($this->lossCond['type'] == 0xff) {
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

	public function Teams(){
		$this->teamscount = $this->ReadUint8();
		for($i = 0; $i < $this->playersnum; $i++){
			$this->teams[$i] = ($this->teamscount != 0) ? $this->ReadUint8() : 0;
		}
	}

	private function ReadPredefinedHeroes() {

		$limit = HEROES_QUANTITY;
		if($this->version == $this::HOTA) {
			//$limit = HEROES_QUANTITY_HOTA;
			$limit = $this->ReadUint32(); //hero count
		}

		switch($this->version) {
			case $this::SOD:
			case $this::WOG:
			case $this::HOTA:
				// Disposed heroes
				for($i = 0; $i < $limit; $i++) {
					$hero = array();
					$hero['id'] = $i;
					$hero['name'] = '';
					$hero['mask'] = 0;
					$hero['face'] = 0;

					$heroc = FromArray($hero['id'], $this->customHeroes, false);
					if($heroc) {
						$hero['name'] = $heroc['name'];
						$hero['mask'] = ByteBin($heroc['mask']);
						$hero['face'] = $heroc['face'];
					}
					
					
					$hero['defname'] = $this->GetHeroById($i);
					$hero['Exp'] = 0;
					$hero['sex'] = '';
					$hero['bio'] = '';
					$hero['priskills'] = array();
					$hero['skills'] = array();
					$hero['spells'] = array();
					$hero['artifacts'] = array();
					
					$custom = $this->ReadUint8();
					if(!$custom) continue;
					

					$hasExp = $this->ReadUint8();
					if($hasExp) {
						$hero['Exp'] = $this->ReadUint32();
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

					$hero['artifacts'] = $this->LoadArtifactsOfHero();

					$hasCustomBio = $this->ReadUint8();
					if($hasCustomBio) {
						$hero['bio'] = $this->ReadString();
					}

					// 0xFF is default, 00 male, 01 female
					$herosex = $this->ReadUint8();					
					$hero['sex'] = $herosex == 0xff ? 'Default' : ($herosex ? 'Female' : 'Male');

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
		
		if($this->version > $this::ROE) {
			$hero['identifier'] = $this->ReadUint32();
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

		$hero['skills'] = array();

		$hasSecSkills = $this->ReadUint8();
		if($hasSecSkills) {
			$howMany = $this->ReadUint32();
			for($yy = 0; $yy < $howMany; $yy++) {
				$hero['skills'][] = array('skillid' => $this->ReadUint8(), 'level' => $this->ReadUint8());
			}
		}

		$hero['stack'] = array();
		$hasGarison = $this->ReadUint8();
		if($hasGarison) {
			$hero['stack'] = $this->ReadCreatureSet(7);
		}

		$hero['formation'] = $this->ReadUint8();
		$hero['artifacts'] = $this->LoadArtifactsOfHero();

		$hero['patrol'] = $this->ReadUint8();
		if($hero['patrol'] == 0xff) {
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
			$hero['sex'] = $herosex == 0xff ? 'Default' : ($herosex ? 'Female' : 'Male');
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
			for($pom = 0; $pom < 16; $pom++) {
				$this->LoadArtifactToSlot($artifacts, $pom);
			}

			// misc5 art //17
			if($this->version >= $this::SOD) {
				$this->LoadArtifactToSlot($artifacts, 16); //ArtifactPosition::MACH4
			}

			$this->LoadArtifactToSlot($artifacts, 17); //ArtifactPosition::SPELLBOOK

			// 19 //???what is that? gap in file or what? - it's probably fifth slot..
			if($this->version > $this::ROE) {
				$this->LoadArtifactToSlot($artifacts, 18); //ArtifactPosition::MISC5
			}
			else {
				$this->SkipBytes(1);
			}

			// bag artifacts //20
			// number of artifacts in hero's bag
			$amount = $this->ReadUint16();
			for($i = 0; $i < $amount; $i++) {
				$this->LoadArtifactToSlot($artifacts, 19);
			}
		}

		return $artifacts;
	}

	private function LoadArtifactToSlot(&$artifacts, $slot) {
		$artmask = $this->version == $this::ROE ? 0xff : 0xffff;
		$artid = -1;

		if($this->version == $this::ROE) {
			$artid = $this->ReadUint8();
		}
		else {
			$artid = $this->ReadUint16();
		}

		$isArt = $artid != $artmask;
		
		if($isArt) $artifacts[] = $this->GetArtifactPosById($slot).': '.$this->GetArtifactById($artid);
	}

	//spell mask
	private function ReadSpells() {
		$spells = array();
		for($i = 0; $i < SPELL_BYTE; $i++) {
			$byte = $this->ReadUint8();
	 		//$spells .= sprintf('%08b ', $byte);
			for($n = 0; $n < 8; $n++) {
				 if(($byte & (1 << $n)) != 0) {
					$spells[] = $this->GetSpellById($i * 8 + $n);
				}
			}
		}
		return $spells;
	}
	
	private function ReadTerrain() {
		for($z = 0; $z < $this->underground + 1; $z++) {
			for($x = 0; $x < $this->map_size; $x++) {
				for($y = 0; $y < $this->map_size; $y++) {
					$cell = new MapCell();
					$cell->surface = $this->ReadUint8();
					$cell->surface_type = $this->ReadUint8();
					$cell->river = $this->ReadUint8();
					$cell->river_type = $this->ReadUint8();
					$cell->road = $this->ReadUint8();
					$cell->road_type = $this->ReadUint8();
					$cell->mirror = $this->ReadUint8();
					$cell->access = 0;
					$cell->owner = 0xfe;
					$cell->special = '';

					$this->terrain[$z][$x][$y] = $cell;
				}
			}
		}
	}
	
	public function DisplayMap() {
		$this->mapimage = sanity_string($this->mapfilename);
		
		$imgmapnameg = MAPDIRIMG.$this->mapimage.'_g.png';
		$imgmapnameu = MAPDIRIMG.$this->mapimage.'_u.png';

		if($this->buildMapImage) {
			$imgsize = $this->map_size;
			$this->img = imagecreate($this->map_size, $this->map_size); //map by size
			$imgmap = imagecreate($this::IMGSIZE, $this::IMGSIZE); //resized to constant size for all map sizes
			/* From web
				First byte - surface codes: (RGB colors on the map)
		 ID	 Terrain					WEB desc	Real map	 Real map blocked		 Players
		 00 - Dirt						(50 3F 0F) #52 39 08	#39 29 08						#FF 00 00 Red
		 01 - Sand						(DF CF 8F) #DE CE 8C	#A5 9C 6B						#31 52 FF Blue
		 02 - Grass					 (00 40 00) #00 42 00	#00 31 00						#9C 73 52 Tan
		 03 - Snow						(B0 C0 C0) #B5 C6 C6	#8C 9C 9C						#42 94 29 Green
		 04 - Swamp					 (4F 80 6F) #4A 84 6B	#21 5A 42						#FF 84 00 Orange
		 05 - Rough					 (80 70 30) #84 73 31	#63 52 21						#8C 29 A5 Purple
		 06 - Subterranean		(00 80 30) #84 31 00	#39 29 08						#08 9C A5 Teal
		 07 - Lava						(4F 4F 4F) #4A 4A 4A	#29 29 29						#C6 7B 8C Pink
		 08 - Water					 (0F 50 90) #08 52 94	#00 29 6B						#84 84 84 Neutral
		 09 - Rock						(00 00 00) #00 00 00
		 */
			$this->imgcolors['dirt'] =				 imagecolorallocate($this->img, 0x52, 0x39, 0x08);
			$this->imgcolors['sand'] =				 imagecolorallocate($this->img, 0xde, 0xce, 0x8c);
			$this->imgcolors['grass'] =				imagecolorallocate($this->img, 0x00, 0x42, 0x00);
			$this->imgcolors['snow'] =				 imagecolorallocate($this->img, 0xb5, 0xc6, 0xc6);
			$this->imgcolors['swamp'] =				imagecolorallocate($this->img, 0x4a, 0x84, 0x6b);
			$this->imgcolors['rough'] =				imagecolorallocate($this->img, 0x84, 0x73, 0x31);
			$this->imgcolors['subterranean'] = imagecolorallocate($this->img, 0x84, 0x31, 0x00);
			$this->imgcolors['lava'] =				 imagecolorallocate($this->img, 0x4a, 0x4a, 0x4a);
			$this->imgcolors['water'] =				imagecolorallocate($this->img, 0x08, 0x52, 0x94);
			$this->imgcolors['rock'] =				 imagecolorallocate($this->img, 0x00, 0x00, 0x00);

			$this->imgcolors['bdirt'] =				 imagecolorallocate($this->img, 0x39, 0x29, 0x08);
			$this->imgcolors['bsand'] =				 imagecolorallocate($this->img, 0xa5, 0x9c, 0x6b);
			$this->imgcolors['bgrass'] =				imagecolorallocate($this->img, 0x00, 0x31, 0x00);
			$this->imgcolors['bsnow'] =				 imagecolorallocate($this->img, 0x8c, 0x9c, 0x9c);
			$this->imgcolors['bswamp'] =				imagecolorallocate($this->img, 0x21, 0x5a, 0x42);
			$this->imgcolors['brough'] =				imagecolorallocate($this->img, 0x63, 0x52, 0x21);
			$this->imgcolors['bsubterranean'] = imagecolorallocate($this->img, 0x5a, 0x08, 0x00);
			$this->imgcolors['blava'] =				 imagecolorallocate($this->img, 0x29, 0x29, 0x29);
			$this->imgcolors['bwater'] =				imagecolorallocate($this->img, 0x00, 0x29, 0x6b);
			$this->imgcolors['brock'] =				 imagecolorallocate($this->img, 0x00, 0x00, 0x00);

			$this->imgcolors['red'] =					 imagecolorallocate($this->img, 0xff, 0x00, 0x00);
			$this->imgcolors['blue'] =					imagecolorallocate($this->img, 0x31, 0x52, 0xff);
			$this->imgcolors['tan'] =					 imagecolorallocate($this->img, 0x9c, 0x73, 0x52);
			$this->imgcolors['green'] =				 imagecolorallocate($this->img, 0x42, 0x94, 0x29);
			$this->imgcolors['orange'] =				imagecolorallocate($this->img, 0xff, 0x84, 0x00);
			$this->imgcolors['purple'] =				imagecolorallocate($this->img, 0x8c, 0x29, 0xa5);
			$this->imgcolors['teal'] =					imagecolorallocate($this->img, 0x08, 0x9c, 0xa5);
			$this->imgcolors['pink'] =					imagecolorallocate($this->img, 0xc6, 0x7b, 0x8c);
			$this->imgcolors['neutral'] =			 imagecolorallocate($this->img, 0x84, 0x84, 0x84);

			$this->imgcolors['none'] =					imagecolorallocate($this->img, 0xff, 0xff, 0xff);
			$this->imgcolors['sp1'] =					 imagecolorallocate($this->img, 0xff, 0xff, 0x00);

			// Map
			$x = $y = 0;
			foreach($this->terrain as $level => $row) {
				foreach($row as $x => $col) {
					foreach($col as $y => $cell) {
						//$this->debug = "$x$y$level";
						$color = $this->GetCellSurface($cell);
						imagesetpixel($this->img, $y, $x, $color);
					}
				}

				$imgmapname = $level == 0 ? $imgmapnameg : $imgmapnameu;
				imagecopyresized($imgmap, $this->img, 0, 0, 0, 0, $this::IMGSIZE, $this::IMGSIZE, $this->map_size, $this->map_size);
				imagepng($imgmap, $imgmapname);
			}

			imagedestroy($this->img);
			imagedestroy($imgmap);
		}

		if($this->printoutput){
			$mapsizepow = $this->map_size * $this->map_size;
			$output = '<br />Map : size='.$this->map_size.', cells='.$mapsizepow.', bytes='.($mapsizepow * 7).'<br />';
			$output .= '<table><tr><td><img src="'.$imgmapnameg.'" alt="ground" title="ground" /></td>';
			if($this->underground) {
				$output .= '<td><img src="'.$imgmapnameu.'" alt="underground" title="underground" /></td>';
			}
			$output .= '</tr></table>';
			echo $output;
		}
	}
	
	private function ReadDefInfo() {
		$defAmount = $this->ReadUint32();
		$this->objTemplatesNum = $defAmount;

		// Read custom defs
		for($i = 0; $i < $defAmount; $i++) {
			$objtemp = array();
			$objtemp['animation'] = $this->ReadString();
			
			$blockMask = array();
			$visitMask = array();
			$objmask = EOL;
			//blockMask
			for($j = 0; $j < 6; $j++) {
				$blockMask[] = $this->ReadUint8();
			}
			//visitMask
			for($j = 0; $j < 6; $j++) {
				$visitMask[] = $this->ReadUint8();
			}

			//object sizes little use for map scan
			$usedTiles = array();
			
			if(1) {
				for ($r = 0; $r < 6; $r++) { // 6 rows
					for ($c = 0; $c < 8; $c++) { // 8 columns
						$usedTiles[$r][$c] = 0;
					}
				}
			
				for ($r = 0; $r < 6; $r++) { // 6 rows y-axis
					for ($c = 0; $c < 8; $c++) { // 8 columns	 x-axis
						$tile = 0x01; //VISIBLE; // assume that all tiles are visible
						$tiletype = '*';
						
						if ((($blockMask[$r] >> $c) & 1 ) == 0) {
							$tile |= 0x02; //BLOCKED;
							$tiletype = 'X';
						}
						if ((($visitMask[$r] >> $c) & 1 ) != 0) {
							$tile |= 0x04; //VISITABLE;
							$tiletype = '+';
						}

						$usedTiles[5 - $r][7 - $c] = $tile;

						$objmask .= $tiletype;
					}
					$objmask .= ENVE;
				}
			}

			$this->ReadUint16();
			$terrMask = $this->ReadUint16(); //allowed terrain for object, not needed
			/*for ($j = 0; $j < 9; $j++) {
				if ((($terrMask >> $j) & 1 ) != 0) allowedTerrains
			}*/

			$objtemp['id'] = $this->ReadUint32();
			$objtemp['subid'] = $this->ReadUint32();
			$objtemp['type'] = $this->ReadUint8();
			$objtemp['printpriority'] = $this->ReadUint8();
			$objtemp['tiles'] = $usedTiles;
			$objtemp['mask'] = $objmask;

			$this->SkipBytes(16);
			
			/*if (id == Obj::EVENT) {
				setSize(1,1);
				usedTiles[0][0] = VISITABLE;
			}*/
			$this->objTemplates[] = $objtemp; //$objtemp['id']
		}
	}
	
	private function ReadMessageAndGuards() {
		$hasMessage = $this->ReadUint8();
		if($hasMessage) {
			$message = $this->ReadString();
			$hasGuards = $this->ReadUint8();
			if($hasGuards) {
				$this->ReadCreatureSet(7);
			}
			$this->SkipBytes(4);
		}
	}

	private function ReadCreatureSet($number) {
		$version = ($this->version > $this::ROE);
		$maxID = $version ? 0xffff : 0xff;
		
		$stack = array();

		for($ir = 0; $ir < $number; $ir++) {
			$creatureID = $version ? $this->ReadUint16() : $this->ReadUint8();
			$count = $this->ReadUint16();

			// Empty slot
			if($creatureID == $maxID) continue;

			if($creatureID > $maxID - 0x0f) {
				//this will happen when random object has random army
				$idRand = $maxID - $creatureID - 1;
			}
			else {
				
			}
			$stack[] = array($creatureID, $count);
		}
		return $stack;
	}

	private function ReadObjects() {

		$howManyObjs = $this->ReadUint32();
		$this->objectsNum = $howManyObjs;
		
		/*foreach($this->objTemplates as $id => $t){
			echo "i $id -> o ".$t['id'].' '.$this->GetObjectById($t['id']).'<br />'.$t['mask'].'<br />';
		}
		echo "$howManyObjs<br />";*/

		for($ww = 0; $ww < $howManyObjs; $ww++) {
			$obj = array();
			$tileowner = 0xfe; //player coloured tile
			$special = 0; //special object displayed on map
			
			$x = $this->ReadUint8();
			$y = $this->ReadUint8();
			$z = $this->ReadUint8();
			
			$obj['pos'] = new MapCoords($x, $y, $z);

			$defnum = $this->ReadUint32(); //maybe object id, or just number in array
	 		$obj['defnum'] = $defnum;

	 		$objid = $objsubid = -1;

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
	 			$objid = -1;
	 			$obj['id'] = $objid;
	 		}

			 /*if($ww >= 4040) {
			 	$this->ppos();
	 			echo "$ww [$x, $y, $z] d $defnum o $objid ".$this->GetObjectById($objid)."<br />";
	 		}*/
			$this->SkipBytes(5);

			//$this->objTemplates

			switch($objid) {
				case OBJECTS::EVENT:
					$event = array();
					//$special = 1;
					$event['MessageStack'] = $this->ReadMessageAndGuards();

					$event['gainedExp'] = $this->ReadUint32();
					$event['manaDiff'] = $this->ReadUint32();
					$event['moraleDiff'] = $this->ReadUint8(); //TODO has to be int
					$event['luckDiff'] = $this->ReadUint8(); //TODO has to be int

					$event['resources'] = $this->ReadResourses();

					$event['priSkill'] = array();
					$event['secSkill'] = array();
					$event['artifacts'] = array();
					$event['spells'] = array();
					$event['stack'] = array();
					
					for($xx = 0; $xx < 4; $xx++) {
						$event['priSkill'][$xx] = $this->ReadUint8();
					}

					$secSkillsNum = $this->ReadUint8(); // Number of gained abilities
					for($oo = 0; $oo < $secSkillsNum; $oo++) {
						$event['secSkill']['skill'] = $this->ReadUint8();
						$event['secSkill']['level'] = $this->ReadUint8();
					}

					$artinum = $this->ReadUint8(); // Number of gained artifacts
					for($oo = 0; $oo < $artinum; $oo++) {
						if($this->version == $this::ROE) {
							$event['artifacts'] = $this->ReadUint8();
						}
						else {
							$event['artifacts'] = $this->ReadUint16();
						}
					}

					$spellnum = $this->ReadUint8(); // Number of gained spells
					for($oo = 0; $oo < $spellnum; $oo++) {
						$event['spells'] = $this->ReadUint8();
					}

					$stackNum = $this->ReadUint8(); //number of gained creatures
					$event['stack'] = $this->ReadCreatureSet($stackNum);

					$this->SkipBytes(8);
					$event['availableFor'] = $this->ReadUint8();
					$event['computerActivate'] = $this->ReadUint8();
					$event['removeAfterVisit'] = $this->ReadUint8();
					$event['humanActivate'] = true;

					$this->SkipBytes(4);

					$obj['data'] = $event;
					break;

			case OBJECTS::HERO:
			case OBJECTS::RANDOM_HERO:
			case OBJECTS::PRISON:
					$obj['data'] = $this->ReadHero();
		 			$tileowner = $obj['data']['PlayerColor'];
		 			$pos = $obj['pos'];

					$pos->x -= 1;
		 			$this->mapobjects[] = array(
						'object' => MAPOBJECTS::HERO,
						'objid' => $objid,
						'pos' => $pos,
						'name' => $obj['data']['name'],
						'owner' => $tileowner,
						'type' => $objsubid
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
					$monster = array();
					$monster['id'] = -1;
					
					if($this->version > $this::ROE) {
						$monster['id'] = $this->ReadUint32();
					}
					
					$monster['count'] = $this->ReadUint16();

					$monster['character'] = $this->ReadUint8();

					$hasMessage = $this->ReadUint8();
					if($hasMessage) {
						$monster['message'] = $this->ReadString();
						$monster['resources'] = $this->ReadResourses();

						$monster['artid'] = -1;
						if ($this->version == $this::ROE) {
							$monster['artid'] = $this->ReadUint8();
						}
						else {
							$monster['artid'] = $this->ReadUint16();
						}

						if($this->version == $this::ROE) {
							if($monster['artid'] != 0xff) {
								//invalid art
							}
						}
						else {
							if($monster['artid'] != 0xffff){
								//invalid art
							}
						}
					}
					$monster['neverFlees'] = $this->ReadUint8();
					$monster['notGrowingTeam'] =$this->ReadUint8();
					$this->SkipBytes(2);

					$obj['data'] = $monster;

					$this->mapobjects[] = array(
						'object' => MAPOBJECTS::MONSTER,
						'objid' => $objid,
						'pos' => $obj['pos'],
						'name' => $this->GetCreatureById($objsubid),
						'owner' => 0xfe,
						'type' => $objsubid
					);
					break;

			case OBJECTS::OCEAN_BOTTLE:
			case OBJECTS::SIGN:
					$bottle['bottleText'] = $this->ReadString();
					$this->SkipBytes(4);
					$obj['data'] = $bottle;
					break;

			case OBJECTS::SEER_HUT:
				$obj['data'] = $this->ReadSeerHut();
				break;

			case OBJECTS::WITCH_HUT:
					// in RoE we cannot specify it - all are allowed (I hope)
					$allowed = array();
					if($this->version > $this::ROE) {
						for($i = 0 ; $i < 4; $i++) {
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
				$artifact['artid'] = -1;
				$artifact['spellid'] = -1;

				$artifact['stack'] = $this->ReadMessageAndGuards();

				if($objid == OBJECTS::SPELL_SCROLL) {
					$artifact['spellid'] = $this->ReadUint32();
					$artifact['artid'] = 'SPELL_SCROLL'; //1
				}
				else if($objid == OBJECTS::ARTIFACT) {
					//specific artifact
					$artifact['artid'] = $obj['subid']; //TODO set correct value
				}
					
				$obj['data'] = $artifact;
				break;

			case OBJECTS::RANDOM_RESOURCE:
			case OBJECTS::RESOURCE:
					$res = array();

					$res['stack'] = $this->ReadMessageAndGuards();

					$res['amount'] = $this->ReadUint32();
					/*if(objTempl.subid == Res::GOLD) { //TODO when constants are done
						// Gold is multiplied by 100.
						res->amount *= 100;
					}*/
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
					
					$pos = $obj['pos'];
					$pos->x -= 2;
					$this->mapobjects[] = array(
						'object' => MAPOBJECTS::TOWN,
						'objid' => $objid,
						'pos' => $pos,
						'name' => $obj['data']['name'],
						'owner' => $tileowner,
						'type' => $objsubid
					);
					break;

			case OBJECTS::MINE:
			case OBJECTS::ABANDONED_MINE:
					$mine['owner'] = $this->ReadUint8();
					$this->SkipBytes(3);
					$tileowner = $mine['owner'];
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
					$shrine['raw_id'] = $this->ReadUint8();

					/*if (255 == raw_id) { //TODO something
						shr->spell = SpellID(SpellID::NONE);
					}
					else {
						shr->spell = SpellID(raw_id);
					}*/

					$this->SkipBytes(3);

					$obj['data'] = $shrine;
					break;

			case OBJECTS::PANDORAS_BOX:
					$box = array();
					$box['stack'] = $this->ReadMessageAndGuards();

					$box['gainedExp'] = $this->ReadUint32();
					$box['manaDiff'] = $this->ReadUint32();
					$box['moraleDiff'] = $this->ReadUint8(); // TODO int!
					$box['luckDiff'] = $this->ReadUint8(); // TODO int!

					$box['res'] = $this->ReadResourses();
					$box['priskill'] = array();
					$box['secskill'] = array();
					$box['artifacts'] = array();
					$box['spells'] = array();
					
					for($xx = 0; $xx < 4; $xx++) {
						$box['priskill'][] = $this->ReadUint8();
					}

					$gabn = $this->ReadUint8();//number of gained abilities
					for($oo = 0; $oo < $gabn; $oo++) {
						$box['secskill'] = array('skillid' => $this->ReadUint8(), 'level' => $this->ReadUint8());
					}

					$gart = $this->ReadUint8(); //number of gained artifacts
					for($oo = 0; $oo < $gart; $oo++) {
						if($this->version > $this::ROE) {
							$box['artifacts'][] = $this->ReadUint16();
						}
						else {
							$box['artifacts'][] = $this->ReadUint8();
						}
					}

					$gspel = $this->ReadUint8(); //number of gained spells
					for($oo = 0; $oo < $gspel; $oo++) {
						$box['spells'][] = $this->ReadUint8();
					}
					
					$gcre = $this->ReadUint8(); //number of gained creatures
					$box['stack'] = $this->ReadCreatureSet($gcre);
					$this->SkipBytes(8);
					
					$obj['data'] = $box;
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
						$dwelling['identifier'] =	$this->ReadUint32();
						if(!$dwelling['identifier']) {
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
					$obj['data'] = $quest;
					break;

			case OBJECTS::SHIPYARD:
					$harbor['owner'] = $this->ReadUint32();
					$obj['data'] = $harbor;
					$tileowner = $harbor['owner'];
					break;

			case OBJECTS::HERO_PLACEHOLDER: //hero placeholder
					$placeholder['owner'] = $this->ReadUint8();
					$tileowner = $placeholder['owner'];

					$htid = $this->ReadUint8(); //hero type id
					$placeholder['heroid'] = $htid;
		 			//$placeholder['heroclass'] = $this->ReadUint8();

					if($htid == 0xff) {
						$placeholder['power'] = $this->ReadUint8();
					}
					else {
						$placeholder['power'] = 0;
					}

					$obj['data'] = $placeholder;
					break;

			case OBJECTS::BORDERGUARD:
			case OBJECTS::BORDER_GATE:
			case OBJECTS::KEYMASTER:
				//$special = 1;
				break;

			case OBJECTS::PYRAMID: //Pyramid of WoG object
				break;
				
			case OBJECTS::LIGHTHOUSE: //Lighthouse
					$lighthouse['owner'] = $this->ReadUint32();
					$tileowner = $lighthouse['owner'];

					$obj['data'] = $lighthouse;
					break;
					
			default: //any other object
					break;
		 }

			//object tiles
			if($objid != -1) { // && ($objid == OBJECTS::TOWN || $objid == OBJECTS::RANDOM_TOWN || $objid == OBJECTS::MINE)
				for($iy = 0; $iy < 6; $iy++){ //y-axis
					for($ix = 0; $ix < 8; $ix++){ //x-axis
						$mx = $x - $ix;
						$my = $y - $iy;
						
						
						if($z > 1 || $my > $this->map_size - 1 || $my < 0 || $mx > $this->map_size - 1 || $mx < 0){
							continue;
						}
						
						if($this->terrain[$z][$my][$mx]->owner != 0xfe || $this->terrain[$z][$my][$mx]->special != '') continue;

						$tilemask = $this->objTemplates[$defnum]['tiles'][$iy][$ix];
						if(($tilemask & 0x06) != 0) { // 11[1]1
			 				if($tileowner != 0xfe) {
								$this->terrain[$z][$my][$mx]->owner = $tileowner;
							}
							elseif($special){
								$this->terrain[$z][$my][$mx]->special = 'sp1';
							}
							elseif(($tilemask & 0x04) == 0) {
								$this->terrain[$z][$my][$mx]->access = $tilemask;
							}
						}
					}
				}
			}
				
			$this->objects[] = $obj;
		}
	}
	
	private function ReadTown() {
		$town = array();
		$town['ident'] = -1;
		$town['name'] = 'Random name';
		
		if($this->version > $this::ROE) {
			$town['ident'] = $this->ReadUint32();
		}

		$town['owner'] = $this->ReadUint8();
		$hasName = $this->ReadUint8();
		if($hasName) {
			$town['name'] = $this->ReadString();
		}

		$hasGarrison = $this->ReadUint8();
		if($hasGarrison) {
			$town['stack'] = $this->ReadCreatureSet(7);
		}
		$town['formation'] = $this->ReadUint8();

		$hasCustomBuildings = $this->ReadUint8();
		if($hasCustomBuildings) {
			for($i = 0; $i < 6; $i++){
				$town['buildingsAllowed'][] = sprintf('%08b ', $this->ReadUint8());
			}

			for($i = 0; $i < 6; $i++){
				$town['buildingsDisabled'][] = sprintf('%08b ', $this->ReadUint8());
			}
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
		if($this->version > $this::ROE) {
			for($i = 0; $i < 9; $i++) {
				$c = $this->ReadUint8();
				/*for($yy = 0; $yy < 8; $yy++) {
					if($i * 8 + $yy < $this::SPELLS_QUANTITY) {
						if($c == ($c | power(2, $yy)) { //add obligatory spell even if it's banned on a map (?)
							//(SpellID(i * 8 + yy));
						}
					}
				}*/
			}
		}

		//spells random
		for($i = 0; $i < 9; $i++) {
			$c = $this->ReadUint8();
			/*for($yy = 0; yy < 8; ++yy) {
				$spellid = i * 8 + yy;
				for($yy = 0; $yy < 8; $yy++) {
					if($i * 8 + $yy < $this::SPELLS_QUANTITY) {
						if($c == ($c | power(2, $yy) && $spellallowedmask) { //add random spell only if it's allowed on entire map
					}
				}
			}*/
		}

		// Read castle events
		$numberOfEvent = $this->ReadUint32();
		for($gh = 0; $gh < $numberOfEvent; $gh++) {
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
			$event['firstOccurence'] = $this->ReadUint16();
			$event['nextOccurence'] =	$this->ReadUint8();

			$this->SkipBytes(17);
			
			for($i = 0; $i < 6; $i++){
				$event['buildings'][] = sprintf('%08b ', $this->ReadUint8());
			}
			
			for($vv = 0; $vv < 7; $vv++) {
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
		
		$hut['taskid'] = QUESTMISSION::NONE;

		if($this->version > $this::ROE) {
			$hut = $this->ReadQuest();
		}
		else {
			//RoE
			$artID = $this->ReadUint8();
			if ($artID != 0xff) {
				$hut['artid'] = $artID;
				$hut['taskid'] = QUESTMISSION::ART;
			}
			else {
				$hut['taskid'] = QUESTMISSION::NONE;
			}
			$hut['timeout'] = -1; //no timeout
		}

		$hut['task'] = FromArray($hut['taskid'], $this->CS->QuestMission);
		
		if(array_key_exists('taskid', $hut)) {
			$hut['rewardid'] = $this->ReadUint8();
			$hut['rewardType'] = FromArray($hut['rewardid'], $this->CS->RewardType);
			$hut['value'] = -1;

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
						$hut['resid'] = $this->ReadUint8();
						$hut['value'] = $this->ReadUint32() & 0x00ffffff;
						break;
				case REWARDTYPE::PRIMARY_SKILL:
						$hut['priskill'] = $this->ReadUint8();
						$hut['value'] = $this->ReadUint8();
						break;
				case REWARDTYPE::SECONDARY_SKILL:
						$hut['secskill'] = $this->ReadUint8();
						$hut['value'] = $this->ReadUint8();
						break;
				case REWARDTYPE::ARTIFACT:
						if ($this->version == $this::ROE) {
							$hut['artid'] = $this->ReadUint8();
						}
						else {
							$hut['artid'] = $this->ReadUint16();
						}
						break;
				case REWARDTYPE::SPELL:
						$hut['spellid'] = $this->ReadUint8();
						break;
				case REWARDTYPE::CREATURE:
						if($this->version > $this::ROE) {
							$hut['monid'] = $this->ReadUint16();
							$hut['value'] = $this->ReadUint16();
						}
						else {
							$hut['monid'] = $this->ReadUint8();
							$hut['value'] = $this->ReadUint16();
						}
						break;
			}
			$this->SkipBytes(2);
		}
		else {
			// missionType==255
			$this->SkipBytes(3);
		}

		return $hut;
	}

	private function ReadQuest() {
		$quest = array();
		$quest['taskid'] = $this->ReadUint8();

		switch($quest['taskid']) {
			case QUESTMISSION::NONE:
				return;
			case QUESTMISSION::PRIMARY_STAT:
				for($x = 0; $x < 4; $x++) {
					$quest['Qpriskill'][] = $this->ReadUint8();
				}
				break;
			case QUESTMISSION::LEVEL:
			case QUESTMISSION::KILL_HERO:
			case QUESTMISSION::KILL_CREATURE:
				$quest['Qkill'] = $this->ReadUint32();
				break;
			case QUESTMISSION::ART:
				$artNumber = $this->ReadUint8();
				for($yy = 0; $yy < $artNumber; $yy++) {
					$artid = $this->ReadUint16();
					$quest['Qartids'][] = $artid;
				}
				break;
			case QUESTMISSION::ARMY:
				$typeNumber = $this->ReadUint8();
				for($hh = 0; $hh < $typeNumber; $hh++) {
					$quest['Qarmy'] = array('monid' => $this->ReadUint16(), 'count' => $this->ReadUint16());
				}
				break;
			case QUESTMISSION::RESOURCES:
				for($x = 0; $x < 7; $x++) {
					$quest['Qres'][] = $this->ReadUint32();
				}
				break;
			case QUESTMISSION::HERO:
			case QUESTMISSION::PLAYER:
				$quest['Qheroplayer'] = $this->ReadUint8();
				break;
		}

		$limit = $this->ReadUint32();
		if($limit == 0xffffffff) {
			$quest['timeout'] = -1;
		}
		else {
			$quest['timeout'] = $limit;
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
			$event['first']= $this->ReadUint16();
			$event['interval']= $this->ReadUint8();

			$this->SkipBytes(17);

			$this->events[] = $event;
		}
	}
	
	private function ReadResourses() {
		$resources = array();
		for($i = 0; $i < 7; $i++) {
			$resources[$i] = $this->ReadInt32();
		}
		return $resources;
	}

	private function GetCellSurface($cell){
		if($cell->owner != 0xfe) {
			switch($cell->owner){
				case 0: return $this->imgcolors['red'];
				case 1: return $this->imgcolors['blue'];
				case 2: return $this->imgcolors['tan'];
				case 3: return $this->imgcolors['green'];
				case 4: return $this->imgcolors['orange'];
				case 5: return $this->imgcolors['purple'];
				case 6: return $this->imgcolors['teal'];
				case 7: return $this->imgcolors['pink'];
				case 0xff: return $this->imgcolors['neutral'];
				default: return $this->imgcolors['neutral'];
			}
		}
		elseif($cell->special != '') {
			return $this->imgcolors[$cell->special];
		}
		elseif($cell->access == 0){
			switch($cell->surface){
				case 0: return $this->imgcolors['dirt'];
				case 1: return $this->imgcolors['sand'];
				case 2: return $this->imgcolors['grass'];
				case 3: return $this->imgcolors['snow'];
				case 4: return $this->imgcolors['swamp'];
				case 5: return $this->imgcolors['rough'];
				case 6: return $this->imgcolors['subterranean'];
				case 7: return $this->imgcolors['lava'];
				case 8: return $this->imgcolors['water'];
				case 9: return $this->imgcolors['rock'];
				default: return $this->imgcolors['none'];
			}
		}
		else {
			switch($cell->surface){
				case 0: return $this->imgcolors['bdirt'];
				case 1: return $this->imgcolors['bsand'];
				case 2: return $this->imgcolors['bgrass'];
				case 3: return $this->imgcolors['bsnow'];
				case 4: return $this->imgcolors['bswamp'];
				case 5: return $this->imgcolors['brough'];
				case 6: return $this->imgcolors['bsubterranean'];
				case 7: return $this->imgcolors['blava'];
				case 8: return $this->imgcolors['bwater'];
				case 9: return $this->imgcolors['brock'];
				default: return $this->imgcolors['none'];
			}
		}
	}
	
	private function ParseFinish(){
		//determine, if hero is in castle by tile being blocked
		foreach($this->mapobjects as $k => $mapobjh) {
			if($mapobjh['object'] == MAPOBJECTS::HERO) {
				foreach($this->mapobjects as $n => $mapobjl) {
					if($mapobjl['object'] == MAPOBJECTS::TOWN) {
						if(	$mapobjh['pos']->x - 1 == $mapobjl['pos']->x //hero at castle has x-1 compared to castle coord
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
	
		if(empty($this->victoryCond)) return;

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
	
	private function GetVersionName() {
		switch($this->version) {
			case $this::ROE: $this->versionname = 'ROE'; break;
			case $this::AB: $this->versionname = 'AB'; break;
			case $this::SOD: $this->versionname = 'SOD'; break;
			case $this::WOG: $this->versionname = 'WOG'; break;
			case $this::HOTA: $this->versionname = 'HOTA'; break;
			default: $this->versionname = '?'; break;
		}
	}

	private function GetMapSize() {
		switch($this->map_size) {
			case 36: $this->map_sizename = 'S'; break;
			case 72: $this->map_sizename = 'M'; break;
			case 108: $this->map_sizename = 'L'; break;
			case 144: $this->map_sizename = 'XL'; break;
			case 180: $this->map_sizename = 'H'; break;
			case 216: $this->map_sizename = 'XH'; break;
			case 252: $this->map_sizename = 'G'; break;
			default: $this->map_sizename = '?'; break;
		}
	}
	
	private function GetDifficulty() {
		switch($this->map_diff) {
			case 0: $this->map_diffname = 'Easy'; break;
			case 1: $this->map_diffname = 'Normal'; break;
			case 2: $this->map_diffname = 'Hard'; break;
			case 3: $this->map_diffname = 'Expert'; break;
			case 4: $this->map_diffname = 'Impossible'; break;
			default: $this->map_diffname = '?'; break;
		}
	}
	
	private function GetResource($resourceid) {
		switch($resourceid) {
			case 0: return 'Wood';
			case 1: return 'Mercury';
			case 2: return 'Ore';
			case 3: return 'Sulfur';
			case 4: return 'Crystal';
			case 5: return 'Gems';
			case 6: return 'Gold';
			default: return '?';
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

	private function GetArtifactById($artid) {
		return FromArray($artid, $this->CS->Artefacts);
	}
	
	private function GetArtifactPosById($artid) {
		return FromArray($artid, $this->CS->ArtifactPosition);
	}
	
	private function GetCreatureById($monid) {
		return FromArray($monid, $this->CS->Monster);
	}
	
	private function GetResourceById($id) {
		return $this->GetResource($id);
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

	private function GetMapObjectByPos($mapobjectid, $coords) {
		if($mapobjectid == MAPOBJECTS::TOWN && $coords->x == HNONE) {
			return 'Any';
		}
			
		foreach($this->mapobjects as $mapobj) {
			if($mapobj['object'] != $mapobjectid) continue;
			
			if($coords->x == $mapobj['pos']->x && $coords->y == $mapobj['pos']->y && $coords->z == $mapobj['pos']->z) {
				if($mapobjectid == MAPOBJECTS::MONSTER && $mapobj['objid'] != OBJECTS::MONSTER) {
					return $this->GetObjectById($mapobj['objid']);
				}
				return $mapobj['name'];
			/*
				switch($mapobjectid) {
					case MAPOBJECTS::HERO: return $mapobj['name'];
					case MAPOBJECTS::TOWN: return $mapobj['name'];
					case MAPOBJECTS::MONSTER: return $mapobj['name'];
				}*/
			}
		}
		return '?';
	}
	
	private function Ungzip() {
		// Raising this value may increase performance
		$buffer_size = 4096; // read 4kb at a time
		

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

		//OR THIS $this->mapdata = gzinflate(substr($this->mapdata, 10, -8));
	}

	private function fix64($numL, $numH){
		if($numH < 0) $numH += 4294967296;
		if($numL < 0) $numL += 4294967296;
		$num = bcadd($numL, bcmul($numH, 4294967296));
		if($num > bcpow(2, 63)) return bcsub($num, bcpow(4294967296, 2)); // 2, 64
		return $num;
	}

	private function ReadUint8(){
		if($this->pos >= $this->filesizeU || $this->pos < 0){
			dbglog();
			//vd($this);
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

	private function ReadInt32(){
		$res = $this->ReadUint32();
		$maxint32 = 2147483648;
		//vd($maxint32.' '.$res);
		if($res > $maxint32) $res -= 4294967296;
		return $res;
	}
	

	private function ReadUint64(){
		return $this->fix64($this->ReadUint32(), $this->ReadUint32());
	}

	private function ReadString($length = -1){
		$res = '';
		if($this->pos >= $this->length || $this->pos < 0){
			dbglog();
			//vd($this);
			die('Bad string pos '.$this->pos);
			return;
		}
		
		if($length == -1){
			$length = $this->ReadUint32();
			if($length == 0) return $res;
			if($length > 100000 || $length < 0) {
				dbglog();
				//vd($this);
				die('Too long string '.$length);
				return;
			}
			$res = substr($this->mapdata, $this->pos, $length);
			$this->pos += $length;
			return w1250_to_utf8($res);
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
		return w1250_to_utf8($res);
	}

	private function SkipBytes($bytes = 31){
		$this->pos += $bytes;
	}
	
	//pr$current position
	private function ppos(){
		vd(dechex($this->pos). ' '.$this->pos);
	}

	public function stuff(){
		foreach($this->CS->SpellID as $k => $s) {
			echo "\t\t\t$k => '".ucfirst($s)."', <br />";
		}
	}
	
}


class MapCell {
	public $surface;
	public $surface_type;
	public $river;
	public $river_type;
	public $road;
	public $road_type;
	public $mirror;
	public $access;
	public $owner;
	public $special; //display some object on map with special colour
}

class MapCoords {
	public $x;
	public $y;
	public $z;
	
 	public function __construct($x, $y, $z) {
		$this->x = $x;
		$this->y = $y;
		$this->z = $z;
	}
	
	public function GetCoords(){
		return '['.$this->x.','.$this->y.','.$this->z.']';
	}
}

function EventSortByDate($a, $b){
	if($a['first'] > $b['first']) return 1;
	if($a['first'] < $b['first']) return -1;
	if($a['order'] > $b['order']) return 1;
	else -1;
}
?>
