<?php
class Update {
	private $lLastUpdateCheck = 'restream.current.version.txt';
	private $lUpdateCheckUrl = 'http://theyosh.nl/restream.dev.version.txt';
	private $lUpdateTimeOut = 86400; // 24 hours
	private $lUpdateFolder = '.update';

	private $lUpdateDownloadUrl;
	private $lUpdateVersion;
	private $lUpdateHash;
	private $lUpdateFile;
	private $lUpdateSourceLocation;

	private $lDreamboxReStreamLocation;
	private $lExistingSettingsFile;

	function __construct($pLoad = true) {
		$this->lUpdateVersion = -1;
		$this->lDreamboxReStreamLocation = getcwd();
		$this->lExistingSettingsFile = $this->lDreamboxReStreamLocation . '/Settings.class.php';
		$this->lUpdateFolder = $this->lDreamboxReStreamLocation . '/' . $this->lUpdateFolder;
		$this->lLastUpdateCheck = $this->lDreamboxReStreamLocation . '/' . $this->lLastUpdateCheck;

		$lCheckOnline = true;
		$lUpdateData = '';

		if (file_exists($this->lLastUpdateCheck)) {
			// Check chage age
			$lCheckOnline = !empty($_GET['checkupdate']) || ((time() - filemtime($this->lLastUpdateCheck)) > $this->lUpdateTimeOut);
			$lUpdateData = file_get_contents($this->lLastUpdateCheck);
		}

		if ($pLoad && $lCheckOnline) {
			if (($lUpdateData = file_get_contents($this->lUpdateCheckUrl . "?" . Settings::getEnigmaVersion())) === false) {
				$lUpdateData = '';
			}
		}

		if ($lUpdateData != '') {
			$lUpdateData = explode("\n",$lUpdateData);
			$this->lUpdateVersion = explode(" ",trim($lUpdateData[0]));
			$this->lUpdateDownloadUrl = trim($this->lUpdateVersion[1]);
			$this->lUpdateHash = trim($this->lUpdateVersion[2]);
			$this->lUpdateVersion = trim($this->lUpdateVersion[0]);
			$this->lUpdateFile = basename(parse_url($this->lUpdateDownloadUrl, PHP_URL_PATH));

			if (!empty($_GET['update'])) {
				// Test data....
				$this->lUpdateVersion = $_GET['update'];
			}
			if($lCheckOnline) @file_put_contents($this->lLastUpdateCheck, implode("\n",$lUpdateData));
		}
	}

	private function downloadUpdate() {
		$lReturnErrors = array();
		Utils::execute('rm -Rf "' . $this->lUpdateFolder . '"','',true);
		if (mkdir($this->lUpdateFolder)) {
			$this->lUpdateFile = $this->lUpdateFolder . '/' . $this->lUpdateFile;
			if (($lUpdateZip = file_get_contents($this->lUpdateDownloadUrl)) !== false) {
				if (file_put_contents($this->lUpdateFile,$lUpdateZip) !== false) {
					if (sha1_file($this->lUpdateFile) == $this->lUpdateHash) {
						return true;
					} else {
						$lReturnErrors[] = 'Sha1 Hash is invalid.';
					}
				} else {
					$lReturnErrors[] = 'Can\'t write file do disk: ' . $this->lUpdateFile;
				}
			} else {
				$lReturnErrors[] = 'Can\'t download the update file from source: ' . $his->lUpdateDownloadUrl;
			}
		} else {
			$lReturnErrors[] = 'Can\'t create update folder. Make sure that the restream folder is writable by the web server. Or remove the existing update directory';
		}
		return $lReturnErrors;
	}

	private function unpackUpdate() {
		$lCMD = 'unzip "' . $this->lUpdateFile . '" -d "' . $this->lUpdateFolder . '"';
		Utils::execute($lCMD,'',true);
		$lFolderData = scandir($this->lUpdateFolder);
		foreach ($lFolderData as $lEntry) {
			if (substr($lEntry,0,1) == '.') continue;
			if (stripos($lEntry, 'restream') !== false && is_dir($this->lUpdateFolder . '/' . $lEntry)) {
				$this->lUpdateSourceLocation = array_merge(array($this->lUpdateFolder . '/' . $lEntry),$this->glob_recursive($this->lUpdateFolder . '/' . $lEntry . '/*'));
				unlink($this->lUpdateFile);
				return true;
			}
		}
		$lReturnErrors[] = 'Can\'t find the update folder at location: ' . $this->lUpdateFolder;
		return $lReturnErrors;
	}

	private function glob_recursive($pattern, $flags = 0) {
	    $files = glob($pattern, $flags);
	    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir)
	    {
			$files = array_merge($files, $this->glob_recursive($dir.'/'.basename($pattern), $flags));
	    }
	    return $files;
	}

	private function checkWriteRights() {
		$lErrors = array();
		$lPrefixLength = strlen($this->lUpdateSourceLocation[0] . '/');
		if (!is_writable($this->lDreamboxReStreamLocation)) $lErrors[] = $this->lDreamboxReStreamLocation;

		foreach ($this->lUpdateSourceLocation as $lUpdateFile) {
			$lFile = substr($lUpdateFile,$lPrefixLength);
			clearstatcache();
			if (file_exists($lFile)) {
				clearstatcache();
				if (!is_writable($lFile)) {
					clearstatcache();
					$lErrors[] = $lFile . ' is not writable by the webserver.';
				}
			} else {
				// Check if parent folder is writable...
				$lData = pathinfo($lUpdateFile);
			}
		}
		return (count($lErrors) == 0 ? true : $lErrors);
	}

	private function copyExistingSetup() {
		$lErrors = array();
		$lCopy = copy($this->lDreamboxReStreamLocation . '/Settings.class.php', $this->lUpdateSourceLocation[0] . '/Settings.class.php');
		if (!$lCopy) {
			$lErrors[] = 'Error copying existing Settings.class.php file to the new update version';
		}
		return (count($lErrors) == 0 ? true : $lErrors);
	}

	private function moveUpdateFiles() {
		// Copy working settings files to the update version
		Utils::execute('rm -Rf ' . $this->lDreamboxReStreamLocation . '/*','',true);
		Utils::execute('cp -Rf ' . $this->lUpdateSourceLocation[0] . '/* ' . $this->lDreamboxReStreamLocation . '/','',true);
		Utils::execute('rm -Rf ' . $this->lUpdateFolder . '/*','',true);
		return true;
	}

	public function updateAvailable() {
		return $this->lUpdateVersion > VERSION;
	}

	public function getLatestUpdateVersion() {
		return $this->lUpdateVersion;
	}
	public function runUpdate() {
		$lErrors = array();
		if ($this->updateAvailable()) {
			if ( ($lErrors = $this->downloadUpdate()) === true) {
				if ( ($lErrors = $this->unpackUpdate()) === true) {
					if ( ($lErrors = $this->copyExistingSetup()) === true) {
						if ( ($lErrors = $this->checkWriteRights()) === true) {
							if ( ($lErrors = $this->moveUpdateFiles()) === true) {
								// Clean up the old cache data in memory
								unset($_SESSION["ReStream2.0"]);
								// reload the page....
								$lErrors = array();
							}
						}
					}
				}
			}
		}
		$lAjaxResponse  = new xajaxResponse();
		if (count($lErrors) > 0) {
			$lAjaxResponse->assign("message","innerHTML","<p class=\"error\" style=\"font-weight: normal\">Errors during updating:<br />" . implode('<br />',$lErrors) . "</p>");
		} else {
			$lAjaxResponse->assign("message","innerHTML","<p>The updated succeded!. The page will reload in 5 seconds for the setup.</p>");
			$lAjaxResponse->script("setTimeout(function() {location.reload();},5000);");
		}
		return $lAjaxResponse;
	}
}