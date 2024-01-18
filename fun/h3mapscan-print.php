<?php


//class to print h3mapscan data to html presentation

class H3MAPSCAN_PRINT {

	private $h3mapscan;

	public function __construct($h3mapscan) {
		$this->h3mapscan = $h3mapscan;
		$this->PrintMapInfo();
	}

	private function PrintMapInfo() {

		$subrev = ($this->h3mapscan->version == $this->h3mapscan::HOTA) ? ' '.$this->h3mapscan->hota_subrev : '';


		$print = '<div class="mapdetail">
			<table class="mapdetailtable">
				<tr>
					<td class="mapdtd vat">
					  <p class="listcontent">
							List of details<br />
							<a href="#description">Description</a><br />
							<a href="#players">Players</a><br />
							<a href="#mapimage">Map</a><br />
							<a href="#terrain">Terrain</a><br />
							<a href="#events">Events</a><br />
							<a href="#heroescustom">Heroes custom</a><br />
							<a href="#artdis">Disabled artifacts</a><br />
							<a href="#spelldis">Disabled spells</a><br />
							<a href="#skilldis">Disabled skills</a><br />
							<a href="#towns">Towns</a><br />
							<a href="#heroes">Heroes</a><br />
							<a href="#artifacts">Artifacts</a><br />
							<a href="#spells">Spells</a><br />
							<a href="#mines">Mines</a><br />
							<a href="#monsters">Monsters</a><br />
							<a href="#quests">Quests</a><br />
							<a href="#townevent">Town events</a><br />
							<a href="#eventbox">Events and pandoras</a><br />
							<a href="#signs">Signs and bottels</a><br />
							<a href="#rumors">Rumors</a><br />
							<a href="#keys">Keys and gates</a><br />
							<a href="#monolith">Monoliths</a><br />
							<a href="#objects">Objects</a>
						</p>
					</td>
					<td class="mapdtd">'.EOL.EOL;


		$print .= '<a name="description"></a>
		<table>
				<tr><th colspan="2">Map details</th></tr>
				<tr><td class="colw200">File</td><td>'.$this->h3mapscan->mapfile.'</td></tr>
				<tr><td>Name</td><td>'.$this->h3mapscan->map_name.'</td></tr>
				<tr><td>Description</td><td>'.nl2br($this->h3mapscan->description).'</td></tr>
				<tr><td>Version</td><td>'.$this->h3mapscan->versionname.$subrev.'</td></tr>
				<tr><td>Size</td><td>'.$this->h3mapscan->map_sizename.'</td></tr>
				<tr><td>Levels</td><td>'.($this->h3mapscan->underground ? 2 : 1).'</td></tr>
				<tr><td>Difficulty</td><td>'.$this->h3mapscan->map_diffname.'</td></tr>
				<tr><td>Victory</td><td>'.$this->h3mapscan->victoryInfo.'</td></tr>
				<tr><td>Loss</td><td>'.$this->h3mapscan->lossInfo.'</td></tr>
				<tr><td>Players count</td><td>'.$this->h3mapscan->mapplayersnum.', '.$this->h3mapscan->mapplayershuman.'/'.$this->h3mapscan->mapplayersai.'</td></tr>
				<tr><td>Team count</td><td>'.$this->h3mapscan->teamscount.'</td></tr>
				<tr><td>Heroes level cap</td><td>'.$this->h3mapscan->hero_levelcap.'</td></tr>
				<tr><td>Language</td><td>'.$this->h3mapscan->GetLanguage().'</td></tr>
			</table>';

		$print .= '<a name="players"></a>
			<table class="smalltable">
				<tr>
					<th>#</th>
					<th>Color</th>
					<th class="nowrap" nowrap="nowrap">Human</th>
					<th>AI</th>
					<th class="nowrap" nowrap="nowrap">Behaviour</th>
					<th class="nowrap" nowrap="nowrap">Team</th>
					<th class="nowrap">Town count</th>
					<th class="nowrap">Factions allowed</th>
					<th class="nowrap">Random town</th>
					<th>Main town</th>
					<th>Hero at Main</th>
					<th class="nowrap">Generate hero</th>
					<th class="nowrap">Town coords</th>
					<th class="nowrap">Random Hero</th>
					<th>Main hero</th>
					<th>Heroes count</th>
					<th>Heroes ids</th>
					<th>Heroes names</th>
				</tr>';


		foreach($this->h3mapscan->players as $k => $player) {
			$print .= '<tr>
					<td class="ac">'.($k + 1).'</td>
					<td class="nowrap" nowrap="nowrap">'.$this->h3mapscan->GetPlayerColorById($k).'</td>
					<td class="ac nowrap" nowrap="nowrap">'.$player['human'].'</td>
					<td class="ac">'.$player['ai'].'</td>
					<td>'.$this->h3mapscan->GetBehaviour($player['behaviour']).'</td>
					<td class="ac">'.$this->h3mapscan->teams[$k].'</td>
					<td class="ar">'.$player['townsOwned'].'</td>
					<td>'.$player['towns_allowed'].'</td>
					<td class="ac">'.$player['IsRandomTown'].'</td>
					<td class="ac">'.$player['HasMainTown'].'</td>
					<td class="ac">'.$player['HeroAtMain'].'</td>
					<td class="ac">'.$player['GenerateHero'].'</td>
					<td>'.$player['townpos']->GetCoords().'</td>
					<td class="ac">'.$player['RandomHero'].'</td>
					<td class="nowrap">'.$player['MainHeroName'].'</td>
					<td class="ar">'.$player['HeroCount'].'</td>
					<td>'.implode($player['HeroFace'], ', ').'</td>
					<td>'.implode($player['HeroName'], ', ').'</td>
				</tr>';
		}
		$print .= '</table>';


		$this->h3mapscan->BuildMap();
		$print .= '<a name="mapimage"></a>'.EOL.$this->h3mapscan->DisplayMap();

		//terrain percentage
		$totalsize1 = $this->h3mapscan->map_size * $this->h3mapscan->map_size;
		$totalsize2 = $totalsize1 * ($this->h3mapscan->underground + 1);

		$print .= '<a name="terrain"></a>
		<table><tr>';
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
			arsort($this->h3mapscan->terrainRate[$i]);
			$print .= '<td>'.$title.'
				<table class="smalltable">
					<tr><th>#</th><th>Terrain</th><th>Percentage</th></tr>';
			foreach($this->h3mapscan->terrainRate[$i] as $terrain => $ratio) {
				$print .= '<tr>
					<td class="ac">'.(++$n).'</td>
					<td>'.$this->h3mapscan->CS->TerrainType[$terrain].'</td>
					<td class="ar">'.comma(100 * $ratio / $totalsize, 1).' %</td>
				</tr>';
			}
			$print .= '</table></td>';
		}
		$print .= '</tr></table>';


		//disabled heroes
		$n = 0;
		$print .= '<a name="heroescustom"></a>
			<table class="smalltable">
				<tr><th>#</th><th colspan="2">Unavailable heroes</th></tr>';
		foreach($this->h3mapscan->disabledHeroes as $class => $heroes) {
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
		foreach($this->h3mapscan->heroesPredefined as $k => $hero) {
			if($hero['mask'] == 0) {
				//continue;
			}
			$playermask = $this->h3mapscan->playerMask & $hero['mask'];

			$skills = [];
			foreach($hero['skills'] as $skill) {
				$skills[] = $skill[0].': '.$skill[1];
			}

			$print .= '<tr>
				<td class="ac">'.($k+1).'</td>
				<td>'.$hero['name'].'<br />('.$hero['defname'].')</td>
				<td>'.$this->h3mapscan->PlayerColors($playermask).'</td>
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


		sort($this->h3mapscan->disabledArtifacts);
		$print .= '<a name="artdis"></a>
			<table class="smalltable">
				<tr><th>#</th><th>Disabled Artifacts</th></tr>';
		foreach($this->h3mapscan->disabledArtifacts as $k => $art) {
			$print .= '<tr>
				<td class="ac">'.($k+1).'</td>
				<td>'.$art.'</td>
			</tr>';
		}
		$print .= '</table>';

		sort($this->h3mapscan->disabledSpells);
		$print .= '<a name="spelldis"></a>
			<table class="smalltable">
				<tr><th>#</th><th>Disabled Spells</th></tr>';
		foreach($this->h3mapscan->disabledSpells as $k => $spell) {
			$print .= '<tr>
				<td class="ac">'.($k+1).'</td>
				<td>'.$spell.'</td>
			</tr>';
		}
		$print .= '</table>';

		sort($this->h3mapscan->disabledSkills);
		$print .= '<a name="skilldis"></a>
			<table class="smalltable">
				<tr><th>#</th><th>Disabled Skills</th></tr>';
		foreach($this->h3mapscan->disabledSkills as $k => $spell) {
			$print .= '<tr>
				<td class="ac">'.($k+1).'</td>
				<td>'.$spell.'</td>
			</tr>';
		}
		$print .= '</table>';

		//towns list
		usort($this->h3mapscan->towns_list, 'SortTownsByName');
		$n = 0;
		$print .= '<a name="towns"></a>
			<table class="smalltable">
				<tr>
					<th class="nowrap" nowrap="nowrap">Towns</th>
					<th>Name</th>
					<th>Position</th>
					<th>Owner</th>
					<th>Type</th>
					<th class="nowrap" nowrap="nowrap">Events</th>
					<th>Troops</th>
					<th>Max Mage Guild</th>
					<th>Spell</th>
				</tr>';
		foreach($this->h3mapscan->towns_list as $towno) {
			$town = $towno['data'];
			$print .= '<tr>
				<td class="ac">'.(++$n).'</td>
				<td>'.$town['name'].'</td>
				<td class="nowrap" nowrap="nowrap">'.$towno['pos']->GetCoords().'</td>
				<td class="nowrap" nowrap="nowrap">'.$town['player'].'</td>
				<td>'.$town['affiliation'].'</td>
				<td class="ar">'.$town['eventsnum'].'</td>
				<td class="colw100">'.$this->h3mapscan->PrintStack($town['stack']).'</td>
				<td class="ac">'.$town['max_guild'].'</td>
				<td>'.$town['spells'].'</td>
			</tr>';
		}
		$print .= '</table>';

		//heroes and placeholder list
		$n = 0;
		$print .= '<a name="heroes"></a>
			<table class="smalltable">
				<tr>
					<th>Heroes</th>
					<th>Name</th>
					<th>Position</th>
					<th>Owner</th>
					<th>Class</th>
					<th>Exp</th>
					<th>Primary</th>
					<th>Secondary</th>
					<th>Troops</th>
					<th>Artifacts</th>
					<th>Spells</th>
				</tr>';
		foreach($this->h3mapscan->heroes_list as $hero) {
			$color = $hero['data']['prisoner'] ? 'Prisoner' : $this->h3mapscan->GetPlayerColorById($hero['data']['PlayerColor']);

			$class = $this->h3mapscan->GetHeroClassByHeroId($hero['data']['subid']);

			$primary = implode($hero['data']['priskills'], ' ');
			$secondary = '';
			foreach($hero['data']['skills'] as $k => $skill) {
				if($k > 0) {
					$secondary .= '<br />';
				}
				$secondary .= $skill['skill'].': '.$skill['level'];
			}
			$artifacts = implode($hero['data']['artifacts'], '<br />');

			$level = $this->h3mapscan->GetLevelByExp($hero['data']['exp']);

			sort($hero['data']['spells']);

			$print .= '<tr>
				<td>'.(++$n).'</td>
				<td>'.$hero['data']['name'].'</td>
				<td>'.$hero['pos']->GetCoords().'</td>
				<td>'.$color.'</td>
				<td>'.$class.'</td>
				<td>'.comma($hero['data']['exp']).'<br />Level '.$level.'</td>
				<td>'.$primary.'</td>
				<td>'.$secondary.'</td>
				<td>'.$this->h3mapscan->PrintStack($hero['data']['stack']).'</td>
				<td>'.$artifacts.'</td>
				<td>'.implode($hero['data']['spells'], '<br />').'</td>
			</tr>';
		}

		foreach($this->h3mapscan->heroes_placeholder as $hero) {
			$print .= '<tr>
				<td>'.(++$n).'</td>
				<td>'.$hero['name'].'</td>
				<td>'.$hero['pos']->GetCoords().'</td>
				<td>'.$this->h3mapscan->GetPlayerColorById($hero['owner']).'</td>
				<td>'.$this->h3mapscan->GetHeroClassByHeroId($hero['heroid']).'</td>
				<td></td>
				<td></td>
				<td></td>
				<td>'.$this->h3mapscan->PrintStack($hero['stack']).'</td>
				<td>'.implode($hero['artifacts'], '<br />').'</td>
				<td></td>
			</tr>';
		}

		$print .= '</table>';


		//artifact list
		usort($this->h3mapscan->artifacts_list, 'ListSortByName');
		$n = 0;
		$print .= '<a name="artifacts"></a>
			<table class="smalltable">
				<tr><th>Artifacts</th><th>Name</th><th>Position</th><th>Parent</th></tr>';
		foreach($this->h3mapscan->artifacts_list as $art) {
			$print .= '<tr>
				<td class="ac">'.(++$n).'</td>
				<td>'.$art->name.'</td>
				<td>'.$art->mapcoor->GetCoords().'</td>
				<td>'.$art->parent.'</td>
			</tr>';
		}
		$print .= '</table>';

		//spell list
		usort($this->h3mapscan->spells_list, 'ListSortByName');
		$n = 0;
		$print .= '<a name="spells"></a>
			<table class="smalltable">
				<tr><th>Spells</th><th>Name</th><th>Position</th><th>Parent</th></tr>';
		foreach($this->h3mapscan->spells_list as $art) {
			$print .= '<tr>
				<td class="ac">'.(++$n).'</td>
				<td>'.$art->name.'</td>
				<td>'.$art->mapcoor->GetCoords().'</td>
				<td>'.$art->parent.'</td>
			</tr>';
		}
		$print .= '</table>';

		//mines list
		usort($this->h3mapscan->mines_list, 'ListSortByName');
		$n = 0;
		$print .= '<a name="mines"></a>
			<table class="smalltable">
				<tr><th>Mines</th><th>Name</th><th>Position</th><th>Owner</th><th>Resources</th></tr>';
		foreach($this->h3mapscan->mines_list as $mine) {

			$print .= '<tr>
				<td class="ac">'.(++$n).'</td>
				<td>'.$mine->name.'</td>
				<td>'.$mine->mapcoor->GetCoords().'</td>
				<td>'.$this->h3mapscan->GetPlayerColorById($mine->owner).'</td>
				<td>'.$mine->info.'</td>
			</tr>';
		}
		$print .= '</table>';

		//monster list
		usort($this->h3mapscan->monsters_list, 'ListSortByName');
		$n = 0;
		$print .= '<a name="monsters"></a>
			<table class="smalltable">
				<tr><th>Monsters</th><th>Name</th><th>Count</th><th>Position</th><th>Parent</th><th>Treasure</th><th>Info</th></tr>';
		foreach($this->h3mapscan->monsters_list as $mon) {
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
		usort($this->h3mapscan->quest_list, 'ListSortByName');
		$n = 0;
		$print .= '<a name="quests"></a>
			<table class="smalltable">
				<tr>
					<th class="nowrap" nowrap="nowrap">Quest</th>
					<th class="nowrap" nowrap="nowrap">Giver</th>
					<th class="nowrap" nowrap="nowrap">Position</th>
					<th>Quest</th>
					<th colspan="3">Reward</th>
					<th class="colw300">Text Give</th>
					<th class="colw300">Text Repeat</th>
					<th class="colw300">Text Finished</th>
				</tr>';

		//$quest['textFirst']
		//$quest['textRepeat']
		//$quest['textDone']

		foreach($this->h3mapscan->quest_list as $quest) {
			$questtext = $quest->parent;
			if($quest->add1 > 0) {
				$questtext .= $this->h3mapscan->GetMapObjectByUID(MAPOBJECTS::NONE, $quest->add1);
			}

			$print .= '<tr>
				<td class="ac">'.(++$n).'</td>
				<td class="nowrap" nowrap="nowrap">'.$quest->name.'</td>
				<td class="nowrap" nowrap="nowrap">'.$quest->mapcoor->GetCoords().'</td>
				<td class="nowrap" nowrap="nowrap">'.$questtext.'</td>
				<td class="nowrap" nowrap="nowrap">'.$quest->owner.'</td>
				<td class="nowrap" nowrap="nowrap">'.$quest->info.'</td>
				<td class="nowrap" nowrap="nowrap">'.$quest->count.'</td>
				<td>'.nl2br($quest->add2[0]).'</td>
				<td>'.nl2br($quest->add2[1]).'</td>
				<td>'.nl2br($quest->add2[2]).'</td>
			</tr>';
		}
		$print .= '</table>';


		//towns events list
		$n = 0;
		$print .= '<a name="townevent"></a>
			<table class="smalltable">
				<tr>
					<th>Town Events</th>
					<th>Name</th>
					<th class="nowrap" nowrap="nowrap">Position</th>
					<th>Owner</th>
					<th>Type</th>
					<th class="nowrap" nowrap="nowrap">Event #</th>
					<th class="nowrap" nowrap="nowrap">Name</th>
					<th class="nowrap" nowrap="nowrap">Players</th>
					<th class="nowrap" nowrap="nowrap">Human / AI</th>
					<th class="nowrap" nowrap="nowrap">First</th>
					<th class="nowrap" nowrap="nowrap">Period</th>
					<th class="nowrap" nowrap="nowrap">Resources</th>
					<th class="nowrap" nowrap="nowrap">Monsters</th>
					<th class="nowrap" nowrap="nowrap">Buildings</th>
					<th>Message</th>
				</tr>';
		foreach($this->h3mapscan->towns_list as $towno) {
			$town = $towno['data'];

			if($town['eventsnum'] == 0) {
				continue;
			}

			$monlvlprint = false;
			$monIdOffset = 0;
			if($towno['id'] == OBJECTS::RANDOM_TOWN) {
				$monlvlprint = true;
			}

			$rows = $town['eventsnum'];

			$print .= '<tr>
				<td class="ac" rowspan="'.$rows.'">'.(++$n).'</td>
				<td rowspan="'.$rows.'">'.$town['name'].'</td>
				<td rowspan="'.$rows.'" class="nowrap" nowrap="nowrap">'.$towno['pos']->GetCoords().'</td>
				<td rowspan="'.$rows.'" class="nowrap" nowrap="nowrap">'.$town['player'].'</td>
				<td rowspan="'.$rows.'" class="nowrap" nowrap="nowrap">'.$town['affiliation'].'</td>';

			usort($town['events'], 'SortTownEventsByDate');
			foreach($town['events'] as $e => $event) {
				if($e > 0) {
					$print .= '<tr>';
				}

				$resources = [];
				foreach($event['res'] as $rid => $amount) {
					$resources[] = $this->h3mapscan->GetResourceById($rid).' = '.$amount;
				}

				$monsters = [];
				foreach($event['monsters'] as $lvl => $amount) {
					if($amount > 0) {
						$monname = $monlvlprint ? 'Lvl '.($lvl + 1) : $this->h3mapscan->GetCreatureById($this->h3mapscan->CS->TownUnits[$towno['subid']][$lvl]);
						$monsters[] = $monname.' = '.$amount;
					}
				}

				$buildings = [];
				foreach($event['buildings'] as $k => $bbyte) {
					for ($i = 0; $i < 8; $i++) {
						if(($bbyte >> $i) & 0x01) {
							$bid = $k * 8 + $i;
							$buildings[] = $this->h3mapscan->GetBuildingById($bid);
						}
					}
				}

				$print .= '
						<td class="ac">'.($e + 1).'</td>
						<td>'.$event['name'].'</td>
						<td>'.$this->h3mapscan->PlayerColors($event['players']).'</td>
						<td class="ac">'.$event['human'].'/'.$event['computerAffected'].'</td>
						<td class="ac">'.$event['firstOccurence'].'</td>
						<td class="ac">'.$event['nextOccurence'].'</td>
						<td class="nowrap" nowrap="nowrap">'.implode($resources, '<br />').'</td>
						<td class="nowrap" nowrap="nowrap">'.implode($monsters, '<br />').'</td>
						<td>'.implode($buildings, '<br />').'</td>
						<td>'.nl2br($event['message']).'</td>
					</tr>';
			}

		}
		$print .= '</table>';


		//events, pandora box
		$n = 0;
		$print .= '<a name="eventbox"></a>
			<table class="smalltable">
				<tr><th>#</th><th>Event / Box</th><th>Position</th><th>Available for</th><th>Human/AI</th><th>One visit</th>
					<th>Guards</th><th>Content</th>
					<th style="width: 50%;">Text</th>
				</tr>';
		foreach($this->h3mapscan->events_list as $evento) {
			$event = $evento['data'];

			$stack = '';
			$msg = '';
			if(!empty($event['MessageStack'])) {
				$msg = nl2br($event['MessageStack']['message']);
				if(array_key_exists('stack', $event['MessageStack'])) {
					$stack = $this->h3mapscan->PrintStack($event['MessageStack']['stack']);
				}
			}

			$content = [];
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
				$content[] = $this->h3mapscan->GetResourceById($rid).' = '.$amount;
			}
			foreach($event['priSkill'] as $k => $ps) {
				if($ps > 0) {
					$content[] = $this->h3mapscan->GetPriskillById($k).' = '.$ps;
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
				$content[] = $this->h3mapscan->PrintStack($event['stack']);
			}


			$print .= '<tr>
				<td class="ac">'.(++$n).'</td>
				<td>'.$evento['objname'].'</td>
				<td>'.$evento['pos']->GetCoords().'</td>
				<td>'.$this->h3mapscan->PlayerColors($event['availableFor']).'</td>
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
		$print .= '<a name="signs"></a>
			<table class="smalltable">
				<tr><th colspan="2">Signs and bottles</th><th>Position</th><th>Text</th></tr>';
		foreach($this->h3mapscan->messages_list as $msg) {
			$print .= '<tr>
				<td class="ac">'.(++$n).'</td>
				<td>'.$msg['objname'].'</td>
				<td>'.$msg['pos']->GetCoords().'</td>
				<td>'.nl2br($msg['data']['text']).'</td>
			</tr>';
		}
		$print .= '</table>';

		//rumors
		$print .= '<a name="rumors"></a>
			<table class="smalltable">
				<tr><th colspan="3">Rumors</th></tr>';
		if(empty($this->h3mapscan->rumors)) {
			$print .= '<tr><td colspan="3">None</td></tr>';
		}

		foreach($this->h3mapscan->rumors as $k => $rumor) {
			$print .= '<tr>
				<td class="ac">'.($k+1).'</td>
				<td>'.$rumor['name'].'</td>
				<td>'.nl2br($rumor['desc']).'</td>
			</tr>';
		}
		$print .= '</table>';


		//day events
		usort($this->h3mapscan->events, 'EventSortByDate');
		$print .= '<a name="events"></a>
			<table class="smalltable">
				<tr>
					<th class="nowrap" nowrap="nowrap">Events Date</th>
					<th class="nowrap" nowrap="nowrap">Name</th>
					<th class="nowrap" nowrap="nowrap">Human</th>
					<th>AI</th>
					<th class="nowrap" nowrap="nowrap">Players</th>
					<th class="nowrap" nowrap="nowrap">First</th>
					<th class="nowrap" nowrap="nowrap">Interval</th>
					<th class="nowrap" nowrap="nowrap">Resources</th>
					<th>Message</th>
				</tr>';
		foreach($this->h3mapscan->events as $k => $event) {
			$eres = [];
			foreach($event['resources'] as $r => $res) {
				if($res != 0) {
					$eres[] = $this->h3mapscan->GetResourceById($r).' '.$res;
				}
			}

			$print .= '<tr>
				<td class="ac">'.($k+1).'</td>
				<td>'.$event['name'].'</td>
				<td class="ac">'.$event['humanAble'].'</td>
				<td class="ac">'.$event['aiAble'].'</td>
				<td class="nowrap" nowrap="nowrap">'.$this->h3mapscan->PlayerColors($event['players'], true).'</td>
				<td class="ar">'.$event['first'].'</td>
				<td class="ar">'.$event['interval'].'</td>
				<td class="nowrap" nowrap="nowrap">'.implode($eres, '<br />').'</td>
				<td>'.nl2br($event['message']).'</td>
			</tr>';
		}
		$print .= '</table>';


		//keymaster's list
		usort($this->h3mapscan->keys_list, 'KeyMasterSort');
		$n = 0;
		$print .= '<a name="keys"></a>
			<table class="smalltable">
				<tr>
					<th>#</th>
					<th>Keymasters</th>
					<th>Subid</th>
					<th>Type</th>
					<th>Position</th>
				</tr>';
		foreach($this->h3mapscan->keys_list as $key) {
			$color = FromArray($key['subid'], $this->h3mapscan->CS->ObjectColors);

			$print .= '<tr>
				<td class="ac">'.(++$n).'</td>
				<td>'.$key['objname'].'</td>
				<td>'.$key['subid'].'</td>
				<td>'.$color.'</td>
				<td>'.$key['pos']->GetCoords().'</td>
			</tr>';
		}
		$print .= '</table>';

		//monolith list
		ksort($this->h3mapscan->monolith_list);
		$n = 0;
		$print .= '<a name="monolith"></a>
			<table class="smalltable">
				<tr>
					<th>#</th>
					<th>Monolith</th>
					<th>Subid</th>
					<th>Color</th>
					<th>Count</th>
					<th>Positions</th>
				</tr>';
		$prev = false;
		$positions = [];
		foreach($this->h3mapscan->monolith_list as $objid => $liths) {
			ksort($liths);
			foreach($liths as $subid => $lith) {
				$name = $this->h3mapscan->GetObjectById($objid);
				$color = '';
				if($objid == OBJECTS::MONOLITH_TWO_WAY) {
					$color = FromArray($subid, $this->h3mapscan->CS->MonolithsTwo);
				}
				elseif($objid == OBJECTS::MONOLITH_ONE_WAY_ENTRANCE || $objid == OBJECTS::MONOLITH_ONE_WAY_EXIT) {
					$color = FromArray($subid, $this->h3mapscan->CS->MonolithsOne);
				}

				$print .= '
					<tr>
						<td class="ac">'.(++$n).'</td>
						<td>'.$name.'</td>
						<td class="ac">'.$subid.'</td>
						<td>'.$color.'</td>
						<td class="ac">'.count($lith).'</td>
						<td>'.implode($lith, '<br />').'</td>
					</tr>';
			}
		}
		$print .= '</table>';


		$print .= '<br />Templates count: '.$this->h3mapscan->objTemplatesNum.'<br />';

		$print .= 'Objects type count: '.count($this->h3mapscan->objects_unique).'<br />';
		$print .= 'Objects total count: '.$this->h3mapscan->objectsNum.'<br />';

		asort($this->h3mapscan->objects_unique);
		$n = 0;
		$print .= '<a name="objects"></a>
			<table class="smalltable">
				<tr><th>Objects</th><th>ID</th><th>Name</th><th>Count</th></tr>';
		foreach($this->h3mapscan->objects_unique as $objid => $obju) {
			$print .= '<tr>
				<td class="ac">'.(++$n).'</td>
				<td>'.$objid.'</td>
				<td>'.$obju['name'].'</td>
				<td class="ar">'.$obju['count'].'</td>
			</tr>';
		}
		$print .= '</table>
						</td>
					</tr>
				</table>
			</div>';

		echo $print;
		if($this->h3mapscan->maphtmcache) {
			file_write(MAPDIRINFO.str_ireplace('.h3m', '.htm', $this->h3mapscan->mapfile).'.gz', gzencode($print));
		}

		//$this->h3mapscan->tm->Measure('End');
		//$this->h3mapscan->tm->showTimes();
	}

}


?>
