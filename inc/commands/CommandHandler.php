<?php

/**
* CommandHandler
*
* @copyright	2017 progit
* @link 		https://github.com/prog-it/cloudflare-ddns-multiaccounts
*/

class CommandHandler {
	private $command;
	private $data;
	
	public function __construct($command) {
		$this->command = $this->parse($command);
		$this->data = PHP_SAPI == 'cli' ? getopt('', [$command.':']) : $_GET;
		$this->data = $this->parse($this->data);
	}

	public function setCommand($command) {
		$this->command = $command;
	}
	
	public function getCommand() {
		return $this->command;
	}
	
	private function setData($data) {
		$this->data = $data;
	}
	
	public function getData() {
		return $this->data;
	}	
	
	# Команда передана
	public function commandExists() {
		if ( isset($this->data[$this->command]) ) {
			return $this->data[$this->command];
		}
		return false;
	}

	# Разбор данных
	private function parse($value) {
		if ( is_array($value) ) {
			$res = array_map('Func::Replacer', $value);
		} else {
			$res = Func::Replacer($value);
		}
		return $res;
	}
	
	# Получить токен
	public function getToken() {
		$res = $this->commandExists();
		if ($res !== false) {
			return $res;
		}
		return false;
	}
	
	# Получить записи для обновления ее DNS
	public function getEntry() {
		$res = $this->commandExists();
		if ($res !== false) {
			return $res;
		}
		return false;
	}
	
}
