<?php

/**
* Logger
*
* @copyright	2017 progit
* @link 		https://github.com/prog-it/cloudflare-ddns-multiaccounts
*/

class Logger {
	
	# Запись
	public static function write($msg, $to_file = true) {
		$date = date('Y.m.d H:i:s', time());
		$out = '['.$date.'] - '.$msg.'.'.PHP_EOL;
		echo $out;
		if ( Config::get('log.enabled') == true && $to_file == true ) {
			file_put_contents( Config::get('db.log'), $out, FILE_APPEND );
		}
	}
	
	# Очистка
	public static function clean() {
		$path = Config::get('db.log');
		if ( Config::get('log.enabled') == true && Config::get('log.clean') == true && file_exists($path) && filesize($path) > Config::get('log.max_filesize')*1024 ) {
			file_put_contents($path, '');
		}
	}	
	
}
