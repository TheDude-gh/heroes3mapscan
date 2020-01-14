<?php

class H3MAPBUILD {
	const IMGSIZE = 576;
	const ROE = 0x0e;
	const AB	= 0x15;
	const SOD = 0x1c;
	const WOG = 0x33;
	const HOTA = 0x20;

	private $version = '';
	private $versionname = '';
	private $mapname = '';

	private $underground = 0;
	
	private $mapsize = 0;
	private $terrain = array(); //color data

	private $img;
	private $imgcolors = array();
	private $mapcolors = array();
	
	private $name = '';

	private $mapdata = '';
	private $mapfile = '';
	private $imgfile = '';
	private $mapfilename = '';
	private $mapfileout = '';
	private $mapimage; //mapfile name for DB

	private $playersnum = 8;
	private $playerscolours = array('Red', 'Blue', 'Tan', 'Green', 'Orange', 'Purple', 'Teal', 'Pink');

	private $pos = 0;
	private $length = 0;
	
	private $buildMapImage = true;

	private $filebad = false;


	public function __construct($mapfile, $imgfile, $size) {
		$this->mapfile = $mapfile;
		$this->imgfile = $imgfile;
		$this->GetMapSize($size);

		$this->version = $this::SOD;

		$this->PrepareImage();
	}

	public function PrepareImage() {
		if(!file_exists($this->imgfile)){
			$this->filebad = true;
			return;
		}
	
		$fileinfo = pathinfo($this->imgfile);
		$ext = strtolower($fileinfo['extension']);
		if($ext == 'png'){
			$imgi = imagecreatefrompng($this->imgfile);
		}
		elseif($ext == 'jpg' || $ext == 'jpeg'){
			$imgi = imagecreatefromjpeg($this->imgfile);
		}
		elseif($ext == 'gif'){
			$imgi = imagecreatefromgif($this->imgfile);
		}
		else {
			$this->filebad = true;
			return;
		}
	
		
		list($width, $height) = getimagesize($this->imgfile);
		
		$this->img = imagecreatetruecolor($this->mapsize, $this->mapsize);
		imagepalettecopy($this->img, $imgi);


		imagecopyresized($this->img, $imgi, 0, 0, 0, 0, $this->mapsize, $this->mapsize, $width, $height);
		imagedestroy($imgi);

		imagepng($this->img, 'mapsb/temp.png');
		
		$this->mapcolors = array(
			array(0x52, 0x39, 0x08),
			array(0xde, 0xce, 0x8c),
			array(0x00, 0x42, 0x00),
			array(0xb5, 0xc6, 0xc6),
			array(0x4a, 0x84, 0x6b),
			array(0x84, 0x73, 0x31),
			array(0x84, 0x31, 0x00),
			array(0x4a, 0x4a, 0x4a),
			array(0x08, 0x52, 0x94),
			//array(0x00, 0x00, 0x00),
		);
		
		
		for($y = 0; $y < $this->mapsize; $y++){
			for($x = 0; $x < $this->mapsize; $x++){
				 $px = imagecolorat($this->img, $x, $y);
				 $rgb = imagecolorsforindex($this->img, $px);
				 //vd($rgb);
				 $this->terrain[0][$y][$x] = $this->GetTerrainByColour($rgb);
			}
		}

		imagedestroy($this->img);

		/*for($x = 0; $x < $this->mapsize; $x++){
			echo implode($this->terrain[0][$x], '').'<br />';
		}*/

		$this->DisplayMap();
	}
	
	public function BuildMap() {
		if($this->filebad) return;
		
		$this->pos = 0;

		//REGEXP read to write
		// \$this->(.*) = (.*)$
		// $2 //$1
	
		$this->WriteUint32($this->version); //version
		if($this->version == $this::HOTA) {
			$this->WriteZeroBytes(4);
		}
		
		$this->WriteUint8(0); //hero presenc //hero_any_onmap
		$this->WriteUint32($this->mapsize); //mapsize
		$this->WriteUint8(0); //underground
		$this->WriteString('Image'); //map_name
		$this->WriteString('Test'); //description
		$this->WriteUint8(0); //map_diff
		
		
		$this->WriteUint8(7); //hero's cap //hero_levelcap

		$this->WritePlayersData();
		// Special Victory Condition
		$this->VictoryCondition();
		// Special loss condition
		$this->LossCondition();
		// Teams
		$this->Teams();
		
		// Free Heroes
		$this->FreeHeroes();

		$this->WriteZeroBytes(31); //unused space
		
		// Artefacts
		$this->Artifacts();
		//allowed spells and abilities
		$this->AllowedSpellsAbilities();
		// Rumors
		$this->Rumors();
		// Heroes Params
		$this->WritePredefinedHeroes();
		// Map
		$this->WriteTerrain();
		//object definitions
		$this->WriteDefInfo();
		//objects
		$this->WriteObjects();
		//global event
		$this->WriteEvents();
		
		file_write('mapsb/mapimg.h3m', $this->mapdata);
	}
	
	public function DisplayMap() {
		if($this->buildMapImage) {
			$this->img = imagecreate($this->mapsize, $this->mapsize); //map by size
			
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

				$imgmapname = 'mapsb/mapimgout.png';
				//imagecopyresized($imgmap, $this->img, 0, 0, 0, 0, $this::IMGSIZE, $this::IMGSIZE, $this->mapsize, $this->mapsize);
				imagepng($this->img, $imgmapname);
			}

			imagedestroy($this->img);
		}
	}
	

	private function GetCellSurface($surface){
		switch($surface){
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
	
	//gets terrain by nearest colour
	private function GetTerrainByColour($rgb){
		$nearest_coef = 0xffff;
		$nearest_col = 0; //terrain index
		$cc = count($this->mapcolors);
		for($c = 0; $c < $cc; $c++) {
			$coef = abs($rgb['red'] - $this->mapcolors[$c][0]) + abs($rgb['green'] - $this->mapcolors[$c][1]) + abs($rgb['blue'] - $this->mapcolors[$c][2]);
			if($coef < $nearest_coef){
				$nearest_coef = $coef;
				$nearest_col = $c;
			}
		}
		return $nearest_col;
	}

	private function GetMapSize($size) {
		if(is_numeric($size)){
			$size = round($size / 36, 0) * 36;
			$this->mapsize = max(min($size, 252), 0);

			/*if($size <= 36) $this->mapsize = 36;
			elseif($size <= 72) $this->mapsize = 72;
			elseif($size <= 108) $this->mapsize = 108;
			elseif($size <= 144) $this->mapsize = 144;
			elseif($size <= 180) $this->mapsize = 180;
			elseif($size <= 216) $this->mapsize = 216;
			elseif($size <= 252) $this->mapsize = 252;
			else $this->mapsize = 252;*/
		}
		else {
			switch($size) {
				case 'S': $this->mapsize = 36; break;
				case 'M': $this->mapsize = 72; break;
				case 'L': $this->mapsize = 108; break;
				case 'XL': $this->mapsize = 144; break;
				case 'H': $this->mapsize = 180; break;
				case 'XH': $this->mapsize = 216; break;
				case 'G': $this->mapsize = 252; break;
				default: $this->mapsize = 144; break;
			}
		}

		vd($size.' -> '.$this->mapsize);
		if($this->mapsize > 144) $this->version = $this::HOTA;
	}
	
	
	private function WritePlayersData() {
		for($i = 0; $i < $this->playersnum; $i++){
			$this->WriteUint8(0); //human
			$this->WriteUint8(0); //ai

			$this->WriteUint8(0); //behaviour
			$this->WriteUint8(0); //town is se
			$this->WriteUint16(HNONETOWN); //allowed towns
			$this->WriteUint8(0); //is random town
			$this->WriteUint8(0); //has main town
			$this->WriteUint8(0); //random hero
			$this->WriteUint8(HNONE); //main hero type
			$this->WriteUint8(1); //placeholder
			$this->WriteUint8(0); //hero count
			$this->WriteZeroBytes(3); //free
		}
	}

	// Special Victory Condition
	private function VictoryCondition() {
		$this->WriteUint8(0xff);
	}

	// Special loss condition
	private function LossCondition() {
		$this->WriteUint8(0xff);
	}

	// Teams
	private function Teams() {
		$this->WriteUint8(0);
	}

	// Free Heroes
	private function FreeHeroes() {
		if($this->version == $this::HOTA) {
			$this->WriteUint32(HEROES_QUANTITY_HOTA);
		}
		$this->WriteZeroBytes(20); //heroes availability
		if($this->version == $this::HOTA) {
			$this->WriteZeroBytes(3);
		}
		$this->WriteUint32(0); //placeholders
		$this->WriteUint8(0); //count
	}

	// Artefacts
	private function Artifacts() {
		if($this->version == $this::HOTA) {
			$this->WriteUint32(168);
		}
		$this->WriteZeroBytes(18); //artifacts availability
		if($this->version == $this::HOTA) {
			$this->WriteZeroBytes(3);
		}
	}

	//allowed spells and abilities
	private function AllowedSpellsAbilities() {
		$this->WriteZeroBytes(SPELL_BYTE);
		$this->WriteZeroBytes(SECSKILL_BYTE);
	}

	// Rumors
	private function Rumors() {
		$this->WriteUint32(0); //count
	}

	// Heroes Params
	private function WritePredefinedHeroes() {
		if($this->version == $this::HOTA) {
			$this->WriteUint32(HEROES_QUANTITY_HOTA);
		}
		$this->WriteZeroBytes(HEROES_QUANTITY);
		if($this->version == $this::HOTA) {
			$this->WriteUint32(HEROES_QUANTITY_HOTA - HEROES_QUANTITY);
		}
	}

	// Map
	private function WriteTerrain() {
		for($z = 0; $z < $this->underground + 1; $z++) {
			for($x = 0; $x < $this->mapsize; $x++) {
				for($y = 0; $y < $this->mapsize; $y++) {
					$cell = new MapCell();
					$this->WriteUint8($this->terrain[0][$x][$y]); //terrain type
					$this->WriteUint8(0); //surface
					$this->WriteUint8(0); //river
					$this->WriteUint8(0); //river
					$this->WriteUint8(0); //road
					$this->WriteUint8(0); //road
					$this->WriteUint8(0); //mirror

					$this->terrain[$z][$x][$y] = $cell;
				}
			}
		}
	}

	//object definitions
	private function WriteDefInfo() {
		$this->WriteUint32(0); //count
	}

	//objects
	private function WriteObjects() {
		$this->WriteUint32(0); //count
	}

	//global event
	private function WriteEvents() {
		$this->WriteUint32(0); //count
	}

	private function fix64($numL, $numH){
		if($numH < 0) $numH += 4294967296;
		if($numL < 0) $numL += 4294967296;
		$num = bcadd($numL, bcmul($numH, 4294967296));
		if($num > bcpow(2, 63)) return bcsub($num, bcpow(4294967296, 2)); // 2, 64
		return $num;
	}

	private function WriteUint8($byte){
		//cC sS lL qQ
		$this->mapdata .= pack('C', $byte);
		$this->pos++;
	}

	private function WriteUint16($uint16){
		$this->mapdata .= pack('S', $uint16);
		$this->pos += 2;
	}

	private function WriteUint32($uint32){
		$this->mapdata .= pack('L', $uint32);
		$this->pos += 4;
	}

	private function WriteInt32($int32){
		$this->mapdata .= pack('l', $int32);
		$this->pos += 4;
	}
	

	/*private function WriteUint64(){
		return $this->fix64($this->WriteUint32(), $this->WriteUint32());
	}*/

	private function WriteString($string = ''){
		$strlen = strlen($string);
		$this->mapdata .= $this->WriteUint32($strlen);
		if($strlen > 0){
			$this->mapdata .= $string;
		}
		$this->pos += 4 + $strlen;
	}

	private function WriteZeroBytes($count = 0){
		for($i = 0; $i < $count; $i++){
			$this->mapdata .= "\x0";
			$this->pos++;
		}
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
