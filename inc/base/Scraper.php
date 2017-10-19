<?php

/**
* Scraper
*
* @copyright	2017 progit
* @link 		https://github.com/prog-it/cloudflare-ddns-multiaccounts
*/

class Scraper {
	
	private $ipv4 = null;
	private $ipv6 = null;
	private $need_ipv4;
	private $need_ipv6;
	private $ip_type;
	
	public function setIpv4($ipv4) {
		$this->ipv4 = $ipv4;
	}
	
	public function getIpv4() {
		return $this->ipv4;
	}

	public function setIpv6($ipv6) {
		$this->ipv6 = $ipv6;
	}
	
	public function getIpv6() {
		return $this->ipv6;
	}

	public function setNeedIpv4($need_ipv4) {
		$this->need_ipv4 = $need_ipv4;
	}
	
	public function getNeedIpv4() {
		return $this->need_ipv4;
	}

	public function setNeedIpv6($need_ipv6) {
		$this->need_ipv6 = $need_ipv6;
	}
	
	public function getNeedIpv6() {
		return $this->need_ipv6;
	}

	private function setTypeIpv4() {
		$this->ip_type = 'ipv4';
	}

	private function setTypeIpv6() {
		$this->ip_type = 'ipv6';
	}

	private function isTypeIpv4() {
		if ($this->ip_type == 'ipv4') {
			return true;
		} else {
			return false;
		}
	}		
	
	# Получить новые IP
	public function getIps() {
		# IPv4
		if ($this->getNeedIpv4() == true && $this->ipv4 === null) {
			$this->setTypeIpv4();
			$this->setIpv4($this->scrape());
			if ($this->ipv4 === false) {
				Logger::Write('Failed to get current IPv4');
			}
		}
		# IPv6
		if ($this->getNeedIpv6() == true && $this->ipv6 === null) {
			$this->setTypeIpv6();
			$this->setIpv6($this->scrape());
			if ($this->ipv6 === false) {
				Logger::Write('Failed to get current IPv6');
			}			
		}		
	}
	
	# Получить данные об IP
	private function scrape() {
		if (Config::get('ip.method') == 'http') {
			return $this->scrapePage();
		} else {
			return $this->scrapeDig();
		}
	}
	
	# Получить данные со страницы "http" методом
	private function scrapePage() {
		$res = false;
		$services = $this->isTypeIpv4() ? Config::get('http.ipv4') : Config::get('http.ipv6');
		if ( count($services) > 0 ) {
			$loops = Config::get('http.loops');
			shuffle($services);
			for ($i = 0; $i < $loops; $i++) {
				$rnd_key = array_rand($services);
				$content = Func::getPage([
					'Url' => $services[$rnd_key],
				]);
				if ( $content !== false ) {
					$content = Func::Replacer($content);
					// Вырезать IP из страницы
					$ip = Func::Replacer( $this->cutIpFromPage($content) );
					// Включена проверка корректности IP
					if ( Config::get('ip.validate') == true ) {
						// IP корректный
						if ( $this->validateIp($ip) === true ) {
							$res = $ip;
							break;
						}
					} else {
						if ($ip) {
							$res = $ip;
							break;					
						}
					}
				}
				unset($services[$rnd_key]);
			}			
		} else {
			Logger::write('Need at least one URL for get current IP');
		}
		return $res;
	}
	
	# Получить данные "dig" методом
	private function scrapeDig() {
		$res = false;
		$query = $this->isTypeIpv4() ? 'dig +short myip.opendns.com @resolver1.opendns.com' : 'dig +short -6 myip.opendns.com aaaa @resolver1.ipv6-sandbox.opendns.com';
		$ans = Func::Replacer(shell_exec($query));
		if ($ans) {
			// Вырезать IP
			$ip = Func::Replacer( $this->cutIpFromPage($ans) );
			// Включена проверка корректности IP
			if ( Config::get('ip.validate') == true ) {
				// IP корректный
				if ( $this->validateIp($ip) === true ) {
					$res = $ip;
				}
			} else {
				if ($ip) {
					$res = $ip;
				}
			}			
		}
		return $res;
	}	
	
	# Вырезать IP из WEB-страницы -> Контент WEB-страницы
	private function cutIpFromPage($content) {
		$res = null;
		if ( $this->isTypeIpv4() ) {
			$regex = '#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#';
		} else {
			$regex = '#(?:(?:(?:[0-9A-Fa-f]{1,4}:){6}|::(?:[0-9A-Fa-f]{1,4}:){5}|(?:[0-9A-Fa-f]{1,4})?::(?:[0-9A-Fa-f]{1,4}:){4}|(?:(?:[0-9A-Fa-f]{1,4}:){0,1}[0-9A-Fa-f]{1,4})?::(?:[0-9A-Fa-f]{1,4}:){3}|(?:(?:[0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})?::(?:[0-9A-Fa-f]{1,4}:){2}|(?:(?:[0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})?::[0-9A-Fa-f]{1,4}:|(?:(?:[0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})?::)(?:[0-9A-Fa-f]{1,4}:[0-9A-Fa-f]{1,4}|(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?))|(?:(?:[0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})?::[0-9A-Fa-f]{1,4}|(?:(?:[0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})?::)#i';
		}
		preg_match($regex, $content, $out);
		if ( isset($out[0]) ) {
			$res = trim($out[0]);
		}
		return $res;
	}

	# Проверка корректности IP адреса -> IP адрес
	public function validateIp($ip) {
		$res = false;
		$ip = trim($ip);
		if ($ip) {
			if ( $this->isTypeIpv4() ) {
				$res = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
			} else {
				$res = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
			}
			$res = $res == $ip ? true : false;
		}
		return $res;
	}	
	
}
