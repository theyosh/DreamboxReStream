<!DOCTYPE html>
<html>
<head>
  <title><?php echo Settings::getDreamboxName() . " - " . Settings::getProgramName() . " (" . Settings::getVersionNumber() . ")"; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta name="author" content="Joshua (TheYOSH) Rubingh" />
  <meta name="copyright" content="<?php echo date("Y"); ?> TheYOSH" />
  <meta name="keywords" content="Restream video from a Dreambox over the internet and mobile phones" />
  <meta name="description" content="Restream video from a Dreambox over the internet and mobile phones" />
  <link rel="shortcut icon" type="image/png" href="images/dreamboxrestream_icon.png" />
  <link rel="apple-touch-icon" href="images/dreamboxrestream_icon.png" />
  <link rel="apple-touch-icon" sizes="72x72" href="images/dreamboxrestream_icon.png" />
  <link rel="apple-touch-icon" sizes="114x114" href="images/dreamboxrestream_icon.png" />
  <link rel="apple-touch-icon" sizes="144x144" href="images/dreamboxrestream_icon.png" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-status-bar-style" content="black" />
  <link rel="apple-touch-icon-precomposed" href="images/dreamboxrestream_icon.png" />
  <?php $xajax->printJavascript(); // XAJAX part 2 ?>
  <link rel="stylesheet" href="css/jquery.mobile-1.4.5.min.css" />
  <link rel="stylesheet" href="css/addtohomescreen.css" />
  <link rel="stylesheet" href="css/mobile.css" />
  <script type="text/javascript">var gSetup = <?php echo ($gSetupObj->configOutDated() ? '1' : '0');?></script>
  <script type="text/javascript">
gAdditionalTimeout = 0;
<?php echo "gAdditionalTimeout += " . Settings::getAdditionalTimeout() . ";"; ?>
</script>

  <script src="hls.js/dist/hls.min.js"></script>
  <script type="text/javascript" src="js/jquery-2.1.4.min.js"></script>
  <script type="text/javascript" src="js/jquery.mobile-1.4.5.min.js"></script>
  <script type="text/javascript" src="js/date.format.js" ></script>
  <script type="text/javascript" src="js/addtohomescreen.min.js" ></script>
  <script type="text/javascript" src="js/humanize-duration.js" ></script>
  <script type="text/javascript" src="js/javascript.js" ></script>
  <script type="text/javascript" src="js/javascript.mobile.js"></script>
</head>
<body>
<div data-role="page" id="channels">
	<div data-role="header" data-position="fixed">
		<?php include('iphone/vlc.status.template.php') ?>
		<h1>Channels</h1>
		<?php include('iphone/options.link.template.php') ?>
	</div>
	<div data-role="content"></div>
	<?php include('iphone/footer.navbar.template.php') ?>
</div>

<div data-role="page" id="recordings">
	<div data-role="header" data-position="fixed">
		<?php include('iphone/vlc.status.template.php') ?>
		<h1>Recordings</h1>
		<?php include('iphone/options.link.template.php') ?>
	</div>
	<div data-role="content" ></div>
	<?php include('iphone/footer.navbar.template.php') ?>
</div>

<div data-role="page" id="movies">
	<div data-role="header" data-position="fixed">
		<?php include('iphone/vlc.status.template.php') ?>
		<h1>Movies</h1>
		<?php include('iphone/options.link.template.php') ?>
	</div>
	<div data-role="content" ></div>
	<?php include('iphone/footer.navbar.template.php') ?>
</div>

<div data-role="page" id="watch">
	<div data-role="header" data-position="fixed">
		<?php include('iphone/vlc.status.template.php') ?>
		<h1>Watch</h1>
		<?php include('iphone/options.link.template.php') ?>
	</div>
	<div data-role="content" >
		<div id="ProgramInfo">
			<div id="ReStreamPlayer">
        <video id="videoPlayer" controls="true"></video>
			</div>
			<h1>[Channel Name]</h1>
			<h2>[Start] [Stop] [Program Name]</h2>
			<p>[Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam turpis tellus, vehicula sit amet consequat vitae, convallis at arcu. Donec rhoncus velit at enim iaculis euismod. Integer interdum tortor facilisis libero pharetra accumsan. Duis vitae viverra sapien.]</p>
			<span id='rtsplink'></span>
		</div>
	</div>
	<?php include('iphone/footer.navbar.template.php') ?>
</div>
<div data-role="page" id="about">
	<div data-role="header" data-position="fixed">
		<?php include('iphone/vlc.status.template.php') ?>
		<h1>About</h1>
		<?php include('iphone/options.link.template.php') ?>
	</div>
	<div data-role="content" >
		<p>
			<img src="images/dreamboxrestream_icon.png" alt="Dreambox ReStream Mobile Logo"/>
			<br />
			<br />
			<?php echo Settings::getDreamboxName() . "<br />Dreambox ReStream (" . Settings::getVersionNumber() . ")"; ?><br />
			Released at <?php echo  date('j F Y',RELEASEDATE); ?><br />
			<br />Mobile interface<br />
			<br />
			Copyright 2006-<?php echo date("Y");?> - <a href="http://theyosh.nl" class="external" target="_blank" title="The YOSH">TheYOSH</a>
			<br />
			<br />
			Like the software?<br />Consider a donation<br />
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank" style="text-align: center">
				<input type="hidden" name="cmd" value="_donations">
				<input type="hidden" name="business" value="paypal@theyosh.nl">
				<input type="hidden" name="lc" value="US">
				<input type="hidden" name="item_name" value="Dreambox ReStream">
				<input type="hidden" name="no_note" value="0">
				<input type="hidden" name="currency_code" value="EUR">
				<input type="hidden" name="bn" value="PP-DonationsBF:btn_donate_LG.gif:NonHostedGuest">
				<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
				<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
			</form>
		</p>
	</div>
	<?php include('iphone/footer.navbar.template.php') ?>
</div>
<div id="errorLog" style="display:none;"></div>
</body>
</html>
