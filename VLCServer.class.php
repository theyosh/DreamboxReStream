<?php
class VLCServer {
	/**
	* @access private
	* @var int
	*/
	private $lVolume;

	/**
	* @access private
	* @var int
	*/
	private $lScale;

	/**
	* @access private
	* @var string
	*/
	private $lVLCExecutable;

	/**
	* @access private
	* @var int
	*/
	private $lChannels;


	/**
	* @access private
	* @var array
	*/
	private $lProfiles;

	private $lSegmentLength;

	private $lDVRLength;

	private $liPhoneDir;

	/**
	* Constructor. This creates a VLC Server object. Some defaults are already set. These are: volume, scale and audio channels. It alse detects if it is running on Windows or Linux.
	* @return VLCServer
	*/
	function __construct() {
		$this->lProfiles = array();

		/*
			Here you can change some codec settings.
			Possible Audio codecs:
				1. mp4a
				2. mp3
				3. mpga
		*/

		// Full HD Profile
		$this->lProfiles['FullHD'] = array(	"name" => "Full HD 1080p",
											"width" => 1920,
											"height" => 1080,
											"videocodec" => "h264",
											"videobitrate" => 2048,
											"videofps" => 25,
											"audiocodec" => "mp4a",
											"audiobitrate" => 192,
											"audiosamplerate" => 48000);

		// HD Ready Profile
		$this->lProfiles['HDReady'] = array("name" => "HD Ready 720p",
											"width" => 1280,
											"height" => 720,
											"videocodec" => "h264",
											"videobitrate" => 1500,
											"videofps" => 25,
											"audiocodec" => "mp4a",
											"audiobitrate" => 160,
											"audiosamplerate" => 48000);

		// Main Profile
		$this->lProfiles['SD'] = array(		"name" => "SD",
											"width" => 854,
											"height" => 480,
											"videocodec" => "h264",
											"videobitrate" => 800,
											"videofps" => 25,
											"audiocodec" => "mp4a",
											"audiobitrate" => 128,
											"audiosamplerate" => 48000);

		// Mobile Profile 1
		$this->lProfiles['Mobile1'] = array("name" => "Mobile1",
											"width" => 512,
											"height" => 288,
											"videocodec" => "h264",
											"videobitrate" => 500,
											"videofps" => 25,
											"audiocodec" => "mp4a",
											"audiobitrate" => 92,
											"audiosamplerate" => 48000);

		// Mobile Profile 2
		$this->lProfiles['Mobile2'] = array("name" => "Mobile2",
											"width" => 320,
											"height" => 180,
											"videocodec" => "h264",
											"videobitrate" => 250,
											"videofps" => 20,
											"audiocodec" => "mp4a",
											"audiobitrate" => 64,
											"audiosamplerate" => 48000);

		$this->lVolume = 80;
		$this->lScale = 0.75;
		$this->lChannels = 2;
		$this->lSegmentLength = 10; // In seconds
		$this->lDVRLength = Settings::getDVRLength();
		$this->lActiveProfiles = Settings::getActiveProfiles();
		$this->lVLCExecutable = Settings::getVLCExecutable();
		$this->liPhoneDir = getcwd();
	}

	/**
	* Start the VLC Server by executing the vlc command line. You have to set all options before starting this server. If the server is started, you should be able to connect to it and watch some tv.
	* @param boolean $pDebug
	* @return string
	*/
	public function startServer($pStreamURL,$pChannelObj) {
		global $gDebugObj;
		$pStreamURL = str_replace('name=zap','name=' . $pChannelObj->getName() ,$pStreamURL);
		$lCurrentStatus = $this->getCurrentStatus();
		if ($lCurrentStatus["streamurl"] != $pStreamURL) {
			$this->stopServer();
			$lCMD = $this->lVLCExecutable . " \"" . $pStreamURL . "\" vlc://quit --sout-transcode-deinterlace ";
			// --sout-deinterlace-mode=bob
			if (Settings::getVLCAudioLanguage() != '') {
				$lAudioTracks = explode(',',Settings::getVLCAudioLanguage());
				if (is_numeric($lAudioTracks[0])) {
					$lCMD .= ' --audio-track=' . $lAudioTracks[0];
				} else {
					$lCMD .= ' --audio-language=' . Settings::getVLCAudioLanguage();
				}
			}
			if (Settings::getVLCSubtitleLanguage() != '') {
				$lSubTracks = explode(',',Settings::getVLCSubtitleLanguage());
				if (is_numeric($lSubTracks[0])) {
					$lCMD .= ' --sub-track=' . $lSubTracks[0];
				} else {
					$lCMD .= ' --sub-language=' . Settings::getVLCSubtitleLanguage();
				}
			}
			if (is_object($pChannelObj) && get_class($pChannelObj) == 'Movie' && (($lSubTitleFile = $pChannelObj->getSubtitle(Settings::getVLCSubtitleLanguage())) != '')) {
				$lCMD .= ' --sub-file=' . $pChannelObj->getSubtitle(Settings::getVLCSubtitleLanguage());
			}

			$lPortNumbers = 20000;
			$lTransCode = "#duplicate{";

			if ($pChannelObj->isHD()) {
				// Only allow one bitrate when the source is HD
				$this->lActiveProfiles = array(Settings::getHDOnlyProfile());
			}

			foreach ($this->lProfiles as $lProfileName => $lProfile) {
				if (!in_array($lProfileName,$this->lActiveProfiles)) continue;
				$lCodecParams = "";
				switch ($lProfile["videocodec"]) {
					case "h264":
						//http://veetle.com/index.php/article/view/x264
						$lCodecParams = "venc=x264{idrint=10,bframes=16,b-adapt=1,ref=3,qpmax=51,qpmin=10,me=hex,merange=16,subq=5,subme=7,qcomp=0.6,aud,keyint=10,nocabac,profile=baseline,level=31,fps=" . ($lProfile["videofps"]) . "},";
					break;
				}
				$lTransCode .= "dst={transcode{width=" . ($lProfile["width"]) . ",height=" . ($lProfile["height"]) . ",threads=4,vcodec=". $lProfile["videocodec"] . "," . $lCodecParams . "vb=". $lProfile["videobitrate"] . ",scale=". $this->lScale .",acodec=". $lProfile["audiocodec"] .",ab=" . $lProfile["audiobitrate"] . ",channels=". $this->lChannels .",samplerate=" . $lProfile["audiosamplerate"] . ",audio-sync,fps=" . ($lProfile["videofps"]) . (Settings::getVLCSubtitleLanguage() != '' ? ',soverlay' : '') . "}";
				$lTransCode .= ":duplicate{";

				if ($lProfileName == Settings::getFLVStreamingProfile()) {
					// FLV Streaming only when enabled and a profile is selected
					$lTransCode .= "dst=std{access=http{mime=video/mp4},mux=ffmpeg{mux=flv},dst=:" . Settings::getFLVStreamingPortnumber() . "/" . $this->vlcStreamUrlFormat($pChannelObj->getName(true)) . ".mp4},";
				} else if(Settings::getFLVStreamingProfile() == '') {
					// Multi bitrate HLS Streaming
					$lTransCode .= "dst=gather:std{access=livehttp{seglen=" . $this->lSegmentLength . ",delsegs=true,numsegs=" . ($this->lDVRLength / $this->lSegmentLength). ",index=" . $this->liPhoneDir . "/iphone/stream/" . $this->vlcStreamUrlFormat($pChannelObj->getName()) . "_" . $lProfileName . ".m3u8,index-url=" . $this->vlcStreamUrlFormat($pChannelObj->getName()) . "_" . $lProfileName . "-########.ts,ratecontrol=true},mux=" . ($lProfile["audiocodec"] == 'mp4a' ? 'ts' : 'raw') . "{use-key-frames},dst=" . $this->liPhoneDir . "/iphone/stream/" . $this->vlcStreamUrlFormat($pChannelObj->getName()) . "_" . $lProfileName . "-########.ts},";
				}

				if ($lProfileName == Settings::getRTSPOnlyProfile()) {
					// RTSP Mobile server only when enabled and a profile is selected
					$lTransCode .= "dst=rtp{dst=". Settings::getVLCPlayerIP(true) .",ttl=20,video=20000,port-audio=20002,sdp=rtsp://:". Settings::getVLCPort() . "/" . $this->vlcStreamUrlFormat($pChannelObj->getName()) . "_" . Settings::getRTSPOnlyProfile() . ".sdp},";
				}

				$lTransCode = substr(trim($lTransCode),0,-1) . "}},";
				$lPortNumbers += 10;
			}

			$lTransCode = substr(trim($lTransCode),0,-1) . "}";

			$lTransCode = "\"" . $lTransCode . "\"";
			$lCMD .= " --sout-avcodec-strict -2 --sout=" . $lTransCode;

			sleep(1); // Wait a bit to make sure that the old VLC process is killed before starting a new one
			if (Settings::getDebug()) {
				$gDebugObj->setDebugMessage('startServer',"VLC Command with aditional delay of " . Settings::getAdditionalTimeout() . " seconds :\n" . $lCMD);
			}
			$_SESSION["ReStream2.0"]["VLC"]["pid"] = Utils::execute($lCMD,'/tmp/restream.vlc.log.txt');
		}

		if (Settings::getFLVStreamingProfile() == '') {
			// Make the multi bit rate m3u8 playlist file
			$this->setIphoneStream($pChannelObj);
			// Add some extra buffer time
			sleep(Settings::getAdditionalTimeout());
		} else {
			// Sleep a few seconds to get the streaming loaded and running (FLV and RTSP)
			sleep(3);
		}
		return $this->getPlayerUrl($pChannelObj);
	}

	public function getPlayerUrl($pChannelObj) {
		$lReturnValue = array();

		if (Settings::getFLVStreamingProfile() == '') {
			$lReturnValue[] = str_replace('//','/',dirname($_SERVER['PHP_SELF']) . '/iphone/stream/' . $this->vlcStreamUrlFormat($pChannelObj->getName(true)) . '.m3u8');
		} else {
			$lReturnValue[] = ':' . Settings::getFLVStreamingPortnumber() . str_replace('//','/', '/' . $this->vlcStreamUrlFormat($pChannelObj->getName(true)) . '.mp4');
		}

		if (Utils::isMobileDevice() && !Utils::isiPhone()) {
			$lReturnValue[] = "rtsp://" . Settings::getVLCPlayerIP(true) . ":" . Settings::getVLCPort() . str_replace('//','/', "/" . $this->vlcStreamUrlFormat($pChannelObj->getName()) . "_" . Settings::getRTSPOnlyProfile() . ".sdp");
		}
		return $lReturnValue;
	}

	public function getCurrentStatus() {
		$this->autoKiller();
		$lReturnArray = array();
		$lReturnArray["channel"] = "";
		$lReturnArray["streamurl"] = "";
		$lReturnArray["procesid"] = -1;

		$lData = trim(shell_exec("ps ax | grep vlc://quit | grep -v grep"));
		if ($lData != '') {
			$lReturnArray["procesid"] = substr($lData,0,stripos($lData,' '));
			$lStartPos = stripos($lData,'dummy') + 6;
			$lEndPos = stripos($lData,'vlc://quit');
			$lReturnArray["streamurl"] = trim(substr($lData,$lStartPos,$lEndPos-$lStartPos));
			if (substr($lReturnArray["streamurl"],0,7) == "http://") {
				$lChannelData = Utils::convertUrlQuery(parse_url($lReturnArray["streamurl"],PHP_URL_QUERY));
				if (!empty($lChannelData['ref'])) {
					$lReturnArray["channel"] = $lChannelData['ref'];
				} else if (!empty($lChannelData['file'])) {
					$lReturnArray["channel"] = $lChannelData['file'];
				}
			} else {
				$lReturnArray["channel"] = $lReturnArray["streamurl"];
			}
			$lReturnArray["channel"] = str_replace("%20"," ",$lReturnArray["channel"]);
		}
		return $lReturnArray;
	}

	private function autoKiller() {
		$killtimerpid = -1;
		if (file_exists('.autoKiller')) {
			$killtimerpid = file_get_contents('.autoKiller');
		}
		if (is_numeric($killtimerpid) && $killtimerpid > 1) {
			// Kill old process
			$lCMD = 'kill -9 ' . $killtimerpid;
			Utils::execute($lCMD,'',true);
		}
		// Start new kill timer...
		$lCMD = '(sleep 120;killall -9 vlc)';
		$pid = Utils::execute($lCMD);
		file_put_contents('.autoKiller', $pid);
	}

	/**
	* Stop the VLC Server. This is handy to spare some system resources.
	*/
	public function stopServer() {
		$lCurrentStatus = $this->getCurrentStatus();
		$lCMD = '';
		if ($lCurrentStatus['procesid'] > 0) $lCMD = "kill -9 " . $lCurrentStatus['procesid'] . ";";
		if (Settings::getFLVStreamingProfile() == '') $lCMD .= "rm " . $this->liPhoneDir . "/iphone/stream/*";
		Utils::execute($lCMD);
	}

	public function setIphoneStream($pChannelObj) {
		global $gDebugObj;

		if ($pChannelObj->isHD()) {
			// Only allow one bitrate when the source is HD
			$this->lActiveProfiles = array(Settings::getHDOnlyProfile());
		}

		if (Settings::getDebug()) $gDebugObj->setDebugMessage('setIphoneStream',"Start checking for iOS playlists... Amount of profiles: " . count($this->lActiveProfiles));
		for ($i = 0; $i < 30; $i++) {
			$lMultiBitrate = "#EXTM3U\n";
			$lProfilesCounter = 0;
			if (Settings::getDebug()) $gDebugObj->setDebugMessage('setIphoneStream',"Checking profile: " . $lProfilesCounter);
			foreach ($this->lProfiles as $lProfileName => $lProfile) {
				if (!in_array($lProfileName,$this->lActiveProfiles)) continue;
				if (file_exists($this->liPhoneDir . "/iphone/stream/" . $this->vlcStreamUrlFormat($pChannelObj->getName()) . "_" . $lProfileName . ".m3u8")) {
					$lMultiBitrate .= "#EXT-X-STREAM-INF:PROGRAM-ID=1,BANDWIDTH=" . round(($lProfile["videobitrate"]+$lProfile["audiobitrate"]) * 1024) . ",RESOLUTION=" . $lProfile["width"] . "x" . $lProfile["height"] . "\n";
					$lMultiBitrate .=  $this->vlcStreamUrlFormat($pChannelObj->getName()) . "_" . $lProfileName . ".m3u8\n";
					$lProfilesCounter++;
				}
			}
			if (Settings::getDebug()) $gDebugObj->setDebugMessage('setIphoneStream',"After $i seconds, amount of profiles done: " . $lProfilesCounter);
			if ($lProfilesCounter == count($this->lActiveProfiles)) {
				file_put_contents($this->liPhoneDir . "/iphone/stream/" . $this->vlcStreamUrlFormat($pChannelObj->getName()) . ".m3u8",$lMultiBitrate);
				// Assumption is that we have now all the playlists with at least one chunk. That is in theory enough to start.
				// In pactical, it needs more time for sure. Wait for at least another chunk is created
				sleep($this->lSegmentLength);
				break;
			}
			// Wait a second for next check
			else sleep(1);
		}
	}

	private function vlcStreamUrlFormat($pUrl) {
		$pUrl = trim($pUrl);
		return str_replace(array(" ","'"),array("_",""),preg_replace("/[^a-zA-Z0-9_\s]/","",$pUrl));
	}

	/**
	* Check if the server is still running.
	* @return boolean
	*/
	public function isServerRunning() {
		$lCurrentStatus = $this->getCurrentStatus();
		return $lCurrentStatus['procesid'] > 0;
	}
}
