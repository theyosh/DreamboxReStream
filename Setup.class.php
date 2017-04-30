<?php
class Setup {
	private $lSettingsFields = array(	'dreamboxName' 			=> array('type' => 'string','required' => true),
										'dreamboxIP' 			=> array('type' => 'string','required' => true),
										'dreamboxWebPort'		=> array('type' => 'int','required' => true),
										'dreamboxUserName'	 	=> array('type' => 'string','required' => false),
										'dreamboxPassword' 		=> array('type' => 'string','required' => false),
										'dreamboxEnigmaVersion' => array('type' => 'string','required' => true),
										'dreamboxDualTuner' 	=> array('type' => 'int','required' => true),
										'privateModusUsername' 	=> array('type' => 'string','required' => false),
										'privateModusPassword'	=> array('type' => 'string','required' => false),
										'privateModus'			=> array('type' => 'int','required' => false),
										'vlcLocation' 			=> array('type' => 'string','required' => true),
										'vlcPlayerIP' 			=> array('type' => 'string','required' => true),
										'vlcLanStreamPort' 		=> array('type' => 'int','required' => true),
										'EPGLimit' 				=> array('type' => 'int','required' => false),
										'vlcAudioLanguage' 		=> array('type' => 'string','required' => false),
										'vlcSubtitleLanguage' 	=> array('type' => 'string','required' => false),
										'DVRLength' 			=> array('type' => 'int','required' => false),
										'AdditionalBufferTime' 	=> array('type' => 'int','required' => false),
									);
	private $lCurrentSettings;
	private $needsUpdate;

	function __construct() {
		$this->lCurrentSettings = array();
		$lConfigFiles = array('Settings.class.sample.php','Settings.class.php');

		foreach($lConfigFiles as $lConfigFile) {
			if (file_exists($lConfigFile)) {
				$lFileContents = file($lConfigFile);
				foreach ($lFileContents as $lLine) {
					if (substr($lLine = trim($lLine),0,14) == 'private static') {
						$lData = explode('=',substr($lLine,14));
						$lVariable = substr(trim($lData[0]),1);
						$lKey = trim($lData[1]);
						if (substr($lKey,-1) == ';') $lKey = trim(substr($lKey,0,-1));
						if (substr($lKey,0,1) == '"') {
							$lKey = substr($lKey,1,-1);
						} else if (substr($lKey,0,1) == "'") {
							$lKey = substr($lKey,1,-1);
						}
						$this->lCurrentSettings[$lVariable] = $lKey;
					}
				}
			}
		}
		$this->needsUpdate = 	!$this->existsSettingsFile() ||
								($this->getSettingsVersion() < $this->getSetupVersion()) ||
								(isset($_GET['setup']) && $_GET['setup'] == 1);
	}

	private function existsSettingsFile() {
		return file_exists('Settings.class.php');
	}

	private function getSetupVersion() {
		return trim(VERSION);
	}

	private function getSettingsVersion() {
		return $this->lCurrentSettings['version'];
	}

	private function validateSettings($pFormData) {
		$lReturnArray = array();
		$lErrorMessages = $this->checkWriteSettings();
		$lReturnArray = array_merge($lReturnArray,$lErrorMessages);

		foreach ($pFormData as $lField => $lValue) {
			if (!empty($this->lSettingsFields[$lField])) {

				if ($this->lSettingsFields[$lField]['required'] && $lValue == '') {
					$lReturnArray[$lField] = 'Field is required';
				} elseif ($this->lSettingsFields[$lField]['type'] == 'int' && $lValue != '' && !is_numeric($lValue)) {
					$lReturnArray[$lField] = 'Invalid input';
				} elseif ($this->lSettingsFields[$lField]['type'] == 'string' && !is_string($lValue)) {
					$lReturnArray[$lField] = 'Invalid input';
				}
			}
		}
		return $lReturnArray;
	}

	private function findIPNumbers() {
		$lReturnArray = array();
		$lCMD = trim(shell_exec('which ip')) . " addr show scope global | grep 'inet '";
		$lData = shell_exec($lCMD);
		$lData = explode("\n",$lData);
		foreach ($lData as $lLine) {
			if (($lLine = trim($lLine)) == '') continue;
			$lLine = explode(' ',$lLine);
			$lIP = substr($lLine[1],0,stripos($lLine[1],'/'));
			$lReturnArray[] = $lIP;
		}
		if (count($lReturnArray) == 0) {
			$lReturnArray[] = '127.0.0.1';
		}
		return array_unique($lReturnArray);
	}

	private function findVLC() {
		if (( $lVLC = trim(shell_exec('which cvlc'))) != '') return $lVLC;
		if (( $lVLC = trim(shell_exec('which vlc'))) != '') return $lVLC;
		return '';
	}

	private function findMediaInfo() {
		return trim(shell_exec('which mediainfo'));
	}

	public function configOutDated() {
		return $this->needsUpdate;
	}

	public function getVariable($pName) {
		$lReturnValue = '';
		if (($pName = trim($pName)) != '') {
			if (!empty($this->lSettingsFields[$pName])) {
				$lFieldData = $this->lSettingsFields[$pName];
				if (!empty($this->lCurrentSettings[$pName])) {
					$lReturnValue = $this->lCurrentSettings[$pName];
				}

				switch ($pName) {
					case 'dreamboxEnigmaVersion':
						$lOptions = array();
						$lOptions[] = array('name' => 'Enigma 1','value'=>'enigma1','selected' => ($lReturnValue == 'enigma1' ? 1 : 0));
						$lOptions[] = array('name' => 'Enigma 2','value'=>'enigma2','selected' => ($lReturnValue == 'enigma2' ? 1 : 0));
						$lReturnValue = $lOptions;
					break;
					
					case 'vlcLocation':
						if ($lReturnValue == '') {
							$lReturnValue = $this->findVLC();
						}
					break;
					case 'vlcPlayerIP':
						$lOptions = array();
						foreach ($this->findIPNumbers() as $lIP) {
							$lOptions[] = array('name' => $lIP,'value'=>$lIP,'selected' => ($lReturnValue == $lIP ? 1 : 0));
						}
						$lReturnValue = $lOptions;
					break;
					case 'mediaInfoLocation':
						if ($lReturnValue == '') {
							$lReturnValue = $this->findMediaInfo();
						}
					break;
				}
			}
		}
		return $lReturnValue;
	}

	public function checkWriteSettings() {
		$lErrorMessages = array();
		if (!file_exists('Settings.class.php')) {
			if (!@touch('Settings.class.php')) {
				$lErrorMessages[] = 'The folder ' . getcwd() . ' is not writable for the web server.';
			} else {
				unlink('Settings.class.php');
			}
		}
		else if (file_exists('Settings.class.php') && !@touch('Settings.class.php')) {
			$lErrorMessages[] = 'Settings file Settings.class.php is not writable.';

		}
		else if (!(@touch('iphone/stream/test.txt') && unlink('iphone/stream/test.txt'))) {
			$lErrorMessages[] = 'The folder iphone/stream is not writable for the web server.';
		}
		else if (!(touch('picon/test.txt') && unlink('picon/test.txt'))) {
			$lErrorMessages[] = 'The folder picon is not writable for the web server.';
		}
		return $lErrorMessages;
	}

	public function updateSettings($pFormData) {
		$lAjaxResponse  = new xajaxResponse();

		$lReturnValue = $this->validateSettings($pFormData);
		if (count($lReturnValue) > 0) {
			$lAjaxResponse->assign("message","innerHTML","<p class='error'>Incorrect data:<br />");
			foreach ($lReturnValue as $lField => $lMessage) {
				if ($lField != '') $lAjaxResponse->script("jQuery('#message p').append(jQuery('label[for=\"" . $lField . "\"]').text() + ': ')");
				$lAjaxResponse->script("jQuery('#message p').append('$lMessage<br />')");
			}
			$lAjaxResponse->script("jQuery('#message').dialog({modal: true,closeOnEscape: false});");
		} else {
			foreach($this->lCurrentSettings as $lField => $lData) {
				switch ($lField) {
					case 'version':
						$pFormData[$lField] = $this->getSetupVersion();
					break;
					default:
						if (!array_key_exists($lField,$pFormData)) $pFormData[$lField] = $lData;
					break;
				}

			}

			$lFileContents = file('Settings.class.sample.php');
			for ($i = 0; $i < count($lFileContents); $i++) {
				$lLine = $lFileContents[$i];
				if (substr($lLine = trim($lLine),0,14) == 'private static') {
					$lData = explode('=',substr($lLine,14));
					$lVariable = substr(trim($lData[0]),1);

					$lStartPos = stripos($lFileContents[$i],$lVariable)-1;
					$lStopPos = stripos($lFileContents[$i],';',$lStartPos)+1;

					$lSearch = substr($lFileContents[$i],$lStartPos,$lStopPos-$lStartPos);

					if (empty($pFormData[$lVariable]) || !is_numeric($pFormData[$lVariable])) $pFormData[$lVariable] = '"' . $pFormData[$lVariable] . '"';
					$lReplace = '$' . $lVariable . ' = ' . $pFormData[$lVariable] . ';';

					$lFileContents[$i] = str_replace($lSearch, $lReplace, $lFileContents[$i]);
				}
			}
			if (file_put_contents('Settings.class.php',implode('',$lFileContents))) {
				unset($_SESSION["ReStream2.0"]["Dreambox"]);
				$lAjaxResponse->assign("message","innerHTML","<p>Settings are configured. The page will refresh in 5 seconds.</p>");
				$lAjaxResponse->script("setTimeout(function() {location.href=location.href.replace(/[&\?]+setup=1/gi,'') ;},5000)");
			} else {
				$lAjaxResponse->assign("message","innerHTML","<p>Error!</br >Could not write the settings file</p>");
			}
		}
		$lAjaxResponse->script("jQuery('#message').dialog({modal: true,closeOnEscape: false});");
		return $lAjaxResponse;
	}
}