<?php

class Webcam {

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

	private $lUsername;
	private $lPassWord;
	private $lUrl;
	private $lProtocol;
	
	

	private $lDuration;
	private $lFileSize;
	private $lBitrate;
	private $lWidth;
	private $lHeight;

	/**
	* Constructor. This creates a Movie object.
	* @param int $pFilename
	* @return Movie
	*/
	function __construct($pUrl) {
		$lUrl = parse_url($pUrl);
		
		$this->lUrl = $pUrl;
		$this->lName = trim($pUrl);
		$this->lID = $this->lName;
	
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
	
	public function isHD() {
		return false;
	}
}