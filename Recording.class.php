<?php
class Recording {

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


	private $lDescription;
	private $lLongDescription;
	private $lServiceName;
	private $lStartTime;
	private $lDuration;
	private $lFilename;
	private $lFileSize;

	/**
	* Constructor. This creates a Channel object.
	* @param int $pID
	* @param string $pIP
	* @param string $pType
	* @param string $pName
	* @return Channel
	*/
	function __construct($pID,$pName, $pDescription, $pLongDescription, $pServiceName, $pStartTime, $pDuration, $pFilename, $plFileSize = 0) {
		$this->lID = trim(str_replace('1:0:0:0:0:0:0:0:0:0:','',$pID));
		$this->lName = trim($pName);
		$this->lDescription = trim($pDescription);
		$this->lLongDescription = trim($pLongDescription);
		$this->lServiceName = trim($pServiceName);
		$this->lStartTime = trim($pStartTime);
		$this->lDuration = trim($pDuration);
		$lStartPos = strripos($pFilename,"/");
		$this->lFilename = trim(substr($pFilename,$lStartPos+1));
		$this->lFileSize = trim($plFileSize);
		
		$this->lDuration = explode(":",$this->lDuration);
		$this->lDuration = ($this->lDuration[0] * 60) + $this->lDuration[1];
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
	public function getName() {
		return $this->lName;
	}

	 /**
	* Get the description.
	* @return string
	*/
	public function getDescription() {
		return $this->lDescription;
	}
	
	public function getLongDescription() {
		return $this->lLongDescription;
	}
	 
	 /**
	* Get the service name.
	* @return string
	*/
	public function getServiceName() {
		 return $this->lServiceName;
	}
   
	/**
	* Get the start time
	* @return int
	*/
	public function getStartTime() {
		return (is_numeric($this->lStartTime) ? $this->lStartTime : 1);
	}
	
	public function getStopTime() {
		return -1;
	}

	/**
	* Get the duration.
	* @return int
	*/
	public function getDuration() {
		return (is_numeric($this->lDuration) ? $this->lDuration : 0);
	}

	/**
	* Get the file name.
	* @return string
	*/
	public function getFilename() {
		return $this->lFilename;
	}

	/**
	* Get the file size.
	* @return int
	*/
	public function getFileSize() {
		return (is_numeric($this->lFileSize) ? $this->lFileSize : 0);
	}
	
	public function isHD() {
		return true;
	}
}
