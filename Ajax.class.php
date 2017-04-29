<?php
function action($pAction,$pArgs = null) {
	global $lDreamBoxObj, $gDebugObj, $gSetupObj, $gUpdateObj;
	$pAction = trim($pAction);
	if ($pAction == "") return false;
	if (!is_array($pArgs)) $pArgs = explode(",",$pArgs);
	$lReturnValue = new xajaxResponse();

	switch ($pAction) {
		case 'initRestream';
			if ($gSetupObj->configOutDated()) {
				$lReturnValue->script("loadChannelList()");
				$lReturnValue->script("jQuery('#ProgramProgress').progressbar({value: 0})");
				$lReturnValue->script("jQuery('#message').html('<p>' + jQuery('#ReStreamPlayer p').html() + '</p>')");
			} else {
				// Clear the session cache
				if (Settings::getDebug()) {
					@unlink('dreambox.cache');
					unset($_SESSION["ReStream2.0"]["Dreambox"]);
				}
				if ( ($lMessage = $lDreamBoxObj->sanityCheck()) !== true) {
					$lReturnValue->script("DreamboxObj.dialog('Loading dreambox<br /><span class=\"error\">Your PHP settings are not correct to work... Change the following setting(s):<br />" . $lMessage . "</span>')");
				} else if ($lDreamBoxObj->isOnline()) {
					$lReturnValue->script("DreamboxObj.dialog('Loading dreambox<br />Loading bouquets....')");
					$lReturnValue->script('DreamboxObj.loadBouquets()');
				} else {
					$lReturnValue->script("DreamboxObj.dialog('Loading dreambox<br /><span class=\"error\">The Dreambox is currently offline!</span>')");
					$lReturnValue->script('DreamboxObj.showBouquets()');
					$lReturnValue->script("setTimeout(function() { DreamboxObj.loadMovies() } , 2000)");
				}
			}
		break;

		case 'loadBouqetData':
			$lDreamBoxObj->loadBouquets(Settings::getBouqetsFilter());
			foreach($lDreamBoxObj->getBouquets() as $lBouquet) {
				$lReturnValue->script("DreamboxObj.addBouquet('" . Utils::JSSave($lBouquet->getID()) . "','" . Utils::JSSave($lBouquet->getName()) . "')");
			}
			$lReturnValue->script('DreamboxObj.showBouquets()');
			$lReturnValue->script("jQuery('h3[id=\"_about\"] ~ div ul').append(jQuery('<li>').attr({'class':'about'}).html('" . Utils::JSSave("<img src='images/dreamboxrestream_icon.png' alt='Dreambox ReStream Logo' width='114' height='114'/><br /><br />" . Settings::getDreamboxName() . "<br />Dreambox ReStream (" . Settings::getVersionNumber() . ")<br />Released at " . date('j F Y',RELEASEDATE) . "<br /><a href=\"javascript:void(0);\" onclick=\"showChangeLog();\" title=\"Read changelog\">CHANGELOG</a><br /><a href=\"?setup=1\" title=\"Click to enter the setup page\" >Setup</a>, <a href=\"?cachepurge=1\" title=\"Purge the cache and reload the data\">Purge</a><br /><br />Desktop interface<br /><br />Copyright 2006-" . date("Y") . " - <a href='http://theyosh.nl' class='external' target='_blank' title='The YOSH'>TheYOSH</a><br /><br />Like the software?<br />Consider a donation<br /><form action=\"https://www.paypal.com/cgi-bin/webscr\" method=\"post\" target=\"_blank\">
<input type=\"hidden\" name=\"cmd\" value=\"_donations\">
<input type=\"hidden\" name=\"business\" value=\"paypal@theyosh.nl\">
<input type=\"hidden\" name=\"lc\" value=\"US\">
<input type=\"hidden\" name=\"item_name\" value=\"Dreambox ReStream\">
<input type=\"hidden\" name=\"no_note\" value=\"0\">
<input type=\"hidden\" name=\"currency_code\" value=\"EUR\">
<input type=\"hidden\" name=\"bn\" value=\"PP-DonationsBF:btn_donate_LG.gif:NonHostedGuest\">
<input type=\"image\" src=\"https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif\" border=\"0\" name=\"submit\" alt=\"PayPal - The safer, easier way to pay online!\">
<img alt=\"\" border=\"0\" src=\"https://www.paypalobjects.com/en_US/i/scr/pixel.gif\" width=\"1\" height=\"1\">
</form>") . "'))");
			$lReturnValue->script("DreamboxObj.dialog('Loading dreambox<br />Loading bouquets.... done!<br />Loading channels....')");
			$lReturnValue->script('DreamboxObj.loadChannels()');
			$lReturnValue->script('DreamboxObj.loadRecordings()');
		break;

		case 'loadChannelData':
			if (substr($pArgs[0],0,1) != '_') {
				$lDreamBoxObj->loadChannel($pArgs[0]);
				$lBouquetObj = $lDreamBoxObj->getBouquet($pArgs[0]);
				if ($lBouquetObj !== false) {
					foreach($lBouquetObj->getChannels() as $lChannelObj) {
						$lReturnValue->script("DreamboxObj.addChannel('" . Utils::JSSave($lChannelObj->getID()) . "','" . Utils::JSSave($lBouquetObj->getID()) . "','" . Utils::JSSave($lChannelObj->getName()) . "','" . ($lChannelObj->isMarker() ? 'marker' : 'channel') . "'," . ($lChannelObj->isHD() ? 'true' : 'false') . ")");
					}
					if ($lBouquetObj->getChannelCount() == 0) {
						$lReturnValue->script("DreamboxObj.removeBouquet('" . Utils::JSSave($lBouquetObj->getID()) . "')");
					} else {
						$lReturnValue->script("DreamboxObj.showChannels('" . Utils::JSSave($lBouquetObj->getID()) . "')");
					}
				}
			}
			$lReturnValue->script('DreamboxObj.loadChannels()');
		break;

		case 'loadRecordingData':
			$lDreamBoxObj->loadRecordings();
			foreach($lDreamBoxObj->getRecordings() as $lRecording) {
				$lReturnValue->script("DreamboxObj.addRecording('" . Utils::JSSave($lRecording->getID()) . "','" . Utils::JSSave($lRecording->getName()) . "'," . Utils::JSSave($lRecording->getStartTime()) . ",'" . Utils::JSSave($lRecording->getServiceName()) . "'," . Utils::JSSave($lRecording->getDuration()) . ",'" . Utils::JSSave($lRecording->getDescription()) . "','" . Utils::JSSave($lRecording->getLongDescription()) . "'," . Utils::JSSave($lRecording->getFileSize()) . ")");
			}
			$lReturnValue->script('DreamboxObj.showRecordings()');
			$lReturnValue->script("DreamboxObj.dialog('Loading dreambox<br />Loading bouquets.... done!<br />Loading channels.... done!<br />Loading recordings.... done!<br />Loading movies....')");
			$lReturnValue->script('DreamboxObj.loadMovies()');
		break;

		case 'loadMovieData':
			$lDreamBoxObj->loadMovies();
			foreach($lDreamBoxObj->getMovies() as $lMovie) {
				$lReturnValue->script("DreamboxObj.addMovie('" . Utils::JSSave($lMovie->getID()) . "','" . Utils::JSSave($lMovie->getName()) . "'," . $lMovie->getDuration() . "," . $lMovie->getFileSize() . "," . $lMovie->getBitrate() . ",'" . $lMovie->getResolution() . "',new Array('" . implode($lMovie->getAvailableSubtitleLanguages(),"','"). "')," . ($lMovie->isHD() ? 'true' : 'false') . ")");
			}
			$lReturnValue->script('DreamboxObj.showMovies()');
			$lReturnValue->script("DreamboxObj.dialog('Loading dreambox<br />Loading bouquets.... done!<br />Loading channels.... done!<br />Loading recordings.... done!<br />Loading movies.... done!')");
			$lReturnValue->script('DreamboxObj.start()');
		break;

		case 'loadWebCamsData':
			$lDreamBoxObj->loadWebCams();
			foreach($lDreamBoxObj->getWebCams() as $lWebCam) {
				$lReturnValue->script("DreamboxObj.addWebCam('" . Utils::JSSave($lMovie->getID()) . "','" . Utils::JSSave($lMovie->getName()) . "')");
			}
			$lReturnValue->script('DreamboxObj.showWebCams()');
			$lReturnValue->script("DreamboxObj.dialog('Loading dreambox<br />Loading bouquets.... done!<br />Loading channels.... done!<br />Loading recordings.... done!<br />Loading movies.... done!<br />Loading webcams.... done!')");
			$lReturnValue->script('DreamboxObj.start()');
		break;

		case 'loadNowAndNextEPGData':
			$lBouquetID = $pArgs[0];
				foreach($lDreamBoxObj->getBouquets() as $lBouquet) {
					if ($lBouquetID == $lBouquet->getID() && $lBouquet->getChannels() > 0) {
					$lDreamBoxObj->loadCurrentAndNextEPG($lBouquetID);
					foreach($lBouquet->getChannels() as $lChannel) {
						$lGuide = $lDreamBoxObj->getProgramGuide($lChannel->getID());
						if ($lGuide !== false) {
							$lCounter = 0;
							foreach($lGuide->getPrograms() as $lProgram) {
								if ($lProgram->getStopTime() < time()) continue;
								$lTitle = Utils::JSSave($lProgram->getTitle());
								$lDescription = Utils::JSSave(nl2br(Utils::parseUrls($lProgram->getDescription())));
								$lReturnValue->script("DreamboxObj.addProgram('" .  Utils::JSSave($lProgram->getID()) . "','" .  Utils::JSSave($lChannel->getID()) . "','" .  $lTitle . "'," .  Utils::JSSave($lProgram->getStartTime()) . "," .  Utils::JSSave($lProgram->getStopTime()) . ",'" . $lDescription . "')");
								$lCounter++;
								if ($lCounter == 2) break;
							}
						}
					}
				}
			}
			$lReturnValue->script("DreamboxObj.showCurrentPrograms('" . $lBouquetID . "')");
			if ((Utils::isMobileDevice() && getAutoLoadEPG()) || !Utils::isMobileDevice()) $lReturnValue->script('DreamboxObj.loadPrograms()');
		break;

		case 'loadChannelEPGData':
		  $lBouquet = $lDreamBoxObj->getBouquet($pArgs[0]);
			$lDreamBoxObj->loadProgramGuide($lBouquet->getID());
			//print_r($lDreamBoxObj);
			foreach ($lBouquet->getChannels() as $lChannel) {
  			$lGuide = $lDreamBoxObj->getProgramGuide($lChannel->getID());
  			if ($lGuide !== false) {
  			  $lReturnValue->script("DreamboxObj.clearPrograms('" . $lBouquet->getID() . "','" . $lChannel->getID() . "')");
  				foreach($lGuide->getPrograms() as $lProgram) {
  					if ($lProgram->getStopTime() < time()) continue; // Skip passed programs
  					$lTitle = Utils::JSSave($lProgram->getTitle());
  					$lDescription = Utils::JSSave(nl2br(Utils::parseUrls($lProgram->getDescription())));
  					if (strlen($lProgram->getLongDescription()) > strlen($lProgram->getDescription())) $lDescription = Utils::JSSave(nl2br(Utils::parseUrls($lProgram->getLongDescription())));
  					$lReturnValue->script("DreamboxObj.addProgram('" .  Utils::JSSave($lProgram->getID()) . "','" .  Utils::JSSave($lChannel->getID()) . "','" . $lTitle . "'," .  Utils::JSSave($lProgram->getStartTime()) . "," .  Utils::JSSave($lProgram->getStopTime()) . ",'" . $lDescription . "')");
  				}
  			}
			}
			$lReturnValue->script("DreamboxObj.showCurrentPrograms('" .  Utils::JSSave($lBouquet->getID()) . "')");
			$lReturnValue->script('DreamboxObj.loadPrograms()');
		break;

		case 'startWatching':
			if (Settings::getMoviesPath() != "" && substr($pArgs[0],0,strlen(Settings::getMoviesPath())) == Settings::getMoviesPath()) {
				$lChannelObj = $lDreamBoxObj->getMovie($pArgs[0]);
			} elseif (substr($pArgs[0],-3) == ".ts") {
				$lChannelObj = $lDreamBoxObj->findRecording($pArgs[0]);
			} else {
				$lChannelObj = $lDreamBoxObj->findChannel($pArgs[0]);
			}

			$lVLCObj = new VLCServer();
			$lPlayerCMDs = $lVLCObj->startServer($lDreamBoxObj->getStreamURL($pArgs[0]),$lChannelObj);
			foreach($lPlayerCMDs as $lPlayerCMD) {
				$lReturnValue->script("DreamboxObj.play('" .  Utils::JSSave($lPlayerCMD) . "')");
			}
			$lReturnValue->script("DreamboxObj.encodingStatus('" . Utils::JSSave($pArgs[0]) . "')");
			$lReturnValue->script("DreamboxObj.showActiveChannelImage('" .  Utils::JSSave($lDreamBoxObj->getChannelImage($pArgs[0])). "')");
			$lReturnValue->script('DreamboxObj.startProgressBar()');
		break;

		case 'stopWatching':
			$lVLCObj = new VLCServer();
			$lVLCObj->stopServer();
			$lReturnValue->script("DreamboxObj.encodingStatus('')");
		break;

		case 'encodingStatus' :
			$lVLCObj = new VLCServer();
			$lVLCData = $lVLCObj->getCurrentStatus();
			$lReturnValue->script("DreamboxObj.encodingStatus('" . Utils::JSSave($lVLCData["channel"]) . "')");
		break;

		case 'saveMobileOptions':
			$lReturnValue = processMobileOptions($pArgs);
		break;

		case 'updateConfig':
			$lReturnValue = $gSetupObj->updateSettings($pArgs);
		break;

		case "updateSoftware":
			$lReturnValue = $gUpdateObj->runUpdate();
		break;
	}

	if (!$gSetupObj->configOutDated() && Settings::getDebug()) {
		if ($gDebugObj->getDebugMessage() != "") {
			$lReturnValue->prepend("errorLog","innerHTML",$gDebugObj->getDebugMessage());
		}
	}
	return $lReturnValue;
}

// Mobile functions
function processMobileOptions($pFormData) {
	setMobileOption("epg",($pFormData["epg"] == "on" ? 1 : 0));
	if (!empty($pFormData["user"])) setMobileOption("user",$pFormData["user"]);
	if (!empty($pFormData["pass"])) setMobileOption("pass",$pFormData["pass"]);

	$lAjaxResponse  = new xajaxResponse();
	$lAjaxResponse->script("jQuery('.ui-dialog').dialog('close')");
	if ($pFormData["epg"] == "on") $lAjaxResponse->script("DreamboxObj.loadPrograms();");
	return $lAjaxResponse;
}

function setMobileOption($pParam,$pValue) {
	$pParam = trim($pParam);
	if ($pParam == "") return;
	switch ($pParam) {
		case "epg":
		case "user":
		case "pass":
			$_SESSION['ReStreamMobile'][$pParam] = $pValue;
		break;
	}
	saveMobileOptions($lOptions);
}

function getMobileOption($pParam) {
	$pParam = trim($pParam);
	if ($pParam == "") return;
	$lValue = "";
	if (empty($_SESSION['ReStreamMobile'])) {
		loadMobileOptions();
	}
	if (isset($_SESSION['ReStreamMobile'][$pParam])) $lValue = trim($_SESSION['ReStreamMobile'][$pParam]);
	return $lValue;
}

function loadMobileOptions() {
	$lOptions = array();
	if (!isset($_COOKIE["ReStreamMobile"])) return $lOptions;
	$lData = explode("|",$_COOKIE["ReStreamMobile"]);
	foreach ($lData as $lOption) {
		$lOption = explode("=",$lOption);
		if (trim($lOption[0]) != "") {
			$lOptions[trim($lOption[0])] = trim($lOption[1]);
		}
	}
	$_SESSION['ReStreamMobile'] = $lOptions;
}

function saveMobileOptions($pOptions) {
	$lCookieData = array();
	foreach ($_SESSION['ReStreamMobile'] as $lParam => $lValue) {
		$lCookieData[] = $lParam . "=" . $lValue;
	}
	setcookie("ReStreamMobile",implode("|",$lCookieData), time()+(3600*24*30)); // 1 month
}

function getAutoLoadEPG() {
	return getMobileOption("epg") == 1;
}
