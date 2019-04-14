<?php
//Defaults
error_reporting(E_ALL);
ini_set("display_errors","1");
ini_set("max_execution_time","300");
define('VERSION','2.4.9');
define('RELEASEDATE',1555239502);
// End defaults
@session_start();

// Force purge action
if (isset($_GET['cachepurge']) && $_GET['cachepurge'] == 1) {
	@unlink('dreambox.cache');
	unset($_SESSION["ReStream2.0"]);
	header('location: index.php');
	exit;
}

// Helper classes
require_once("Utils.class.php");
require_once('Setup.class.php');
require_once('Update.class.php');
if (file_exists('Settings.class.php'))
	require_once('Settings.class.php');

// XAJAX Framework part
include_once("Ajax.class.php");
$gSetupObj = new Setup();

$gSetupObj->configOutDated();
$gUpdateObj = new Update(!$gSetupObj->configOutDated());
if (!$gSetupObj->configOutDated()) {
	if (Settings::isPrivate()) {
		$lUser = '';
		$lPassword = '';
		if (getMobileOption('user') != '' && getMobileOption('pass') != '') {
			$lUser = $_SERVER["PHP_AUTH_USER"] = getMobileOption('user');
			$lPassword = $_SERVER["PHP_AUTH_PW"] = getMobileOption('pass');
			$_SERVER['HTTP_AUTHORIZATION'] = 'Basic ' . base64_encode($lUser . ':' . $lPassword);
		}
		if (isset($_SERVER["PHP_AUTH_USER"]) && isset($_SERVER["PHP_AUTH_PW"])) {
			$lUser = $_SERVER["PHP_AUTH_USER"];
			$lPassword = $_SERVER["PHP_AUTH_PW"];
		} elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
			list($lUser, $lPassword) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
		}

		if ($lUser != Settings::getPrivateModusUsername() || $lPassword != Settings::getPrivateModusPassword()) {
			header("WWW-Authenticate: Basic realm=\"Enter username and password to proceed\"");
			header("HTTP/1.0 401 Unauthorized");
			echo "<h1>Authentication failed</h1>Dreambox ReStream is running in private modus.";
			exit;
		}
	}

	if (Settings::getDebug()) {
		require_once("Debug.class.php");
		$gDebugObj = new Debug();
	}
	// Dreambox part
	require_once("Dreambox.class.php");
	require_once("Bouquet.class.php");
	require_once("Channel.class.php");
	require_once("ProgramGuide.class.php");
	require_once("Program.class.php");
	require_once("Recording.class.php");
	require_once("MediaInfo.class.php");
	require_once("Movie.class.php");

	// Server part
	require_once("VLCServer.class.php");
	$lDreamBoxObj = new Dreambox();

}
require("xajax/xajax_core/xajax.inc.php");
$xajax = new xajax();

if (!$gSetupObj->configOutDated() && Settings::getDebug()) {
	$xajax->configure('debug',true);
}

$xajax->configure('javascript URI','xajax');
$xajax->register(XAJAX_FUNCTION,'action');
$xajax->processRequest();

if (!$gSetupObj->configOutDated()) {
	$lDreamBoxObj->isOnline();
	if ((Utils::isMobileDevice() || (isset($_GET["mobile"]) && $_GET["mobile"] == 1))) {
		require_once("index-mobile.php");
		exit;
	}
}
?>
<!DOCTYPE html>
<html>
<head>
  <title><?php echo (!$gSetupObj->configOutDated() ? Settings::getDreamboxName() . " - " . Settings::getProgramName() : "Dreambox ReStream Setup" ) . " (" . VERSION . ")"; ?></title>
  <meta charset="utf-8">
  <meta name="author" content="Joshua (TheYOSH) Rubingh"/>
  <meta name="copyright" content="2006 - <?php echo date("Y") ?> TheYOSH" />
  <meta name="keywords" content="Restream video from a Dreambox/AZBox/VU+/Enigma based Satelite receiver" />
  <meta name="description" content="Restream video from a Dreambox/AZBox/VU+/Enigma based Satelite receiver" />
  <link rel="shortcut icon" type="image/png" href="images/dreamboxrestream_icon.png" />
  <link rel="stylesheet" type="text/css" media="screen" href="css/jquery-ui.min.css" />
  <link rel="stylesheet" type="text/css" media="screen" href="css/style.css" />
  <script type="text/javascript">var gSetup = <?php echo ($gSetupObj->configOutDated() ? '1' : '0');?>;</script>
  <?php $xajax->printJavascript(); // XAJAX part 2 ?>
</head>
<body <?php echo($gSetupObj->configOutDated() ? 'class="setup"' : '') ?> >
<?php if ($gUpdateObj->updateAvailable()) echo '<div id="updateAvailable" class="separator">There is an update available of Dreambox ReStream. Click here to <a href="javascript:void(0);" onclick="DreamboxObj.update()" title="Update Dreambox ReStream to version ' . $gUpdateObj->getLatestUpdateVersion() . '">update to the latest version ' . $gUpdateObj->getLatestUpdateVersion() . '</a><span style="float:right; height: 20px; width:20px; display:block;"><a href="javascript:void(0);" onclick="jQuery(\'#updateAvailable\').hide();" title="Close">X</a></span></div>';?>
<div id="wrapper">
	<h1><script type="text/javascript">document.write(document.title);</script></h1>
	<div id="ReStreamUI">
<?php if (date("m") == 12 && (date('d') == 25 || date('d') == 26)) echo '<div id="christmashat" style="background: url(\'images/christmashat.png\') no-repeat;width: 76px; height: 88px;background-size:100%;position:absolute;z-index:99;left: 910px;top:-30px;float:right;"></div>'; ?>
		<div id="PlayerWrapper">
			<div id="ReStreamPlayer">
				<?php if ($gSetupObj->configOutDated()) { ?>
				<h2>Setup</h2>
				<p>Dreambox ReStream needs to be setup. Fill in the form and press write config. This will write the configuration to disk and reload the page with the new settings. Move the mouse over the labels to get more information about the setup values.</p>
				<?php if (count(($lErrorMessages = $gSetupObj->checkWriteSettings())) > 0) echo '<br /><span class="error">' . implode('<br />',$lErrorMessages). '</span>'; ?>
				<form class="setup" id="setup">
					<table>
						<tr>
							<td>
								<h3>Required</h3>
								<label for="dreamboxName" title="Program name">Dreambox Name</label><input type="text" name="dreamboxName" value="<?php echo $gSetupObj->getVariable('dreamboxName'); ?>"/><br />
								<label for="dreamboxIP" title="Enter the IP number of the TV decoder">Dreambox IP number</label><input type="text" name="dreamboxIP" value="<?php echo $gSetupObj->getVariable('dreamboxIP'); ?>"/><br />
								<label for="dreamboxWebPort" title="Enter the port number of the TV decoder's website interface">Dreambox Webport</label><input type="text" name="dreamboxWebPort" value="<?php echo $gSetupObj->getVariable('dreamboxWebPort'); ?>" /><br />
								<label for="dreamboxEnigmaVersion" title="Select the Enigma type in the TV decoder">Dreambox Type</label><select name="dreamboxEnigmaVersion"><?php foreach($gSetupObj->getVariable('dreamboxEnigmaVersion') as $lType) echo '<option value="' . $lType['value'] . '" ' . ($lType['selected'] == 1 ? 'selected="selected"' : '') . '>' . $lType['name'] . '</option>' ?></select><br />
								<label for="dreamboxDualTuner" title="Does the TV decoder has a dual tunner">Dreambox Dual Tuner</label><input type="radio" name="dreamboxDualTuner" value="1" <?php if ($gSetupObj->getVariable('dreamboxDualTuner')) echo 'checked="checked"' ?> />Yes - <input type="radio" name="dreamboxDualTuner" value="0" <?php if (!$gSetupObj->getVariable('dreamboxDualTuner')) echo 'checked="checked"' ?> />No<br />
								<label for="vlcLocation" title="Enter the full path to the VLC executable. The default is based on the OS">VLC Executable</label><input type="text" name="vlcLocation" value="<?php echo $gSetupObj->getVariable('vlcLocation'); ?>"/><br />
								<label for="vlcPlayerIP" title="Enter the IP number that VLC uses for its RTSP streaming. Default it is the external IP number">VLC IP Number</label><select name="vlcPlayerIP"><?php foreach($gSetupObj->getVariable('vlcPlayerIP') as $lType) echo '<option value="' . $lType['value'] . '" ' . ($lType['selected'] == 1 ? 'selected="selected"' : '') . '>' . $lType['name'] . '</option>' ?></select><br />
								<label for="vlcLanStreamPort" title="Enter the port number that VLC uses for RTSP streaming. It should be higher than 1024">VLC Port Number</label><input type="text" name="vlcLanStreamPort" value="<?php echo $gSetupObj->getVariable('vlcLanStreamPort'); ?>"/><br />
							</td>
							<td>
								<h3>Optional</h3>
								<label for="dreamboxUserName" title="Enter the user name that is used for accessing the TV decoder web interface. Leave empty when no security is enabled">Dreambox User Name</label><input type="text" name="dreamboxUserName" value="<?php echo $gSetupObj->getVariable('dreamboxUserName'); ?>"/><br />
								<label for="dreamboxPassword" title="Enter the password that is used for accessing the TV decoder web interface. Leave empty when no security is enabled">Dreambox User Password</label><input type="text" name="dreamboxPassword" value="<?php echo $gSetupObj->getVariable('dreamboxPassword'); ?>"/><br />
								<label for="privateModus" title="This will enable an authentication on the Dreambox ReStream website">Enable private modus</label>
								<input type="radio" name="privateModus" value="1" <?php if ($gSetupObj->getVariable('privateModus')) echo 'checked="checked"' ?>> Yes - <input type="radio" name="privateModus" value="0" <?php if (!$gSetupObj->getVariable('privateModus')) echo 'checked="checked"' ?>> No<br />
								<label for="privateModusUsername" title="Enter the user name that will be used to access a private Dreambox ReStream website">Private User Name</label><input type="text" name="privateModusUsername" value="<?php echo $gSetupObj->getVariable('privateModusUsername'); ?>"/><br />
								<label for="privateModusPassword" title="Enter the password that will be used to access a private Dreambox ReStream website">Private User Password</label><input type="text" name="privateModusPassword" value="<?php echo $gSetupObj->getVariable('privateModusPassword'); ?>"/><br />
								<label for="vlcAudioLanguage" title="Use 2 letter country names seperated by a comma">Audio languages</label><input type="text" name="vlcAudioLanguage" value="<?php echo $gSetupObj->getVariable('vlcAudioLanguage'); ?>" /><br />
								<label for="vlcSubtitleLanguage" title="Use 2 letter country names seperated by a comma">Subtitle languages</label><input type="text" name="vlcSubtitleLanguage" value="<?php echo $gSetupObj->getVariable('vlcSubtitleLanguage'); ?>" /><br />
								<label for="EPGLimit" title="Limit the amount of EPG data by setting a future time limit. The amount is in hours">EPG future limit</label><input type="text" name="EPGLimit" value="<?php echo $gSetupObj->getVariable('EPGLimit'); ?>"/><br />
								<label for="DVRLength" title="Set the DVR length of the stream. Minimum length is 30 seconds. The amount is in seconds">DVR Length in seconds</label><input type="text" name="DVRLength" value="<?php echo $gSetupObj->getVariable('DVRLength'); ?>"/><br />
								<label for="AdditionalBufferTime" title="Set extra buffer timeout for streaming. This can create a smoother stream. The amount is in seconds">Additional buffer time in seconds</label><input type="text" name="AdditionalBufferTime" value="<?php echo $gSetupObj->getVariable('AdditionalBufferTime'); ?>"/><br /><br />
								<br />
							</td>
						</tr>
					</table>
					<input type="button" onclick="xajax_action('updateConfig',xajax.getFormValues('setup'));" value="Write config" style="margin: 50px 50%;"/>
				</form>
				<?php } else { ?>
				<div id="videoPlayer"></div>
				<?php } ?>
			</div>
			<?php if (!$gSetupObj->configOutDated()) { ?>
			<div id="loading"></div>
			<div id="ProgramInfo">
				<h1>[Channel Name]</h1>
				<h2>[Start] [Stop] [Program Name]</h2>
				<p>[Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam turpis tellus, vehicula sit amet consequat vitae, convallis at arcu. Donec rhoncus velit at enim iaculis euismod. Integer interdum tortor facilisis libero pharetra accumsan. Duis vitae viverra sapien.]</p>
			</div>
			<div id="ProgramProgress"></div>
			<div id="EncoderStatus" class="stopped"></div>
		</div>
		<div id="ProgramGuide">
			<div id="channels">
				<h3><a href="#">Boutiques</a></h3>
				<div><p style="text-align:center; margin-top: 50px;">Loading....</p></div>
			</div>
		</div>
		<script type="text/javascript">
			gAdditionalTimeout = 0;
			<?php echo "gAdditionalTimeout += " . Settings::getAdditionalTimeout() . ";"; ?>
		</script>
		<?php } ?>
	</div>
</div>
<div id="message" title="Message..."></div>
<div id="epg" title="EPG Channel ..."></div>
<script type="text/javascript" src="js/jquery-3.4.0.min.js"></script>
<script type="text/javascript" src="js/humanize-duration.js" ></script>
<script type="text/javascript" src="js/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/jquery.scrollTo.min.js"></script>
<script type="text/javascript" src="js/date.format.js" ></script>
<script type="text/javascript" src="js/clappr.min.js"></script>
<script type="text/javascript" src="js/clappr-stats.min.js"></script>
<script type="text/javascript" src="js/clappr-nerd-stats.min.js"></script>
<script type="text/javascript" src="js/level-selector.min.js"></script>
<script type="text/javascript" src="js/javascript.js" ></script>
</body>
</html>
