<?php
class Settings {
/* Main configuration part */
	/**
	* Set the program version. Should not be changed
	* This setting is required
	* @access private
	* @var string
	*/
	private static $version = '2.3.7';

	/**
	* Set the name of the dreambox. Anything is allowed :P
	* This setting is optional
	* @access private
	* @var string
	*/
	private static $dreamboxName = "Yoshie's Dreambox";

	/**
	* Set the local IP address of your dreambox. Should be an internal IP number or hostname. For best performance, use an IP number.
	* This setting is required
	* @access private
	* @var string
	*/
	private static $dreamboxIP = '127.0.0.1';

	/**
	* Set the web port number of your dreambox. Mostly is should be port 80, but some versions have a different portnumber.
	* This setting is required
	* @access private
	* @var int
	*/
	private static $dreamboxWebPort = 80;

	/**
	* Set the username for the dreambox webinterface. Leave empty when no authentication is required.
	* This setting is optional
	* @access private
	* @var string
	*/
	private static $dreamboxUserName = "root";

	/**
	* Set the password for the dreambox webinterface. Leave empty when no authentication is required.
	* This setting is optional
	* @access private
	* @var string
	*/
	private static $dreamboxPassword = "Jos24Kin80";

	/**
	* Set the enigma version of the dreambox. Valid values are enigma1 of enigma2
	* This setting is required
	* @access private
	* @var string
	*/
	private static $dreamboxEnigmaVersion = "enigma2";

	/**
	* Select true if the dreambox has a second tuner. This is only valid for enigma2 based firmwares
	* If you own a single tuner dreambox, put this value to 0. This will trigger the function zap before watching
	* on your dreambox.
	* This setting is required
	* @access private
	* @var boolean
	*/
	private static $dreamboxDualTuner = 1;


	/**
	* Set the limit for the EPG date in the future in hours. This will safe some bandwidth.
	* @access private
	* @var integer
	*/
	private static $EPGLimit = 36;

	/**
	* Set the limit in Bouquetes for loading the now and next EPG. With a lot of bouquetes
	* it could case hanging in the browser. You can change the value from 0 to unlimited
	* Enter the value 0 to disable the loading of the now and next EPG data.
	* @access private
	* @var integer
	*/
	private static $EPGNowAndNextLimit = 2;

	private static $IgnoreEmptyBouquets = "true";

	/**
	* The full path to your movies folder on the server. It has to be a local path.
	*
	* Leave empty to disable this feature.
	* This setting is optional
	*
	* @access private
	* @var string
	*/
	private static $moviesPath = '';

	/**
	* Set the alowed extension for valid movies. Should not be changed
	* This setting is required
	* @access private
	* @var string
	*/
	private static $validMovies = "avi,mkv,mpeg,mpg,mp4,mov";

/* Private configuration */

	/**
	* If you want to make Dreambox Restream private, set the variable below to 1 and set the username and password
	* Set to false to make Dreambox Restream plublic
	* This setting is optional
	*/
	private static $privateModus = "0";

	private static $privateModusUsername = "aap";

	private static $privateModusPassword = "aap";

/* VLC configuration */

	/**
	* Set the location of the VLC server executable. At least set the value that is corresponding with your
	* operating system where the PHP code is running. The default settings should be working for 90% of the time.
	* This setting is required
	* @access private
	* @var string
	*/
	private static $vlcLocation = "/usr/bin/cvlc";

/* Bitrate settings */

	/**
	* Select the profiles to use. There are five profiles that can be selected. The full list is in the
	* file VLCServer.class.php. The more profiles are used, the more CPU power is needed. If there are problems
	* with playing the stream, remove a profile from the list to reduce the CPU load and improve the stream.
	*
	* valid profiles are: FullHD, HDReady, SD, Mobile1, Mobile2
	* @access private
	* @var string
	*/
	private static $enabledProfiles = "HDReady,SD,Mobile2";

	/**
	* Sometimes transcoding a full HD source does give problems. This can be fixed by setting this variable
	* to the number 19. This option changes the transcoding in such manier, that only one profile is used instead
	* of the profiles selected above.
	* To disable this behavior, select the number -1. This will enable full transcoding with all the selected
	* profiles from above.
	* @access private
	* @var integer
	*/
	private static $DMHDID = 19;

	/**
	* Select here the profiles that should be used for transcoding full HD sources. For more information see above
	* A valid profiles is one of from the list in $enabledProfiles
	* @access private
	* @var string
	*/
	private static $HDOnlyProfile = "SD";

	/**
	* Select here the profiles that should be used for transcoding to RTSP enabled mobiles. This is used for Android streaming
	* Leave empty to disable this transcoding settings. New Androids should be able to play the HLS stream.
	* When using this option, make sure that port number in $vlcLanStreamPort is open on your firewall and forwarded
	* A valid profiles is one of from the list in $enabledProfiles
	* @access private
	* @var string
	*/
	private static $RTSPOnlyProfile = "";


	/**
	* This setting will enable the FLV streaming option in stead of HLS streaming.
	* This will make the stream start faster, but you lose the multi bit rate option. Also this will drop iOS support.
	* When using this option, make sure that port number in $vlcLanStreamPort is open on your firewall and forwarded
	* When leaving empty, this setting is disabled.
	* A valid profiles is one of from the list in $enabledProfiles
	* @access private
	* @var string
	*/

	private static $FLVStreamingProfile = "";

	/**
	* Set the external IP or hostname for external connections to the VLC Server. The VLC Server will bind on all interfaces on the server
	* You can use port forwarding to enable access from the internet to the internal VLC server.
	* This setting is optional
	* @access private
	* @var string
	*/
	private static $vlcPlayerIP = '127.0.0.1';

	/**
	* Set the the internal port number for streaming. This portnumber should be forwareded in your router / modem.
	* This setting is required
	* @access private
	* @var string
	*
	*/
	private static $vlcLanStreamPort = 1234;

	/**
	*
	* Specify the country codes order for the audio language track.
	* Valid codes: http://nl.wikipedia.org/wiki/Lijst_van_ISO_639-2-codes
	* Or use an integer for selecting the right track.
	* @access private
	* @var string
	*
	*/
	private static $vlcAudioLanguage = "";

	/**
	*
	* Specify the country codes order for the subtitle track.
	* Valid codes: http://nl.wikipedia.org/wiki/Lijst_van_ISO_639-2-codes
	* Or use an integer for selecting the subtitle track.
	* @access private
	* @var string
	*
	*/
	private static $vlcSubtitleLanguage = "";

	/**
	*
	* Specify the iOS DVR length in seconds. Minimum length = 30 seconds.
	* @access private
	* @var int
	*/
	private static $DVRLength = 120;

	/**
	*
	* Add extra timeout before the player starts playing. This could fix some
	* stuttering in the stream when. The higher this value, the bigger the buffer
	* @access private
	* @var int
	*/
	private static $AdditionalBufferTime = 5;

	/**
	*
	* Specify the desktop player to use. Possible options are: JW, OSMF, Grind.
	* @access private
	* @var string
	*/
	private static $DesktopPlayer = "jw";

/* MediaInfo configration */

	/**
	* Set the location of the MediaInfo server executable. At least set the value that is corresponding with your
	* operating system where the PHP code is running. The default settings should be working for 90% of the time.
	*
	* Leave empty to disable the support of MediaInfo. You will lose the duration information on your movies.
	* This setting is optional
	*
	* @access private
	* @var string
	*/
	private static $mediaInfoLocation = "/usr/bin/mediainfo";

/* End of settings configuration. You should not edit below this line */

	/**
	* Get the next program from the program guide.
	* @return string
	*/
	static public function getDreamboxName() {
		return trim(Settings::$dreamboxName);
	}

	/**
	* Get the program name.
	* @return string
	*/
	static public function getProgramName() {
		return 'Dreambox ReStream';
	}

	/**
	* Get the program version number.
	* @return string
	*/
	static public function getVersionNumber() {
		return trim(Settings::$version);
	}

	/**
	* Get the IP number of the dreambox.
	* @return string
	*/
	static public function getDreamboxIP() {
		return gethostbyname(trim(Settings::$dreamboxIP));
	}

	/**
	* Get the port number of the dreambox.
	* @return integer
	*/
	static public function getDreamboxWebPort() {
		return trim(Settings::$dreamboxWebPort);
	}

	/**
	* Get the username of the dreambox.
	* @return string
	*/
	static public function getDreamboxUserName() {
		return trim(Settings::$dreamboxUserName);
	}

	/**
	* Get the password of the dreambox.
	* @return string
	*/
	static public function getDreamboxPassword() {
		return trim(Settings::$dreamboxPassword);
	}

	/**
	* Get the enigma version of the dreambox.
	* @return string
	*/
	static public function getEnigmaVersion() {
		return trim(Settings::$dreamboxEnigmaVersion);
	}

	/**
	* Return true when the dreambox is dual tuner enabled.
	* @return boolean
	*/
	static public function isDualTuner() {
		return (Settings::$dreamboxDualTuner);
	}

	static public function getEPGLimit() {
		if (!is_numeric(Settings::$EPGLimit)) Settings::$EPGLimit = 24;
		if (Settings::$EPGLimit > (7 * 24) || Settings::$EPGLimit < 1) {
			Settings::$EPGLimit = 24;
		}
		return (Settings::$EPGLimit);
	}

	static public function getNowAndNextEPGLimit() {
		return ((is_numeric(Settings::$EPGNowAndNextLimit) && Settings::$EPGNowAndNextLimit > -1) ? Settings::$EPGNowAndNextLimit : 6);
	}

	/**
	* Return the VLC executable based on OS type.
	* @return string
	*/
	static public function getVLCExecutable() {
		$lVLCExecutable = trim(Settings::$vlcLocation);
		return ($lVLCExecutable != "" && file_exists($lVLCExecutable)? $lVLCExecutable : false);
	}


	static public function getActiveProfiles() {
		return explode(',',str_replace(' ','',Settings::$enabledProfiles));
	}

	static public function getDMHDID() {
		return Settings::$DMHDID;
	}

	static public function getHDOnlyProfile() {
		return Settings::$HDOnlyProfile;
	}

	static public function getRTSPOnlyProfile() {
		return Settings::$RTSPOnlyProfile;
	}

	static public function getFLVStreamingProfile() {
		return trim(Settings::$FLVStreamingProfile);
	}

	static public function getFLVStreamingPortnumber() {
		return Settings::getVLCPort();
	}

	/**
	* Get the VLC Server Player IP number.
	* @return string
	*/
	static public function getVLCPlayerIP($pResolve = false) {
		$lIP = trim(Settings::$vlcPlayerIP);
		if ($pResolve) $lIP = gethostbyname($lIP);
		return $lIP;
	}

	/**
	* Get the internal streaming port of the VLC Server.
	* @return string
	*/
	static public function getVLCPort() {
		return trim(Settings::$vlcLanStreamPort);
	}

	static public function getVLCAudioLanguage() {
		return trim(Settings::$vlcAudioLanguage);
	}

	static public function getVLCSubtitleLanguage() {
		return trim(Settings::$vlcSubtitleLanguage);
	}

	static public function getDVRLength() {
		$lDVRLength = 30; // Min DVR Length = 30 seconds
		if (is_numeric(Settings::$DVRLength) && Settings::$DVRLength > $lDVRLength) $lDVRLength = Settings::$DVRLength;
		return $lDVRLength;
	}

	static public function getAdditionalTimeout() {
		$AdditionalBufferTime = 0; // Min Length = 0 seconds
		if (is_numeric(Settings::$AdditionalBufferTime) && Settings::$AdditionalBufferTime > $AdditionalBufferTime) $AdditionalBufferTime = Settings::$AdditionalBufferTime;
		return $AdditionalBufferTime;
	}

	static public function getDesktopPlayer() {
		$lValidPlayers = array('jw','osmf','grind');
		if (in_array(strtolower(Settings::$DesktopPlayer),$lValidPlayers)) {
			return trim(strtolower(Settings::$DesktopPlayer));
		}
		return 'jw'; // Default player
	}

	static public function getMediaInfoxecutable() {
		$lMediaInfoExecutable = trim(Settings::$mediaInfoLocation);
		return ($lMediaInfoExecutable != "" && file_exists($lMediaInfoExecutable)? $lMediaInfoExecutable : false);
	}

	static public function isPrivate() {
		return (Settings::$privateModus && Settings::getPrivateModusUsername() != "" && Settings::getPrivateModusPassword() != "");
	}

	static public function getPrivateModusUsername() {
		return trim(Settings::$privateModusUsername);
	}

	static public function getPrivateModusPassword() {
		return trim(Settings::$privateModusPassword);
	}

	static public function getMoviesPath() {
		return trim(Settings::$moviesPath);
	}

	static public function getValidMovies() {
		return explode(",",Settings::$validMovies);
	}

	static public function getIgnoreEmptyBouquets() {
		return $IgnoreEmptyBouquets === true;
	}

	static public function getDebug() {
		return (isset($_GET["debug"]) && $_GET["debug"] == 1);
	}
}
