<?php

const MAXINT32 = 0x80000000; //1 << 31;
const MININT32 = 0x100000000; //1 << 32; //without minus
	
class ByteReader {
	
	public $pos = 0;
	public $length = 0;
	public $data;
	public $skipstrings = false;


	public function __construct($data = '') {
		$this->data = $data;
		$this->pos = 0;
		$this->length = strlen($this->data);
	}
	
	public function ReadUint8() {
		if($this->pos >= $this->length) {
			dbglog();
			throw new Exception('Bad position '.$this->pos);
			return;
		}
		return ord($this->data[$this->pos++]);
	}

	public function ReadUint16() {
		if($this->pos >= $this->length - 1) {
			throw new Exception('Bad position '.$this->pos);
			return;
		}

		$res = ord($this->data[$this->pos++]);
		$res += ord($this->data[$this->pos++]) << 8;
		return $res;
	}

	public function ReadUint32() {
		if($this->pos >= $this->length - 3) {
			throw new Exception('Bad position '.$this->pos);
			return;
		}

		$res  = ord($this->data[$this->pos++]);
		$res += ord($this->data[$this->pos++]) << 8;
		$res += ord($this->data[$this->pos++]) << 16;
		$res += ord($this->data[$this->pos++]) << 24;
		return $res;
	}

	public function ReadInt8() {
		if($this->pos >= $this->length) {
			throw new Exception('Bad position '.$this->pos);
			return;
		}
		$res  = ord($this->data[$this->pos++]);
		if($res > 0x7E) {
			$res -= 0x100;
		}
		return $res;
	}

	public function ReadInt32() {
		if($this->pos >= $this->length - 3) {
			throw new Exception('Bad position '.$this->pos);
			return;
		}

		$res  = ord($this->data[$this->pos++]);
		$res += ord($this->data[$this->pos++]) << 8;
		$res += ord($this->data[$this->pos++]) << 16;
		$res += ord($this->data[$this->pos++]) << 24;
		if($res > MAXINT32) {
			$res -= MININT32;
		}
		return $res;
	}

	public function ReadString($length = -1) {
		$res = '';
		if($this->pos >= $this->length) {
			dbglog();
			$this->data = null;
			throw new Exception('Bad string pos '.$this->pos);
			return;
		}

		if($length == -1) {
			$length = $this->ReadUint32();
			if($length == 0) {
				return $res;
			}
			if($length > 100000 || $length < 0) {
				dbglog();
				$this->data = null;
				throw new Exception('Too long string '.$length);
				return;
			}

			//fastread
			if($this->skipstrings) {
				$this->pos += $length;
				return '';
			}

			$res = substr($this->data, $this->pos, $length);
			$this->pos += $length;
		}
		elseif($length > 0) {
			$res = trim(substr($this->data, $this->pos, $length));
			$this->pos += $length;
		}/*
		else {
			return;
			while(ord($this->data[$this->pos]) != 0) {
				$res .= $this->data[$this->pos++];
			}
			$this->pos++; // advance pointer after finding the 0
		}
		*/

		return $res;
	}

	public function ReadStringH2() {
		$res = '';
		if($this->pos >= $this->length) {
			dbglog();
			$this->data = null;
			throw new Exception('Bad string pos '.$this->pos);
			return;
		}

		$length = $this->ReadUint16();
		if($length == 0) {
			return $res;
		}
		if($length > 100000 || $length < 0) {
			dbglog();
			$this->data = null;
			throw new Exception('Too long string '.$length);
			return;
		}

		$res = substr($this->data, $this->pos, $length);
		$this->pos += $length;

		return $res;
	}

	public function SkipBytes($bytes = 31) {
		$this->pos += $bytes;
	}

	public function SetPos($pos) {
		$this->pos = $pos;
	}

	public function GetPos() {
		return $this->pos;
	}
	
	public function ResetPos() {
		return $this->pos = 0;
	}
	
	public function Length() {
		return strlen($this->data);
	}

	public function GetLength() {
		return $this->length;
	}
	
	public function Rewind($bytes) {
		$this->pos -= $bytes;
	}
	
	//print current position
	public function ppos() {
		vd(dechex($this->pos). ' '.$this->pos);
	}
	
	public function rpos() {
		return '0x'.dechex($this->pos);
	}

	public function pvar($var) {
		echo ' '.dechex($var). ' '.$var.'<br />';
	}

	public function bvar($var) {
		$bprint = sprintf('%08b', $var & 0xff);
		if($var > 0xff) {
			$bprint = sprintf('%08b', ($var >> 8) & 0xff).' '.$bprint;
		}
		return $bprint;
	}

}

?>
