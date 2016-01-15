<?php
class MediaInfo {
    private $lMediaInfoLocation = null;
    private $lMediaFile = null;
    private $lXMLOutput = null;
    private $lMediaData = null;

    public function __construct($pFullFileLocation) {
        if(file_exists(($pFullFileLocation = html_entity_decode($pFullFileLocation)))) {
            $this->lMediaFile = $pFullFileLocation;
            $this->lMediaInfoLocation = Settings::getMediaInfoxecutable();
            if ($this->lMediaInfoLocation != "") {
                $this->loadMediaInfo();
            } else {
                throw new Exception("Mediainfo is not installed on this server. Please install Mediainfo");
            }
        } else {
            throw new Exception("File $pFullFileLocation does not exist or is not accessible for the web server");
        }
    }

    public function loadMediaInfo() {
        if ($this->lMediaFile != null) { // File was valid when object created
            $lCMD = $this->lMediaInfoLocation . " --Output=XML " . escapeshellarg($this->lMediaFile);
            $this->lXMLOutput = `$lCMD`;
            $this->__parseData();
        }
    }

    private function __parseData() {
        $lXMLData = new DOMDocument;
        $lXMLData->preserveWhiteSpace = false;

        $lXMLData->loadXML($this->lXMLOutput);
		$lXpath = new DOMXpath($lXMLData);

        $this->lMediaData = array(); // Reset the media data array
        $this->lMediaData["duration"] = trim(@$lXpath->query("//Mediainfo/File/track[@type='General']/Duration")->item(0)->nodeValue);
        $this->lMediaData["screenwidth"] = trim(@$lXpath->query("//Mediainfo/File/track[@type='Video']/Width")->item(0)->nodeValue);
        $this->lMediaData["screenheight"] = trim(@$lXpath->query("//Mediainfo/File/track[@type='Video']/Height")->item(0)->nodeValue);
        $this->lMediaData["bitrate"] = trim(@$lXpath->query("//Mediainfo/File/track[@type='General']/Overall_bit_rate")->item(0)->nodeValue);

        unset($this->lXMLOutput); // Clean up some memory...
        $this->__normalizeData();
    }

    private function __normalizeData() {
        $this->lMediaData["bitrate"]     = $this->__bitrateNormalization($this->lMediaData["bitrate"]);
        $this->lMediaData["duration"]       = $this->__timeNormalization($this->lMediaData["duration"]);
        $this->lMediaData["screenwidth"]    = $this->__resolutionNormalization($this->lMediaData["screenwidth"]);
        $this->lMediaData["screenheight"]   = $this->__resolutionNormalization($this->lMediaData["screenheight"]);
    }

    private function __resolutionNormalization($pValue) {
        if (is_numeric($pValue)) {
            // Return to readable output
            return $pValue;
        } else {
            // Return numeric value of text represitation
            $lData = explode(" ",$pValue);
            $lDummy = trim(array_pop($lData));
            $lNumber = implode($lData);
            return (is_numeric($lNumber) ? $lNumber : 0);
        }
    }

    private function __bitrateNormalization($pValue) {
        if (is_numeric($pValue)) {
            // Return to readable output
            return $pValue;
        } else {
            // Return numeric value of text represitation
            $lFactor = trim(substr($pValue,-4));
            $lNumber = trim(substr(str_replace(' ','',$pValue),0,-4));
            switch (strtolower($lFactor)) {
                case "mbps";
                    $lNumber *= 1024;
                case "kbps";
                    $lNumber *= 1024;
                case "bps":
                    $lNumber *= 1;
                break;
               }
            return (is_numeric($lNumber) ? $lNumber : 0);
        }
    }

    private function __timeNormalization($pValue) {
        if (is_numeric($pValue)) {
            // Return to readable output
            return $pValue;
        } else {
            $lData = explode(" ",$pValue);
            $lTime = 0;
            foreach ($lData as $lPart) {
                $lPart = trim($lPart);
            	if (substr($lPart,-1) == "h") {
            		$lTime += substr($lPart,0,-1) * 60 * 60; // Hours to seconds
            	} elseif (substr($lPart,-2) == "mn") {
            		$lTime += substr($lPart,0,-2) * 60; // Minutes to seconds
            	} elseif (substr($lPart,-1) == "s" || is_numeric($lPart)) {
	            	$lTime += $lPart;
            	}
            }
            return (is_numeric($lTime) ? $lTime : 0);
        }
    }

    public function __toString() {
        $lDummyData = "";
        if (is_array($this->lMediaData)) {
            foreach ($this->lMediaData as $lType => $lData) {
                $lDummyData .= "$lType => $lData\n";
            }
        }
        return "Parse time: " . $this->lParseTime . "\n" . $lDummyData;
    }

	public function getBitrate() {
        return (is_numeric($this->lMediaData["bitrate"]) ? $this->lMediaData["bitrate"] : 0);
    }

    public function getVideoBitrate() {
        return (is_numeric($this->lMediaData["videobitrate"]) ? $this->lMediaData["videobitrate"] : 0);
    }

    public function getAudioBitrate() {
        return (is_numeric($this->lMediaData["audiobitrate"]) ? $this->lMediaData["audiobitrate"] : 0);
    }

    public function getWidth() {
        return (is_numeric($this->lMediaData["screenwidth"]) ? $this->lMediaData["screenwidth"] : -1);
    }

    public function getHeight() {
        return (is_numeric($this->lMediaData["screenheight"]) ? $this->lMediaData["screenheight"] : -1);
    }

    public function getResolution() {
        return $this->getWidth() . "x" . $this->getHeight();
    }

    public function getDuration() {
        return (is_numeric($this->lMediaData["duration"]) ? $this->lMediaData["duration"] : -1);
    }
}