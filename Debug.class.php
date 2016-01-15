<?php
class Debug {
	private $lDebugMessage;
	function __construct() {
		$this->lDebugMessage = "";
	}
	private static function makeClickableLinks($s) {
		return preg_replace_callback(
        	'@(https?://([-\w\.]+[-\w:\@])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^"\'\.\s])?)?)@',
        	function ($matches) {
            	return '<a href="' . $matches[0] . '" target="_blank">' . Debug::stripPassword($matches[0]) . '</a>';
        	},
        	$s);
	}

	private static function stripPassword($text) {
		return preg_replace("/(https?:\\/\\/.*):(.*)@/","$1:xxxx@",$text);
	}
	public function setDebugMessage($pFunction,$pMessage) {
		$this->lDebugMessage .= '[' . date("d-m-Y H:i:s") . ']:[server]:[' . $pFunction . '] ' . $pMessage . "\n";
	}
	public function getDebugMessage() {
		return nl2br($this->makeClickableLinks($this->lDebugMessage));
	}
}