<?php
class Movie {

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

	private $lDuration;
	private $lFileSize;
	private $lBitrate;
	private $lWidth;
	private $lHeight;

	private $lSubTitles;

	/**
	* Constructor. This creates a Movie object.
	* @param int $pFilename
	* @return Movie
	*/
	function __construct($pFilename) {
		if ( ($lMovieInfo = @stat($pFilename)) !== false) {
			$this->lID = trim($pFilename);
			$this->lFileSize = $lMovieInfo["size"];
			$lMovieInfo = pathinfo($this->lID);
			$this->lName = trim(str_ireplace(array("ESiR","dvd","vpc","rip","Sels","proper","dvdrip","axxo","klaxxon","fxg","xvid","divx","SLeT","SiLENT","vhs","bluray","x264","sinners","720p","DiSSOLVE","deity","1080p","HDTV","iLL","AEN","nlsubs","Gaiagrim","DVDSCR","AC3","BDRip","KLN","TVRip","()","NLT","release","pdtv","lol","xor","riot","immerse"),"",$lMovieInfo["filename"]));
			while (substr($this->lName,-1) == "-") {
				$this->lName = trim(substr($this->lName,0,-1));
			}
			while (substr($this->lName,0,1) == "-") {
				$this->lName = trim(substr($this->lName,1));
			}
			$this->lName = trim(str_ireplace(array("[]","( - )"),"",$this->lName));
			$this->lName = trim(str_ireplace(array(".","_")," ",$this->lName));
			$this->lName = ucfirst(trim(str_ireplace("-"," - ",$this->lName)));

			$this->loadInfo();
			$this->_findSubtitles();

		}
	}

	private function _findSubtitles() {
		$this->lSubTitles = array();
		$lFileInfo = pathinfo($this->lID);
		$lScanPattern = $lFileInfo['dirname'] . '/' . $lFileInfo['filename'] . '*.{[sS][rR][tT]}';
		foreach (glob($lScanPattern,GLOB_BRACE) as $lSubTitle) {
			$lSubTitleInfo = pathinfo($lSubTitle);
			$lLanguage = substr($lSubTitleInfo['filename'],-2);
			if (!empty($lLanguage) && strlen($lLanguage) >= 2) {
				$this->lSubTitles[strtolower($lLanguage)] = $lSubTitle;
			}
		}
	}

	public function loadInfo() {
		if (Settings::getMediaInfoxecutable() !== false) {
			$lMovieMediaInfo = new MediaInfo($this->lID);
			$this->lDuration = $lMovieMediaInfo->getDuration(); // In seconds
			$this->lBitrate = $lMovieMediaInfo->getBitrate();
			$this->lWidth = $lMovieMediaInfo->getWidth();
			$this->lHeight = $lMovieMediaInfo->getHeight();
			unset($lMovieMediaInfo);
		}
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
	* Get the duration.
	* @return int
	*/
	public function getDuration() {
		return (is_numeric($this->lDuration) ? $this->lDuration : -1);
	}

	/**
	* Get the file size.
	* @return int
	*/
	public function getFileSize() {
        return (is_numeric($this->lFileSize) ? $this->lFileSize : -1);
	}

	public function getBitrate() {
        return (is_numeric($this->lBitrate) ? $this->lBitrate : -1);
	}

	public function getResolution() {
		return (is_numeric($this->lWidth) && is_numeric($this->lHeight) ? $this->lWidth . "x" . $this->lHeight : '-1');
	}

	public function getAvailableSubtitleLanguages() {
		return array_keys($this->lSubTitles);
	}

	public function getSubtitle($pLanguages) {
		if (!is_array($pLanguages)) $pLanguages = explode(',',$pLanguages);
		foreach ($pLanguages as $lLanguage) {
			if (!empty($this->lSubTitles[strtolower($lLanguage)])) return $this->lSubTitles[strtolower($lLanguage)];
		}
		if (is_array($this->lSubTitles) && count($this->lSubTitles) > 0 ) return array_shift($this->lSubTitles);
	}

	public function isHD() {
		return $this->lWidth == 1920 && $this->lHeight == 1080;
		//return false;
	}
}