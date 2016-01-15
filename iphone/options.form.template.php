<?php
include_once("../Ajax.class.php");
$lAutoLoadEPG = getAutoLoadEPG();
?>
<html>
  <head>
  <title>Options</title>
  <link rel="stylesheet" href="iphone/jquery.mobile-1.3.1.min.css" />
  <script type="text/javascript" src="js/jquery-2.0.2.min.js"></script>
  <script type="text/javascript" src="iphone/jquery.mobile-1.3.1.min.js"></script>
</head>
<body>
	<div data-role="page" id="mobileOptions" class="dialog-actionsheet">
		<div data-role="header">
			<h1>Options</h1>
		</div>
		<div data-role="content">
			<p>Here you can save some options.</p>
			<form action="#" data-ajax="false" method="post" onsubmit="xajax_action('saveMobileOptions',xajax.getFormValues('mobileOptions'));return false;">
				<div data-role="fieldcontain">
			        <label for="epg">Load automatic TV guide<br />(When on, it takes more time to load):</label>
			        <select name="epg" id="epg" data-role="slider" data-mini="true">
			            <option value="off" <?php if (!$lAutoLoadEPG) echo 'selected'; ?>>Off</option>
			            <option value="on" <?php if ($lAutoLoadEPG) echo 'selected'; ?>>On</option>
			        </select>
				</div>
				<label for="purge">Purge cache data<label>
				<button type="button" data-icon="check" onclick="location.href='/?cachepurge=1'">Purge</button><br />
				<label for="un" >Private modus username:</label>
				<input name="user" id="un" value="<?php echo getMobileOption('user') ?>" placeholder="username" type="text">
				<label for="pw" >Private modus password:</label>
				<input name="pass" id="pw" value="<?php echo getMobileOption('pass') ?>" placeholder="password" type="password">
				<button type="submit" data-icon="check">Save</button>
			</form>
		</div>
	</div>
</body>
</html>