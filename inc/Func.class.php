<?php

/**
* Functions
*
* @copyright	2017 progit
* @link 		https://github.com/prog-it/cloudflare-ddns-multiaccounts
*/

class Func {
	
	# Получить страницу через cURL
	public static function getPage($p) {
		$cURL = [
			'Useragent'		=> Config::get('curl.useragent'),
			'Timeout'		=> Config::get('curl.timeout'),
			'Codes'			=> Config::get('curl.codes'),
		];		
		
		$p['Useragent'] = isset($p['Useragent']) ? $p['Useragent'] : $cURL['Useragent'];
		$p['Timeout'] = isset($p['Timeout']) ? $p['Timeout'] : $cURL['Timeout'];
		$p['Codes'] = isset($p['Codes']) ? $p['Codes'] : $cURL['Codes'];
		$p['Cookie'] = isset($p['Cookie']) ? $p['Cookie'] : false;

		$ch = curl_init($p['Url']);
		curl_setopt($ch, CURLOPT_URL, $p['Url']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLOPT_USERAGENT, $p['Useragent']);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $p['Timeout']);
		curl_setopt($ch, CURLOPT_TIMEOUT, $p['Timeout']);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		if ($p['Cookie'] !== false) {
			curl_setopt($ch, CURLOPT_COOKIEJAR, $p['Cookie']);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $p['Cookie']);
		}
		$content = curl_exec($ch);
		$hc = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$err = curl_errno($ch);
		$errmsg = curl_error($ch);
		curl_close($ch);
		if ( !$err && !$errmsg && in_array($hc, $p['Codes']) ) { 
			return $content; 
		}
		return false;
	}

	# Чистка кода
	public static function Replacer($txt) {
		$txt = strip_tags($txt);
		$txt = trim($txt);
		return $txt;
	}
	
	# Получить записи для обновления
	public static function getAllEntries() {
		$files = glob('config/entries/*.php');
		$res = array_filter($files, function($f) {
			return is_file($f);
		});
		if (!$res) {
			Logger::write('No entries to update');
		}
		return $res;
	}
	
	# Заполнены необходимые поля в конфиге
	public static function checkValidEntry($path, $obj) {
		$errors = 0;
		foreach (['email', 'key', 'domain', 'ipv4_enabled', 'ipv6_enabled', 'zones'] as $key) {
			$value = $obj->get($key);
			if ( 
				!isset($value) ||
				(in_array($key, ['email', 'key', 'domain']) && $value == '') ||
				(in_array($key, ['ipv4_enabled', 'ipv6_enabled']) && ($value != true && $value != false)) ||
				(in_array($key, ['zones']) && count($value) ==0)
			) {
				$errors += 1;
				Logger::write('In config '.$path.' is invalid value: '.$key);
			}
		}
		if (
			($obj->exists('ipv4_enabled') && $obj->exists('ipv6_enabled')) &&
			($obj->get('ipv4_enabled') == false && $obj->get('ipv6_enabled') == false)
		) {
			$errors += 1;
			Logger::write('In config '.$path.' is disabled checking IPv4 and IPv6');
		}
		return $errors == 0;
	}
	
	# Проверка токена
	public static function checkToken() {
		if (PHP_SAPI == 'cli') {
			$args = getopt('', ['token:']);
		} else {
			$args = $_GET;
		}
		$args = array_map('self::Replacer', $args);
		if ( !isset($args['token']) || $args['token'] != Config::get('cron.token') ) {
			Logger::write('Invalid startup token');
			exit;
		}
	}
	
}
