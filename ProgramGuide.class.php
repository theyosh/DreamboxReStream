<?php
class ProgramGuide {

	/**
	* @access private
	* @var Channel
	*/
	private $lChannel;

	/**
	* @access private
	* @var array
	*/
	private $lPrograms;

	/**
	* @access private
	* @var int
	*/
	private $lProgramsQueueCounter;

	/**
	* @access private
	* @var int
	*/
	private $lProgramsCount;

	/**
	* Constructor. This creates a ProgramGuide object.
	* @param Channel $pChannel
	* @return ProgramGuide
	*/
	public function __construct($pChannelObj) {
		$this->lChannel = $pChannelObj;
		$this->lPrograms = array();
		$this->lProgramsCount = 0;
		$this->lProgramsQueueCounter = -1;
	}

	/**
	* Sort the programs based in start time
	*/
	public function sortOnStartTime() {
	/*
		$lStartTimes = array();
		foreach ($this->lPrograms as $lProgram) {
			$lStartTimes[] = $lProgram->getStartTime();
		}
		array_multisort($lStartTimes, SORT_ASC, $this->lPrograms);
		*/
		ksort($this->lPrograms,SORT_NUMERIC);
		$this->lProgramsQueueCounter = -1;
	}

	/**
	* Add a program to a channel. Every channel has its own programs array.
	* @param Program $pProgramObj
	*/
	public function addProgram(Program $pProgramObj) {
		$this->lPrograms[$pProgramObj->getStartTime()] = $pProgramObj;
		$this->lProgramsCount = count($this->lPrograms);
	}

	/**
	* Get the total programs inside a program guide.
	* @return int
	*/
	public function getProgramsCount() {
		return $this->lProgramsCount;
	}

	/**
	* Get the next program from the program guide.
	* @return Program
	*/
	public function getNextProgram() {
		$this->lProgramsQueueCounter++;
		if (isset($this->lPrograms[$this->lProgramsQueueCounter])) {
			return $this->lPrograms[$this->lProgramsQueueCounter];
		}
		return false;
	}

	public function getPrograms() {
		return $this->lPrograms;
	}

	/**
	* Get the current program based on current time.
	* @return Program
	*/
	public function getCurrentProgram() {
		$this->lProgramsQueueCounter = 0;
		foreach ($this->lPrograms as $lProgram) {
			if ($lProgram->getStartTime() < time() && $lProgram->getStopTime() > time()) {
				return $lProgram;
			}
			$this->lProgramsQueueCounter++;
		}
		return false;
	}
	
	/**
	* Get the channel Object of which this is the Program guide.
	* @return Channel
	*/
	public function getChannel() {
		return $this->lChannel;
	}
}