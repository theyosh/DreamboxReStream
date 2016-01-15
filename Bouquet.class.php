<?php
class Bouquet {

	/**
	* @access private
	* @var string
	*/
	private $lID;

	/**
	* @access private
	* @var string
	*/
	private $lName;

	/**
	* @access private
	* @var array
	*/
	private $lChannels;
	
	private $lChannelCount;

	/**
	* Constructor. This creates a Bouquet object.
	* @param int $pID
	* @param string $pName
	* @return Channel
	*/
	function __construct($pID,$pName) {
		$this->lID = trim($pID);
		$this->lName = trim($pName);
		$this->lChannels = array();
		$this->lChannelCount = 0;
	}

	/**
	* Get the bouquet ID.
	* @return string
	*/
	public function getID() {
		return $this->lID;
	}

	/**
	* Get the bouquet name.
	* @return string
	*/
	public function getName() {
		return $this->lName;
	}

	/**
	* Add a channel to the bouquet.
	* @param Channel $pChannel
	*/
	public function addChannel(Channel $pChannel) {
		if ($pChannel->getID() != 'none') {
			$this->lChannels[] = $pChannel;
			$this->lChannelCount++;
		}
	}
	
	public function getChannels() {
		return $this->lChannels;
	}

	/**
	* Get the channel object based in channel id.
	* @param string $pChannelID
	* @return Channel
	*/
	public function getChannel($pChannelID) {
		if ( ($pChannelID = trim($pChannelID)) == "") return false;
		foreach ($this->lChannels as $lChannel) {
			if ($lChannel->getID() == $pChannelID) {
				return $lChannel;
			}
		}
		return false;
	}

	/**
	* Get the total channels inside a bouquet.
	* @return int
	*/
	public function getChannelCount() {
		return $this->lChannelCount;
	}

	/**
	* Reset the internal channel counter.
	*/
	public function resetChannelCounter() {
		$this->lChannelQueueCounter = -1;
	}

	/**
	* Get the next channel from the bouquet.
	* @return Channel
	*/
	public function getNextChannel() {
		$this->lChannelQueueCounter++;
		if (isset($this->lChannels[$this->lChannelQueueCounter])) {
			return $this->lChannels[$this->lChannelQueueCounter];
		}
		return null;
	}
}