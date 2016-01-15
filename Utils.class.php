<?php
class Utils {
	static function parseUrls($pContent) {
		// http://stackoverflow.com/questions/10002227/linkify-regex-function-php-daring-fireball-method/10002262#10002262
		$pattern = '(?xi)\b((?:https?://|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))';
		return preg_replace_callback("#$pattern#i", function($matches) {
	    	$input = $matches[0];
	    	$url = preg_match('!^https?://!i', $input) ? $input : "http://$input";
	    	return '<a href="' . $url . '" rel="nofollow" target="_blank" class="external">' . "$input</a>";
		}, $pContent);
	}

	/**
	* Make some text javascript save. This means escaping the single quote and removing new lines.
	* @param string $pText
	* @return string
	*/
	static public function JSSave($pText) {
		$lSearchArray  = array("\\","'","\n","\r");
		$lReplaceArray = array("\\\\","\'"," "," ");

		$pText = trim(str_replace($lSearchArray,$lReplaceArray,$pText));
		$pText = preg_replace('/\s+/', ' ',$pText);

		return $pText;
	}

	static public function durationFormat($pDuration) {
		$lReturnString = "";

		$lSeconds = $pDuration % 60;
		$pDuration /= 60;
		$lMinutes = $pDuration % 60;
		$pDuration /= 60;
		$lHours = $pDuration % 24;

		$lReturnString .= ($lHours > 0 ? $lHours . " hour" . ($lHours > 1 ? "s" : "" ) . " " : "") . $lMinutes . " minute" . ($lMinutes > 1 ? "s" : "" ) . " " . $lSeconds . " second" . ($lSeconds > 1 ? "s" : "" );
		return $lReturnString;
	}

	static public function execute($pCommand,$pLogLocation = '',$pWait = false) {
		if (($pCommand = trim($pCommand)) == "") return false;
		if ($pLogLocation == '') {
			$pCommand .= ' >/dev/null 1>/dev/null 2>/dev/null';
		} else {
			$pCommand .= ' >' . $pLogLocation . ' 1>' . $pLogLocation . '.1 2>' . $pLogLocation . '.2';
		}
		if (!$pWait) {
			$pCommand .= " & echo $!";
		}
		exec($pCommand,$pid);
		if ($pWait) {
			sleep(1);
			return -1;
		} else {
			return $pid[0]*1;
		}
	}

	/**
	* Chechk if the user is using Internet Explorer browser
	* @return boolean
	*/
	static public function isIE() {
		return stripos($_SERVER['HTTP_USER_AGENT'],"MSIE") !== false;
	}

	/**
	* Chechk if the user is using Firefox browser
	* @return boolean
	*/
	static public function isFF() {
		return stripos($_SERVER['HTTP_USER_AGENT'],"firefox") !== false;
	}

	/**
	* Check if the user is using a mobile device.
	* @return boolean
	*/
	static public function isMobileDevice(){
		if (isset($_GET["mobile"]) && $_GET["mobile"] == 1) return true;

		// check if the user agent gives away any tell tale signs it's a mobile browser
		if(preg_match('/Windows Phone|IEMobile|Android|armv|up.browser|up.link|windows ce|iemobile|mini|mmp|symbian|midp|wap|phone|pad|pocket|mobile|pda|psp/i',$_SERVER['HTTP_USER_AGENT'])){
			return true;
		}
		// check the http accept header to see if wap.wml or wap.xhtml support is claimed
		if(stristr($_SERVER['HTTP_ACCEPT'],'text/vnd.wap.wml')||stristr($_SERVER['HTTP_ACCEPT'],'application/vnd.wap.xhtml+xml')){
			return true;
		}
		// check if there are any tell tales signs it's a mobile device from the _server headers
		if(isset($_SERVER['HTTP_X_WAP_PROFILE'])||isset($_SERVER['HTTP_PROFILE'])||isset($_SERVER['X-OperaMini-Features'])||isset($_SERVER['UA-pixels'])){
			return true;
		}
		// build an array with the first four characters from the most common mobile user agents
		$a = array('acs-','alav','alca','amoi','audi','aste','avan','benq','bird','blac','blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno','ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-','maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-','newt','noki','opwv','palm','pana','pant','pdxg','phil','play','pluc','port','prox','qtek','qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar','sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-','tosh','tsm-','upg1','upsi','vk-v','voda','w3c ','wap-','wapa','wapi','wapp','wapr','webc','winw','winw','xda','xda-');
		// check if the first four characters of the current user agent are set as a key in the array
		if(isset($a[substr($_SERVER['HTTP_USER_AGENT'],0,4)])){
			return true;
		}
	}

	static public function convertUrlQuery($query) {
		if (empty($query))
			return array();
	    $queryParts = explode('&', $query);

	    $params = array();
	    foreach ($queryParts as $param) {
	        $item = explode('=', $param);
	        $params[$item[0]] = $item[1];
	    }
	    return $params;
	}
	static public function isiPhone(){
		return ((isset($_GET["iphone"]) && $_GET["iphone"] == 1) || stripos($_SERVER['HTTP_USER_AGENT'],"iphone") !== false || stripos($_SERVER['HTTP_USER_AGENT'],"ipad") !== false);
	}
}