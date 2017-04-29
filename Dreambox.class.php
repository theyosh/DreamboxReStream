<?php
class Dreambox
{
	/**
	* @access private
	* @var string
	*/
	private $lIPNumber;

	private $lPortNumber;

	/**
	* @access private
	* @var string
	*/
	private $lBouquets;

	/**
	* @access private
	* @var string
	*/
	private $lType;

	/**
	* @access private
	* @var ProgramGuide
	*/
	private $lProgramGuide;

	/**
	* @access private
	* @var string
	*/
	private $lRecordings;

	/**
	* @access private
	* @var string
	*/
	private $lMovies;

	private $lWebCams;

	/**
	* @access private
	* @var array
	*/
	private $lAuthentication;

	private $lOnline;

	private $lCacheFile = 'dreambox.cache';

	private $lEPGFast = true;

	private $lWebInfoOK = false;

	/**
	* Constructor. This creates the Dreambox object. If it exists in the session cache, it will be recreated from the cahce instead a new object.
	* @return Dreambox
	*/
	function __construct()
	{
		global $gDebugObj, $gCachePurge;
		if (!$gCachePurge && isset($_SESSION["ReStream2.0"]["Dreambox"]) && isset($_SESSION["ReStream2.0"]["Dreambox"]["Obj"])) {
			// Use cached version of the dreambox object
			$lDreamboxObj = unserialize($_SESSION["ReStream2.0"]["Dreambox"]["Obj"]);
			$this->setDreamboxIP($lDreamboxObj->lIPNumber);
			$this->setDreamboxWebPort($lDreamboxObj->lPortNumber);
			$this->setDreamboxType($lDreamboxObj->lType);
			$this->lAuthentication = array();
			$this->lAuthentication = array($lDreamboxObj->lAuthentication[0],$lDreamboxObj->lAuthentication[1]);
			$this->lOnline = $lDreamboxObj->lOnline;
			if (isset($lDreamboxObj->lBouquets)) {
				$this->lBouquets = $lDreamboxObj->lBouquets;
			}
			if (isset($lDreamboxObj->lProgramGuide)) {
				$this->lProgramGuide = $lDreamboxObj->lProgramGuide;
			}
			if (isset($lDreamboxObj->lRecordings)) {
				$this->lRecordings = $lDreamboxObj->lRecordings;
			}
			if (isset($lDreamboxObj->lMovies)) {
				$this->lMovies = $lDreamboxObj->lMovies;
			}
			if (isset($lDreamboxObj->lWebCams)) {
				$this->lWebCams = $lDreamboxObj->lWebCams;
			}
			$this->lEPGFast = false;
		} else if (!$gCachePurge && ( time() - @filectime($this->lCacheFile) < (60 * 60 * (Settings::getEPGLimit())))) {
			// Load cache from disk....
			$lDreamboxObj = unserialize(file_get_contents($this->lCacheFile));
			$this->setDreamboxIP($lDreamboxObj->lIPNumber);
			$this->setDreamboxWebPort($lDreamboxObj->lPortNumber);
			$this->setDreamboxType($lDreamboxObj->lType);
			$this->lAuthentication = array();
			$this->lAuthentication = array($lDreamboxObj->lAuthentication[0],$lDreamboxObj->lAuthentication[1]);
			$this->lOnline = $lDreamboxObj->lOnline;
			if (isset($lDreamboxObj->lBouquets)) {
				$this->lBouquets = $lDreamboxObj->lBouquets;
			}
			if (isset($lDreamboxObj->lProgramGuide)) {
				$this->lProgramGuide = $lDreamboxObj->lProgramGuide;
			}
			if (isset($lDreamboxObj->lRecordings)) {
				$this->lRecordings = $lDreamboxObj->lRecordings;
			}
			if (isset($lDreamboxObj->lMovies)) {
				$this->lMovies = $lDreamboxObj->lMovies;
			}
			if (isset($lDreamboxObj->lWebCams)) {
				$this->lWebCams = $lDreamboxObj->lWebCams;
			}
			$this->lEPGFast = false;
			$this->serializeMe(false);
			if (Settings::getDebug()) $gDebugObj->setDebugMessage('DreamboxObj','Created file cached DreamboxObj');
		} else {
			// Initialize new object
			$this->setDreamboxIP(Settings::getDreamboxIP());
			$this->setDreamboxWebPort(Settings::getDreamboxWebPort());
			$this->setDreamboxType(Settings::getEnigmaVersion());
			$this->lAuthentication = array(Settings::getDreamboxUserName(),Settings::getDreamboxPassword());
			$this->lBouquets = null;
			$this->lRecordings = null;
			$this->lProgramGuide = null;
			$this->lMovies = null;
			$this->lWebCams = null;
			$this->lOnline = -1;
			$this->serializeMe();
			if (Settings::getDebug()) $gDebugObj->setDebugMessage('DreamboxObj','Created new version of DreamboxObj');
		}
	}

	/**
	* Save the object in the current session. This is done with serialization
	*/
	private function serializeMe($pSave = true) {
		global $gDebugObj;
		$_SESSION["ReStream2.0"]["Dreambox"]["cachetime"] = time();
		$_SESSION["ReStream2.0"]["Dreambox"]["Obj"] = serialize($this);

		if ($pSave) {
			if (@file_put_contents(getcwd() . '/' . $this->lCacheFile,serialize($this))) {
                            if (Settings::getDebug()) $gDebugObj->setDebugMessage('serializeMe','Save cache data to disk: ' . getcwd() . '/' . $this->lCacheFile,serialize($this));
                        } else {
                            if (Settings::getDebug()) $gDebugObj->setDebugMessage('serializeMe','Error saving caching to disk: ' . getcwd() . '/' . $this->lCacheFile,serialize($this) . '. Is the webserver allowed to write?');
                        }
		}
	}

	/**
	* Set the Dreambox IP nummer. If the IP nummer is not valid, it will be ignored.
	* @param string $pIp
	* @return boolean
	*/
	private function setDreamboxIP($pIp)
	{
		$this->lIPNumber = (long2ip(ip2long($pIp)) == $pIp ? $pIp : false);
	}

	private function setDreamboxWebPort($pNumber)
	{
		$this->lPortNumber = (is_numeric($pNumber) ? $pNumber : false);
	}

	/**
	* Set the Dreambox type. Valid values are 'enigma1' and 'enigma2'.
	* @param string $pType
	* @return boolean
	*/
	private function setDreamboxType($pType)
	{
		$this->lType = false;
		switch (strtolower($pType)) {
			case "enigma1":
			case "enigma2";
				$this->lType = strtolower($pType);
			break;
		}
	}

	/**
	* Private function to get the authentication part for the Dreambox url
	* @return string
	*/
	private function getAuthentication() {
		$lExtraUrl = "";
		if ($this->lAuthentication[0] != "" || $this->lAuthentication[1] != "") {
			$lExtraUrl = $this->lAuthentication[0] . ":" . $this->lAuthentication[1] . "@";
		}
		return $lExtraUrl;
	}

	/**
	* Check if the Dreambox is online.
	* @return boolean
	*/
	public function isOnline()
	{
		global $gDebugObj;
		$lPingOK = false;
		if (!empty($this->lOnline) && (time() - $this->lOnline) < (15 * 60)) {
			$lPingOK = true;
			if (Settings::getDebug()) $gDebugObj->setDebugMessage('isOnline',"Ping dreambox result (cached): " . ($lPingOK ? "online" : "offline"));
		} else {
			$lCMD = 'ping -c2 -n ' . $this->lIPNumber . '| grep \'packet loss\'';
			$lResult = trim(shell_exec($lCMD));
			$lResult = explode(' ',$lResult);
			$lPingOK = ($lResult[0] == $lResult[3]);

			if (Settings::getDebug()) $gDebugObj->setDebugMessage('isOnline',"Ping dreambox result: " . ($lPingOK ? "online" : "offline"));
			if ($lPingOK) {
				$this->lOnline = time();
			}
			$this->serializeMe();
		}
		return $lPingOK;
	}

	/**
	* Load the data from the Dreambox. Here all youre boutiques will be collected and saved in the dreambox object.
	*/
	public function loadBouquets($pBouqetsFilter = array())
	{
		global $gDebugObj;
		if (!$this->isOnline()) return;
		$lChange = false;

		if(Settings::getDebug()) $gDebugObj->setDebugMessage('loadBouquets','Cached data available: ' . (is_array($this->lBouquets) ? count($this->lBouquets) : 'empty'));

		if (!is_array($this->lBouquets)) {
			$this->lBouquets = array();
			if ( ($lData = @file_get_contents($this->getBouquetUrl())) !== false && ($lData = trim($lData)) != "")
			{
				if (Settings::getDebug()) $gDebugObj->setDebugMessage('loadBouquets','Loading bouquet url: ' . $this->getBouquetUrl());
				switch ($this->lType) {
					case "enigma1":
						$lStartPos = stripos($lData, "var bouquets = new Array(");
						$lStartPos = stripos($lData, "\n", $lStartPos);
						$lStopPos = stripos($lData, "\n", $lStartPos + 1);
						$lDataNames = explode("\",", trim(substr($lData, $lStartPos, $lStopPos - $lStartPos)));
						$lStartPos = stripos($lData, "var bouquetRefs = new Array(");
						$lStartPos = stripos($lData, "\n", $lStartPos);
						$lStopPos = stripos($lData, "\n", $lStartPos + 1);
						$lDataIDs = explode("\",", trim(substr($lData, $lStartPos, $lStopPos - $lStartPos)));
						for ($i = 0; $i < sizeof($lDataIDs); $i++) {
							$lDataIDs[$i] = trim($lDataIDs[$i]);
							$lDataNames[$i] = trim($lDataNames[$i]);
							if (substr($lDataIDs[$i],0,1)   == "\"") $lDataIDs[$i]   = substr($lDataIDs[$i],1);
							if (substr($lDataIDs[$i],-1)    == "\"") $lDataIDs[$i]   = substr($lDataIDs[$i],0,-1);
							if (substr($lDataNames[$i],0,1) == "\"") $lDataNames[$i] = substr($lDataNames[$i],1);
							if (substr($lDataNames[$i],-1)  == "\"") $lDataNames[$i] = substr($lDataNames[$i],0,-1);
							$this->lBouquets[$lDataIDs[$i]] = new Bouquet($lDataIDs[$i], $lDataNames[$i]);
							//$lChange = true;
						}
					break;
					case "enigma2";
					  $lServices = json_decode($lData);
					  if (Settings::getDebug()) $gDebugObj->setDebugMessage('loadBouquets',count($lServices->services) . ' bouquets loaded');
						foreach ($lServices->services as $lService) {
						  $lBouquetId = $lService->servicereference;
						  $lBouquetTitle = $lService->servicename;
							if (empty($pBouqetsFilter) || in_array($lBouquetTitle,$pBouqetsFilter)) {
								preg_match('/\\"(?P<bouquet>.*)\\"/', $lBouquetId, $matches);
								$lBouquetId = $matches['bouquet'];
								$this->lBouquets[$lBouquetId] = new Bouquet(
									$lBouquetId,
									$lBouquetTitle
								);
							}
						}
						//$lChange = true;
					break;
				}
			}
			// special bouquets....
			$this->lBouquets['_recordings'] = new Bouquet('_recordings', 'Recordings');
			$this->lBouquets['_movies'] = new Bouquet('_movies', 'Movies');
			//$this->lBouquets['_webcams'] = new Bouquet('_webcams', 'Webcams');
			$this->lBouquets['_search'] = new Bouquet('_search', 'Search');
			$this->lBouquets['_about'] = new Bouquet('_about', 'About');
			$this->serializeMe();
		}
	}

	/**
	* Load the channel data. This is done after the boutiques are loaded. The actual loading of the data happens in the Boutique class.
	*/
	/*
	public function loadChannels(){
		global $gDebugObj;

		if (!$this->isOnline()) return;
		if (!isset($this->lBouquets) || !is_array($this->lBouquets)) return;
		//$lChange = false;
		foreach ($this->lBouquets as $lBouquet) {
			$this->loadChannel($lBouquet->getID());
		}
	}
	*/

	public function loadChannel($pBouquetID)
	{
		global $gDebugObj;
		$lStartTime = microtime(true);
		if (!$this->isOnline()) return;

		$lChange = false;
		$lBouquet = $this->lBouquets[$pBouquetID];
		if (Settings::getDebug()) $gDebugObj->setDebugMessage('loadChannels',"Checking cached data: " . $lBouquet->getChannelCount());
		if ($lBouquet->getChannelCount() == 0) {
			if ( ($lData = @file_get_contents($this->getChannelUrl($lBouquet->getID()))) !== false && ($lData = trim($lData)) != "")
			{
				if (Settings::getDebug()) $gDebugObj->setDebugMessage('loadChannels',"Loading channel url: " . $this->getChannelUrl($lBouquet->getID()));
				switch ($this->lType) {
					case "enigma1":
						$lStartPos = stripos($lData,"var bouquetRefs = new Array(");
						$lStartPos = stripos($lData,"\n",$lStartPos);
						$lStopPos = stripos($lData,"\n",$lStartPos+1);
						$lDataIDs = explode("\",", trim(substr($lData, $lStartPos, $lStopPos - $lStartPos)));
						for ($i = 0; $i < sizeof($lDataIDs); $i++) {
							$lDataIDs[$i] = trim($lDataIDs[$i]);
							if (substr($lDataIDs[$i],0,1)  == "\"") $lDataIDs[$i] = substr($lDataIDs[$i],1);
							if (substr($lDataIDs[$i],-1) == "\"") $lDataIDs[$i] = substr($lDataIDs[$i],0,-1);
							if (trim($lBouquet->getID()) == trim($lDataIDs[$i])) { // This is me....
								$lStartPos = stripos($lData,"channels[$i] = new Array(");
								if ($lStartPos !== false) {
									$lStartPos += strlen("channels[$i] = new Array(");
									$lStopPos = stripos($lData,");",$lStartPos);
									$lDataNames = explode("\",", trim(substr($lData, $lStartPos, $lStopPos - $lStartPos)));
									$lStartPos = stripos($lData,"channelRefs[$i] = new Array(");
									if ($lStartPos !== false) {
										$lStartPos += strlen("channelRefs[$i] = new Array(");
										$lStopPos = stripos($lData,");",$lStartPos);
										$lDataIDs2 = explode("\",", trim(substr($lData, $lStartPos, $lStopPos - $lStartPos)));
										for ($j = 0; $j < sizeof($lDataIDs2); $j++) {
											$lDataIDs2[$j] = trim($lDataIDs2[$j]);
											$lDataNames[$j] = trim($lDataNames[$j]);
											if (substr($lDataIDs2[$j],0,1)  == "\"") $lDataIDs2[$j]  = substr($lDataIDs2[$j],1);
											if (substr($lDataIDs2[$j],-1)   == "\"") $lDataIDs2[$j]  = substr($lDataIDs2[$j],0,-1);
											if (substr($lDataNames[$j],0,1) == "\"") $lDataNames[$j] = substr($lDataNames[$j],1);
											if (substr($lDataNames[$j],-1)  == "\"") $lDataNames[$j] = substr($lDataNames[$j],0,-1);
											$lBouquet->addChannel(new Channel($lDataIDs2[$j],$lDataNames[$j]));
											$lChange = true;
										}
									}
								}
								break; // Stop looping, because current lBouquet is found and the channels are loaded
							}
						}
					break;

					case "enigma2";
					  $lChannels = json_decode($lData);
					  foreach ($lChannels->services as $lChannel) {
							$lBouquet->addChannel(
								new Channel(
								  $lChannel->servicereference,
								  $lChannel->servicename
								)
							);
							$lChange = true;
						}
					break;
				}
			}
		}
		if (Settings::getDebug()) $gDebugObj->setDebugMessage('loadChannels',"Loaded in " . round((microtime(true)-$lStartTime),2) . " seconds with " . $lBouquet->getChannelCount() . " channels");

		if ($lChange) $this->serializeMe();
	}

	public function loadCurrentAndNextEPG($pBouquetID) {
		global $gDebugObj;
		$lStartTime = microtime(true);
		if (!$this->isOnline()) return;
		$lChange = false;
		$lReturnValue = "";

		if (!is_array($this->lBouquets) || !empty($this->lProgramGuide))  return false;
		foreach ($this->lBouquets as $lBouquet) {
			if (substr($lBouquet->getID(),0,1) == '_' || $lBouquet->getID() != $pBouquetID) continue;
			if (Settings::getDebug()) $gDebugObj->setDebugMessage('loadCurrentAndNextEPG',"Loading current programs url: " . $this->getNOWEPGUrl($lBouquet->getID()));
			$lEPGNOWData  = @file_get_contents($this->getNOWEPGUrl($lBouquet->getID()));
			if (Settings::getDebug()) $gDebugObj->setDebugMessage('loadCurrentAndNextEPG',"Loading next programs url: " . $this->getNEXTEPGUrl($lBouquet->getID()));
			$lEPGNEXTData = @file_get_contents($this->getNEXTEPGUrl($lBouquet->getID()));
			switch ($this->lType) {
				case "enigma1":
				break;

				case "enigma2":
				  $lPrograms = array_merge((array)json_decode($lEPGNOWData)->events,(array)json_decode($lEPGNEXTData)->events);
				  foreach ($lPrograms as $lProgram) {
            if (!is_numeric($lProgram->id)) continue;
            if (empty($this->lProgramGuide[$lProgram->sref])) $this->lProgramGuide[$lProgram->sref] = new ProgramGuide($lProgram->sref);
            $this->lProgramGuide[$lProgram->sref]->addProgram(
								new Program(
								  $lProgram->id,
								  $lProgram->begin_timestamp,
								  $lProgram->duration_sec,
								  $lProgram->title,
								  $lProgram->shortdesc,
								  $lProgram->longdesc
								)
						);
						$lChange = true;
            $this->lProgramGuide[$lProgram->sref]->sortOnStartTime();
          }
				break;
			}
		}
		if (Settings::getDebug()) $gDebugObj->setDebugMessage('loadCurrentAndNextEPG',"Loaded in " . round((microtime(true)-$lStartTime),2) . " seconds");
		if ($lChange) $this->serializeMe();
	}

	public function loadProgramGuide($pBouquetID = null) {
		global $gDebugObj;
		$lStartTime = microtime(true);
		if (!$this->isOnline()) return;

		$lChange = false;
		foreach ($this->lBouquets as $lBouquet) {
			if ($pBouquetID == null || $pBouquetID == $lBouquet->getID()) {
				switch($this->lType) {
					case 'enigma2':
						$lEPGTimeLimit = time() + (Settings::getEPGLimit() * 3600);
   		      			$lEPGData  = @file_get_contents($this->getMultiEPGUrl($lBouquet->getID(),Settings::getEPGLimit()));
   		      			$lEPGData = json_decode($lEPGData);

   		      			$lPrevChannel = '';
   		      			foreach($lEPGData->events as $lEvent) {
   		      				// Force clearing the existing now and next EPG data...
   		      				if ($lPrevChannel != $lEvent->sref) {
   		      					$this->lProgramGuide[$lEvent->sref] = new ProgramGuide($lEvent->sref);
   		      					$lPrevChannel = $lEvent->sref;
   		      				}

   		      				if ($lEvent->begin_timestamp < $lEPGTimeLimit) {
	            				$this->lProgramGuide[$lEvent->sref]->addProgram(
									new Program(
									  $lEvent->id,
									  $lEvent->begin_timestamp,
									  $lEvent->duration_sec,
									  $lEvent->title,
									  $lEvent->shortdesc,
									  $lEvent->longdesc
									)
								);
								$lChange = true;
            				}
   		      			}

					break;

					case 'enigma1':
					/*
						if (!isset($this->lProgramGuide[$lChannel->getID()])) $this->lProgramGuide[$lChannel->getID()] = new ProgramGuide($lChannel->getID());
    					if (Settings::getDebug()) $gDebugObj->setDebugMessage('loadProgramGuide',"Channel '" . $lChannel->getName() . "' has number of programs loaded: " . $this->lProgramGuide[$lChannel->getID()]->getProgramsCount() );
    					if ($this->lProgramGuide[$lChannel->getID()]->getProgramsCount() <= 2) {

    						$this->lProgramGuide[$lChannel->getID()] = $this->loadProgramGuideChannel($lChannel);
    						$lChange = ($this->lProgramGuide[$lChannel->getID()]->getProgramsCount() > 0);
    						if ($pChannelID != null && $pChannelID == $lChannel->getID()) {
    							if ($lChange) $this->serializeMe();
    							return true;
    						}
    					}
    					*/
					break;
				}
			}
		}
		if (Settings::getDebug()) $gDebugObj->setDebugMessage('loadProgramGuide',"Loaded in " . round((microtime(true)-$lStartTime),2) . " seconds");
		if ($lChange) $this->serializeMe();
	}

	public function getProgramGuide($pChannelID) {
		if (isset($this->lProgramGuide[$pChannelID]))
			return $this->lProgramGuide[$pChannelID];
		return false;
	}

	public function loadProgramGuideChannel($pChannel) {
		global $gDebugObj;
		$lStartTime = microtime(true);
		if (!$this->isOnline()) return;

		$lGuide = new ProgramGuide($pChannel);
		$lEPGLimit = time() + (Settings::getEPGLimit() * 60 * 60);
		if (Settings::getDebug()) $gDebugObj->setDebugMessage('loadProgramGuideChannel',"Loading all programs for channel '" . $pChannel->getName() . "' url: " . $this->getProgramGuideUrl($pChannel->getID()));
		if (!$pChannel->isMarker() && ($lData = @file_get_contents($this->getProgramGuideUrl($pChannel->getID()))) !== false && ($lData = trim($lData)) != "")
		{
			switch ($this->lType) {
				case "enigma1":
					$lData = explode("<tr",$lData);
					for ($i = 2; $i < sizeof($lData); $i++) {
						$lStartPos = stripos($lData[$i],"javascript:record(") + strlen("javascript:record(");
						$lStopPos = stripos($lData[$i],")",$lStartPos+1);
						$lDataParts = explode(",",str_ireplace("'","",trim(substr($lData[$i],$lStartPos,$lStopPos-$lStartPos))));
						$lStartPos = stripos($lData[$i],"class=\"description\"");

						if ($lStartPos !== false) {
							$lStartPos += strlen("class=\"description\"")+1;
							$lStopPos = stripos($lData[$i],"</",$lStartPos+1);
							$lExtendedInfo = trim(substr($lData[$i],$lStartPos,$lStopPos-$lStartPos));
						}
						if (trim($lDataParts[1]) > $lEPGLimit) break;
						$lGuide->addProgram(
							new Program(
								trim($lDataParts[1]),
								trim($lDataParts[1]),
								trim($lDataParts[2]),
								trim($lDataParts[3]),
								$lExtendedInfo,""
							)
						);
					}
				break;
				case "enigma2";
					$lXMLEPG = new DOMDocument();
					$lXMLEPG->loadXML($lData);
					$lPrograms = $lXMLEPG->getElementsByTagName("e2event");
					foreach ($lPrograms as $lProgram) {
						if ($lProgram->getElementsByTagName("e2eventstart")->item(0)->nodeValue > $lEPGLimit) break;
						$lGuide->addProgram(
							new Program(
								$lProgram->getElementsByTagName("e2eventid")->item(0)->nodeValue,
								$lProgram->getElementsByTagName("e2eventstart")->item(0)->nodeValue,
								$lProgram->getElementsByTagName("e2eventduration")->item(0)->nodeValue,
								$lProgram->getElementsByTagName("e2eventtitle")->item(0)->nodeValue,
								$lProgram->getElementsByTagName("e2eventdescription")->item(0)->nodeValue,
								$lProgram->getElementsByTagName("e2eventdescriptionextended")->item(0)->nodeValue
							)
						);
					}
				break;
			}
			$lGuide->sortOnStartTime();
		} else {
			//echo "Error getting EPG: " . $this->getProgramGuideUrl($pChannel->getID()) . "\n";
		}
		if (Settings::getDebug()) $gDebugObj->setDebugMessage('loadProgramGuideChannel',"Loaded in " . round((microtime(true)-$lStartTime),2) . " seconds");
		return $lGuide;
	}
	/**
	* Get the Bouquet url. This is used for getting bouquet information from your dreambox.
	* @return string
	*/
	private function getBouquetUrl()
	{
		switch ($this->lType) {
			case "enigma1":
				return "http://" . $this->getAuthentication() . $this->lIPNumber . ":" . $this->lPortNumber . "/body?mode=zap&zapmode=0&zapsubmode=4";
			break;
			case "enigma2";
				return "http://" . $this->getAuthentication() . $this->lIPNumber . ":" . $this->lPortNumber . "/api/getservices";
			break;
			default:
				return false;
			break;
		}
	}

	/**
	* Get the Boutique url. This is used for getting boutique information from your dreambox.
	* @return string
	*/
	private function getChannelUrl($pBouquetID)
	{
		switch ($this->lType) {
			case "enigma1":
				return "http://" . $this->getAuthentication() . $this->lIPNumber . ":" . $this->lPortNumber . "/body?mode=zap&zapmode=0&zapsubmode=4";
			break;
			case "enigma2";
				return "http://" . $this->getAuthentication() . $this->lIPNumber . ":" . $this->lPortNumber . "/api/getservices?sRef=1:7:1:0:0:0:0:0:0:0:FROM%20BOUQUET%20%22" . $pBouquetID . "%22%20ORDER%20BY%20bouquet";
			break;
		}
	}

	private function getNOWEPGUrl($pBouquetID) {
		switch ($this->lType) {
			case "enigma1":
				return false;
			break;
			case "enigma2";
				return "http://" . $this->getAuthentication() . $this->lIPNumber . ":" . $this->lPortNumber . "/api/epgnow?bRef=1:7:1:0:0:0:0:0:0:0:FROM%20BOUQUET%20%22" . $pBouquetID . "%22%20ORDER%20BY%20bouquet";
			break;
		}
	}

	private function getNEXTEPGUrl($pBouquetID) {
		switch ($this->lType) {
			case "enigma1":
				return false;
			break;
			case "enigma2";
				return "http://" . $this->getAuthentication() . $this->lIPNumber . ":" . $this->lPortNumber . "/api/epgnext?bRef=1:7:1:0:0:0:0:0:0:0:FROM%20BOUQUET%20%22" . $pBouquetID . "%22%20ORDER%20BY%20bouquet";
			break;
		}
	}

	private function getMultiEPGUrl($pBouquetID, $pHours = 24) {
		switch ($this->lType) {
			case "enigma1":
				return false;
			break;
			case "enigma2";
				return "http://" . $this->getAuthentication() . $this->lIPNumber . ":" . $this->lPortNumber . "/api/epgmulti?bRef=1%3A7%3A1%3A0%3A0%3A0%3A0%3A0%3A0%3A0%3AFROM%20BOUQUET%20%22" . $pBouquetID . "%22%20ORDER%20BY%20bouquet";
				// . "&time=" . time() . "&endTime=" . (time()+($pHours * 3600));
			break;
		}
	}

	private function getProgramGuideUrl($pChannelID)
	{
		switch ($this->lType) {
			case "enigma1":
				return "http://" . $this->getAuthentication() . $this->lIPNumber . ":" . $this->lPortNumber . "/getcurrentepg?type=extended&ref="  . $pChannelID;
			break;
			case "enigma2";
				return "http://" . $this->getAuthentication() . $this->lIPNumber . ":" . $this->lPortNumber . "/web/epgservice?sRef="  . $pChannelID;
			break;
		}
	}

	public function getBouquets()
	{
		return $this->lBouquets;
	}


	public function getBouquet($pBouquetID)
	{
		if (isset($this->lBouquets[$pBouquetID])) {
			return $this->lBouquets[$pBouquetID];
		}
		return false;
	}


	public function findChannel($pChannelID) {
		if (!is_array($this->lBouquets)) return false;
		foreach ($this->lBouquets as $lBouquet) {
			if (($lChannelObj = $lBouquet->getChannel($pChannelID)) !== false) {
				return $lChannelObj;
			}
		}
		return false;
	}
	/**
	* Get the Recording url. This is used for getting recoring information from your dreambox.
	* @return string
	*/

	private function getRecordingUrl()
	{
		switch ($this->lType) {
			case "enigma1":
				return "http://" . $this->getAuthentication() . $this->lIPNumber . ":" . $this->lPortNumber . "/body?mode=zap&zapmode=3&zapsubmode=1";
			break;
			case "enigma2";
				return "http://" . $this->getAuthentication() . $this->lIPNumber . ":" . $this->lPortNumber . "/api/movielist";
			break;
		}
	}

	private function getBoxInfoUrl()
	{
		switch ($this->lType) {
			case "enigma1":
				return "";
			break;
			case "enigma2";
				return "http://" . $this->getAuthentication() . $this->lIPNumber . ":" . $this->lPortNumber . "/api/about";
			break;
			default:
				return false;
			break;
		}
	}

	public function checkWebIfVersion() {
		if ($this->lType == 'enigma1') return true;
		$lData = @file_get_contents($this->getBoxInfoUrl());
		if ($lData !== false) {
			$lData = json_decode($lData);
			$this->lWebInfoOK = !empty($lData->info->webifver);
			return $this->lWebInfoOK;
		}
		return false;
	}

	/**
	* Get all recordings from the dreambox. The recordings will be save in a recording object array.
	* It can self see with enigma version is used, and what the code will be that will be returned from the dreambox
	*/
	public function loadRecordings($pLocation = '')
	{
		if (!$this->isOnline()) return;
		$lChange = false;
		if (!is_array($this->lRecordings) || count($this->lRecordings) == 0) {
			$this->lRecordings = array();
			if ( ($lData = @file_get_contents($this->getRecordingUrl() . ($pLocation != '' ? '?dirname=' . $pLocation : ''))) !== false && ($lData = trim($lData)) != "")
			{
				switch ($this->lType) {
					case "enigma1":
						$lStartPos = stripos($lData, "channels[0] = new Array(\"") + strlen("channels[0] = new Array(\"");
						$lStopPos = stripos($lData, "\");", $lStartPos + 1);
						$lDataNames = explode(",", str_ireplace("\"", "", trim(substr($lData, $lStartPos, $lStopPos - $lStartPos))));

						$lStartPos = stripos($lData, "channelRefs[0] = new Array(\"") + strlen("channelRefs[0] = new Array(\"");
						$lStopPos = stripos($lData, "\");", $lStartPos + 1);
						$lDataIDs = explode(",", str_ireplace("\"", "", trim(substr($lData, $lStartPos, $lStopPos - $lStartPos))));

						for ($i = 0; $i < sizeof($lDataIDs); $i++) {
							$lDataLine = trim($lDataNames[$i]);
							$lTitle = trim(substr($lDataLine, stripos($lDataLine, "]") + 1));
							$lFileSize = substr($lDataLine, 1, stripos($lDataLine, "]") - 4) * 1024;
							$lFileDate = explode("-", trim(substr($lDataLine, stripos($lDataLine, "]") + 1, 9)));
							if (sizeof($lFileDate) == 3) {
								$lFileDate = mktime(0, 0, 0, $lFileDate[1], $lFileDate[2], $lFileDate[0]);
								$lTitle = trim(substr($lTitle, 11));
							} else {
								$lFileDate = time();
							}
							$this->lRecordings[] = new Recording($lDataIDs[$i], $lTitle, '', '', '', $lFileDate, -1, $lDataIDs[$i], $lFileSize);
							$lChange = true;
						}
					break;

					case "enigma2";
						$lData = json_decode($lData);
						foreach($lData->movies as $lMovieData) {
						  if (stripos($lMovieData->length, '?') !== false) continue;
							$this->lRecordings[] = new Recording(
								$lMovieData->serviceref,
								$lMovieData->eventname,
								$lMovieData->description,
								$lMovieData->descriptionExtended,
								$lMovieData->servicename,
								$lMovieData->recordingtime,
								$lMovieData->length,
								$lMovieData->filename_stripped,
								$lMovieData->filesize
							);
						}
						foreach($lData->bookmarks as $lSubDir) {
							$this->loadRecordings(str_replace(' ','%20',$lData->directory . $lSubDir));
						}
						$lChange = true;
					break;
				}
				$this->sortOnStartTime();
			}
		}
		if ($lChange) $this->serializeMe();
	}

	public function loadMovies()
	{
		$lChange = false;
		if (!is_array($this->lMovies) || count($this->lMovies) == 0) {
			$this->lMovies = array();
			if (Settings::getMoviesPath() != "" && is_dir(Settings::getMoviesPath())) {
				$lMovieStartLocation = new RecursiveDirectoryIterator(Settings::getMoviesPath());
				foreach (new RecursiveIteratorIterator($lMovieStartLocation) as $lMovieFile) {
					$lMovieFile = pathinfo($lMovieFile);
					if (in_array(strtolower($lMovieFile["extension"]),Settings::getValidMovies())) {
						$lMoviePath = trim($lMovieFile["dirname"] . "/" . $lMovieFile["basename"]);
						if ($lMoviePath != "" && filesize($lMoviePath) > 1024) { // Skip movie files smaller than 1K. Mostly meta data files
							$this->lMovies[] = new Movie($lMoviePath);
							$lChange = true;
						}
					}
				}
				$this->sortOnName();
			} else {
				$lChange = true;
			}
		}
		if ($lChange) $this->serializeMe();
	}


	public function getMovie($pMovieID) {
		foreach ($this->lMovies as $lMovie) {
			if ($pMovieID == $lMovie->getID()) return $lMovie;
		}
		return false;
	}

	public function loadMovie($pMovieID) {
		if (($lMovieObj = $this->getMovie($pMovieID)) !== false) {
			if ($lMovieObj->getDuration() == -1) { // Only load movie data when not loaded before...
				$lMovieObj->loadInfo();
				$this->serializeMe();
			}
		}
	}

	/**
	* Sort the programs based in start time
	*/
	private function sortOnStartTime() {
		$lStartTimes = array();
		foreach ($this->lRecordings as $lRecording) {
			$lStartTimes[] = $lRecording->getStartTime();
		}
		array_multisort($lStartTimes, SORT_ASC, $this->lRecordings);
	}


	public function getRecordings()
	{
		return $this->lRecordings;
	}

	public function findRecording($pRecordingID) {
		foreach ($this->lRecordings as $lRecording) {
			if ($lRecording->getID() == $pRecordingID) return $lRecording;
		}
		return false;
	}

	private function sortOnName() {
		$lNames = array();
		foreach ($this->lMovies as $lMovie) {
			$lNames[] = $lMovie->getName();
		}
		array_multisort($lNames, SORT_ASC, $this->lMovies);
	}

	public function getMovies()
	{
		return $this->lMovies;
	}

	public function loadWebCams()
	{
		$lChange = false;
		if (!is_array($this->lWebCams) || count($this->lWebCams) == 0) {
			$this->lWebCams = array();
			$this->lWebCams[] = new WebCam('http://yoshieaxis/mjpg/video.mjpg');


			/*
			if (Settings::getMoviesPath() != "" && is_dir(Settings::getMoviesPath())) {
				$lMovieStartLocation = new RecursiveDirectoryIterator(Settings::getMoviesPath());
				foreach (new RecursiveIteratorIterator($lMovieStartLocation) as $lMovieFile) {
					$lMovieFile = pathinfo($lMovieFile);
					if (in_array(strtolower($lMovieFile["extension"]),Settings::getValidMovies())) {
						$lMoviePath = trim($lMovieFile["dirname"] . "/" . $lMovieFile["basename"]);
						if ($lMoviePath != "" && filesize($lMoviePath) > 1024) { // Skip movie files smaller than 1K. Mostly meta data files
							$this->lMovies[] = new WebCam($lMoviePath);
							$lChange = true;
						}
					}
				}
				$this->sortOnName();
			} else {
				$lChange = true;
			}
			*/
		}

		//if ($lChange)
		$this->serializeMe();
	}

	public function getWebCams()
	{
		return $this->lWebCams;
	}

	public function getWebCam($pWebCamID) {
		foreach ($this->lWebCams as $lWebCam) {
			if ($pWebCamID == $lWebCam->getID()) return $lWebCam;
		}
		return false;
	}

/*
	public function loadWebCam($pWebCamID) {
		if (($lMovieObj = $this->getMovie($pWebCamID)) !== false) {
			if ($lMovieObj->getDuration() == -1) { // Only load movie data when not loaded before...
				$lMovieObj->loadInfo();
				$this->serializeMe();
			}
		}
	}
	*/

	public function getStreamURL($pChannelID)
	{
		global $gDebugObj;
		if (Settings::getMoviesPath() != "" && substr($pChannelID,0,strlen(Settings::getMoviesPath())) == Settings::getMoviesPath()) {
			$lStreamUrl = $pChannelID;
		} else {
			switch ($this->lType) {
				case "enigma1":
					if (substr($pChannelID,-2) == "ts") { # Recording
						$lRecording = explode(":",$pChannelID);
						$lStreamUrl = "http://" . $this->getAuthentication() . $this->lIPNumber .  ":" . $this->lPortNumber . $this->dreamboxStreamUrlFormat($lRecording[10]);
					} else{
						//Zap first
						$lZapUrl = "http://" . $this->getAuthentication() . $this->lIPNumber . ":" . $this->lPortNumber . "/cgi-bin/zapTo?path=". $pChannelID ."&curBouquet=0&curChannel=0";
						if (Settings::getDebug()) $gDebugObj->setDebugMessage('getStreamURL',"VLC ZAP Enigma1 Url: '".$lZapUrl . "'");
						@file_get_contents($lZapUrl);
						sleep(1); // Wait for the dreambox to change channel
						$lStreamUrl = "http://" . $this->getAuthentication() . $this->lIPNumber . ":" . $this->lPortNumber . "/video.m3u";
						if (Settings::getDebug()) $gDebugObj->setDebugMessage('getStreamURL',"VLC Final Enigma1 Url: '".$lStreamUrl . "'");
					}
				break;

				case "enigma2":
					if (!Settings::isDualTuner()) {
						$lZapUrl = "http://" . $this->getAuthentication() . $this->lIPNumber . ":" . $this->lPortNumber . "/web/zap?sRef=". $pChannelID;
						if (Settings::getDebug()) $gDebugObj->setDebugMessage('getStreamURL',"VLC ZAP Enigma2 Url: '".$lZapUrl . "'");
						@file_get_contents($lZapUrl);
					}
					if (substr($pChannelID,-3) == ".ts") {
						$pChannelID = substr($pChannelID,stripos($pChannelID,"/"));
						// This code will fix the authentication issue with downloads. Due to a redirect, the authentication is lost.
						// So parse the content, and rebuild the url with authentication on it.
						$recording_playlist = file_get_contents("http://" . $this->getAuthentication() . $this->lIPNumber .  ":" . $this->lPortNumber . "/web/ts.m3u?file=" . $this->dreamboxStreamUrlFormat($pChannelID));
						$recording_url = $this->m3u_parser($recording_playlist);
						if ($recording_url !== false) {
							$recording_url = parse_url($recording_url);
							$recording_url = (!empty($recording_url['scheme']) ? $recording_url['scheme'] : 'http') . '://' . $this->getAuthentication() . $recording_url['host'] . ':' . $recording_url['port'] . $recording_url['path'] . '?' . $recording_url['query'];
						} else {
							// Failback backup.... should not be needed...
							$recording_url = "http://" . $this->getAuthentication() . $this->lIPNumber .  ":" . $this->lPortNumber . "/file?file=" . $this->dreamboxStreamUrlFormat($pChannelID);
						}
						$lStreamUrl = $recording_url;
					} else {
						$lStreamUrl = "http://" . $this->getAuthentication() . $this->lIPNumber .  ":" . $this->lPortNumber .  "/web/stream.m3u?ref=" . $pChannelID . '&name=zap';
					}
					if (Settings::getDebug()) $gDebugObj->setDebugMessage('getStreamURL',"VLC Final Enigma2 Url: '".$lStreamUrl . "'");
				break;
			}
		}
		return $lStreamUrl;
	}

	public function getCurrentProgram () {
		// Enigma2 url: http://192.168.5.119/web/getcurrent
	}

	private function dreamboxStreamUrlFormat($pUrl) {
		$pUrl = trim($pUrl);
		switch ($this->lType) {
			case "enigma1":
			//break;
			case "enigma2":
				return str_replace(array(" ","&"),array("%20","%26"),$pUrl);
			break;
		}
	}

	public function getChannelImage($pChannelID) {
		$pChannelID = str_replace(':','_',substr($pChannelID,0,-1));

		$lImage = 'picon/' . $pChannelID . '.png';
		if (!file_exists($lImage) || filesize($lImage) == 0 || (filemtime($lImage) < time() - 24 * 3600 )) { // Check once every day for an update...
			// Get from the Dreambox source.
			if (@file_put_contents($lImage,file_get_contents("http://" . $this->getAuthentication() . $this->lIPNumber .  ":" . $this->lPortNumber . "/" . $lImage)) === false) @unlink($lImage);
		}
		return $lImage;
	}

	public function m3u_parser($contents) {
		if (!is_array($contents)) {
			$contents = explode("\n", $contents);
		}
		foreach ($contents as $contentline) {
			if (substr(trim($contentline),0,1) != '#') {
				return trim($contentline);
			}
		}
		return false;
	}

	public function sanityCheck() {
		$lReturnMessage = '';

		if (@ini_get('safe_mode') != 0) {
				$lReturnMessage .= "Please disable PHP safe mode!<br />";

		}
		if (!$this->checkWebIfVersion()) {
			$lReturnMessage .= "Please install the new WebInterface plugin. (https://github.com/E2OpenPlugins/e2openplugin-OpenWebif)<br />";
		}
		if (!(@touch('iphone/stream/test.txt') && unlink('iphone/stream/test.txt'))) {
			$lReturnMessage .= 'The folder iphone/stream is not writable for the web server.';
		}
		if (!(@touch('picon/test.txt') && unlink('picon/test.txt'))) {
			$lReturnMessage .= 'The folder picon is not writable for the web server.';
		}



		return ($lReturnMessage == '' ? true : $lReturnMessage);
	}

	/**
	* Dump the complete object to screen.
	*/
	public function dump()
	{
		print_r($this);
	}
}
