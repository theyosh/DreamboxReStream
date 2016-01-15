<?php
class Program {

	/**
	* @access private static
	* @var string
	*/
	private $lID;

	/**
	* @access private static
	* @var int
	*/
	private $lStartTime;

	/**
	* @access private static
	* @var int
	*/
	private $lStopTime;

	/**
	* @access private static
	* @var int
	*/
	private $lDuration;

	/**
	* @access private static
	* @var string
	*/
	private $lTitle;

	/**
	* @access private static
	* @var string
	*/
	private $lDescription;

	/**
	* @access private static
	* @var string
	*/
	private $lLongDescription;

	/**
	* Constructor. This creates a Program object.
	* @param int $pID
	* @param int $pStartTime
	* @param int $pDuration
	* @param string $pTitle
	* @param string $pDescription
	* @param string $pLongDescription
	* @return Program
	*/
	public function __construct($pID,$pStartTime,$pDuration,$pTitle,$pDescription,$pLongDescription) {
		$this->lID = trim($pID);
		$this->lStartTime = trim($pStartTime);
		$this->lDuration = trim($pDuration);
		$this->lTitle = trim($pTitle);
		$this->lDescription = trim($pDescription);
		$this->lLongDescription = trim($pLongDescription);
		if ($this->lDescription == "") $this->lDescription = trim($this->lLongDescription);
		$this->setStopTime();
	}

	/**
	* Set the stop time based in start time and duration
	*/
	private function setStopTime() {
		$this->lStopTime = $this->lStartTime + $this->lDuration;
	}

	/**
	* Get the program id.
	* @return int
	*/
	public function getID() {
		return $this->lID;
	}

	/**
	* Get the program start time in seconds.
	* @return array
	*/
	public function getStartTime() {
		return $this->lStartTime;
	}

	/**
	* Get the program stop time in seconds.
	* @return array
	*/
	public function getStopTime() {
		return $this->lStopTime;
	}

	/**
	* Get the program duration in seconds.
	* @return array
	*/
	public function getDuration() {
		return $this->lDuration;
	}

	/**
	* Get the program title.
	* @return array
	*/
	public function getTitle() {
		return $this->lTitle;
	}

	/**
	* Get the program description.
	* @return array
	*/
	public function getDescription() {
		return $this->lDescription;
	}

	/**
	* Get the program long description.
	* @return array
	*/
	public function getLongDescription($pJSSave = false) {
		return $this->lLongDescription;
	}
}