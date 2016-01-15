<?php
class Channel {

	/**
	* @access private
	* @var string
	*/
	private $lID;

	/**
	* @access private
	* @var string
	*/
	private $lName;

	private $lMarker;

	private $lHD;

	/**
	* Constructor. This creates a Channel object.
	* @param int $pID
	* @param string $pName
	* @return Channel
	*/
	function __construct($pID,$pName) {
		$this->lID = trim(str_replace(array(' ','"'),array('%20','%22'),$pID));
		$this->lName = trim($pName);
		$this->lMarker = (preg_match('/1:64:(.*):0:0:0:0:0:0:0::/i',$this->lID) == 1);
		$this->lHD = (preg_match('/\d:\d:' . Settings::getDMHDID() . ':/',$this->lID) == 1);
	}

	/**
	* Get the channel ID.
	* @return string
	*/
	public function getID() {
		return $this->lID;
	}

	/**
	* Get the channel name.
	* @return string
	*/
	public function getName($pSafe = false) {
		return $this->lName;
	}

	public function isMarker() {
		return $this->lMarker;
	}

	public function isHD() {
		return ($this->lHD == true);
	}
}