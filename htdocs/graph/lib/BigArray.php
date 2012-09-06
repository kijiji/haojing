<?php
//yubing@baixing.com

class BigArray implements Iterator {
	private $tmpFile;
	
	public function __construct() {
		$this->tmpFile = tmpfile();
	}
	
	public function push($row) {
		fwrite($this->tmpFile, PHP_EOL . $row);
	}

	public function rewind() {
		rewind($this->tmpFile);
		if($this->valid()) {
			$this->current(); 	//Skip空的第一行
		}
	}

	public function valid() {
		return !feof($this->tmpFile);
	}

	public function current() {
		return trim(fgets($this->tmpFile));
	}

	public function next() {}
	public function key() {}

}
