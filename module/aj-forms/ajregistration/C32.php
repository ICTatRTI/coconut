<?php
class C32
{
	public $value;
    public $ENCODE_MAP = array(
    "o"=> "0",
    "O"=> "0",
    "0"=> "0",
    "i"=> "1",
    "I"=> "1",
    "l"=> "1",
    "1"=> "1",
    "2"=> "2",
    "3"=> "3",
    "4"=> "4",
    "5"=> "5",
    "6"=> "6",
    "7"=> "7",
    "8"=> "8",
    "9"=> "9",
    "a"=> "A",
    "A"=> "A",
    "B"=> "B",
    "c"=> "C",
    "C"=> "C",
    "d"=> "D",
    "D"=> "D",
    "e"=> "E",
    "E"=> "E",
    "f"=> "F",
    "F"=> "F",
    "g"=> "G",
    "G"=> "G",
    "h"=> "H",
    "H"=> "H",
    "j"=> "J",
    "J"=> "J",
    "k"=> "K",
    "K"=> "K",
    "m"=> "M",
    "M"=> "M",
    "n"=> "N",
    "N"=> "N",
    "p"=> "P",
    "P"=> "P",
    "q"=> "Q",
    "Q"=> "Q",
    "r"=> "R",
    "R"=> "R",
    "s"=> "S",
    "S"=> "S",
    "t"=> "T",
    "T"=> "T",
    "v"=> "V",
    "V"=> "V",
    "w"=> "W",
    "W"=> "W",
    "x"=> "X",
    "X"=> "X",
    "y"=> "Y",
    "Y"=> "Y",
    "z"=> "Z",
    "Z"=> "Z"
  );

    public $POOL = array('0','1','2','3','4','5','6','7','8','9','A','B','D','E','F','G','H','J','K','M','N','P','Q','R','S','T','V','W','X','Y','Z');

    public $POOL_MAP = array(
    "0"=> 0,
    "1"=> 1,
    "2"=> 2,
    "3"=> 3,
    "4"=> 4,
    "5"=> 5,
    "6"=> 6,
    "7"=> 7,
    "8"=> 8,
    "9"=> 9,
    "A"=> 10,
    "B"=> 11,
    "C"=> 12,
    "D"=> 13,
    "E"=> 14,
    "F"=> 15,
    "G"=> 16,
    "H"=> 17,
    "J"=> 18,
    "K"=> 19,
    "M"=> 20,
    "N"=> 21,
    "P"=> 22,
    "Q"=> 23,
    "R"=> 24,
    "S"=> 25,
    "T"=> 26,
    "V"=> 27,
    "W"=> 28,
    "X"=> 29,
    "Y"=> 30,
    "Z"=> 31
  );

    public $CHECKSUM_POOL = array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F','G','H','J','K','M','N','P','Q','R','S','T','V','W','Z','Y','Z','*','~','$','=','U');

    public function __construct() {
        $this->value = '0';
    }

    public function parseInt($unclean) {
		if ($unclean === null) {
			$unclean = '';
		}
		$this->value = generateValues($unclean);
    }

    public function generateValues($unclean) {
		$results = array();
		$len = unclean.length;
		for ($i = 0; $i < $len; $i++) {
			$c = $unclean[$i];
			$results.push($ENCODE_MAP[$c]);
		}
		return $results.join('', '');
    }

    public function toTen() {
        function suma($a, $b) {
			return $a + $b;
		}
		$_value = $this->value;
		$results = array();
		$len = strlen($_value);
		for ($i = 0; $i < $len; $i++) {
			$n = $_value[$i];
			array_push($results, ($this->POOL_MAP[$n] * pow(32, $len - ($i + 1))));
    	}
		return array_reduce($results, 'suma' );
	}

	public function addChecksum() {
		$checksum = $this->calcCheckSum();
		$this->value = $this->value . $checksum;
	}

    public function calcChecksum() {
      $ten = $this->toTen();
      $ten_in_float = floatval($ten);
      return $this->CHECKSUM_POOL[fmod($ten_in_float, 37)];
    }

    public function isValid() {
    	$_value = $this->value;
    	return $_value.substr(-1, 1) === $this->calcChecksum($this->toTen($_value.substring(0, $_value.length - 1)));
	}

	public function getRandom($length) {
	    if ($length === null) {
	      $length = 0;
	    }
	    $results = array();
	    $ref = $length - 1;
	    for ($i = 0, $j = 0; 0 <= $ref ? $j <= $ref : $j >= $ref; $i = 0 <= $ref ? ++$j : --$j) {
	        array_push($results, $this->POOL[array_rand($this->POOL)]);
	    }
	    $this->value = implode($results);
	}
}
